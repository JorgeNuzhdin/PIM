<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profession')) {
                $table->string('profession')->nullable()->after('institution');
            }
            if (!Schema::hasColumn('users', 'reason')) {
                $table->string('reason')->nullable()->after('profession');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profession', 'reason']);
        });
    }
};
