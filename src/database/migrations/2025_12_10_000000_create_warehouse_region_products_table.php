<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warehouse_region_products', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('warehouse_region_id');
            $table->foreign('warehouse_region_id')->references('id')->on('warehouse_regions');

            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products');

            $table->decimal('min_quantity')->nullable();
            $table->decimal('max_quantity')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_region_products');
    }
};
