<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Support\OmnichannelSchema;
use Karnoweb\Omnichannel\Support\OmnichannelTables;

/**
 * Squashed schema for `karnoweb/omnichannel`.
 *
 * Boundary rules:
 * - Host tables (`users`, `branches`, `products`, ...) are NEVER hard-FK'd here;
 *   every cross-boundary reference (`branch_id`, `created_by`, `product_id`) is a
 *   soft key resolved via OmnichannelSchema / string column.
 * - Foreign keys exist strictly between tables owned by this package, resolved
 *   through OmnichannelTables::name().
 */
return new class extends Migration
{
    public function up(): void
    {
        $channelsTable = OmnichannelTables::name('channels');
        $connectionsTable = OmnichannelTables::name('connections');
        $publicationsTable = OmnichannelTables::name('publications');
        $logsTable = OmnichannelTables::name('publication_logs');

        Schema::create($channelsTable, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver')->index();
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create($connectionsTable, function (Blueprint $table) use ($channelsTable): void {
            $table->id();
            OmnichannelSchema::branchKey($table)->index();
            OmnichannelSchema::userKey($table, 'created_by')->index();
            $table->foreignId('channel_id')
                ->constrained($channelsTable, 'id', 'fk_omc_conn_channel')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('external_account_id')->nullable();
            $table->string('external_account_name')->nullable();
            $table->text('credentials')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('status_message')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'channel_id', 'external_account_id'], 'omc_conn_branch_channel_ext_unique');
            $table->index(['branch_id', 'channel_id', 'status'], 'omc_conn_branch_channel_status_idx');
        });

        Schema::create($publicationsTable, function (Blueprint $table) use ($channelsTable, $connectionsTable): void {
            $table->id();
            OmnichannelSchema::branchKey($table)->index();
            OmnichannelSchema::userKey($table, 'created_by')->index();
            $table->string('product_id')->nullable()->index();
            $table->string('product_name')->nullable();
            $table->string('source_type')->default('product')->index();
            $table->string('source_reference_type')->nullable();
            $table->string('source_reference_id')->nullable();
            $table->string('source_title')->nullable();
            $table->foreignId('channel_id')
                ->constrained($channelsTable, 'id', 'fk_omc_pub_channel')
                ->restrictOnDelete();
            $table->foreignId('channel_connection_id')
                ->constrained($connectionsTable, 'id', 'fk_omc_pub_conn')
                ->restrictOnDelete();
            $table->string('type')->index();
            $table->string('status')->default('draft')->index();
            $table->json('content');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->text('external_url')->nullable();
            $table->string('idempotency_key')->unique();
            $table->string('failure_class')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at'], 'omc_publications_status_scheduled_idx');
            $table->index(['branch_id', 'channel_id', 'status'], 'omc_publications_branch_channel_status_idx');
        });

        Schema::create($logsTable, function (Blueprint $table) use ($publicationsTable, $connectionsTable): void {
            $table->id();
            $table->foreignId('publication_id')
                ->nullable()
                ->constrained($publicationsTable, 'id', 'fk_omc_log_pub')
                ->nullOnDelete();
            $table->foreignId('channel_connection_id')
                ->nullable()
                ->constrained($connectionsTable, 'id', 'fk_omc_log_conn')
                ->nullOnDelete();
            $table->string('operation')->index();
            $table->string('status')->index();
            $table->unsignedInteger('attempt')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('external_id')->nullable();
            $table->string('failure_class')->nullable()->index();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('retryable')->default(false);
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['publication_id', 'created_at'], 'omc_logs_publication_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(OmnichannelTables::name('publication_logs'));
        Schema::dropIfExists(OmnichannelTables::name('publications'));
        Schema::dropIfExists(OmnichannelTables::name('connections'));
        Schema::dropIfExists(OmnichannelTables::name('channels'));
    }
};
