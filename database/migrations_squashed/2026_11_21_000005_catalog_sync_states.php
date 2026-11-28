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
        $tableName = OmnichannelTables::name('catalog_sync_states');
        $publicationsTable = OmnichannelTables::name('publications');

        Schema::create($tableName, function (Blueprint $table) use ($publicationsTable): void {
            $table->id();
            OmnichannelSchema::branchKey($table)->index();
            $table->foreignId('publication_id')
                ->constrained($publicationsTable, 'id', 'fk_omc_catalog_sync_pub')
                ->cascadeOnDelete();
            $table->string('listing_identity')->index();
            $table->string('status')->default('idle')->index();
            $table->unsignedBigInteger('price_hash')->nullable();
            $table->unsignedBigInteger('stock_hash')->nullable();
            $table->timestamp('last_dispatched_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['publication_id'], 'omc_catalog_sync_pub_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(OmnichannelTables::name('catalog_sync_states'));
    }
};
