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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('full_name', 255)->nullable();
            $table->string('phone_number', 30)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('identification_no', 50)->nullable()->unique();
            $table->string('avatar_url', 2048)->nullable();
            $table->text('bio')->nullable();
            $table->enum('shopper_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->index();
            $table->timestamp('shopper_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
