<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Service extends Model implements FromCollection,WithHeadings
{
    protected $table = 'services';
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
        'supplier_status',
        'remqty',
        'address_option',
        'created_via',
        'po_revise_date',
        'pdf_path'
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
        return $this->hasMany('App\serviceTable','poid','id');
    }

    public function servicepurchaseBill()
    {
        return $this->hasMany('App\servicePurchaseBill', 'purchaseOrder_id', 'id');
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