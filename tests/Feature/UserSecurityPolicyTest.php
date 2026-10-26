<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSecurityPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected Role $superAdminRole;

    protected Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'ViewAny:User', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:User', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Create:User', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Update:User', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Delete:User', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ForceDelete:User', 'guard_name' => 'web']);
    }

    public function test_super_admin_cannot_delete_own_account_via_policy(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin);

        $this->assertFalse($superAdmin->can('delete', $superAdmin));
        $this->assertFalse($superAdmin->can('forceDelete', $superAdmin));
    }

    public function test_super_admin_cannot_delete_own_account_via_model_event(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // Create a second super admin so it's not blocked purely by last super admin check
        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $this->actingAs($superAdmin);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('You cannot delete your own account.');

        $superAdmin->delete();
    }

    public function test_last_remaining_super_admin_cannot_be_deleted(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The last remaining super admin cannot be deleted.');

        $superAdmin->delete();
    }

    public function test_non_super_admin_cannot_delete_a_super_admin_via_policy_or_model(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->givePermissionTo('Delete:User');

        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');
        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $this->actingAs($regularUser);

        // Policy denies deletion of super_admin to non-super_admin
        $this->assertFalse($regularUser->can('delete', $superAdmin1));
        $this->assertFalse($regularUser->can('forceDelete', $superAdmin1));

        // Model event throws validation exception
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only a super admin can delete another super admin.');

        $superAdmin1->delete();
    }

    public function test_super_admin_can_delete_another_super_admin_leaving_at_least_one(): void
    {
        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $this->actingAs($superAdmin1);

        $this->assertTrue($superAdmin1->can('delete', $superAdmin2));

        $superAdmin2->delete();

        $this->assertTrue($superAdmin2->trashed());
        $this->assertEquals(1, User::role('super_admin')->count());
        $this->assertFalse($superAdmin1->trashed());
    }

    public function test_super_admin_can_delete_regular_user(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $regularUser = User::factory()->create();

        $this->actingAs($superAdmin);

        $this->assertTrue($superAdmin->can('delete', $regularUser));

        $regularUser->delete();

        $this->assertTrue($regularUser->trashed());
    }

    public function test_non_super_admin_cannot_update_a_super_admin(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->givePermissionTo('Update:User');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($regularUser);

        $this->assertFalse($regularUser->can('update', $superAdmin));
    }

    public function test_super_admin_can_update_other_super_admins_and_users(): void
    {
        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $regularUser = User::factory()->create();

        $this->actingAs($superAdmin1);

        $this->assertTrue($superAdmin1->can('update', $superAdmin2));
        $this->assertTrue($superAdmin1->can('update', $regularUser));
    }

    public function test_users_table_hides_delete_action_for_own_user_row_and_shows_for_others(): void
    {
        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $regularUser = User::factory()->create();

        $this->actingAs($superAdmin1);

        Livewire::test(ListUsers::class)
            ->assertSuccessful()
            ->assertTableActionHidden('delete', $superAdmin1)
            ->assertTableActionVisible('delete', $superAdmin2)
            ->assertTableActionVisible('delete', $regularUser);
    }

    public function test_edit_user_page_hides_delete_action_for_own_account_and_shows_for_others(): void
    {
        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $this->actingAs($superAdmin1);

        // Header delete action is hidden when editing own account
        Livewire::test(EditUser::class, ['record' => $superAdmin1->getKey()])
            ->assertSuccessful()
            ->assertActionHidden('delete');

        // Header delete action is visible when editing another super admin
        Livewire::test(EditUser::class, ['record' => $superAdmin2->getKey()])
            ->assertSuccessful()
            ->assertActionVisible('delete');
    }

    public function test_super_admin_editing_own_account_has_roles_disabled_and_cannot_remove_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin);

        Livewire::test(EditUser::class, ['record' => $superAdmin->getKey()])
            ->assertSuccessful()
            ->assertFormFieldIsDisabled('roles')
            ->fillForm([
                'first_name' => 'UpdatedName',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $superAdmin->refresh();
        $this->assertSame('UpdatedName', $superAdmin->first_name);
        $this->assertTrue($superAdmin->hasRole('super_admin'));
    }

    public function test_super_admin_can_edit_roles_of_another_user(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $otherUser = User::factory()->create();

        $this->actingAs($superAdmin);

        Livewire::test(EditUser::class, ['record' => $otherUser->getKey()])
            ->assertSuccessful()
            ->assertFormFieldIsEnabled('roles')
            ->fillForm([
                'roles' => [$this->managerRole->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $otherUser->refresh();
        $this->assertTrue($otherUser->hasRole('manager'));
        $this->assertFalse($otherUser->hasRole('super_admin'));
    }

    public function test_super_admin_can_change_roles_of_another_super_admin(): void
    {
        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $this->actingAs($superAdmin1);

        // superAdmin1 can change superAdmin2's roles (e.g. demote to manager)
        Livewire::test(EditUser::class, ['record' => $superAdmin2->getKey()])
            ->assertSuccessful()
            ->assertFormFieldIsEnabled('roles')
            ->fillForm([
                'roles' => [$this->managerRole->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $superAdmin2->refresh();
        $this->assertTrue($superAdmin2->hasRole('manager'));
        $this->assertFalse($superAdmin2->hasRole('super_admin'));
        // superAdmin1 remains super_admin
        $this->assertTrue($superAdmin1->hasRole('super_admin'));
    }

    public function test_non_super_admin_cannot_see_super_admin_role_option_when_editing_user(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->givePermissionTo(['ViewAny:User', 'View:User', 'Update:User']);

        $otherUser = User::factory()->create();

        $this->actingAs($regularUser);

        $component = Livewire::test(EditUser::class, ['record' => $otherUser->getKey()])
            ->assertSuccessful();

        $form = $component->instance()->form;
        $rolesField = $form->getComponent('roles');

        $options = $rolesField->getOptions();
        $this->assertArrayNotHasKey($this->superAdminRole->id, $options);
        $this->assertArrayHasKey($this->managerRole->id, $options);
    }
}
