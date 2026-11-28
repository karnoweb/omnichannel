<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Support\OmnichannelSchema;
use Karnoweb\Omnichannel\Support\OmnichannelTables;

return new class extends Migration
{
    public function up(): void
    {
        $assetsTable = OmnichannelTables::name('media_assets');
        $pivotTable = OmnichannelTables::name('publication_media');
        $publicationsTable = OmnichannelTables::name('publications');

        Schema::create($assetsTable, function (Blueprint $table): void {
            $table->id();
            OmnichannelSchema::branchKey($table)->index();
            OmnichannelSchema::userKey($table, 'created_by')->nullable()->index();
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->string('original_name')->nullable();
            $table->string('media_type')->index();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('checksum', 64)->nullable()->index();
            $table->string('status')->default('temporary')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create($pivotTable, function (Blueprint $table) use ($assetsTable, $publicationsTable): void {
            $table->id();
            $table->foreignId('publication_id')
                ->constrained($publicationsTable, 'id', 'fk_omc_pub_media_pub')
                ->cascadeOnDelete();
            $table->foreignId('media_asset_id')
                ->constrained($assetsTable, 'id', 'fk_omc_pub_media_asset')
                ->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('role')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['publication_id', 'media_asset_id'], 'omc_pub_media_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(OmnichannelTables::name('publication_media'));
        Schema::dropIfExists(OmnichannelTables::name('media_assets'));
    }
};
