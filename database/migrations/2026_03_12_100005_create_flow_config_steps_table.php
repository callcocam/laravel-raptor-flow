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
        $configsTable = $this->prefix.'configs';
        $templatesTable = $this->prefix.'step_templates';

        Schema::connection(config('flow.connection'))->create($this->prefix.'config_steps', function (Blueprint $table) use ($configsTable, $templatesTable) {
            $table->ulid('id')->primary();
            $table->foreignUlid('flow_config_id')->constrained($configsTable)->cascadeOnDelete();
            $table->foreignUlid('flow_step_template_id')->constrained($templatesTable)->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->ulid('default_role_id')->nullable();
            $table->ulid('suggested_responsible_id')->nullable();
            $table->unsignedInteger('estimated_duration_days')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_skip')->default(false);
            $table->boolean('auto_assign_role')->default(false);
            $table->boolean('auto_assign_user')->default(false);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection(config('flow.connection'))->dropIfExists($this->prefix.'config_steps');
    }
};
