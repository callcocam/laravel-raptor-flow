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
        Schema::connection(config('flow.connection'))->create($this->prefix.'step_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedInteger('suggested_order')->default(0);
            $table->unsignedInteger('estimated_duration_days')->nullable();
            $table->ulid('default_role_id')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_required_by_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection(config('flow.connection'))->dropIfExists($this->prefix.'step_templates');
    }
};
