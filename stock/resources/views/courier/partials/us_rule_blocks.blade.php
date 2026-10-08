{{-- Block engine: US & Canada → in/lb; UK, EU, Australia → cm/kg. Legacy custom_condition when no blocks. --}}
@php
    $usBlocks = $usRuleBlocksArray ?? [];
    $ruleBlocksCountryInit = '';
    if (isset($courier)) {
        $cc = strtoupper((string) ($courier->country ?? ''));
        if (\App\Services\UsCourierRuleService::supportsBlockEngine($cc)) {
            $ruleBlocksCountryInit = $cc;
        }
    }
    $usShow = $ruleBlocksCountryInit !== '';
@endphp

<div id="us-rule-engine-wrap" class="mt-4 p-3 border rounded bg-light" style="display: {{ $usShow ? 'block' : 'none' }};">
    <h4 class="mb-2" id="country-rule-blocks-title">Surcharge rule blocks</h4>
    <p class="text-muted small mb-3" id="country-rule-blocks-help">
        <span class="us-only-help"><strong>US &amp; Canada:</strong> Dimensions in <strong>inches</strong> (box cm ÷ 2.54). Weights in <strong>lbs</strong> (volumetric lb or box gross kg → lb).</span>
        <span class="uk-only-help" style="display:none;"><strong>UK, EU &amp; Australia:</strong> Dimensions in <strong>cm</strong> (same as pricing box fields). Weights in <strong>kg</strong> (volumetric kg or box gross kg).</span>
        <span class="d-block mt-1"><strong>2 sides</strong> = any pair of edges where <em>both</em> pass the tier; <strong>3 sides</strong> = all three pass. Highest matching tier wins per condition. <strong>OR</strong> block: first matching condition wins; <strong>AND</strong>: all must match, surcharges sum. Matched blocks sum.</span>
    </p>
    <style>
        #us-blocks-root .us-block-card {
            background: #fff;
            border: 1px solid #ced4da;
            border-left-width: 5px;
            border-radius: 0.375rem;
            box-shadow: 0 0.15rem 0.55rem rgba(0, 0, 0, 0.07);
            padding: 1rem 1.15rem 1.15rem;
            margin-bottom: 1.75rem !important;
        }
        #us-blocks-root .us-block-card:nth-child(4n + 1) { border-left-color: #0d6efd; }
        #us-blocks-root .us-block-card:nth-child(4n + 2) { border-left-color: #6f42c1; }
        #us-blocks-root .us-block-card:nth-child(4n + 3) { border-left-color: #0aa2c0; }
        #us-blocks-root .us-block-card:nth-child(4n + 4) { border-left-color: #198754; }
        #us-blocks-root .us-block-card:last-child { margin-bottom: 0.5rem !important; }
        #us-blocks-root .us-block-card-header {
            border-bottom: 2px solid rgba(13, 110, 253, 0.2);
            margin: -0.25rem -0.15rem 1rem -0.15rem;
            padding: 0.35rem 0 0.65rem;
        }
        #us-blocks-root .us-cond-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.3rem;
            padding: 0.75rem !important;
            margin-left: 0 !important;
            margin-bottom: 0.75rem !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }
        #us-blocks-root .us-cond-card:last-child { margin-bottom: 0 !important; }
        #us-blocks-root .us-conds {
            padding: 0.5rem 0 0.25rem;
            margin-top: 0.25rem;
            border-top: 1px dashed #dee2e6;
        }
    </style>
    <div id="us-blocks-root" class="mb-2"></div>
    <button type="button" class="btn btn-sm btn-primary" id="us-btn-add-block">Add block</button>
    <input type="hidden" name="us_rule_blocks_json" id="us_rule_blocks_json" value="">
    <input type="hidden" name="uk_rule_blocks_json" id="uk_rule_blocks_json" value="">
    <div class="mt-3"><strong>Preview</strong></div>
    <pre id="us_rule_preview" class="small bg-white border p-2 mt-1 mb-0" style="white-space:pre-wrap;max-height:220px;overflow:auto;"></pre>
</div>

<script>
(function () {
    var RULE_BLOCKS_INIT_COUNTRY = @json($ruleBlocksCountryInit);
    var LOADED_RULE_BLOCKS = @json($usBlocks);

    var ATTR_OPTS_UK = [
        { v: '1_sides', t: '1 side (largest edge, cm)' },
        { v: '2_sides', t: '2 sides (any pair both exceed, cm)' },
        { v: '3_sides', t: '3 sides (all edges exceed, cm)' },
        { v: 'length', t: 'Length (longest side, cm)' },
        { v: 'girth', t: 'Girth 2×(mid+small)+long (cm)' },
        { v: 'length_plus_girth', t: 'Long + 2×(mid+small) (cm)' },
        { v: 'kg', t: 'Volumetric weight (kg)' },
        { v: 'box_weight', t: 'Box weight — gross (kg)' }
    ];

    var ATTR_OPTS_US = [
        { v: '1_sides', t: '1 side (largest edge, in)' },
        { v: '2_sides', t: '2 sides (any pair both exceed, in)' },
        { v: '3_sides', t: '3 sides (all edges exceed, in)' },
        { v: 'length', t: 'Length (longest side, in)' },
        { v: 'girth', t: 'Girth 2×(mid+small)+long (in)' },
        { v: 'length_plus_girth', t: 'Long + 2×(mid+small) (in)' },
        { v: 'lbs', t: 'Volumetric weight (lbs)' },
        { v: 'box_weight', t: 'Box weight — gross product (lbs)' }
    ];

    var ATTR_OPTS = ATTR_OPTS_US;
    var activeBlocksCountry = '';
    var loadedMeta = { country: RULE_BLOCKS_INIT_COUNTRY, initial: LOADED_RULE_BLOCKS };

    var BLOCK_ENGINE_SET = { US: 1, UK: 1, EU: 1, CANADA: 1, AUSTRALIA: 1 };

    function normalizeSelCountry(v) {
        return String(v || '').toUpperCase();
    }
    function isBlockEngineCountry(v) {
        return !!BLOCK_ENGINE_SET[normalizeSelCountry(v)];
    }
    function usesUsBlockUnits(v) {
        var u = normalizeSelCountry(v);
        return u === 'US' || u === 'CANADA';
    }

    /** Display-only currency labels by country (does not affect stored amounts). */
    function currencyCodeForCountry(v) {
        var u = normalizeSelCountry(v);
        if (u === 'US') return 'USD';
        if (u === 'CANADA') return 'CAD';
        if (u === 'EU') return 'EUR';
        if (u === 'AUSTRALIA') return 'AUD';
        if (u === 'UK') return 'GBP';
        return 'GBP';
    }
    function currencySymbolForCountry(v) {
        var u = normalizeSelCountry(v);
        if (u === 'US') return '$';
        if (u === 'CANADA') return 'C$';
        if (u === 'EU') return '€';
        if (u === 'AUSTRALIA') return 'A$';
        if (u === 'UK') return '£';
        return '£';
    }
    function surchargeLabelForCountry(v) {
        return 'Surcharge (' + currencyCodeForCountry(v) + ')';
    }

    function unitForAttributeKey(attr) {
        var a = String(attr || '').toLowerCase().replace(/-/g, '_');
        if (usesUsBlockUnits(activeBlocksCountry)) {
            if (a === 'lbs' || a === 'box_weight' || a === 'boxweight') return 'lbs';
            if (a === 'kg') return 'kg';
            return 'in';
        }
        if (a === 'lbs') return 'lbs';
        if (a === 'kg' || a === 'box_weight' || a === 'boxweight') return 'kg';
        return 'cm';
    }
    var OP_OPTS = ['>', '>=', '<', '<=', '=', 'between', 'always'];

    function attrSelectHtml(val) {
        var v0 = val || '1_sides';
        var h = '<select class="form-control form-control-sm us-attr">';
        var seen = {};
        ATTR_OPTS.forEach(function (o) {
            seen[o.v] = true;
            h += '<option value="' + o.v + '"' + (o.v === v0 ? ' selected' : '') + '>' + o.t + '</option>';
        });
        if (v0 && !seen[v0]) {
            h += '<option value="' + String(v0).replace(/"/g, '&quot;') + '" selected>(legacy) ' + String(v0).replace(/</g, '') + '</option>';
        }
        h += '</select>';
        return h;
    }
    function opSelectHtml(val) {
        var h = '<select class="form-control form-control-sm us-tier-op">';
        OP_OPTS.forEach(function (o) {
            h += '<option value="' + o + '"' + (o === val ? ' selected' : '') + '>' + o + '</option>';
        });
        h += '</select>';
        return h;
    }

    var state = [];

    function defaultTier() {
        return { operator: '>', value_min: '', value_max: '', surcharge: '' };
    }
    function defaultCondition() {
        return { condition_priority: 10, attribute_key: '1_sides', unit: unitForAttributeKey('1_sides'), tiers: [defaultTier()] };
    }
    function defaultBlock() {
        return { block_name: '', block_priority: 100, condition_operator: 'OR', conditions: [defaultCondition()] };
    }

    var root = document.getElementById('us-blocks-root');
    var previewEl = document.getElementById('us_rule_preview');
    var hiddenUs = document.getElementById('us_rule_blocks_json');
    var hiddenUk = document.getElementById('uk_rule_blocks_json');
    if (!root) return;

    function readCountrySelect() {
        var s = document.querySelector('select[name="country"]');
        if (!s || !s.value) return '';
        var v = normalizeSelCountry(s.value);
        return isBlockEngineCountry(v) ? v : '';
    }

    function syncHidden() {
        readFormIntoState();
        var json = JSON.stringify(serializeState());
        var mode = activeBlocksCountry;
        var usProfile = usesUsBlockUnits(mode);
        if (hiddenUs) hiddenUs.value = usProfile ? json : '[]';
        if (hiddenUk) hiddenUk.value = (!usProfile && mode) ? json : '[]';
        updatePreview();
    }

    function setRuleBlocksCountryMode(u) {
        u = normalizeSelCountry(u);
        if (!isBlockEngineCountry(u)) u = '';
        activeBlocksCountry = u;
        ATTR_OPTS = usesUsBlockUnits(u) ? ATTR_OPTS_US : ATTR_OPTS_UK;
        var incoming = (u && loadedMeta.country === u && Array.isArray(loadedMeta.initial)) ? loadedMeta.initial : [];
        state = JSON.parse(JSON.stringify(incoming));
        var title = document.getElementById('country-rule-blocks-title');
        if (title) {
            if (u === 'US') title.textContent = 'US surcharge rule blocks';
            else if (u === 'CANADA') title.textContent = 'Canada surcharge rule blocks';
            else if (u === 'UK') title.textContent = 'UK surcharge rule blocks';
            else if (u === 'EU') title.textContent = 'EU surcharge rule blocks';
            else if (u === 'AUSTRALIA') title.textContent = 'Australia surcharge rule blocks';
            else title.textContent = 'Surcharge rule blocks';
        }
        var uh = document.querySelector('.us-only-help');
        var kh = document.querySelector('.uk-only-help');
        if (uh) uh.style.display = usesUsBlockUnits(u) ? '' : 'none';
        if (kh) kh.style.display = (!usesUsBlockUnits(u) && u) ? '' : 'none';
        render();
        syncHidden();
    }
    window.setRuleBlocksCountryMode = setRuleBlocksCountryMode;

    function serializeState() {
        return state.map(function (b) {
            return {
                block_name: b.block_name || '',
                block_priority: parseInt(b.block_priority, 10) || 0,
                condition_operator: b.condition_operator === 'AND' ? 'AND' : 'OR',
                conditions: (b.conditions || []).map(function (c) {
                    var ak = c.attribute_key || '1_sides';
                    return {
                        condition_priority: parseInt(c.condition_priority, 10) || 0,
                        attribute_key: ak,
                        unit: unitForAttributeKey(ak),
                        tiers: (c.tiers || []).map(function (t) {
                            return {
                                operator: t.operator || '>',
                                value_min: t.value_min === '' || t.value_min === null ? null : parseFloat(t.value_min),
                                value_max: t.value_max === '' || t.value_max === null ? null : parseFloat(t.value_max),
                                surcharge: parseFloat(t.surcharge) || 0
                            };
                        })
                    };
                })
            };
        });
    }

    function updatePreview() {
        var lines = [];
        state.forEach(function (b, bi) {
            lines.push('Block ' + (bi + 1) + ': ' + (b.block_name || '(unnamed)') + ' [' + (b.condition_operator || 'OR') + '] display order ' + (b.block_priority || 0));
            (b.conditions || []).forEach(function (c, ci) {
                lines.push('  Condition ' + (ci + 1) + ' [P' + (c.condition_priority || 0) + '] ' + (c.attribute_key || '') + ' (' + (c.unit || '') + ')');
                (c.tiers || []).forEach(function (t, ti) {
                    var vm = t.value_min !== '' && t.value_min != null ? t.value_min : '—';
                    var vx = t.value_max !== '' && t.value_max != null ? t.value_max : '—';
                    var cur = currencyCodeForCountry(activeBlocksCountry);
                    lines.push('    Tier ' + (ti + 1) + ': ' + (t.operator || '') + ' min=' + vm + ' max=' + vx + ' → +' + (t.surcharge || 0) + ' ' + cur);
                });
            });
        });
        previewEl.textContent = lines.length ? lines.join('\n') : '(no blocks)';
    }

    function readFormIntoState() {
        root.querySelectorAll('.us-block-card').forEach(function (card, bi) {
            if (!state[bi]) return;
            state[bi].block_name = card.querySelector('.us-block-name').value;
            state[bi].block_priority = card.querySelector('.us-block-prio').value;
            state[bi].condition_operator = card.querySelector('.us-block-op').value;
            card.querySelectorAll('.us-cond-card').forEach(function (cc, ci) {
                if (!state[bi].conditions[ci]) return;
                state[bi].conditions[ci].condition_priority = cc.querySelector('.us-cond-prio').value;
                var selAttr = cc.querySelector('.us-attr').value;
                state[bi].conditions[ci].attribute_key = selAttr;
                state[bi].conditions[ci].unit = unitForAttributeKey(selAttr);
                var unitEl = cc.querySelector('.us-cond-unit');
                if (unitEl) unitEl.value = unitForAttributeKey(selAttr);
                cc.querySelectorAll('.us-tier-row').forEach(function (tr, ti) {
                    if (!state[bi].conditions[ci].tiers[ti]) return;
                    state[bi].conditions[ci].tiers[ti].operator = tr.querySelector('.us-tier-op').value;
                    state[bi].conditions[ci].tiers[ti].value_min = tr.querySelector('.us-tier-min').value;
                    state[bi].conditions[ci].tiers[ti].value_max = tr.querySelector('.us-tier-max').value;
                    state[bi].conditions[ci].tiers[ti].surcharge = tr.querySelector('.us-tier-sur').value;
                });
            });
        });
    }

    function render() {
        root.innerHTML = '';
        state.forEach(function (b, bi) {
            var card = document.createElement('div');
            var blockNum = bi + 1;
            card.className = 'us-block-card';
            card.innerHTML =
                '<div class="us-block-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">' +
                '<div class="d-flex align-items-center flex-wrap gap-2">' +
                '<span class="badge rounded-pill bg-primary px-3 py-2 fs-6">Block ' + blockNum + '</span>' +
                '<span class="text-muted small d-none d-sm-inline">Separate surcharge group — stacked with other blocks</span></div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger us-remove-block">Remove block</button></div>' +
                '<div class="row g-2 mb-2">' +
                '<div class="col-md-4"><label class="small fw-semibold">Block name</label><input type="text" class="form-control form-control-sm us-block-name" placeholder="e.g. Oversize length" value="' + (b.block_name || '').replace(/"/g, '&quot;') + '"></div>' +
                '<div class="col-md-3"><label class="small fw-semibold">Display order</label><input type="number" class="form-control form-control-sm us-block-prio" value="' + (b.block_priority || 0) + '"></div>' +
                '<div class="col-md-5"><label class="small fw-semibold">Conditions join</label><select class="form-control form-control-sm us-block-op"><option value="OR"' + (b.condition_operator !== 'AND' ? ' selected' : '') + '>OR (first match wins)</option><option value="AND"' + (b.condition_operator === 'AND' ? ' selected' : '') + '>AND (all must match, sum)</option></select></div></div>' +
                '<div class="us-conds"></div>' +
                '<button type="button" class="btn btn-sm btn-secondary us-add-cond mt-1">Add condition</button>';

            var condsEl = card.querySelector('.us-conds');
            (b.conditions || []).forEach(function (c, ci) {
                condsEl.appendChild(renderCondition(bi, ci, c));
            });

            card.querySelector('.us-remove-block').onclick = function () {
                readFormIntoState();
                state.splice(bi, 1);
                render();
                syncHidden();
            };
            card.querySelector('.us-add-cond').onclick = function () {
                readFormIntoState();
                state[bi].conditions.push(defaultCondition());
                render();
                syncHidden();
            };
            root.appendChild(card);
        });
        syncHidden();
    }

    function renderCondition(bi, ci, c) {
        var wrap = document.createElement('div');
        wrap.className = 'us-cond-card';
        wrap.innerHTML =
            '<div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-secondary border-opacity-25"><span class="badge bg-secondary">Condition ' + (ci + 1) + '</span>' +
            '<button type="button" class="btn btn-sm btn-outline-danger us-remove-cond">Remove</button></div>' +
            '<div class="row g-2 mb-2">' +
            '<div class="col-md-5">' + attrSelectHtml(c.attribute_key) + '</div>' +
            '<div class="col-md-3"><label class="small">Unit</label><input type="text" class="form-control form-control-sm us-cond-unit" readonly title="Set automatically from attribute" value="' + unitForAttributeKey(c.attribute_key).replace(/"/g, '&quot;') + '"></div>' +
            '<div class="col-md-4"><label class="small">Condition priority (1=highest)</label><input type="number" class="form-control form-control-sm us-cond-prio" value="' + (c.condition_priority || 10) + '"></div></div>' +
            '<div class="us-tiers small"></div>' +
            '<button type="button" class="btn btn-sm btn-outline-primary us-add-tier">Add tier</button>';

        wrap.querySelector('.us-remove-cond').onclick = function () {
            readFormIntoState();
            state[bi].conditions.splice(ci, 1);
            if (!state[bi].conditions.length) state[bi].conditions.push(defaultCondition());
            render();
            syncHidden();
        };
        wrap.querySelector('.us-add-tier').onclick = function () {
            readFormIntoState();
            state[bi].conditions[ci].tiers.push(defaultTier());
            render();
            syncHidden();
        };

        var tiersEl = wrap.querySelector('.us-tiers');
        (c.tiers || []).forEach(function (t, ti) {
            tiersEl.appendChild(renderTier(bi, ci, ti, t));
        });

        wrap.querySelector('.us-attr').addEventListener('change', function () {
            var u = unitForAttributeKey(this.value);
            var ue = wrap.querySelector('.us-cond-unit');
            if (ue) ue.value = u;
            readFormIntoState();
            syncHidden();
        });

        return wrap;
    }

    function renderTier(bi, ci, ti, t) {
        var row = document.createElement('div');
        row.className = 'us-tier-row row g-1 align-items-end mb-1';
        var op = t.operator || '>';
        var showMax = op === 'between';
        var surLabel = surchargeLabelForCountry(activeBlocksCountry);
        row.innerHTML =
            '<div class="col-md-2"><label class="small">Operator</label>' + opSelectHtml(op) + '</div>' +
            '<div class="col-md-2"><label class="small">Value min</label><input type="number" step="any" class="form-control form-control-sm us-tier-min" value="' + (t.value_min !== null && t.value_min !== undefined && t.value_min !== '' ? t.value_min : '') + '"></div>' +
            '<div class="col-md-2 us-tier-max-wrap"><label class="small">Value max</label><input type="number" step="any" class="form-control form-control-sm us-tier-max" value="' + (showMax && t.value_max !== null && t.value_max !== undefined && t.value_max !== '' ? t.value_max : '') + '"' + (showMax ? '' : ' disabled placeholder="between only"') + '></div>' +
            '<div class="col-md-2"><label class="small">' + surLabel + '</label><input type="number" step="any" class="form-control form-control-sm us-tier-sur" value="' + (t.surcharge !== null && t.surcharge !== undefined && t.surcharge !== '' ? t.surcharge : '') + '"></div>' +
            '<div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger us-remove-tier">×</button></div>';

        var opEl = row.querySelector('.us-tier-op');
        var maxEl = row.querySelector('.us-tier-max');
        opEl.addEventListener('change', function () {
            var on = opEl.value === 'between';
            maxEl.disabled = !on;
            if (!on) maxEl.value = '';
        });

        row.querySelector('.us-remove-tier').onclick = function () {
            readFormIntoState();
            state[bi].conditions[ci].tiers.splice(ti, 1);
            if (!state[bi].conditions[ci].tiers.length) state[bi].conditions[ci].tiers.push(defaultTier());
            render();
            syncHidden();
        };
        return row;
    }

    document.getElementById('us-btn-add-block').addEventListener('click', function () {
        readFormIntoState();
        state.push(defaultBlock());
        render();
        syncHidden();
    });

    var courierForm = document.querySelector('form[action*="courier"]');
    if (courierForm) {
        courierForm.addEventListener('submit', function () {
            readFormIntoState();
            syncHidden();
        });
    }

    setRuleBlocksCountryMode(RULE_BLOCKS_INIT_COUNTRY || readCountrySelect());
})();
</script>
