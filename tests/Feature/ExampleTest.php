<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_url_redirects_to_the_sign_in_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/masuk');
    }

    public function test_the_health_check_endpoint_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
