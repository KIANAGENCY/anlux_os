<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_route_is_registered(): void
    {
        $this->assertTrue(Route::has('login'), 'Named route login must exist');
    }

    public function test_login_page_responds_ok(): void
    {
        $this->get('/login')->assertOk();
    }
}
