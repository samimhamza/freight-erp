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
        Schema::create('remittance_advices', function (Blueprint $table) {
            $table->id();
            $table->string('advice_number')->unique();
            $table->foreignId('carrier_id')->constrained('carriers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('master_bill_id')->constrained('master_bills')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('advice_date');
            $table->date('due_date')->nullable();
            $table->string('status');
            $table->timestamp('sent_at')->nullable();
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
        Schema::dropIfExists('remittance_advices');
    }
};
