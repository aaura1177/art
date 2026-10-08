<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class invoice extends Model
{
    protected $table = 'invoice';
    protected $fillable =
    [
        'consignee_id',	
    	'buyer_id',
    	'date',
    	'buyerorderno',
    	'containerno',
    	'vehicleno',
    	'totalbox',
    	'pkgs',
    	'currency',
        'fob',
        'payterms',
        'shipmentby',
        'desgoods',
    	'carriage',
    	'receipt',
    	'shipment',
    	'postloading',
    	'discharge',
    	'destination',
    	'totalgst',
    	'totalquantity',
    	'totalwt',
        'totalgrosswt',
    	'totalamount',
    	'conrate',
    	'rateamount',
        'status',
        'invoiceno',
        'invoicetype',
        'exportstatus',
        'ewaybillno',
        'declaration',
        'shipping_charges',
        'packing_charges',
        'discount',
        'additional_info',
        'bl_no',
        'bl_date',
        'irn',
        'ack_no',
        'ack_date',
        'einvoice_qr',
        'lr_rr_no',
        'distance',
        'transportertame',
        'transporterid',
        'docdate',
    ];

    public function buyer()
    {
        return $this->belongsTo('App\buyer');
    }
    
    public function consignee()
    {
        return $this->belongsTo('App\buyer','consignee_id');
    }

    public function invoiceTable()
    {
        return $this->hasMany('App\invoiceTable','invoice_id');
    }

    public function stockoutTable()
    {
        return $this->hasMany('App\stockoutTable');
    }

    public function packingSheet()
    {
        return $this->hasOne('App\packingSheet');
    }

    public function stockout()
    {
        return $this->hasMany('App\stockout');
    }

    public function invexport()
    {
        return $this->hasMany('App\invexport');
    }
    public function logisticPartner()
    {
        // Relate the discharge field of the invoices with the logistic partner name
        return $this->belongsTo(LogisticPartener::class, 'discharge', 'name');
    }

    public function portInfo()
    {
        // Relate the discharge field of the invoices with the Port name
        return $this->belongsTo(Port::class, 'discharge', 'name');
    }

    public function emissionLogs()
    {
        return $this->hasMany(EmissionInvoiceLogIn::class);
    }

    public function canBeCanceled(): bool
    {
        if ((int) $this->is_canceled === 1) {
            return false;
        }

        $stockoutCount = $this->relationLoaded('stockout')
            ? $this->stockout->count()
            : $this->stockout()->count();

        if ($stockoutCount > 0) {
            return false;
        }

        return $this->allQuantityRemaining();
    }

    public function allQuantityRemaining(): bool
    {
        $lines = $this->relationLoaded('invoiceTable')
            ? $this->invoiceTable
            : $this->invoiceTable()->get();

        foreach ($lines as $line) {
            if ((float) $line->remqty !== (float) $line->quantity) {
                return false;
            }
        }

        return true;
    }

    public function suggestedCloneInvoiceNumber(): ?string
    {
        return static::nextAvailableInvoiceNumber((string) $this->invoiceno);
    }

    public static function nextAvailableInvoiceNumber(?string $invoiceno): ?string
    {
        $invoiceno = strtoupper(trim((string) $invoiceno));
        if ($invoiceno === '' || !preg_match('/^(.*?)(\d+)$/', $invoiceno, $m)) {
            return null;
        }

        $prefix = $m[1];
        $pad = strlen($m[2]);
        $cacheKey = strtoupper($prefix) . '#' . $pad;
        static $seriesMax = [];

        if (!isset($seriesMax[$cacheKey])) {
            $max = (int) $m[2];
            $pattern = '/^' . preg_quote($prefix, '/') . '(\d+)$/i';
            $candidates = static::query()
                ->where('invoiceno', 'like', $prefix . '%')
                ->pluck('invoiceno');

            foreach ($candidates as $existing) {
                if (preg_match($pattern, strtoupper((string) $existing), $em)) {
                    $max = max($max, (int) $em[1]);
                }
            }
            $seriesMax[$cacheKey] = $max;
        }

        return $prefix . str_pad((string) ($seriesMax[$cacheKey] + 1), $pad, '0', STR_PAD_LEFT);
    }

    public static function invoiceNumberExists(string $invoiceno, ?int $ignoreId = null): bool
    {
        $query = static::query()->whereRaw('UPPER(invoiceno) = ?', [strtoupper(trim($invoiceno))]);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}