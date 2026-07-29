<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('parent_id')->nullable()->index();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('document_categories')->nullOnDelete();
        });

        Schema::create('folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('parent_id')->nullable()->index();
            $table->uuid('department_id')->nullable()->index();
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_favorite')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'parent_id', 'name']);
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('folders')->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });

        Schema::create('document_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('folder_id')->nullable()->index();
            $table->uuid('department_id')->nullable()->index();
            $table->uuid('category_id')->nullable()->index();
            $table->uuid('subcategory_id')->nullable()->index();
            $table->uuid('owner_id')->nullable()->index();
            $table->uuid('uploader_id')->nullable()->index();

            $table->string('title');
            $table->string('reference_no')->nullable()->index();
            $table->string('archive_no')->nullable()->index();
            $table->string('barcode')->nullable()->unique();
            $table->string('qr_code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->text('keywords')->nullable();
            $table->string('confidentiality_level', 30)->default('internal')->index();
            $table->string('document_type', 50)->nullable()->index();
            $table->unsignedInteger('version')->default(1);
            $table->date('retention_until')->nullable();
            $table->date('archive_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('approval_status', 30)->default('draft')->index();
            $table->string('status', 30)->default('active')->index();
            $table->text('remarks')->nullable();

            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->string('extension', 20)->nullable()->index();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable();

            $table->boolean('is_locked')->default(false)->index();
            $table->uuid('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->uuid('checked_out_by')->nullable()->index();
            $table->timestamp('checked_out_at')->nullable();

            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('folder_id')->references('id')->on('folders')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('document_categories')->nullOnDelete();
            $table->foreign('subcategory_id')->references('id')->on('document_categories')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('uploader_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('locked_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('checked_out_by')->references('id')->on('users')->nullOnDelete();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('documents', function (Blueprint $table) {
                $table->fullText(['title', 'reference_no', 'archive_no', 'description', 'keywords'], 'documents_search_fulltext');
            });
        }

        Schema::create('document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id')->index();
            $table->unsignedInteger('version_number');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->text('change_summary')->nullable();
            $table->uuid('uploaded_by')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version_number']);
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('document_tag', function (Blueprint $table) {
            $table->uuid('document_id');
            $table->uuid('document_tag_id');
            $table->primary(['document_id', 'document_tag_id']);
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('document_tag_id')->references('id')->on('document_tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_tag');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_tags');
        Schema::dropIfExists('folders');
        Schema::dropIfExists('document_categories');
    }
};
