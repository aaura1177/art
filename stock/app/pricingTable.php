<?php

namespace App;

use App\Support\DestinationPricingPolicy;
use Illuminate\Database\Eloquent\Model;

class pricingTable extends Model
{
    protected $table = 'pricingTable';
    protected $fillable =
    [
        'product_id', 
        'buyer_id',
        'startDate',
        'endDate',
        'remarks',
        'productheight',
        'productwidth',
        'productdepth',
        'boxheight',
        'boxwidth',
        'boxdepth',
        'wholesalevolume',
        'dropshipvolume',
        'buyingCost',
        'fabricCost',
        'tapestryConsumed',
        'tapestryUnitCost',
        'tapestryCost',
        'fillerCost',
        'labourCost',
        'hardwareCost1',
        'hardwareCost2',
        'hardwareCost3',
        'hardwareCost4',
        'hardwareCost5',
        'pUnitCost',
        'polishCost',
        'wSPackageCost',
        'dSPackageCost',
        'shippingCost',
        'costPrice',
        'adminCostPercent',
        'adminCost',
        'profitPercent',
        'finalCost',
        'indiaShippingCost',
        'indiaCostPrice',
        'indiaAdminCostPercent',
        'indiaAdminCost',
        'indiaProfitPercent',
        'indiaFinalCost',
        'currency',
        'converRate',
        'fobINCost',
        'tariff_percent',
        'final_fob_in_cost',
        'boxWt',
        'volWt',
        'volWt_lbs',
        'fuelCharge',
        'shippingCost2',
        'StorageCost',
        'adminCost2',
        'qualityAssurance',
        'landedCost',
        'adminProfit',
        'adminPrice',
        'finalPricePer',
        'finalPrice',
        'courierType',
        'courierCost',
        'deliveryCost',
        'adjustment',
        'newDelCost',
        'productType',
        'deliveredCostStatus',
        'buyer_id2',
        'destination',
		'hardware1',
        'hardware2',
        'hardware3',
        'hardware4',
    	'hardware5',
		'hardware1_quantity',
        'hardware2_quantity',
        'hardware3_quantity',
        'hardware4_quantity',
    	'hardware5_quantity'
    ];

    protected static function booted(): void
    {
        static::saving(function (self $pricing): void {
            // The India block sits in the shared pricing form and is stored
            // alongside each product row without changing non-India formulas.
            foreach ([
                'indiaShippingCost', 'indiaCostPrice', 'indiaAdminCostPercent',
                'indiaAdminCost', 'indiaProfitPercent', 'indiaFinalCost',
            ] as $field) {
                if (request()->has($field)) {
                    $pricing->setAttribute($field, request()->input($field));
                }
            }

            if (! DestinationPricingPolicy::isIndia($pricing->destination)) {
                return;
            }

            $pricing->forceFill(
                DestinationPricingPolicy::applyIndia($pricing->getAttributes())
            );
        });
    }

    public function product()
    {
        return $this->belongsTo('App\product');
    }

    public function tempProduct()
    {
        return $this->belongsTo('App\tempProduct', 'product_id', 'id');
    }

    public function tempBuyer()
    {
        return $this->belongsTo('App\tempBuyer', 'buyer_id', 'id');
    }

    public function tempBuyer2()
    {
        return $this->belongsTo('App\tempBuyer', 'buyer_id2', 'id');
    }

    /**
     * Pricing row ids shown on the list (one per product_id — same row as Edit).
     */
    public static function listRowIds()
    {
        return static::orderBy('id')->get()->unique('product_id')->pluck('id')->values()->all();
    }

    /**
     * Map list-row ids to export ids, optionally filtered by destination buyer (buyer_id2).
     */
    public static function resolveExportIds(array $listRowIds, $buyerId2 = null): array
    {
        $listRowIds = array_values(array_filter(array_map('intval', $listRowIds)));

        if (empty($listRowIds)) {
            return [];
        }

        if ($buyerId2 === null || $buyerId2 === '' || $buyerId2 === 'all') {
            return $listRowIds;
        }

        $buyerId2 = (int) $buyerId2;
        $resolvedIds = [];

        static::whereIn('id', $listRowIds)
            ->get(['id', 'product_id', 'buyer_id', 'productType'])
            ->each(function ($row) use ($buyerId2, &$resolvedIds) {
                $matchId = static::where('product_id', $row->product_id)
                    ->where('productType', $row->productType)
                    ->where('buyer_id', $row->buyer_id)
                    ->where('buyer_id2', $buyerId2)
                    ->orderBy('id')
                    ->value('id');

                if ($matchId) {
                    $resolvedIds[] = (int) $matchId;
                }
            });

        return array_values(array_unique($resolvedIds));
    }
}
