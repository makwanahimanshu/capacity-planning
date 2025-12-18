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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('project_manager_id')->nullable()->default(null);
            $table->foreign('project_manager_id')->references('id')->on('resources')->onDelete('cascade');
            $table->json('resource_ids')->nullable()->default(null);
            $table->integer('total_hours')->default(0)->comment('Total planned hours for the project');
            $table->enum('status', ['planned','active','completed','on_hold'])->default('planned');
            $table->tinyInteger('priority')->default(null)->comment('1 = Low, 2 = Medium, 3 = High');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_billable')->default(true);
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
