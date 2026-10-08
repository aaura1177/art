<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hybrid pricing export: keep inputs + Courier Cost as values;
 * write Excel formulas for the derived pricing chain so sheet math
 * matches the app (cost → FOB → landed → final → delivered).
 * Also adds a "Calculation Notes" sheet explaining remaining columns.
 */
trait AppliesPricingExportFormulas
{
    /**
     * @param  array<string, string>  $cols  logical name => column letter
     * @param  array<int, string>  $headings  export heading labels (0-based)
     * @return array<string, callable>
     */
    protected function pricingFormulaEvents(array $cols, array $headings = []): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) use ($cols, $headings) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = (int) $sheet->getHighestRow();
                if ($highestRow >= 2) {
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $this->applyPricingFormulasToRow($sheet, $row, $cols);
                    }
                }

                $this->addCalculationNotesSheet(
                    $sheet,
                    $cols,
                    $headings,
                    $this->pricingExportNotesCountry()
                );
            },
        ];
    }

    /**
     * Destination country for Calculation Notes courier section.
     * Return null to document all countries (mixed / "All destinations" export).
     * Override in export classes when the sheet is for one country.
     */
    protected function pricingExportNotesCountry(): ?string
    {
        return null;
    }

    protected function normalizeNotesCountry(?string $country): ?string
    {
        if ($country === null || trim($country) === '') {
            return null;
        }

        $u = strtoupper(trim($country));
        if ($u === 'UNITED STATES' || $u === 'USA') {
            return 'US';
        }
        if ($u === 'UNITED KINGDOM' || $u === 'GB' || $u === 'GREAT BRITAIN') {
            return 'UK';
        }

        return $u;
    }

    /**
     * @param  array<string, string>  $c
     */
    protected function applyPricingFormulasToRow(Worksheet $sheet, int $row, array $c): void
    {
        $n = function (string $key) use ($c, $row): string {
            $col = $c[$key];

            return $col.$row;
        };
        $has = function (string $key) use ($c): bool {
            return isset($c[$key]);
        };
        $isIndia = $has('destination')
            ? 'LOWER(TRIM('.$n('destination').'))="india"'
            : 'FALSE';
        $destinationValue = $has('destination')
            ? strtolower(trim((string) $sheet->getCell($n('destination'))->getValue()))
            : '';
        $rowIsIndia = $destinationValue === 'india';
        $hasIndiaStack = $has('indiaShippingCost')
            && $has('indiaCostPrice')
            && $has('indiaAdminCostPercent')
            && $has('indiaAdminCost')
            && $has('indiaProfitPercent')
            && $has('indiaFinalCost')
            && $has('shippingCost');

        // India rows: write explicit 0s for skipped destination-side inputs first so
        // live India formulas and the sheet itself do not look empty.
        if ($rowIsIndia) {
            foreach ([
                'converRate', 'tariff', 'ship2', 'storage', 'outbound', 'qa',
                'adminProfit', 'finalPricePer', 'adjustment',
            ] as $zeroKey) {
                if ($has($zeroKey)) {
                    $sheet->setCellValue($n($zeroKey), 0);
                }
            }
            // Maatwebsite can omit numeric 0; keep India Shipping visible as 0.
            if ($has('indiaShippingCost')) {
                $current = $sheet->getCell($n('indiaShippingCost'))->getValue();
                if ($current === null || $current === '') {
                    $sheet->setCellValue($n('indiaShippingCost'), 0);
                }
            }
        }

        // India rows: compute the India cost chain as real numbers (not live formulas).
        // Prefer dedicated India columns when present; otherwise use main Admin/Profit %
        // and Admin/Final Cost values already mapped from the India stack in PHP.
        if ($rowIsIndia && $has('costPrice') && $has('adminCostPercent') && $has('profitPercent')) {
            $courierCached = $has('courierCost')
                ? (float) ($sheet->getCell($n('courierCost'))->getValue() ?: 0)
                : 0.0;

            if ($hasIndiaStack) {
                $costPrice = (float) ($sheet->getCell($n('costPrice'))->getValue() ?: 0);
                $commonShipping = (float) ($sheet->getCell($n('shippingCost'))->getValue() ?: 0);
                $indiaShipping = (float) ($sheet->getCell($n('indiaShippingCost'))->getValue() ?: 0);
                $indiaAdminPercent = (float) ($sheet->getCell($n('indiaAdminCostPercent'))->getValue() ?: 0);
                $indiaProfitPercent = (float) ($sheet->getCell($n('indiaProfitPercent'))->getValue() ?: 0);

                $indiaCostPrice = round(max(0, $costPrice - $commonShipping) + $indiaShipping, 2);
                $indiaAdminCostValue = round($indiaCostPrice * (1 + ($indiaAdminPercent / 100)), 2);
                $indiaFinalCostValue = round($indiaAdminCostValue * (1 + ($indiaProfitPercent / 100)), 2);

                $sheet->setCellValue($n('indiaCostPrice'), $indiaCostPrice);
                $sheet->setCellValue($n('indiaAdminCost'), $indiaAdminCostValue);
                $sheet->setCellValue($n('indiaFinalCost'), $indiaFinalCostValue);
            } else {
                // Main Shipping / Cost Price / Admin% / Profit% columns already hold
                // India-only values for India rows — do not subtract shipping again.
                $indiaCostPrice = (float) ($sheet->getCell($n('costPrice'))->getValue() ?: 0);
                $indiaAdminPercent = (float) ($sheet->getCell($n('adminCostPercent'))->getValue() ?: 0);
                $indiaProfitPercent = (float) ($sheet->getCell($n('profitPercent'))->getValue() ?: 0);

                $indiaAdminCostValue = round($indiaCostPrice * (1 + ($indiaAdminPercent / 100)), 2);
                $indiaFinalCostValue = round($indiaAdminCostValue * (1 + ($indiaProfitPercent / 100)), 2);

                $mappedAdmin = (float) ($sheet->getCell($n('adminCost'))->getValue() ?: 0);
                $mappedFinal = (float) ($sheet->getCell($n('finalCost'))->getValue() ?: 0);
                if ($mappedAdmin > 0) {
                    $indiaAdminCostValue = $mappedAdmin;
                }
                if ($mappedFinal > 0) {
                    $indiaFinalCostValue = $mappedFinal;
                }
            }

            $deliveryCached = round($indiaFinalCostValue + $courierCached, 2);

            $sheet->setCellValue($n('adminCost'), $indiaAdminCostValue);
            $sheet->setCellValue($n('finalCost'), $indiaFinalCostValue);
            if ($has('fob')) {
                $sheet->setCellValue($n('fob'), 0);
            }
            if ($has('tariffFob')) {
                $sheet->setCellValue($n('tariffFob'), 0);
            }
            $sheet->setCellValue($n('landed'), $indiaFinalCostValue);
            $sheet->setCellValue($n('adminPrice'), $indiaFinalCostValue);
            $sheet->setCellValue($n('finalPrice'), $indiaFinalCostValue);
            $sheet->setCellValue($n('deliveryCost'), $deliveryCached);
            $sheet->setCellValue($n('newDelCost'), (float) round($deliveryCached));

            return;
        }

        // Non-India (and fallback): keep the existing live Excel formula chain.
        $sheet->setCellValue(
            $n('adminCost'),
            '=ROUND('.$n('costPrice').'*(1+N('.$n('adminCostPercent').')/100),2)'
        );
        $sheet->setCellValue(
            $n('finalCost'),
            '=ROUND('.$n('adminCost').'*(1+N('.$n('profitPercent').')/100),2)'
        );
        $sheet->setCellValue(
            $n('fob'),
            '=IF(OR('.$isIndia.',N('.$n('converRate').')=0),0,ROUND(ROUND('.$n('finalCost').'/'.$n('converRate').',2),0))'
        );
        $sheet->setCellValue(
            $n('tariffFob'),
            '=IF('.$isIndia.',0,ROUND(ROUND('.$n('fob').'*(1+N('.$n('tariff').')/100),2),0))'
        );
        $sheet->setCellValue(
            $n('landed'),
            '=IF('.$isIndia.',N('.$n('finalCost').'),ROUND(N('.$n('ship2').')+N('.$n('storage').')+N('.$n('outbound').')+N('.$n('qa').')+N('.$n('tariffFob').'),2))'
        );
        $sheet->setCellValue(
            $n('adminPrice'),
            '=IF('.$isIndia.',N('.$n('finalCost').'),ROUND('.$n('landed').'*(1+N('.$n('adminProfit').')/100),2))'
        );
        $sheet->setCellValue(
            $n('finalPrice'),
            '=IF('.$isIndia.',N('.$n('finalCost').'),ROUND('.$n('adminPrice').'*(1+N('.$n('finalPricePer').')/100),2))'
        );
        $sheet->setCellValue(
            $n('deliveryCost'),
            '=ROUND(N('.$n('finalPrice').')+N('.$n('courierCost').'),2)'
        );
        $sheet->setCellValue(
            $n('newDelCost'),
            '=IF('.$isIndia.',ROUND('.$n('deliveryCost').',0),ROUND('.$n('deliveryCost').'*(1+N('.$n('adjustment').')/100),0))'
        );
    }

    /**
     * Extra sheet: explain formula columns vs value columns (incl. courier).
     *
     * @param  array<string, string>  $cols
     * @param  array<int, string>  $headings
     */
    protected function addCalculationNotesSheet(Worksheet $dataSheet, array $cols, array $headings, ?string $notesCountry = null): void
    {
        $spreadsheet = $dataSheet->getParent();
        if ($spreadsheet === null) {
            return;
        }

        // Avoid duplicate notes sheet if export is re-run in same process
        if ($spreadsheet->sheetNameExists('Calculation Notes')) {
            $notes = $spreadsheet->getSheetByName('Calculation Notes');
        } else {
            $notes = $spreadsheet->createSheet();
            $notes->setTitle('Calculation Notes');
        }

        // Only the derived outputs are formulas; inputs in $cols stay values.
        $formulaLogical = [
            'adminCost', 'finalCost', 'fob', 'tariffFob', 'landed',
            'adminPrice', 'finalPrice', 'deliveryCost', 'newDelCost',
        ];
        $formulaLetters = [];
        foreach ($formulaLogical as $key) {
            if (isset($cols[$key])) {
                $formulaLetters[$cols[$key]] = true;
            }
        }

        $explanations = $this->pricingColumnExplanations();
        $notesCountry = $this->normalizeNotesCountry($notesCountry);

        $notes->setCellValue('A1', 'Pricing export — calculation guide');
        $notes->setCellValue('A2', 'Hybrid export: some columns are live Excel formulas; others are fixed values from the system (same numbers as the app).');
        if ($notesCountry) {
            $notes->setCellValue('A3', "Courier Cost is always a fixed value. Notes below describe {$notesCountry} courier logic only (this export's destination).");
        } else {
            $notes->setCellValue('A3', 'Courier Cost is always a fixed value (bands + fuel + surcharges are calculated in the app, not as Excel formulas).');
        }
        $notes->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $notes->getStyle('A2:A3')->getFont()->setItalic(true);

        $notes->setCellValue('A5', 'Column');
        $notes->setCellValue('B5', 'Type');
        $notes->setCellValue('C5', 'Explanation / formula');
        $notes->getStyle('A5:C5')->getFont()->setBold(true);

        $r = 6;
        foreach ($headings as $index => $heading) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $isFormula = isset($formulaLetters[$letter]);
            $type = $isFormula ? 'Excel formula' : 'Fixed value (from system)';
            $explain = $explanations[$heading] ?? $this->defaultExplanationForHeading($heading, $isFormula);

            $notes->setCellValue("A{$r}", "{$letter} — {$heading}");
            $notes->setCellValue("B{$r}", $type);
            $notes->setCellValue("C{$r}", $explain);
            $r++;
        }

        $r += 1;
        $notes->setCellValue("A{$r}", 'Formula columns (live Excel)');
        $notes->getStyle("A{$r}")->getFont()->setBold(true);
        $r++;
        foreach ($formulaLogical as $key) {
            if (! isset($cols[$key])) {
                continue;
            }
            $notes->setCellValue("A{$r}", $cols[$key]);
            $notes->setCellValue("B{$r}", $key);
            $notes->setCellValue("C{$r}", $this->formulaDescription($key));
            $r++;
        }

        $this->appendCourierCostNotes($notes, $r + 1, $notesCountry);

        $notes->getColumnDimension('A')->setWidth(44);
        $notes->getColumnDimension('B')->setWidth(36);
        $notes->getColumnDimension('C')->setWidth(100);

        // Keep data sheet first
        $spreadsheet->setActiveSheetIndex(0);
    }

    /**
     * Courier-cost guide. When $country is set (e.g. UK export), only that country's logic is written.
     */
    protected function appendCourierCostNotes(Worksheet $notes, int $r, ?string $country = null): void
    {
        $write = function (string $a, string $b = '', string $c = '') use ($notes, &$r): void {
            $notes->setCellValue("A{$r}", $a);
            if ($b !== '') {
                $notes->setCellValue("B{$r}", $b);
            }
            if ($c !== '') {
                $notes->setCellValue("C{$r}", $c);
            }
            $r++;
        };
        $heading = function (string $title) use ($notes, &$r): void {
            $notes->setCellValue("A{$r}", $title);
            $notes->getStyle("A{$r}")->getFont()->setBold(true);
            $r++;
        };

        $profiles = $this->courierNotesCountryProfiles();
        $isLbs = in_array($country, ['US', 'CANADA'], true);
        $hasBlocks = in_array($country, ['US', 'CANADA', 'UK', 'EU', 'AUSTRALIA'], true);
        $usesUkEngine = in_array($country, ['UK', 'EU', 'AUSTRALIA'], true);
        $usesUsEngine = in_array($country, ['US', 'CANADA'], true);

        if ($country && isset($profiles[$country])) {
            $p = $profiles[$country];
            $heading("Courier Cost — {$country} calculation (app, not Excel)");
            $write('This export is for '.$country.'. Only '.$country.' courier logic is documented below.');
            $write('Courier Cost on the data sheet is a fixed exported value. Excel does not recompute bands, fuel, or surcharges.');
            $write('Delivered Cost and Final Delivered Cost are Excel formulas that use that fixed Courier Cost.');
            if ($country === 'INDIA') {
                $heading('India-only pricing chain');
                $write('India Shipping / Cost Price / Admin % / Admin Cost / Profit % / Final Cost', 'India-only stack', 'Main columns hold India values only. Common Shipping Cost India, Cost Price, Admin, Profit, and Final Cost are not used for India destination pricing.');
                $write('Skipped / zero', 'Conversion, FOB, tariff, destination shipping/storage, outbound, QA, destination markups, adjustment', 'No conversion rate is applied.');
                $write('Delivered Cost', 'Final Cost + Courier Cost', 'Courier Cost already includes rate bands, fuel, and applicable surcharge rules.');
            }

            $heading("How {$country} Courier Cost is built");
            $write('1. Resolve courier', 'No Courier (temp buyer courier blank/0)', 'Courier Cost = 0; no bands, fuel, or surcharges.');
            $write('', 'Otherwise', 'Use the '.$country.' default courier (or the courier selected on the pricing row).');
            $write('2. Volumetric weight', $p['vol_short'], $p['vol_detail']);
            $write('3. Chargeable weight', $isLbs ? 'lbs' : 'kg', $p['chargeable']);
            $write('4. Base courier', 'rate + highest-band-only overage + fuel%', 'Included weight = lowest band "Over weight" (from). If chargeable ≤ included → overage 0. Else overage = (chargeable − included) × rate of the highest band where chargeable > band.from. Bands are NOT stacked. Then fuel = (fuel_charge_percent × (rate + overage)) / 100. Base = rate + overage + fuel.');
            $write('5. Surcharges', $p['surcharge_title'], $p['surcharge_detail']);
            if (! empty($p['post_rules'])) {
                $write('6. '.$country.' post-rules', '', $p['post_rules']);
                $write('7. Delivered Cost', '', 'Final Price + Courier Cost (Excel formula on export; also saved in app).');
            } elseif ($country === 'INDIA') {
                $write('6. Delivered Cost', '', 'India Final Cost + Courier Cost (Excel formula on export; also saved in app).');
            } else {
                $write('6. Delivered Cost', '', 'Final Price + Courier Cost (Excel formula on export; also saved in app).');
            }

            if ($hasBlocks) {
                $heading('Surcharge rule blocks ('.$country.')');
                if ($usesUsEngine) {
                    $write('Engine', 'us_blocks_v1', 'Metrics in inches and lbs (box edges from cm÷2.54).');
                } elseif ($usesUkEngine) {
                    $write('Engine', 'uk_blocks_v1', 'Metrics in cm and kg.');
                }
                $write('How blocks apply', 'Block priority order', 'Each matched block adds its surcharge (blocks sum).');
                $write('', 'Conditions inside a block', 'OR = first matching condition; AND = all conditions must match (their surcharges sum).');
                $write('', 'Tiers inside a condition', 'Operators: >, >=, <, <=, =, between, always. Among matching tiers, the highest-threshold surcharge wins (not all tiers).');
                $write('Typical attributes', '1_sides / 2_sides / 3_sides, length, girth, length+girth, kg, lbs, box_weight, …', 'Exact attribute list is on the courier setup screen.');
            }

            if (! empty($p['include_legacy'])) {
                $heading('Legacy popup / custom_condition (when no blocks on this courier)');
                $write('Object JSON rules', 'sides / kg / lbs', 'If a rule matches, candidate = popup_price + base; take the maximum candidate.');
                $write('', 'box weight tiers', 'Highest matching popup can be added on top of base (add-on, not a full replace).');
                $write('Older numeric array rules', '[type, value, price]', 'First matching side rule and first matching kg/lbs rule (legacy sequential format).');
            }

            $heading('Export labelling note');
            if ($isLbs) {
                $write('Column "Volumetric Wt(kg)"', 'Labelled kg on the sheet', 'For '.$country.' the stored volumetric value used in courier calc is in lbs in the app.');
            }
            $write('Column "Courier Cost"', 'Fixed system value', 'Cannot be reverse-engineered from Excel by editing box size or bands — change pricing in the app to recalculate.');

            return;
        }

        // Mixed / unknown destination: full multi-country notes
        $heading('Courier Cost — detailed calculation (app, not Excel)');
        $write('Courier Cost on the data sheet is always a fixed exported value. Excel does not recompute bands, fuel, or surcharges.');
        $write('Delivered Cost and Final Delivered Cost are Excel formulas that use that fixed Courier Cost.');
        $write('This export may include multiple destinations — country differences are listed at the end.');

        $heading('Shared core (all destinations with a courier)');
        $write('1. Resolve courier', 'No Courier (temp buyer courier blank/0)', 'Courier Cost = 0; no bands, fuel, or surcharges.');
        $write('', 'Otherwise', 'Use the destination default courier (or the courier selected on the pricing row).');
        $write('2. Volumetric weight', 'From box H×W×D (cm)', 'Country-specific formula (see country table). Stored as ceil(raw).');
        $write('3. Chargeable weight', 'For base rate bands', 'max(ceil(volumetric), ceil(box weight)) in destination units.');
        $write('', 'US / Canada', 'Box kg is converted to lbs (× 2.20462) before the max().');
        $write('', 'Other countries', 'Both volumetric and box weight are in kg.');
        $write('4. Base courier', 'rate', 'Courier master fixed rate.');
        $write('', '+ weight-band overage (highest-band-only)', 'Included weight = lowest band "Over weight" (from). If chargeable ≤ included → overage 0. Else overage = (chargeable − included) × rate of the highest band where chargeable > band.from. Bands are NOT stacked.');
        $write('', '+ fuel %', 'fuel = (fuel_charge_percent × (rate + overage)) / 100. Base = rate + overage + fuel.');
        $write('5. Surcharges', 'Prefer rule blocks', 'If the courier has active blocks for that country: sum matched block surcharges onto base.');
        $write('', 'Else legacy conditions', 'If no block payload: use courier custom_condition / popup rules (see below).');
        $write('6. After surcharges', 'Delivered Cost', 'Final Price + Courier Cost (Excel formula on export; also saved in app).');

        $heading('Surcharge layer — rule blocks (US, Canada, UK, EU, Australia)');
        $write('Engines', 'US / Canada → us_blocks_v1', 'Metrics in inches and lbs (edges from cm÷2.54).');
        $write('', 'UK / EU / Australia → uk_blocks_v1', 'Metrics in cm and kg.');
        $write('How blocks apply', 'Block priority order', 'Each matched block adds its surcharge (blocks sum).');
        $write('', 'Conditions inside a block', 'OR = first matching condition; AND = all conditions must match (their surcharges sum).');
        $write('', 'Tiers inside a condition', 'Operators: >, >=, <, <=, =, between, always. Among matching tiers, the highest-threshold surcharge wins (not all tiers).');
        $write('Typical attributes', '1_sides / 2_sides / 3_sides, length, girth, length+girth, kg, lbs, box_weight, …', 'Exact attribute list is on the courier setup screen.');

        $heading('Surcharge layer — legacy popup / custom_condition (when no blocks)');
        $write('Object JSON rules', 'sides / kg / lbs', 'If a rule matches, candidate = popup_price + base; take the maximum candidate.');
        $write('', 'box weight tiers', 'Highest matching popup can be added on top of base (add-on, not a full replace).');
        $write('Older numeric array rules', '[type, value, price]', 'First matching side rule and first matching kg/lbs rule (legacy sequential format).');

        $heading('Country differences (only what differs from the shared core)');
        $notes->setCellValue("A{$r}", 'Country');
        $notes->setCellValue("B{$r}", 'Units / volumetric');
        $notes->setCellValue("C{$r}", 'Surcharges & extra rules');
        $notes->getStyle("A{$r}:C{$r}")->getFont()->setBold(true);
        $r++;

        foreach ($profiles as $name => $p) {
            $write($name, $p['vol_detail'], trim($p['surcharge_detail'].($p['post_rules'] ? ' '.$p['post_rules'] : '')));
        }

        $heading('Export labelling note');
        $write('Column "Volumetric Wt(kg)"', 'Always labelled kg on the sheet', 'For US/Canada the stored volumetric value used in courier calc is in lbs in the app.');
        $write('Column "Courier Cost"', 'Fixed system value', 'Cannot be reverse-engineered from Excel by editing box size or bands — change pricing in the app to recalculate.');
    }

    /**
     * @return array<string, array{vol_short: string, vol_detail: string, chargeable: string, surcharge_title: string, surcharge_detail: string, post_rules: string, include_legacy: bool}>
     */
    protected function courierNotesCountryProfiles(): array
    {
        return [
            'US' => [
                'vol_short' => 'lbs',
                'vol_detail' => 'Vol = ceil(in)×ceil(in)×ceil(in) ÷ us_volumetric_weight_lbs (default 166). cm edges → inches via ceil(cm/2.54). Stored as ceil(raw).',
                'chargeable' => 'max(ceil(vol_lbs), ceil(box_kg × 2.20462)).',
                'surcharge_title' => 'us_blocks_v1, else legacy',
                'surcharge_detail' => 'Prefer rule blocks (inches/lbs). If no blocks: legacy custom_condition / popup rules.',
                'post_rules' => '',
                'include_legacy' => true,
            ],
            'CANADA' => [
                'vol_short' => 'lbs',
                'vol_detail' => 'Same inch path as US, using canada_volumetric_weight_lbs (default 166). Stored as ceil(raw).',
                'chargeable' => 'max(ceil(vol_lbs), ceil(box_kg × 2.20462)).',
                'surcharge_title' => 'us_blocks_v1, else legacy',
                'surcharge_detail' => 'Prefer rule blocks (inches/lbs). If no blocks: legacy custom_condition / popup rules.',
                'post_rules' => '',
                'include_legacy' => true,
            ],
            'UK' => [
                'vol_short' => 'kg',
                'vol_detail' => 'Vol = (box cm³) ÷ uk_volumetric_weight_kg (default 5000), then ceil.',
                'chargeable' => 'max(ceil(vol_kg), ceil(box_kg)).',
                'surcharge_title' => 'uk_blocks_v1, else legacy',
                'surcharge_detail' => 'Prefer rule blocks (cm/kg). If no blocks: legacy custom_condition / popup rules.',
                'post_rules' => 'After surcharges: if cost > 100 and not already Palletways → switch to Palletways and recompute; if still > 100 → force Courier Cost to 115.',
                'include_legacy' => true,
            ],
            'EU' => [
                'vol_short' => 'kg',
                'vol_detail' => 'Vol = (box cm³) ÷ eu_volumetric_weight_kg (default 5000), then ceil.',
                'chargeable' => 'max(ceil(vol_kg), ceil(box_kg)).',
                'surcharge_title' => 'uk_blocks_v1, else legacy',
                'surcharge_detail' => 'Prefer rule blocks (cm/kg). If no blocks: legacy custom_condition / popup rules. No UK Palletways / 115 cap.',
                'post_rules' => '',
                'include_legacy' => true,
            ],
            'AUSTRALIA' => [
                'vol_short' => 'kg',
                'vol_detail' => 'Vol = (box cm³) ÷ australia_volumetric_weight_kg (default 5000), then ceil.',
                'chargeable' => 'max(ceil(vol_kg), ceil(box_kg)).',
                'surcharge_title' => 'uk_blocks_v1, else legacy',
                'surcharge_detail' => 'Prefer rule blocks (cm/kg). If no blocks: legacy custom_condition / popup rules. No UK cap.',
                'post_rules' => '',
                'include_legacy' => true,
            ],
            'CALIFORNIA' => [
                'vol_short' => 'kg',
                'vol_detail' => 'Vol = (box cm³) ÷ california_volumetric_weight_kg (default 5000), then ceil.',
                'chargeable' => 'max(ceil(vol_kg), ceil(box_kg)).',
                'surcharge_title' => 'No block engine',
                'surcharge_detail' => 'Base + fuel only, plus legacy conditions if present on the courier.',
                'post_rules' => '',
                'include_legacy' => true,
            ],
            'INDIA' => [
                'vol_short' => 'kg',
                'vol_detail' => 'Vol = (box cm³) ÷ india_volumetric_weight_kg (default 5000), then ceil.',
                'chargeable' => 'max(ceil(vol_kg), ceil(box_kg)).',
                'surcharge_title' => 'Courier surcharge rules',
                'surcharge_detail' => 'Base courier rate + highest-band-only overage + fuel, then legacy/custom surcharge rules if configured on the India courier.',
                'post_rules' => '',
                'include_legacy' => true,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function pricingColumnExplanations(): array
    {
        return [
            'id' => 'Pricing row id in the database.',
            'SKU' => 'Product / temp-product code.',
            'Product' => 'Product name and code.',
            'Buyer' => 'Primary buyer (buyer_id).',
            'Valid From' => 'Pricing validity start date.',
            'Valid Till' => 'Pricing validity end date.',
            'Remarks' => 'Free-text notes.',
            'Product Height' => 'Product height (cm) — reference dimension.',
            'Product Width' => 'Product width (cm) — reference dimension.',
            'Product Depth' => 'Product depth (cm) — reference dimension.',
            'Box Height' => 'Box height (cm) used for volumetric weight and courier surcharges.',
            'Box Width' => 'Box width (cm) used for volumetric weight and courier surcharges.',
            'Box Depth' => 'Box depth (cm) used for volumetric weight and courier surcharges.',
            'WholeSale volume' => 'Wholesale CBM / volume from product.',
            'DropShip Volume' => 'Dropship CBM / volume from product.',
            'Buying Cost' => 'Input cost (INR). Included in Cost Price.',
            'Fabric Type' => 'Fabric label / type (text).',
            'Tapestry Consumed(m)' => 'Input metres. Tapestry Cost = Consumed × Unit Cost (in app).',
            'Tapestry Unit Cost' => 'Input rate per metre. Tapestry Cost = Consumed × Unit Cost (in app).',
            'Tapestry Cost' => 'Fixed exported value. In app: Consumed × Unit Cost. Also drives solid vs upholstered tariff default.',
            'FillerCost' => 'Input cost. Included in Cost Price.',
            'Labour Cost' => 'Input cost. Included in Cost Price.',
            'Hardware 1' => 'Hardware item name.',
            'Hardware 1 Quantity' => 'Qty for hardware 1.',
            'Hardware Cost1' => 'Input cost. Included in Cost Price.',
            'Hardware 2' => 'Hardware item name.',
            'Hardware 2 Quantity' => 'Qty for hardware 2.',
            'Hardware Cost2' => 'Input cost. Included in Cost Price.',
            'Hardware 3' => 'Hardware item name.',
            'Hardware 3 Quantity' => 'Qty for hardware 3.',
            'Hardware Cost3' => 'Input cost. Included in Cost Price.',
            'Hardware 4' => 'Hardware item name.',
            'Hardware 4 Quantity' => 'Qty for hardware 4.',
            'Hardware Cost4' => 'Input cost. Included in Cost Price.',
            'Hardware 5' => 'Hardware item name.',
            'Hardware 5 Quantity' => 'Qty for hardware 5.',
            'Hardware Cost5' => 'Input cost. Included in Cost Price.',
            'Hardware Cost1' => 'Input cost. Included in Cost Price.',
            'Hardware Cost2' => 'Input cost. Included in Cost Price.',
            'Hardware Cost3' => 'Input cost. Included in Cost Price.',
            'Hardware Cost4' => 'Input cost. Included in Cost Price.',
            'Hardware Cost5' => 'Input cost. Included in Cost Price.',
            'Polish Unit Cost' => 'Input used in app to derive Polish Cost.',
            'Polish Cost' => 'Fixed exported value. Included in Cost Price.',
            'WholeSale Package Cost' => 'Package cost when wholesale packaging is selected. Included in Cost Price when applicable.',
            'DropShip Package Cost' => 'Package cost when dropship packaging is selected. Included in Cost Price when applicable.',
            'Shipping CostIndia' => 'India shipping input. Included in Cost Price. Not used for India destination pricing (India Shipping Cost is used instead).',
            'India Shipping Cost' => 'India-only shipping input used for India Cost Price. Common Shipping Cost India is not used on India destination rows.',
            'Shipping Cost India (common)' => 'Common Shipping Cost India input. Included in Cost Price for non-India; not used for India destination pricing.',
            'Shipping Cost' => 'Shipping input (India or destination sheet label). Included in Cost Price / fulfilment as applicable.',
            'Destination Shipping Cost' => 'Destination shipping amount. Always 0 for India destination pricing.',
            'Cost Price' => 'Fixed exported value. Sum of cost inputs in the app (buying, tapestry, filler, labour, hardware, polish, selected package, shipping). Not used for India destination pricing.',
            'India Cost Price' => 'India-only cost: common inputs without common shipping, plus India Shipping Cost. Used for India Admin / Final Cost.',
            'Admin Cost Percent' => 'Input %. For India rows this is India Admin Cost %. Non-India Admin Cost formula uses this column.',
            'Admin Cost Percent (India)' => 'India Admin Cost %. Used only for India destination pricing.',
            'Admin Cost(%)' => 'Input %. For India rows this is India Admin Cost %.',
            'Admin Cost(%) (India)' => 'India Admin Cost %. Used only for India destination pricing.',
            'Admin Cost' => 'Excel formula: India rows use India Admin Cost; otherwise ROUND(CostPrice × (1 + AdminCostPercent/100), 2).',
            'Admin Cost (India)' => 'India Admin Cost: India Cost Price × (1 + India Admin Cost % / 100).',
            'Profit(%)' => 'Input %. For India rows this is India Profit %. Non-India Final Cost formula uses this column.',
            'Profit(%) (India)' => 'India Profit %. Used only for India destination pricing.',
            'Final Cost' => 'Excel formula: India rows use India Final Cost; otherwise ROUND(AdminCost × (1 + Profit%/100), 2).',
            'Final Cost (India)' => 'India Final Cost: India Admin Cost × (1 + India Profit % / 100). Landed / Final Price / Delivered use this.',
            'Currency' => 'Destination currency symbol / label.',
            'Conversion Rate' => 'INR → destination currency rate. Used by FOB formula. Always 0 for India.',
            'FOB Indian Cost' => 'Excel formula: India → 0; else ROUND(ROUND(FinalCost / ConversionRate, 2), 0).',
            'Tariff' => 'Tariff % input. Used by Tariff-Adjusted FOB formula. Always 0 for India.',
            'Tariff-Adjusted FOB Cost' => 'Excel formula: India → 0; else ROUND(ROUND(FOB × (1 + Tariff%/100), 2), 0).',
            'Buyer2' => 'Destination fulfilment buyer name (buyer_id2).',
            'Destination' => 'Destination country (UK/US/Canada/Australia/EU…). Drives vol weight units and courier rules.',
            'Box Wt(kg)' => 'Box / gross weight (kg). Used with volumetric weight for chargeable courier weight.',
            'Volumetric Wt(kg)' => 'Volumetric weight (kg). US/Canada also use lbs in the app for courier.',
            'Shipping Cost UK' => 'Destination shipping / fulfilment shipping input (value). Used by Landed Cost formula.',
            'Storage(1.5 month)' => 'Storage cost input. Used by Landed Cost formula. Always 0 for India.',
            'Outbound Charges' => 'Outbound / adminCost2 input. Used by Landed Cost formula. Always 0 for India.',
            'Quality Assurance' => 'QA cost input. Used by Landed Cost formula. Always 0 for India.',
            'Landed Cost' => 'Excel formula: India → India Final Cost; else ROUND(Shipping + Storage + Outbound + QA + TariffFOB, 2).',
            'Admin Profit(%)' => 'Input %. Used by Admin Price formula. Always 0 for India.',
            'Admin Price' => 'Excel formula: India → India Final Cost; else ROUND(Landed × (1 + AdminProfit%/100), 2).',
            'Final Price Percent' => 'Input %. Used by Final Price formula. Always 0 for India.',
            'Final Price(%)' => 'Input %. Used by Final Price formula. Always 0 for India.',
            'Final Price' => 'Excel formula: India → India Final Cost; else ROUND(AdminPrice × (1 + FinalPricePercent/100), 2).',
            'Courier Type' => 'Selected courier name (fixed). Empty / none when temp buyer has No Courier.',
            'Courier Cost' => 'Fixed value from system — NOT an Excel formula. Shared core: base rate + highest-band-only overage + fuel% + block or legacy surcharges. Full country detail is in the Courier Cost section below.',
            'Delivered Cost' => 'Excel formula: ROUND(FinalPrice + CourierCost, 2).',
            'Adjustment' => 'Cost adjustment % input (from destination buyer / pricing). Always 0 for India.',
            'Final Delivered Cost' => 'Excel formula: India → ROUND(DeliveredCost, 0); else ROUND(DeliveredCost × (1 + Adjustment%/100), 0).',
        ];
    }

    protected function defaultExplanationForHeading(string $heading, bool $isFormula): string
    {
        if ($isFormula) {
            return 'Live Excel formula matching the pricing screen calculation.';
        }

        return 'Fixed value exported from the system (same as saved pricing).';
    }

    protected function formulaDescription(string $key): string
    {
        return match ($key) {
            'adminCost' => 'India: India Admin Cost. Otherwise ROUND(CostPrice × (1 + AdminCostPercent/100), 2)',
            'finalCost' => 'India: India Final Cost. Otherwise ROUND(AdminCost × (1 + Profit%/100), 2)',
            'fob' => 'India or ConversionRate=0 → 0; else ROUND(ROUND(FinalCost/ConversionRate, 2), 0)',
            'tariffFob' => 'India → 0; else ROUND(ROUND(FOB × (1 + Tariff%/100), 2), 0)',
            'landed' => 'India: India Final Cost. Otherwise ROUND(Shipping + Storage + Outbound + QA + TariffFOB, 2)',
            'adminPrice' => 'India: India Final Cost. Otherwise ROUND(Landed × (1 + AdminProfit%/100), 2)',
            'finalPrice' => 'India: India Final Cost. Otherwise ROUND(AdminPrice × (1 + FinalPricePercent/100), 2)',
            'deliveryCost' => 'ROUND(FinalPrice + CourierCost, 2)  — CourierCost is a fixed value',
            'newDelCost' => 'India: ROUND(DeliveredCost, 0). Otherwise ROUND(DeliveredCost × (1 + Adjustment%/100), 0)',
            default => 'See data sheet formula',
        };
    }
}
