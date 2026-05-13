<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('url');
            $table->json('events');
            $table->string('secret', 64);
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }
};
