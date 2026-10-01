<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ThrustAuthorizationTest extends TestCase
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

    public function test_rde_admin_can_open_thrust_management(): void
    {
        $this->withSession([
            'user_id' => 10,
            'user_role' => 'admin',
            'user_college_id' => null,
        ])->get(route('thrusts.create'))
            ->assertOk()
            ->assertSee('Create Thrust')
            ->assertSee(route('thrusts.index'));
    }

    public function test_super_admin_can_open_thrust_management(): void
    {
        $this->withSession([
            'user_id' => 1,
            'user_role' => 'super_admin',
        ])->get(route('thrusts.create'))
            ->assertOk()
            ->assertSee('Create Thrust');
    }

    public function test_college_admin_cannot_open_thrust_management(): void
    {
        $this->withSession([
            'user_id' => 11,
            'user_role' => 'admin',
            'user_college_id' => 5,
        ])->get(route('thrusts.create'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Unauthorized.');
    }
}