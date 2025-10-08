<?php

use App\Models\Company;
use App\Models\User;
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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable();
            $table->foreignIdFor(Company::class)->nullable();
            $table->string('first_name', 30)->required();
            $table->string('last_name', 30)->required();
            $table->string('phone', 15)->required();
            $table->text('about')->nullable();
            $table->boolean('terms_of_service')->nullable();
            $table->dateTime('login_date_time')->nullable();
            $table->string('joining_ip')->nullable();
            $table->string('joining_ip_location')->nullable();
            $table->string('joining_city')->nullable();
            $table->text('why_chosen_us')->nullable()->comment('Why you chosen us');
            $table->string('profile_image')->nullable();
            $table->integer('country_id')->nullable();
            $table->integer('state_id')->nullable();
            $table->string('city')->nullable();
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('social_security_number')->unique()->nullable();
            $table->integer('profile_status')->nullable()->comment('0=profile do not complete yet. 1= profile completed done');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
