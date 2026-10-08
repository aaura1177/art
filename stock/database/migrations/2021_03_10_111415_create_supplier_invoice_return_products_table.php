<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSupplierInvoiceReturnProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_invoice_return_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('supplier_invoice_id')->unsigned();
            $table->integer('supplier_invoice_return_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->float('qty')->nullable();
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
        Schema::dropIfExists('supplier_invoice_return_products');
    }
}
