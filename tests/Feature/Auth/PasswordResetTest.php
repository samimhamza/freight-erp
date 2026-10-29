<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_page_can_be_rendered(): void
    {
        $response = $this->get(route('filament.admin.auth.password-reset.request'));

        $response->assertSuccessful();
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        Livewire::test(RequestPasswordReset::class)
            ->fillForm([
                'email' => 'user@example.com',
            ])
            ->call('request')
            ->assertHasNoFormErrors()
            ->assertNotified();
    }

    public function test_password_reset_fails_for_non_existent_email(): void
    {
        Livewire::test(RequestPasswordReset::class)
            ->fillForm([
                'email' => 'nonexistent@example.com',
            ])
            ->call('request')
            ->assertNotified();
    }
}
