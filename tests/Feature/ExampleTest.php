<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root path redirects guests to the admin panel login.
     */
    public function test_the_root_path_redirects_to_the_admin_panel(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
