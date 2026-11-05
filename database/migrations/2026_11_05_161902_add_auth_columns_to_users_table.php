<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Prijava preko GitHub-a (odeljak 11.6).
            $table->string('github_id')->nullable()->unique()->after('email');
            // Dvofaktorska prijava (odeljak 11.7); tajna je šifrovana.
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['github_id']);
            $table->dropColumn(['github_id', 'two_factor_secret', 'two_factor_confirmed_at']);
        });
    }
};
