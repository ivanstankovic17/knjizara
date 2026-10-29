<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('isbn', 13)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price');          // u dinarima
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->timestamps();

            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
