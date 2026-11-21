<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Support\OmnichannelTables;

return new class extends Migration
{
    public function up(): void
    {
        $connections = OmnichannelTables::name('connections');
        $publications = OmnichannelTables::name('publications');
        $logs = OmnichannelTables::name('publication_logs');

        Schema::table($connections, function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['channel_id'] : 'fk_omc_conn_channel');
            $table->foreign('channel_id', 'fk_omc_conn_channel')->references('id')
                ->on(OmnichannelTables::name('channels'))->restrictOnDelete();
        });
        Schema::table($publications, function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['channel_id'] : 'fk_omc_pub_channel');
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['channel_connection_id'] : 'fk_omc_pub_conn');
            $table->foreign('channel_id', 'fk_omc_pub_channel')->references('id')
                ->on(OmnichannelTables::name('channels'))->restrictOnDelete();
            $table->foreign('channel_connection_id', 'fk_omc_pub_conn')->references('id')
                ->on(OmnichannelTables::name('connections'))->restrictOnDelete();
        });
        Schema::table($logs, function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['publication_id'] : 'fk_omc_log_pub');
            $table->foreign('publication_id', 'fk_omc_log_pub')->references('id')
                ->on(OmnichannelTables::name('publications'))->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $connections = OmnichannelTables::name('connections');
        $publications = OmnichannelTables::name('publications');
        $logs = OmnichannelTables::name('publication_logs');

        Schema::table($logs, function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['publication_id'] : 'fk_omc_log_pub');
            $table->foreign('publication_id', 'fk_omc_log_pub')->references('id')
                ->on(OmnichannelTables::name('publications'))->cascadeOnDelete();
        });
        Schema::table($publications, function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['channel_id'] : 'fk_omc_pub_channel');
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['channel_connection_id'] : 'fk_omc_pub_conn');
            $table->foreign('channel_id', 'fk_omc_pub_channel')->references('id')
                ->on(OmnichannelTables::name('channels'))->cascadeOnDelete();
            $table->foreign('channel_connection_id', 'fk_omc_pub_conn')->references('id')
                ->on(OmnichannelTables::name('connections'))->cascadeOnDelete();
        });
        Schema::table($connections, function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['channel_id'] : 'fk_omc_conn_channel');
            $table->foreign('channel_id', 'fk_omc_conn_channel')->references('id')
                ->on(OmnichannelTables::name('channels'))->cascadeOnDelete();
        });
    }
};
