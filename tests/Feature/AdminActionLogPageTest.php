<?php

namespace Tests\Feature;

use App\Models\AdminActionLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminActionLogPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role', 20);
            $table->string('action');
            $table->string('method', 10);
            $table->string('route_name')->nullable();
            $table->string('path');
            $table->text('url');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestamps();
        });
    }

    public function test_super_admin_can_view_admin_action_logs_page(): void
    {
        AdminActionLog::create([
            'user_id' => 7,
            'role' => 'super_admin',
            'action' => 'users.update',
            'method' => 'PUT',
            'route_name' => 'users.update',
            'path' => 'users/7',
            'url' => 'http://localhost/users/7',
            'response_status' => 200,
            'request_data' => ['name' => 'Jane Doe'],
        ]);

        $response = $this->withSession([
            'user_id' => 1,
            'user_role' => 'super_admin',
            'user_name' => 'System Admin',
        ])->get(route('super-admin.admin-action-logs.index'));

        $response->assertOk();
        $response->assertSee('Admin Activity Logs');
        $response->assertSee('users.update');
    }
}
