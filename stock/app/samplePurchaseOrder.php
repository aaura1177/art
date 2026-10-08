<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class samplePurchaseOrder extends Model implements FromCollection,WithHeadings
{
    protected $table = 'sample_purchase_order';
    protected $fillable =
    [
        'pono',
        'supplier_id',
        'podate',
        'del_date',
        'ref_supplier',
        'buyer_orderno',
        'payterms',
        'remarks',
        'subTotal',
        'tgst',
        'tquantity',
        'tamount',
        'status',
        'send_to_supplier_status',
        'remqty'
    ];

    public function supplier()
    {
        return $this->belongsTo('App\supplier');
    }

    public function product()
    {
        return $this->hasMany('App\product');
    }

    public function poTable()
    {
        return $this->hasMany('App\poTable','poid','id');
    }

    public function sample()
    {
        return $this->hasMany('App\sample');
    }

    public function posTable()
    {
        return $this->hasMany('App\posTable','poid','id');
    }

    public function purchaseBill()
    {
        return $this->hasMany('App\purchaseBill', 'purchaseOrder_id', 'id');
    }

    public function collection() {
        return purchaseOrder::all();
    }

    public function headings(): array {
        return [
           "Id","PO NO","Supplier Id","PO Date","Delivery Date","Supplier Ref. No","Buyer Order No","PayTerms","Remarks","TotalGST","Total Quantity","SubTotal","Total Amount","RemQTY","Status","Created_at","Updated_at"
        ];
    }  
}