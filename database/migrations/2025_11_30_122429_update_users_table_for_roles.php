<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove old role column if it exists
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            // Add new fields
            if (!Schema::hasColumn('users', 'nickname')) {
                $table->string('nickname')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'profile_picture')) {
                $table->string('profile_picture')->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('users', 'privacy_settings')) {
                $table->json('privacy_settings')->nullable()->after('bio');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable();
            $table->dropColumn(['nickname', 'profile_picture', 'privacy_settings']);
        });
    }
};
