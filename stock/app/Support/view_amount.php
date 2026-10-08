<?php

use App\Helpers\PrintAmountHelper;

if (! function_exists('view_amount')) {
    function view_amount($value = 0): string
    {
        return PrintAmountHelper::formatViewAmount($value);
    }
}
