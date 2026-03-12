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
        Schema::connection(config('flow.connection'))->create($this->prefix.'participants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->string('participable_type');
            $table->ulid('participable_id');
            $table->string('role_in_step')->default('assignee');
            $table->boolean('is_pre_assigned')->default(false);
            $table->timestamp('assigned_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'participable_type', 'participable_id'], 'flow_participants_unique');
            $table->index(['participable_type', 'participable_id']);
        });
    }

    public function down(): void
    {
        Schema::connection(config('flow.connection'))->dropIfExists($this->prefix.'participants');
    }
};
