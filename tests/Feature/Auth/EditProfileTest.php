<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EditProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(EditProfile::getUrl())
            ->assertSuccessful();
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'username' => 'janesmith',
                'email' => 'john@example.com',
                'phone' => '0987654321',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Jane', $user->first_name);
        $this->assertSame('Smith', $user->last_name);
        $this->assertSame('janesmith', $user->username);
        $this->assertSame('0987654321', $user->phone);
    }

    public function test_email_change_requires_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'original@example.com',
            'password' => 'Password123!',
        ]);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'email' => 'newemail@example.com',
                'currentPassword' => '',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword' => 'required']);

        $user->refresh();
        $this->assertSame('original@example.com', $user->email);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'currentPassword' => '',
                'password' => 'NewPassword123!',
                'passwordConfirmation' => 'NewPassword123!',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword' => 'required']);
    }

    public function test_password_and_confirmation_must_match(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'Password123!',
                'password' => 'NewPassword123!',
                'passwordConfirmation' => 'DifferentPassword123!',
            ])
            ->call('save')
            ->assertHasFormErrors(['password' => 'same']);
    }

    public function test_user_can_successfully_change_password(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'currentPassword' => 'Password123!',
                'password' => 'BrandNewPassword123!',
                'passwordConfirmation' => 'BrandNewPassword123!',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('BrandNewPassword123!', $user->password));
    }
}
