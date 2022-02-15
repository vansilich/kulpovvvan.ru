<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\AllowFluid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Tests\TestCase;

class AllowFluidTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_pass_ip()
    {
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $middleware = new AllowFluid();

        $response = $middleware->handle($request, function ($req) {
            return true;
        });

        $this->assertEquals(true, $response );
    }

    public function test_reject_ip()
    {
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '228.0.0.11');

        $middleware = new AllowFluid();

        $response = $middleware->handle($request, function ($req) {
            return true;
        });

        $this->assertEquals(500, $response->getStatusCode() );
    }
}
