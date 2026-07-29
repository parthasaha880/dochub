<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->foreignUuid('category_id')->nullable()->constrained('document_categories')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignUuid('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workflow_id', 'step_order']);
            $table->index(['workflow_id', 'step_order']);
        });

        Schema::create('workflow_step_approvers', function (Blueprint $table) {
            $table->foreignUuid('workflow_step_id')->constrained('workflow_steps')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['workflow_step_id', 'user_id']);
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('workflow_id')->constrained('workflows')->restrictOnDelete();
            $table->foreignUuid('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('status', 30)->default('in_progress')->index();
            $table->foreignUuid('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('submission_note')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['document_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index(['current_step_id', 'status']);
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignUuid('workflow_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('action', 30)->index();
            $table->foreignUuid('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('comments')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index(['workflow_instance_id', 'acted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_step_approvers');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');
    }
};
