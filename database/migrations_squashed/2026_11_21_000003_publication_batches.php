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
        $batchesTable = OmnichannelTables::name('publication_batches');
        $itemsTable = OmnichannelTables::name('publication_batch_items');
        $publicationsTable = OmnichannelTables::name('publications');
        $connectionsTable = OmnichannelTables::name('connections');

        Schema::create($batchesTable, function (Blueprint $table): void {
            $table->id();
            OmnichannelSchema::branchKey($table)->index();
            OmnichannelSchema::userKey($table, 'created_by')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('source_type')->index();
            $table->string('source_reference_type')->nullable();
            $table->string('source_reference_id')->nullable();
            $table->string('product_id')->nullable()->index();
            $table->json('base_content');
            $table->string('intent_id', 64)->nullable()->index();
            $table->string('request_fingerprint', 64)->nullable();
            $table->string('dispatch_mode')->default('draft')->index();
            $table->string('status')->default('draft')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'intent_id'], 'omc_pub_batches_branch_intent_unique');
        });

        Schema::create($itemsTable, function (Blueprint $table) use ($batchesTable, $publicationsTable, $connectionsTable): void {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained($batchesTable, 'id', 'fk_omc_batch_item_batch')
                ->cascadeOnDelete();
            $table->foreignId('publication_id')
                ->constrained($publicationsTable, 'id', 'fk_omc_batch_item_pub')
                ->restrictOnDelete();
            $table->foreignId('channel_connection_id')
                ->constrained($connectionsTable, 'id', 'fk_omc_batch_item_conn')
                ->restrictOnDelete();
            $table->string('publication_type')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['batch_id', 'channel_connection_id'], 'omc_batch_item_batch_conn_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(OmnichannelTables::name('publication_batch_items'));
        Schema::dropIfExists(OmnichannelTables::name('publication_batches'));
    }
};
