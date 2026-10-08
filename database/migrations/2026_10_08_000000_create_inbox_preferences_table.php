<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ruvelo\Inbox\Models\Preference;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Preference::tableName(), function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type');
            $table->string('notifiable_id', 36);
            $table->string('type');
            $table->string('channel', 64);
            $table->boolean('enabled');
            $table->timestamps();

            $table->unique(['notifiable_type', 'notifiable_id', 'type', 'channel'], Preference::tableName().'_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Preference::tableName());
    }
};
