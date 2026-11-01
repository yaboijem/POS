<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->char('reference', 26)->unique();
            $table->string('transaction_number', 40)->unique();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('completed')->index();
            $table->decimal('subtotal', 12, 2);
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('amount_tendered', 12, 2);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('payment_method', 30)->index();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->char('idempotency_key', 36)->unique();
            $table->timestamp('sold_at')->index();
            $table->timestamps();

            $table->index(['cashier_id', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
