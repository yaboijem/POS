<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('checkout_hash', 64)->nullable();
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('category_name', 120)->nullable();
            $table->string('category_color', 7)->nullable();
        });
        // Legacy attribution can only use the category currently known at migration time.
        DB::statement('UPDATE sale_items si JOIN products p ON p.id = si.product_id JOIN categories c ON c.id = p.category_id SET si.category_name = c.name, si.category_color = c.color');
    }

    public function down(): void
    {
        Schema::table('sale_items', fn (Blueprint $table) => $table->dropColumn(['category_name', 'category_color']));
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('checkout_hash'));
    }
};
