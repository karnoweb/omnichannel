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
        $table = OmnichannelTables::name('publications');
        if (Schema::hasColumn($table, 'source_type')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->string('source_type')->default('product')->index();
            $blueprint->string('source_reference_type')->nullable();
            $blueprint->string('source_reference_id')->nullable();
            $blueprint->string('source_title')->nullable();
        });

        DB::table($table)->whereNull('source_type')->orWhere('source_type', '')->update(['source_type' => 'product']);

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite rebuild for nullable product_id
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('product_id')->nullable()->change();
            });
        } else {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('product_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $table = OmnichannelTables::name('publications');
        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->dropIndex(['source_type']);
            $blueprint->dropColumn([
                'source_type',
                'source_reference_type',
                'source_reference_id',
                'source_title',
            ]);
        });
    }
};
