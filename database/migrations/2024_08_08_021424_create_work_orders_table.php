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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('work_order_unique_id');
            $table->integer('template_id')->nullable();
            $table->string('work_order_title')->nullable();
            $table->integer('default_client_id')->nullable();
            $table->integer('project_id')->nullable();
            $table->boolean('export_bool')->nullable();
            $table->boolean('counter_offer_bool')->nullable();
            $table->boolean('gps_bool')->nullable();
            $table->longText('service_description_public')->nullable();
            $table->longText('service_description_note_private')->nullable();
            $table->integer('work_category_id')->nullable();
            $table->integer('additional_work_category_id')->nullable();
            $table->integer('service_type_id')->nullable();
            $table->json('qualification_type')->nullable();
            $table->string('location_id')->nullable();
            $table->enum('schedule_type', GlobalConstant::ORDER_SCHEDULE_TYPE)->nullable();
            $table->date('schedule_date')->nullable();
            $table->time('schedule_time')->nullable();
            $table->string('time_zone')->nullable();
            $table->date('schedule_date_between_1')->nullable();
            $table->date('schedule_date_between_2')->nullable();
            $table->time('schedule_time_between_1')->nullable();
            $table->time('schedule_time_between_2')->nullable();
            $table->date('between_date')->nullable();
            $table->time('between_time')->nullable();
            $table->date('through_date')->nullable();
            $table->time('through_time')->nullable();
            $table->integer('work_order_manager_id')->nullable();
            $table->json('additional_contact_id')->nullable();
            $table->json('documents_file')->nullable();
            $table->json('tasks')->nullable();
            $table->text('buyer_custom_field')->nullable();
            $table->enum('pay_type', GlobalConstant::PAY_TYPE);
            $table->decimal('hourly_rate')->nullable();
            $table->integer('max_hours')->nullable();
            $table->integer('approximate_hour_complete')->nullable();
            $table->decimal('total_pay')->nullable();
            $table->decimal('per_device_rate')->nullable();
            $table->integer('max_device')->nullable();
            $table->decimal('fixed_payment')->nullable();
            $table->integer('fixed_hours')->nullable();
            $table->decimal('additional_hourly_rate')->nullable();
            $table->integer('max_additional_hour')->nullable();
            $table->decimal('labor')->nullable();
            $table->decimal('state_tax')->nullable();
            $table->string('bank_account_id')->nullable();
            $table->integer('rule_id')->nullable();
            $table->json('shipment_id')->nullable();
            $table->enum('status', GlobalConstant::ORDER_STATUS)->default(GlobalConstant::ORDER_STATUS[0]);
            $table->string('assigned_status')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('assigned_id')->nullable();
            $table->string('assigned_uuid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
