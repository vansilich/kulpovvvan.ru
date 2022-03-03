<?php

namespace Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MailgunerControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_success_unsubForm()
    {
        $response = $this->get( route('mailganerUnsubForm') );

        $response->assertStatus(200);
        $response->assertViewIs('mailganer.unsubForm');
    }

    public function test_success_handleUnsub()
    {
        $response = $this->get( route('handleUnsub') );

        $response->assertStatus(200);
    }
}
