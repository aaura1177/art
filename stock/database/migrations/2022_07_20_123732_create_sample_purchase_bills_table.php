<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSamplePurchaseBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sample_purchase_bills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('purchaseOrder_id')->default(0);
            $table->date('supp_inv_date')->nullable();
            $table->string('ewaybill')->nullable();
            $table->integer('quantity')->nullable();
            $table->float('subtotal')->nullable();
            $table->float('gst')->nullable();
            $table->float('freight')->nullable();
            $table->float('total')->nullable();
            $table->integer('swap_id')->nullable();
            $table->integer('is_checked')->nullable();
            $table->integer('is_downloaded')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('payment_status')->nullable();
            $table->integer('invoice_status')->nullable();
            $table->integer('supplier_invoice_id')->nullable();
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
        Schema::dropIfExists('sample_purchase_bills');
    }
}
