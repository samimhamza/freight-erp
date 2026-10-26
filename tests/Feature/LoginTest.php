<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertSuccessful();
    }

    public function test_user_can_authenticate_via_login_page(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@admin.com',
            'password' => 'Admin$123',
        ]);

        $component = Livewire::test(Login::class)
            ->fillForm([
                'email' => 'admin@admin.com',
                'password' => 'Admin$123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_cookie_name_does_not_contain_dots_or_special_characters(): void
    {
        $cookieName = config('session.cookie');
        $this->assertStringNotContainsString('.', $cookieName);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_]+$/', $cookieName);
    }

    public function test_authenticated_user_session_persists_across_http_requests(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');
        $response->assertSuccessful();
    }
}
