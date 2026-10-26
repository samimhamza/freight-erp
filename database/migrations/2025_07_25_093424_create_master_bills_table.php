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
        Schema::create('master_bills', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->string('console')->unique();
            $table->string('ocean_bill_of_lading')->unique();
            $table->foreignId('origin_id')->nullable()->constrained('locations')->cascadeOnUpdate()->nullOnDelete();
            $table->date('etd');
            $table->foreignId('port_of_discharge_id')->nullable()->constrained('locations')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained('locations')->cascadeOnUpdate()->nullOnDelete();
            $table->date('eta');
            $table->date('ata')->nullable();
            $table->string('mode')->nullable();
            $table->foreignId('vessel_id')->nullable()->constrained('vessels')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->cascadeOnUpdate()->nullOnDelete();
            $table->string('customs_entry_number')->nullable();
            $table->string('master_freight')->nullable();
            $table->foreignId('export_agent_id')->nullable()->constrained('export_agents')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('import_agent_id')->nullable()->constrained('import_agents')->cascadeOnUpdate()->nullOnDelete();
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
        Schema::dropIfExists('master_bills');
    }
};
