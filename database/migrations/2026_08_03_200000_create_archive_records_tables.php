<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('archive_locations')->nullOnDelete();
            $table->string('type', 20); // room|rack|shelf|box|file
            $table->string('code', 50);
            $table->string('name');
            $table->string('barcode', 64)->nullable()->unique();
            $table->string('qr_code', 64)->nullable()->unique();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'type', 'parent_id']);
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('series', 40); // ARC, REF, BOX, LOC
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'series', 'year']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('media_type', 20)->default('digital')->after('status'); // digital|physical|hybrid
            $table->foreignUuid('location_id')->nullable()->after('folder_id')->constrained('archive_locations')->nullOnDelete();
            $table->string('physical_reference', 120)->nullable()->after('archive_no');
            $table->index(['organization_id', 'media_type']);
            $table->index(['location_id']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn(['media_type', 'physical_reference']);
        });

        Schema::dropIfExists('document_number_sequences');
        Schema::dropIfExists('archive_locations');
    }
};
