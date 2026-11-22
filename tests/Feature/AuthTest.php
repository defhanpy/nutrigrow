<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guests_to_the_sign_in_page(): void
    {
        $this->get('/')->assertRedirect('/masuk');
    }

    public function test_sign_in_page_renders_demo_account_helpers(): void
    {
        $response = $this->get('/masuk');

        $response->assertOk();
        $response->assertSee('Masuk ke NUTRIGROW');
        $response->assertSee('kader@nutrigrow.id');
        $response->assertSee('rahasia123');
    }

    public function test_valid_credentials_redirect_each_role_to_its_portal(): void
    {
        $accounts = [
            Role::Kader->value => '/kader/dashboard',
            Role::OrangTua->value => '/orang-tua/beranda',
            Role::TenagaKesehatan->value => '/tenaga-kesehatan/dashboard',
            Role::AdminPuskesmas->value => '/puskesmas/dashboard',
        ];

        foreach ($accounts as $role => $expected) {
            $user = User::factory()->role(Role::from($role))->create([
                'email' => $role.'@example.test',
                'password' => 'rahasia123',
            ]);

            $this->post('/masuk', [
                'email' => $user->email,
                'password' => 'rahasia123',
            ])->assertRedirect($expected);

            $this->post('/keluar');
        }
    }

    public function test_invalid_password_returns_a_friendly_error(): void
    {
        User::factory()->create(['email' => 'kader@example.test', 'password' => 'rahasia123']);

        $response = $this->from('/masuk')->post('/masuk', [
            'email' => 'kader@example.test',
            'password' => 'salah',
        ]);

        $response->assertRedirect('/masuk');
        $response->assertSessionHasErrors(['email' => 'Alamat surel atau kata sandi belum sesuai.']);
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_sign_in(): void
    {
        User::factory()->inactive()->create(['email' => 'nonaktif@example.test', 'password' => 'rahasia123']);

        $response = $this->from('/masuk')->post('/masuk', [
            'email' => 'nonaktif@example.test',
            'password' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_away_from_the_portal(): void
    {
        $this->get('/kader/dashboard')->assertRedirect('/masuk');
    }

    public function test_user_cannot_open_another_role_portal(): void
    {
        $kader = User::factory()->kader()->create();

        $this->actingAs($kader)->get('/tenaga-kesehatan/dashboard')->assertForbidden();
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->kader()->create();

        $response = $this->actingAs($user)->post('/keluar');

        $response->assertRedirect('/masuk');
        $this->assertGuest();
    }
}
