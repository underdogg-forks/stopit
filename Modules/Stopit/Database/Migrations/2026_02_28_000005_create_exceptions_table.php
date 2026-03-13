<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('exceptions', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('exception_class', 500);
            $table->text('message');
            $table->string('file', 1000)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_url', 2048)->nullable();
            $table->text('headers')->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_id')->nullable();
            $table->text('context')->nullable();
            $table->string('severity', 20)->default('error');
            $table->unsignedBigInteger('occurrence_count')->default(1);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('first_occurred_at')->useCurrent();
            $table->timestamp('last_occurred_at')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'exception_class', 'message'], 'uniq_app_class_message');
            $table->index(['application_id', 'exception_class'], 'idx_app_class');
            $table->index(['application_id', 'is_resolved', 'last_occurred_at'], 'idx_app_resolved_last');
            $table->index(['application_id', 'severity'], 'idx_app_severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }
};
