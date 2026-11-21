<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

// Reapply retention for installations that recorded the earlier SQLite no-op.
return new class extends Migration
{
    public function up(): void
    {
        $retention = require __DIR__.'/2026_11_20_000002_preserve_publication_history.php';
        $retention->up();
    }

    public function down(): void
    {
        // Keep retention when rolling back this repair; the original migration
        // remains responsible for explicitly reverting its constraint policy.
    }
};
