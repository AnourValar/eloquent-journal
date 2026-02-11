<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    //protected $connection = 'pgsql';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection(null)->create('journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->string('entity')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('type')->index();
            $table->string('event')->index();
            $table->jsonb('data')->nullable();
            $table->boolean('success');
            $table->jsonb('tags')->nullable(); // index below [for example: provider, external_id ...]
            $table->timestamps(); // index below

            $table->index('tags', null, 'GIN'); // remove if not postgresql
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(null)->dropIfExists('journals');
    }
};
