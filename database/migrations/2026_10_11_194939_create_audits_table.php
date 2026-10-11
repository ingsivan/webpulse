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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('load_time_ms')->nullable();
            $table->unsignedInteger('pages_crawled')->default(0);
            $table->unsignedTinyInteger('performance_score')->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedTinyInteger('links_score')->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
