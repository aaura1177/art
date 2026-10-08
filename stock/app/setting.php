<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class setting extends Model
{
    protected $table = 'settings';
    protected $fillable =
    [
        'c_name',
        'pan',
        'gstin',
        'address1',
        'address2',
        'city',
        'state',
        'country',
        'postcode',
        'iec',
        'rbi',
        'gsp',
        'lut',
        'website',
        'email',
        'phone1',
        'phone2',
        'logourl',
                'sign_url',
        'volWt',
        'shippingCost2',
        'StorageCost',
                'corner_rate',
                'packaging_per_sqinch_rate',
                'factory_address',
                'invoice_declaration_export',
                'invoice_declaration_local',
                'volumetric_weight_lbs',
                'electricity_factor',
                'distance_factor',
                'distribution_factor',
                'petrol_price',
                'diesel_price',
                                'contractor_finishing_new_rate_from',
                'canada_product_1',
                'canada_product_2',
                'california_product_1',
                'california_product_2',
                'canada_product_1_stock',
                'canada_product_2_stock',
                'california_product_1_stock',
                'california_product_2_stock',
                'california_remaining_stock',
                'canada_remaining_stock',
'australia_product_1',
                'australia_product_2',
                'australia_product_1_stock',
                'australia_product_2_stock',
                'australia_remaining_stock',
                'backmonth_edit_day_limit',
                'backmonth_factory_can_cumulative',
                'backmonth_factory_can_individual',
                'uk_volumetric_weight_kg',
                'us_volumetric_weight_lbs',
                'eu_volumetric_weight_kg',
                'canada_volumetric_weight_lbs',
                'california_volumetric_weight_kg',
                'australia_volumetric_weight_kg',
                'india_volumetric_weight_kg',

    ];
}