<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Support\OmnichannelTables;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = OmnichannelTables::name('publications');

        Schema::table($tableName, function (Blueprint $table): void {
            $table->uuid('attempt_token')->nullable();
            $table->index('attempt_token', 'omc_publications_attempt_token_idx');
            $table->unsignedInteger('content_version')->default(0);
        });
    }

    public function down(): void
    {
        $tableName = OmnichannelTables::name('publications');

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropIndex('omc_publications_attempt_token_idx');
            $table->dropColumn(['attempt_token', 'content_version']);
        });
    }
};
