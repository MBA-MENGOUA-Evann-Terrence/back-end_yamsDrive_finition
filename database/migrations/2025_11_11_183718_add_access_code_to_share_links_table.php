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
        Schema::table('share_links', function (Blueprint $table) {
            $table->string('access_code', 6)->nullable()->after('permission_level');
            $table->boolean('require_code')->default(false)->after('access_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('share_links', function (Blueprint $table) {
            $table->dropColumn(['access_code', 'require_code']);
        });
    }
};
