<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('subscriptions.tables.limitations'), function (Blueprint $table): void {
            $table->id();

            $table->json('title');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->string('type');
            $table->unsignedMediumInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('subscriptions.tables.limitations'));
    }
};
