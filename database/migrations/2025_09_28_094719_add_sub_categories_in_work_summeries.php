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
        Schema::table('work_summeries', function (Blueprint $table) {
            $table->unsignedBigInteger('work_sub_category_id')->nullable();
            $table->string('work_sub_category_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_summeries', function (Blueprint $table) {
            $table->dropColumn('work_sub_category_id');
            $table->dropColumn('work_sub_category_name');
        });
    }
};
