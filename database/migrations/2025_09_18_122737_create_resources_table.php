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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->unsignedBigInteger('dept_id')->nullable()->default(null);
            $table->foreign('dept_id')->references('id')->on('departments')->onDelete('cascade');
            $table->tinyInteger('is_project_manager')->default(0)->comment('0 = No, 1 = Yes');
            $table->decimal('total_hours', 8, 2)->default(0.00);
            $table->decimal('leave_hours', 8, 2)->default(0.00);
            $table->string('role', 100)->nullable();
            $table->decimal('daily_capacity', 5, 2)->default(8.00); // 8 hrs/day default
            $table->tinyInteger('status')->default(null)->comment('0 = inactive, 1 = active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
