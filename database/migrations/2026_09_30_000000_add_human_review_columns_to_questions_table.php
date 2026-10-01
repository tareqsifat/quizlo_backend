<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('checked_by_human')->default(false)->after('verified_by_ai')->index();
            $table->text('rejection_reason')->nullable()->after('checked_by_human');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['checked_by_human']);
            $table->dropColumn(['checked_by_human', 'rejection_reason']);
        });
    }
};
