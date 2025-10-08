<?php

use App\Models\Role;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->enum('organization_role', GlobalConstant::ORGANIZATION)->nullable();
            $table->string('username', 30)->nullable()->unique();
            $table->string('email', 30)->nullable()->unique();
            $table->string('password', 100)->nullable();
            $table->string('role', 20)->nullable();
            $table->enum('status', GlobalConstant::SWITCH)->default(GlobalConstant::SWITCH[0]);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('stripe_email', 30)->nullable()->unique();
            $table->string('stripe_account_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();
            $table->string('setup_intent_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
