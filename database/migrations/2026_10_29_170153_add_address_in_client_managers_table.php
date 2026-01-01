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
        Schema::table('client_managers', function (Blueprint $table) {
            $table->renameColumn('address', 'address_one');
            $table->string('address_two', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_managers', function (Blueprint $table) {
            $table->renameColumn('address_one', 'address');
            $table->dropColumn('address_two');
        });
    }
};
