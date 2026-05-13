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
        Schema::table('webhooks', function (Blueprint $table): void {
            // `encrypted` cast emits base64-encoded ciphertext that's much
            // wider than the original 48-byte hex secret. text fits anything.
            $table->text('secret')->change();
        });
    }
};
