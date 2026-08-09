<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('notification_digest_frequency', 20)->default('none')->after('status');
            $table->timestamp('notification_digest_last_sent_at')->nullable()->after('notification_digest_frequency');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notification_digest_frequency', 'notification_digest_last_sent_at']);
        });
    }
};
