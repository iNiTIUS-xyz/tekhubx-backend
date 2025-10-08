<?php

use App\Models\User;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('title')->required();
            $table->string('default_client_id')->nullable();
            $table->unsignedBigInteger('project_manager_id')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('provider_penalty')->nullable();
            $table->unsignedBigInteger('secondary_account_owner_id')->nullable();
            $table->enum('auto_dispatch', GlobalConstant::YN)->default(GlobalConstant::YN[1])->nullable();
            $table->enum('notification_enabled', GlobalConstant::YN)->default(GlobalConstant::YN[1])->nullable();
            $table->string('other')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
