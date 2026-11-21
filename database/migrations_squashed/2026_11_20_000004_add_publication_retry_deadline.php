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
        Schema::table(OmnichannelTables::name('publications'), function (Blueprint $table): void {
            $table->unsignedBigInteger('retry_deadline_timestamp')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table(OmnichannelTables::name('publications'), function (Blueprint $table): void {
            $table->dropColumn('retry_deadline_timestamp');
        });
    }
};
