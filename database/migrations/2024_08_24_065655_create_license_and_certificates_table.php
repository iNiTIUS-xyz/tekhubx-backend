<?php

use App\Models\EmployeeProvider;
use App\Utils\GlobalConstant;
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
        Schema::create('license_and_certificates', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_id')->nullable();
            $table->integer('client_id')->nullable();
            $table->foreignIdFor(EmployeeProvider::class)->nullable();
            $table->integer('license_id')->nullable();
            $table->integer('certificate_id')->nullable();
            $table->string('state_name')->nullable();
            $table->integer('license_number')->nullable();
            $table->integer('applicable_work_category_id')->nullable();
            $table->text('certificate_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expired_date')->nullable();
            $table->string('file')->nullable();
            $table->string('status')->default('Under Review')->comment('Approved, Rejected, Under Review');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_and_certificates');
    }
};
