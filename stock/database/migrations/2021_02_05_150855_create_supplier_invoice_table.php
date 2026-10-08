<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSupplierInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('purchase_order_id');
			$table->string('supplier_invoice_number')->nullable();
			$table->string('internal_invoice_number');
			$table->string('eway_bill_no')->nullable();
			$table->string('eway_bill_pdf')->nullable();
			$table->string('vehicle_no')->nullable();
			$table->integer('tquantity');
			$table->float('tgst');
			$table->float('subTotal');
			$table->float('tamount');
			$table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_invoice');
    }
}
