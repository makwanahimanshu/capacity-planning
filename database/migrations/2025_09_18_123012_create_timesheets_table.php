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
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resource_id')->nullable()->default(null);
            $table->foreign('resource_id')->references('id')->on('resources')->onDelete('cascade');
            $table->unsignedBigInteger('project_id')->nullable()->default(null);
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->date('date');
            $table->decimal('actual_hours', 5, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['resource_id', 'project_id', 'date'], 'unique_timesheet_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
