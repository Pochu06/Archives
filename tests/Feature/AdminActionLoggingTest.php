<?php

namespace Tests\Feature;

use App\Models\AdminActionLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminActionLoggingTest extends TestCase
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

    public function test_admin_requests_are_logged_with_sensitive_fields_removed(): void
    {
        Route::middleware('web')->post('/admin-action-log-test', function () {
            return response()->json(['ok' => true]);
        })->name('admin-action-log-test');

        $response = $this->withSession([
            'user_id' => 10,
            'user_role' => 'admin',
        ])->post('/admin-action-log-test', [
            'title' => 'Sample update',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertOk();

        $log = AdminActionLog::query()->first();

        $this->assertNotNull($log);
        $this->assertSame('admin', $log->role);
        $this->assertSame('POST', $log->method);
        $this->assertSame('admin-action-log-test', $log->route_name);
        $this->assertSame(200, $log->response_status);
        $this->assertSame(['title' => 'Sample update'], $log->request_data);
    }
}