<?php

use App\Utils\GlobalConstant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('template_name')->nullable();
            $table->unsignedBigInteger('default_client_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('work_order_title')->nullable();
            $table->boolean('export_button')->default(false)->nullable();
            $table->boolean('counter_offer')->default(false)->nullable();
            $table->boolean('gps_on')->default(false)->nullable();
            $table->text('public_description')->nullable();
            $table->text('private_description')->nullable();
            $table->unsignedBigInteger('work_category_id')->nullable();
            $table->unsignedBigInteger('additional_work_category_id')->nullable();
            $table->unsignedBigInteger('service_type_id')->nullable();
            $table->json('qualification_type')->nullable();
            $table->unsignedBigInteger('work_order_manager_id')->nullable();
            $table->string('additional_contact_id')->nullable();
            $table->json('task')->nullable();
            $table->text('buyer_custom_field')->nullable();
            $table->enum('pay_type', GlobalConstant::PAY_TYPE)->nullable();
            $table->decimal('hourly_rate')->nullable();
            $table->unsignedBigInteger('max_hours')->nullable();
            $table->unsignedBigInteger('approximate_hour_complete')->nullable();
            $table->decimal('total_pay')->nullable();
            $table->decimal('per_device_rate')->nullable();
            $table->unsignedBigInteger('max_device')->nullable();
            $table->decimal('fixed_payment')->nullable();
            $table->unsignedBigInteger('fixed_hours')->nullable();
            $table->decimal('additional_hourly_rate')->nullable();
            $table->unsignedBigInteger('max_additional_hour')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
