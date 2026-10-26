<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('remittance_advice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remittance_advice_id')->constrained('remittance_advices')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('charge_id')->constrained('charges')->cascadeOnUpdate()->restrictOnDelete();
            $table->smallInteger('quantity');
            $table->decimal('amount', 10, 2);
            $table->decimal('vat', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance_advice_items');
    }
};
