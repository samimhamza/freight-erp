<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('status');
            $table->index('invoice_date');
        });

        Schema::table('master_bills', function (Blueprint $table) {
            $table->index('ata');
            $table->index('date');
        });

        Schema::table('remittance_advices', function (Blueprint $table) {
            $table->index('status');
            $table->index('advice_date');
        });
    }

    public function down(): void
    {
        Schema::table('remittance_advices', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['advice_date']);
        });

        Schema::table('master_bills', function (Blueprint $table) {
            $table->dropIndex(['ata']);
            $table->dropIndex(['date']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['invoice_date']);
        });
    }
};
