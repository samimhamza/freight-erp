<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('house_bills', function (Blueprint $table) {
            $table->foreignId('notify_party_id')->nullable()->change();
            $table->foreignId('consignor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('house_bills', function (Blueprint $table) {
            $table->foreignId('notify_party_id')->nullable(false)->change();
            $table->foreignId('consignor_id')->nullable(false)->change();
        });
    }
};
