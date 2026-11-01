<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_label', 50)->nullable()->after('payment_method');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_label', 50)->nullable()->after('method');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_label');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('payment_label');
        });
    }
};
