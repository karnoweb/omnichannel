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
        $channelsTable = OmnichannelTables::name('channels');
        $externalTable = OmnichannelTables::name('external_categories');
        $mappingsTable = OmnichannelTables::name('category_mappings');
        $connectionsTable = OmnichannelTables::name('connections');

        Schema::create($externalTable, function (Blueprint $table) use ($channelsTable): void {
            $table->id();
            $table->foreignId('channel_id')
                ->constrained($channelsTable, 'id', 'fk_omc_ext_cat_channel')
                ->cascadeOnDelete();
            $table->string('scope_key')->default('global')->index();
            $table->string('external_id')->index();
            $table->string('external_parent_id')->nullable()->index();
            $table->string('name');
            $table->text('path')->nullable();
            $table->boolean('is_leaf')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'scope_key', 'external_id'], 'omc_ext_cat_unique');
        });

        Schema::create($mappingsTable, function (Blueprint $table) use ($connectionsTable, $externalTable): void {
            $table->id();
            OmnichannelSchema::branchKey($table)->index();
            $table->foreignId('channel_connection_id')
                ->constrained($connectionsTable, 'id', 'fk_omc_cat_map_conn')
                ->cascadeOnDelete();
            $table->string('internal_category_id')->index();
            $table->foreignId('external_category_id')
                ->constrained($externalTable, 'id', 'fk_omc_cat_map_ext')
                ->restrictOnDelete();
            $table->json('attribute_mappings')->nullable();
            $table->json('default_attribute_values')->nullable();
            $table->boolean('is_active')->default(true)->index();
            OmnichannelSchema::userKey($table, 'created_by')->nullable();
            OmnichannelSchema::userKey($table, 'updated_by')->nullable();
            $table->timestamps();

            $table->unique(
                ['branch_id', 'channel_connection_id', 'internal_category_id'],
                'omc_cat_map_branch_conn_internal_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(OmnichannelTables::name('category_mappings'));
        Schema::dropIfExists(OmnichannelTables::name('external_categories'));
    }
};
