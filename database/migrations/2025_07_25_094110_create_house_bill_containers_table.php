<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('house_bill_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')->constrained('containers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('house_bill_id')->constrained('house_bills')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('packages')->default(0);
            $table->string('seal')->unique();
            $table->decimal('net_weight')->nullable();
            $table->decimal('gross_weight');
            $table->text('descriptions_of_goods')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->unique(['container_id', 'house_bill_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('house_bill_containers');
    }
};
