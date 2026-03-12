<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $prefix;

    public function __construct()
    {
        $this->prefix = config('flow.table_prefix', 'flow_');
    }

    public function up(): void
    {
        $configStepsTable = $this->prefix.'config_steps';

        Schema::connection(config('flow.connection'))->create($this->prefix.'notifications', function (Blueprint $table) use ($configStepsTable) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('notifiable_type');
            $table->ulid('notifiable_id');
            $table->ulid('flow_config_step_id')->nullable();
            $table->string('type');
            $table->string('priority')->default('medium');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::connection(config('flow.connection'))->dropIfExists($this->prefix.'notifications');
    }
};
