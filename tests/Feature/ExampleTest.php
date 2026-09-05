<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_application_redirects_to_admin(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
    }

    public function test_admin_login_page_can_be_accessed(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }
}
