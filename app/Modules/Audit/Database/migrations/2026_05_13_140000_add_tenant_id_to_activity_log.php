<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// No down() by design — see CODING_STANDARDS.md §17. Rollbacks are forward-fix
// migrations, not reversibility.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            // Nullable: not every activity has a tenant (system events,
            // pre-tenant-context boot). The audit endpoint filters
            // by tenant_id, so null-tenant entries are intentionally
            // invisible to any tenant's feed.
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });
    }
};
