<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class purchaseOrderConsumable extends Model implements FromCollection,WithHeadings
{
    protected $table = 'purchase_order_consumables';
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
        'remqty',
        'created_via',
        'month',
        'type',
        'po_revise_date',
        'address_option',
        'monthend_buyer_ids',
        'monthend_buyer_key'
    ];

    public function supplier()
    {
        return $this->belongsTo('App\supplier');
    }

    public function product()
    {
        return $this->hasMany('App\product');
    }
       public function consumable()
    {
        return $this->hasMany('App\consumable');
    }

    public function poTable()
    {
        return $this->hasMany('App\pocTable','poid','id');
    }
      public function popTable()
    {
        return $this->hasMany('App\popTable','poid','id');
    }

    public function purchaseBill()
    {
        return $this->hasMany('App\purchaseBill', 'purchaseOrder_id', 'id');
    }

    /**
     * Consumable supplier bills (purchase_bill_consumables), not furniture {@see purchaseBill}.
     */
    public function purchaseBillConsumables()
    {
        return $this->hasMany(purchaseBillConsumable::class, 'purchaseOrder_id', 'id');
    }
  

    public function collection() {
        return purchaseOrder::all();
    }
      public function purchaseBillCarton()
    {
        return $this->hasMany('App\PurchaseBillCarton', 'purchaseOrder_id', 'id');
    }

    public function headings(): array {
        return [
           "Id","PO NO","Supplier Id","PO Date","Delivery Date","Supplier Ref. No","Buyer Order No","PayTerms","Remarks","TotalGST","Total Quantity","SubTotal","Total Amount","RemQTY","Status","Created_at","Updated_at"
        ];
    }  
}