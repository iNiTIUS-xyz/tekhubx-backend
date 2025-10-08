<?php

use App\Models\User;
use App\Utils\GlobalConstant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable();
            $table->string('company_name', 50)->nullable();
            $table->text('company_bio')->nullable();
            $table->text('about_us')->nullable();
            $table->json('types_of_work')->nullable();
            $table->json('skill_sets')->nullable();
            $table->json('equipments')->nullable();
            $table->json('licenses')->nullable();
            $table->json('certifications')->nullable();
            $table->foreignIdFor(User::class, 'employed_providers')->nullable();
            $table->enum('status', GlobalConstant::SWITCH)->default(GlobalConstant::SWITCH[0]);
            $table->string('logo')->nullable();
            $table->string('address', 60)->nullable();
            $table->string('company_website')->nullable();
            $table->enum('annual_revenue', GlobalConstant::REVENUE)->nullable();
            $table->enum('need_technicians', GlobalConstant::NEED)->nullable();
            $table->enum('employee_counter', GlobalConstant::EMP_COUNTER)->nullable();
            $table->enum('technicians_hire', GlobalConstant::HIRE)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
