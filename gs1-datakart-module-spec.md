# GS1 / DataKart Module — Refined Spec & Build Plan

> Paste this whole file into Cursor as the module brief. Sections 0–2 are the
> non-negotiable rules; sections 3–8 are the feature specs; section 12 is the
> checklist for fixing the already-written code.

---

## 0. Context for the agent

Stack: Laravel (existing ERP). Existing `products` table is **owned by another
part of the system and must not be altered** — no new columns, no renames, no
casts changed.

We are adding a GS1 module that does three things:

1. **Reconcile** — pull from DataKart and correct the EAN/GTIN on ERP products
   (and nothing else).
2. **Author & publish** — create/update products in the ERP, enrich them with
   DataKart-only attributes, review, then push to DataKart.
3. **Label** — generate country-specific labels with per-country units of
   measure and multi-language text.

`sku` is the correlation key between the two systems. It is immutable once a
product is linked.

### Authority of existing code over this document

**The DataKart form, endpoints, and field definitions already present in this
repository are the source of truth.** So is the existing label field set. Where
this document lists fields or columns, treat them as a *reference checklist* —
use them to spot something that is genuinely missing, not to rename, replace, or
re-derive what already exists. Do not invent endpoints or field names.

What this document *is* authoritative about is **structure**: where data lives,
how a value is resolved, what triggers what, who is allowed to press what. If
the existing form has a field this doc doesn't mention, keep the field and slot
it into the right layer (§2, §3). If this doc describes a behaviour the code
doesn't have, add it.

### Schema changes: raw SQL only, no migrations

**Do not create Laravel migrations for this module.** All schema work is written
as raw SQL and **appended to the existing module `.sql` file** that already
holds every statement run so far. That file is the deployment artifact — it gets
copied to production by hand, so it must stay runnable top to bottom.

The database and much of the code already exist. Before writing a single
statement, **inspect the live schema** (`SHOW CREATE TABLE`, `DESCRIBE`) and diff
it against §2. Emit SQL only for what is genuinely missing. Do not re-create a
table that exists, and do not restate columns that are already there.

### Keep module files separate

Continue the existing convention: GS1 module code lives in its own directory
tree, with its own routes file, config file, views directory, and `.sql` file.
Touch shared ERP files only where there is no alternative — and when you must,
keep the change to a single registration line (a service provider binding, an
observer registration, a route include) rather than logic. Never put GS1 logic
inside an existing shared controller or model.

---

## 1. Architectural principles (these resolve the current confusion)

1. **`products` is read-only for this module**, with exactly one possible
   exception: the EAN column, if one already exists there (see §4). Everything
   else lives in satellite tables keyed by `product_id`.
2. **Never copy a value that already exists in `products`.** Satellites store
   only what the base table does not have, or a deliberate per-market override.
   This is the whole answer to "if we update the product table, will labels
   update?" — they will, because labels read the base value live.
3. **Resolve at read time, snapshot only at commit time.** A label or a
   DataKart payload is composed on demand through a resolution chain. The
   resolved result is frozen *only* when a label is printed or a payload is
   pushed, for audit and reprint fidelity.
4. **Every inbound and outbound call is logged** with request, response, HTTP
   status and a correlation id.
5. **Syncs are idempotent.** A payload hash decides whether a push is needed at
   all; an idempotency key prevents duplicates on retry.
6. **Push to DataKart is always manual, and always by an admin.** No cron, no
   observer, no queue worker may ever initiate a push. Automation may *prepare*
   a push (rebuild the draft, validate it, flag it for review); a human with the
   admin role presses the button. There is no config flag to relax this.
7. **The draft is always current.** When anything upstream changes, the DataKart
   draft payload is rebuilt immediately and the product is flagged as needing
   review — the draft is never left stale waiting for someone to re-open a form.

---

## 2. Data model

All tables prefixed `gs1_`. Foreign key is `product_id` → `products.id`.

**Read this as a target shape, not a create script.** Several of these tables
already exist. For each one: diff against the live schema, then append only the
delta to the `.sql` file — `ALTER TABLE … ADD COLUMN` for a missing column,
`CREATE TABLE` only for a table that genuinely isn't there. Where an existing
column serves the same purpose under a different name, **keep the existing name**
and note the mapping in a comment rather than renaming it.

### 2.1 `gs1_product_details` (1:1 with product)

The DataKart-only attributes that have no home in `products`.

| Column | Type | Notes |
|---|---|---|
| `product_id` | FK, unique | |
| `gtin` | string(14), nullable, indexed | Only if `products` has no EAN column |
| `gtin_source` | enum | `erp`, `datakart`, `manual` |
| `packaging_level` | enum | `base_unit`, `inner_pack`, `case`, `pallet` |
| `parent_product_id` | FK nullable | packaging hierarchy |
| `brand_name` | string | |
| `sub_brand` | string nullable | |
| `gpc_brick_code` | string | GS1 classification |
| `net_content_value` | decimal(12,4) | canonical value, one unit only |
| `net_content_uom` | FK → `gs1_uoms.code` | canonical UOM |
| `country_of_origin` | char(2) | ISO 3166-1 alpha-2 |
| `hsn_code` | string nullable | |
| `default_target_market` | char(2) | |
| `datakart_id` | string nullable, indexed | remote primary key |
| `sync_state` | enum | see §8 |
| `draft_payload` | json nullable | current DataKart payload, rebuilt on every upstream change |
| `draft_hash` | char(64) nullable | sha256 of `draft_payload` |
| `pushed_payload` | json nullable | exactly what DataKart last accepted |
| `pushed_hash` | char(64) nullable | sha256 of `pushed_payload` |
| `needs_review` | bool, indexed | `draft_hash !== pushed_hash` — denormalised for fast listing |
| `needs_review_since` | timestamp nullable | |
| `last_change_reason` | string nullable | `product_edit`, `market_edit`, `translation_edit`, `enrichment`, `pull` |
| `reviewed_by` / `reviewed_at` | nullable | cleared whenever the draft is rebuilt |
| `last_pushed_at` / `last_pulled_at` | timestamp nullable | |
| `last_error` | text nullable | |
| `revision` | unsigned int | bumped on every relevant change |

`needs_review` is the single flag the whole UI keys off. It is derived, so
recompute it in one place (`SyncStateMachine::refreshDraft()`) and nowhere else.

### 2.2 `gs1_uoms`

| Column | Notes |
|---|---|
| `code` | PK, e.g. `GRM`, `KGM`, `MLT`, `LTR`, `ONZ`, `LBR`, `FOZ`, `MMT` |
| `unece_code` | UN/ECE Rec 20 code sent to DataKart |
| `dimension` | `mass`, `volume`, `length`, `count` |
| `factor_to_base` | decimal(20,10) — factor to the dimension's base unit |
| `symbol_default` | `g`, `ml`, `oz` — overridable per locale |

Conversion is always `value × factor_to_base ÷ target.factor_to_base`.
Cross-dimension conversion is forbidden and must throw.

### 2.3 `gs1_markets`

One row per country this client sells into.

| Column | Notes |
|---|---|
| `country_code` | char(2), PK |
| `preferred_dimension_system` | `metric`, `imperial` |
| `requires_dual_declaration` | bool — print both metric and customary |
| `rounding_rule` | json — decimals / step per dimension |
| `date_format` | string |
| `required_label_fields` | json array of field keys |
| `required_locales` | json array, ordered |

### 2.4 `gs1_product_market` (per product, per country)

Only overrides. A null column means "fall back".

| Column | Notes |
|---|---|
| `product_id`, `country_code` | composite unique |
| `net_content_value` / `net_content_uom` | override only if the market pack genuinely differs |
| `display_uom_code` | nullable — convert for display, do not change the canonical value |
| `mrp` / `currency` | nullable |
| `importer_name`, `importer_address` | nullable |
| `regulatory_marks` | json — e.g. FSSAI licence no., veg/non-veg mark, CE, recycling mark |
| `market_specific_fields` | json — long tail, key/value |
| `is_active` | bool |

### 2.5 `gs1_product_translations`

Key/value so a new label field or a new language never needs a schema change.

| Column | Notes |
|---|---|
| `product_id`, `locale`, `field_key` | composite unique |
| `country_code` | nullable — set only when a translation is market-specific |
| `value` | text |

`field_key` must be validated against a whitelist enum
(`name`, `description`, `ingredients`, `allergens`, `usage_instructions`,
`storage`, `care`, `warnings`, `marketing_claim`, …).

### 2.6 `gs1_label_templates`

| Column | Notes |
|---|---|
| `country_code`, `code`, `version` | composite unique |
| `label_type` | `retail`, `shipping`, `shelf` |
| `width_mm`, `height_mm`, `dpi` | |
| `symbology` | `EAN13`, `ITF14`, `GS1_128`, `GS1_DATAMATRIX` |
| `view_path` | blade view |
| `required_fields` | json |

### 2.7 `gs1_label_renders` (immutable)

`product_id`, `country_code`, `template_id`, `template_version`,
`resolved_payload` (json — the full frozen field set), `file_path`,
`product_revision`, `rendered_by`, `rendered_at`.

Reprint uses the stored snapshot. "Reprint with latest data" re-resolves and
creates a **new** row. Never update a render row.

### 2.8 `gs1_sync_logs`

`direction` (`inbound`/`outbound`), `operation`, `product_id` nullable,
`endpoint`, `request_payload`, `response_payload`, `http_status`,
`status` (`success`/`failed`), `correlation_id`, `duration_ms`.

### 2.9 `gs1_ean_conflicts`

`product_id`, `sku`, `local_ean`, `remote_ean`, `detected_at`,
`status` (`pending`/`accepted_remote`/`kept_local`), `resolved_by`,
`resolved_at`.

### 2.10 `gs1_reconciliation_runs`

`id`, `triggered_by` (user id, or `system` for the cron), `started_at`,
`finished_at`, `total_scanned`, and a count column per §4 outcome
(`filled`, `verified`, `conflicts`, `remote_missing`, `not_found`, `errors`).

---

## 2A. SQL conventions

The `.sql` file is copied to production by hand, so treat it as a permanent,
append-only changelog. Rules:

1. **Append, never rewrite.** Do not edit or reorder statements already in the
   file — they have run in production. New work goes at the bottom under a
   dated, commented block header:

   ```sql
   -- ============================================================
   -- 2026-08-18 · GS1 module · draft payload + review flags
   -- ============================================================
   ```

2. **Every statement must be idempotent**, because the file gets re-run:

   ```sql
   CREATE TABLE IF NOT EXISTS `gs1_product_market` ( … );
   ALTER TABLE `gs1_product_details` ADD COLUMN IF NOT EXISTS `draft_hash` CHAR(64) NULL AFTER `draft_payload`;
   ```

   On a MySQL/MariaDB build without `ADD COLUMN IF NOT EXISTS`, wrap the change
   in a guarded procedure that checks `information_schema.COLUMNS` first, then
   drops the procedure. Never leave a bare `ADD COLUMN` that fails on second run.

3. **Additive only.** No `DROP TABLE`, no `DROP COLUMN`, no destructive
   `MODIFY` on anything outside the `gs1_` prefix. If a column becomes
   redundant, leave it and comment it as deprecated with the date.

4. **`products` is never altered** — with the single possible exception of the
   EAN column in §4, and only if it already exists there. There is no statement
   in this file that adds a column to `products`.

5. **Explicit everything**: charset and collation on every `CREATE TABLE`
   (match the existing tables — check, don't assume), `ENGINE=InnoDB`, named
   foreign keys (`fk_gs1_pm_product`), named indexes, `NOT NULL` with defaults
   where sensible.

6. **Seed data as `INSERT … ON DUPLICATE KEY UPDATE`** (or `INSERT IGNORE`) so
   re-running doesn't duplicate rows. This covers `gs1_uoms` and `gs1_markets`.

7. **Comment intent, not syntax.** One line above each block saying what feature
   it supports, so the file is readable a year from now.

8. **No Eloquent migrations, and no schema changes from application code** — no
   `Schema::` calls, no `DB::statement` DDL at runtime, no auto-creating tables
   on boot.

State at the end of each appended block, as a comment, which application code
depends on it — so a partial prod copy is diagnosable.

---

## 3. Field resolution chain — the core service

`Gs1FieldResolver::resolve(Product $product, string $countryCode, string $locale): ResolvedFieldBag`

Order of precedence, **first non-null wins**:

```
1. gs1_product_translations  (product + locale + country)   ← most specific
2. gs1_product_translations  (product + locale, country null)
3. gs1_product_market        (product + country)
4. gs1_product_details       (product)
5. products                  (base table)                    ← source of truth
```

Locale fallback within steps 1–2 follows the market's `required_locales` order,
then the app's default locale.

Unit handling inside the resolver:

- canonical value always comes from `gs1_product_details` (or a market override)
- if `display_uom_code` is set, or the market's `preferred_dimension_system`
  differs from the canonical UOM's system, convert and round per
  `gs1_markets.rounding_rule`
- if `requires_dual_declaration`, return **both** representations as
  `net_content_primary` and `net_content_secondary`
- converted values are never persisted outside a label snapshot

The resolver returns, alongside values, a `sources` map (`field => layer`) so
the review UI can show "inherited from product" vs "overridden for IN".

Everything downstream — the DataKart payload builder and the label renderer —
consumes this one resolver. Do not write a second resolution path.

---

## 4. Part 1 — Inbound EAN reconciliation

**Scope: the EAN is the only value this job may write. Nothing else.**

Job: `ReconcileEanFromDataKart` (chunked, queued).

**Triggers — phased:**

- *Phase 1 (build now):* a manual **"Run full reconciliation"** button on the GS1
  dashboard, admin-gated, plus a per-product "Re-check EAN" action. Guard both
  with a cache lock so two runs can't overlap.
- *Phase 2 (build the plumbing now, enable later):* the same job registered in
  `app/Console/Kernel.php` behind `gs1.reconcile.schedule_enabled` (default
  `false`) and `gs1.reconcile.cron` in config. Flipping the flag is the only
  change needed to go live — no code edit.

Write each run to a `gs1_reconciliation_runs` row (`triggered_by` — user id or
`system`, started/finished, counts per outcome) so the button and the cron share
one history view.

**This job pulls only. It must never push, and never touch a draft payload.**

For each product with a non-empty `sku`:

1. Look up the remote record — by `datakart_id` if known, else by SKU, else by
   GTIN. *(Confirm from the API docs that DataKart exposes a lookup by internal
   SKU/supplier code; if it does not, see §13 item 3.)*
2. Apply these rules:

| Local EAN | Remote EAN | Action |
|---|---|---|
| empty | present | write it, `gtin_source = datakart`, log |
| present | identical | touch `last_pulled_at` only |
| present | different | **do not overwrite** — create `gs1_ean_conflicts` row, set `sync_state = conflict` |
| present | empty | no write; flag `remote_missing_gtin` for the push flow |
| any | no match found | mark `not_found_remote`, no write |

3. Config flag `gs1.ean.auto_accept_remote` (default **false**) can turn the
   conflict case into an automatic overwrite. Keep the default off — silently
   changing a GTIN on a live product breaks barcodes already in the wild.

Storage target: if `products` already has an EAN column, that column is the one
permitted write in the whole module. Otherwise write to
`gs1_product_details.gtin` and treat that as canonical.

UI: a reconciliation report (counts by outcome) + a conflict queue where a user
accepts remote or keeps local, with the decision written to the conflict row.

---

## 5. Part 2 — ERP-first authoring and publishing

The ERP replaces the DataKart portal for both create and update. Flow:

```
create product (existing ERP flow, writes products only)
        ↓  observer creates gs1_product_details, sync_state = draft
enrichment form (the existing DataKart form)
        ↓  every save → rebuild draft_payload + draft_hash
validation against the DataKart mandatory-attribute profile
        ↓  sync_state = needs_review, needs_review = true
review screen (draft_payload vs pushed_payload, field-level diff)
        ↓  admin marks reviewed → sync_state = ready
        ↓  admin presses "Push to DataKart"  ← manual, admin only
queued job → PushProductToDataKart → create or update by datakart_id presence
        ↓
store datakart_id + returned GTIN + pushed_payload + pushed_hash
needs_review = false, sync_state = synced
```

The queued job exists only so the HTTP request doesn't block and so retries
work. It is dispatched by the admin's click and by nothing else.

### 5.1 Permissions

| Ability | Who |
|---|---|
| `gs1.enrich` | any user who can edit products |
| `gs1.label.print` | any user who can edit products |
| `gs1.reconcile.run` | admin |
| `gs1.review` | admin |
| `gs1.push` | **admin only** |

Enforce `gs1.push` in three places, not one: a policy check in the controller, a
role check inside `PushProductToDataKart::handle()` (so a stray dispatch from
anywhere still fails), and a `@can` on the button. The job takes the acting
user's id as a constructor argument and refuses to run without one — that alone
makes an automated push structurally impossible.

Log every push attempt with the acting user id in `gs1_sync_logs`.

Implementation notes:

- `DataKartPayloadBuilder` takes the `ResolvedFieldBag` for the product's
  `default_target_market` and maps it to the DataKart DTO. Keep the ERP→DataKart
  field map in `config/gs1.php` as a versioned array, not scattered in code.
- Decide create vs update **only** by `datakart_id !== null`. Never by "did we
  push before".
- `Idempotency-Key: sha256(sku . '|' . payload_hash)` on every push.
- Skip the push entirely if `payload_hash` is unchanged; report "no changes".
- Retries: 3 attempts, exponential backoff, retry only on 5xx/timeout/429.
  Never retry a 4xx validation failure — surface it in the UI verbatim.
- On failure: `sync_state = error`, `last_error` populated, product stays
  editable, no partial writes.
- Never delete on DataKart from the ERP. Deactivation only, if the API supports
  it.

---

## 6. Part 3 — Country-wise labels

`LabelService::render(Product $p, string $country, string $templateCode)`:

1. Load the market → get `required_locales` and `required_label_fields`.
2. For each required locale, call `Gs1FieldResolver` → merge into a
   `LabelPayload` DTO containing: identifier block (GTIN + symbology),
   per-locale text blocks in market order, net content (primary + optional
   secondary), regulatory marks, importer block, country of origin, MRP.
3. **Validate before render.** Any missing `required_label_fields` or missing
   required-locale translation blocks the render and returns a precise list of
   what is missing and which layer should supply it.
4. Render the blade template → PDF (Browsershot or dompdf; Browsershot if you
   need proper Devanagari/Arabic shaping).
5. Barcode: EAN-13 for retail base units, ITF-14 for cases, GS1-128 with
   application identifiers where batch/expiry is needed. Generate from the
   resolved GTIN — never from a hand-typed field.
6. Write a `gs1_label_renders` row with the fully resolved payload.

Because steps 1–3 read live through the resolver, **any edit to `products`
appears on the next render with no sync step**. Only already-printed snapshots
stay historical, which is what you want for traceability.

Preview mode uses the identical path with `persist = false`.

---

## 7. Change propagation — "if the product table updates, everything updates"

A product edit must update **both** the label and the DataKart draft. Each side
gets there differently, and neither duplicates data.

### 7.1 Labels — automatic, nothing to do

Labels resolve live through §3, so the next render already shows the new value.
Two extra touches for visibility:

- Invalidate any cached label preview for the affected product/markets.
- Compare `gs1_label_renders.product_revision` (latest render) against the
  product's current `revision`. If they differ, show a **"Printed label is out
  of date — reprint"** badge on the label studio and product row. This is the
  only place where a stale snapshot matters, and it should be visible, not
  silent.

### 7.2 DataKart draft — rebuilt immediately, pushed never

`ProductObserver::updated()` (and the same logic on `gs1_product_market` and
`gs1_product_translations`):

1. Compare changed attributes against `config('gs1.watched_product_fields')`.
   If nothing matched, return.
2. Call `SyncStateMachine::refreshDraft($product, reason: 'product_edit')`,
   which:
   - rebuilds `draft_payload` via the resolver + payload builder
   - recomputes `draft_hash`
   - if `draft_hash === pushed_hash` → `needs_review = false`, state back to
     `synced` (the edit cancelled out an earlier one — this case is real, handle
     it)
   - otherwise → `needs_review = true`, `needs_review_since = now()`,
     `last_change_reason = 'product_edit'`, clear `reviewed_by`/`reviewed_at`,
     bump `revision`, state → `needs_review`
3. Re-run validation and store the result, so the review screen can show
   "changed **and** now invalid" without the admin having to open it.
4. **No push. Ever.** The only output is a flag.

Clearing `reviewed_by` on rebuild matters: a review approves a specific payload,
not a product. If the payload changes after approval, the approval is void.

### 7.3 Visibility requirements (build all of these)

- Dashboard counter: **"N products awaiting review"**, linking to a filtered list.
- Product list column: a coloured `sync_state` chip, plus the age of
  `needs_review_since`.
- Banner on the product edit screen itself: "Changes here will require a
  DataKart review before they reach GS1."
- On the review screen: what changed, when, and why
  (`last_change_reason`), with the field-level diff of `draft_payload` vs
  `pushed_payload`.
- Never show a "Push" button on a product in `needs_review` — the admin must
  pass through review first. Push is only available in `ready`.

---

## 8. Sync state machine

```
        ┌──────────────── any upstream edit ────────────────┐
        │              (rebuilds draft, voids review)       │
        ▼                                                   │
     draft ──validate──▶ needs_review ──admin reviews──▶ ready
                              ▲                            │
                              │                            │ admin presses Push
                              │ validation fails           ▼
                              │                       publishing
                              │                            │
                              ├────────── error ◀──────────┤
                              │                            ▼
                              └──────────────────────── synced
                                                           │
                                                  upstream edit → needs_review

conflict  ← EAN mismatch from §4; resolved manually → returns to prior state
```

Rules:

- `ready` is reachable **only** from `needs_review`, and only by an admin action.
- `publishing` is reachable **only** from `ready`, and only by an admin action.
- Any upstream edit from any state (except `publishing`) sends the product to
  `needs_review` and voids an existing review.
- `synced` means `draft_hash === pushed_hash`. If those ever diverge without the
  state changing, that's a bug — assert it.

Enforce transitions in `SyncStateMachine`, not with scattered `if` statements.

---

## 9. Validation profiles

Keep DataKart's mandatory-attribute list in `config/gs1.php` as a named profile
(e.g. `profiles.datakart_v1`), and the per-country label requirements in
`gs1_markets.required_label_fields`. One `Gs1Validator` reads both. When
DataKart changes its requirements, you edit config — not code.

---

## 10. Class layout

```
app/Modules/Gs1/
├── Clients/DataKartClient.php            auth, base URI, retry, logging middleware
├── Repositories/DataKartProductRepository.php  fetchBySku, fetchByGtin, create, update
├── Services/
│   ├── Gs1FieldResolver.php              §3 — single source of truth
│   ├── DataKartPayloadBuilder.php        ResolvedFieldBag → DataKart DTO
│   ├── DataKartResponseMapper.php        DataKart DTO → ERP DTO
│   ├── Gs1Validator.php
│   ├── UomConverter.php
│   ├── LabelService.php
│   └── SyncStateMachine.php
├── DTOs/                                 ResolvedFieldBag, LabelPayload, DataKartProductDto
├── Jobs/ReconcileEanFromDataKart.php, PushProductToDataKart.php, PullDataKartProduct.php
├── Observers/ProductObserver.php
├── Http/Controllers/                     Enrichment, Review, Conflicts, Labels
└── config/gs1.php
```

The ERP's existing product controllers stay untouched. The module hooks in via
the observer and its own routes.

Everything above lives under the module directory, plus:
`routes/gs1.php`, `config/gs1.php`, `resources/views/gs1/`, and the module
`.sql` file. The only edits permitted outside that tree are single registration
lines: the service provider, the observer binding, the route include, and one
`@include` if a GS1 badge has to appear on an existing product screen. If a
change appears to need more than that, stop and flag it rather than spreading
GS1 logic into shared files.

---

## 11. Screens

1. **GS1 dashboard** — "N awaiting review" counter (primary), counts by
   `sync_state`, conflict queue, "Run full reconciliation" button (admin), last
   reconciliation run summary.
2. **Enrichment form** — the existing DataKart form, reorganised into tabs:
   GS1 attributes → Markets → Translations. Every inherited field shows its
   source layer and an "override for this market" toggle. Saving rebuilds the
   draft and flags review.
3. **Review & push** (admin only) — draft vs last-pushed field-level diff,
   change reason and timestamp, validation errors inline, "Mark reviewed" then a
   separate "Push to DataKart" button. Two clicks, deliberately.
4. **Conflict queue** — accept remote / keep local per row.
5. **Label studio** — pick country + template + locale set, live preview,
   blocking validation list, print, and a render history with reprint.

---

## 12. Gap-fix checklist for the existing code

Run through these against what is already built:

- [ ] Is any GS1 attribute currently stored on `products` or duplicated into a
      satellite? Move it / stop duplicating it.
- [ ] Is there more than one place that decides "which value do I use"?
      Collapse into `Gs1FieldResolver`.
- [ ] Are unit conversions done inline anywhere? Move into `UomConverter`, and
      confirm no converted value is persisted outside a label snapshot.
- [ ] Are translations stored as columns per language? Move to the key/value
      table so a new market needs no schema change.
- [ ] Do any migration files exist for this module? Remove them; the `.sql` file
      is the only schema record.
- [ ] Is every statement in the `.sql` file re-runnable without error?
- [ ] Does any statement in it alter `products` or drop anything?
- [ ] Does the live DB match the `.sql` file, or has something been applied by
      hand and never recorded? Reconcile before adding new blocks.
- [ ] Is any GS1 logic sitting in a shared ERP controller, model, or view?
- [ ] Does the EAN sync write any field other than EAN? Remove those writes.
- [ ] Does the EAN sync overwrite a differing EAN silently? Replace with the
      conflict queue.
- [ ] Is create-vs-update on push decided by anything other than `datakart_id`?
- [ ] Is there a `payload_hash` short-circuit and an idempotency key?
- [ ] Are 4xx responses being retried? Stop.
- [ ] Are requests/responses logged with a correlation id?
- [ ] Do labels read live from `products`, or from a copied snapshot?
- [ ] Is label validation blocking, or does it render with blanks?
- [ ] Is `sync_state` free-form, or enforced by a state machine?
- [ ] Are DataKart credentials in config/env, never committed?
- [ ] Can any code path push to DataKart without an acting admin user id? Close
      it — check jobs, observers, console commands, and the scheduler.
- [ ] Does editing a product rebuild the draft payload, or only flag it? It must
      rebuild.
- [ ] Does an existing review survive a later edit? It must not — clear
      `reviewed_by`.
- [ ] Is `needs_review` computed in more than one place? Collapse to one.
- [ ] Is the "Push" button visible in any state other than `ready`?
- [ ] Is there a visible indicator that a *printed* label is now out of date?
- [ ] Are the existing form's fields all mapped to a layer in §2, or are some
      still homeless / duplicated onto `products`?

---

## 13. Open decisions — confirm before coding

*(Endpoints, auth, and the full field list are already in the repo — read them
from there rather than asking. The items below are design choices the existing
code may not settle.)*

1. **Sandbox**: is there a non-production DataKart environment? If not, gate the
   push button behind an extra confirm in non-prod and never seed real GTINs.
2. **GTIN allocation**: are GTINs allocated from your GS1 company prefix inside
   the ERP, or assigned by DataKart on create? This decides whether push sends a
   GTIN or receives one.
3. **SKU lookup**: does DataKart store and expose your internal SKU? If not, the
   link must be `gs1_product_details.datakart_id` established at first push, and
   the initial backfill has to be matched on GTIN or done by import.
4. **Packaging hierarchy**: in scope for v1? Each level needs its own GTIN and
   its own label.
5. **Images**: DataKart usually requires publicly reachable product image URLs —
   confirm hosting and minimum resolution.
6. **Roles**: who may enrich vs who may publish? Assume separate permissions.
7. **Country list**: which markets for v1, and which of those need
   dual-declaration and non-Latin scripts?
8. **Label output**: PDF for a thermal printer (exact mm, no scaling) or A4
   sheet layout? Affects the render engine choice.

---

## 14. Build order

1. **Schema diff first.** Dump the live GS1 tables, diff against §2, and append
   one dated SQL block covering every gap. Map every field from the existing
   DataKart form to a layer as you go. Then add/adjust the Eloquent models to
   match — models only, no schema code.
2. `Gs1FieldResolver` + `UomConverter`, with unit tests. Nothing else until
   these are right — everything depends on them.
3. `SyncStateMachine` (`refreshDraft`, transitions) + permissions. Build this
   *before* the push path so the admin-only rule is structural, not bolted on.
4. `DataKartClient` + `gs1_sync_logs` (existing endpoints).
5. Part 1: reconciliation button + job + conflict queue.
6. `ProductObserver` → draft rebuild + review flagging + visibility.
7. Part 2: review screen → manual admin push.
8. Part 3: templates, label service, render history, out-of-date badge.
9. Dashboard.
10. Register the reconciliation cron behind its disabled flag.

---

## 15. Tests worth writing

- Resolver returns the correct layer for each precedence case, including
  "market override present but null".
- Unit conversion round-trips within tolerance; cross-dimension throws.
- Dual declaration produces both values with correct rounding.
- EAN reconciliation: one test per row of the §4 table.
- Push is skipped when `payload_hash` is unchanged.
- Editing a watched field on `products` rebuilds `draft_payload` and sets
  `needs_review = true`; editing an unwatched field does neither.
- An edit that restores a product to its last-pushed values clears
  `needs_review` instead of leaving it stuck on.
- Approving a review then editing the product voids `reviewed_by`.
- A non-admin cannot reach the push endpoint, and dispatching
  `PushProductToDataKart` without an admin user id throws.
- No scheduled task or observer dispatches a push (assert on the scheduler's
  registered events).
- A label render with a missing required translation is blocked, not blank.
- A product edited after printing shows the out-of-date-label indicator.
