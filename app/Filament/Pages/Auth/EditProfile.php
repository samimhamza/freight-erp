<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use SensitiveParameter;

class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'Profile & Password';

    public static function getLabel(): string
    {
        return 'Profile & Password';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Information')
                    ->description('Update your account personal details and email address.')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true),
                        $this->getEmailFormComponent(),
                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])
                    ->columns(2),

                Section::make('Change Password')
                    ->description('To change your password, provide your current password and enter your new password twice.')
                    ->schema([
                        TextInput::make('currentPassword')
                            ->label('Current Password')
                            ->password()
                            ->autocomplete('current-password')
                            ->revealable(filament()->arePasswordsRevealable())
                            ->currentPassword(guard: Filament::getAuthGuard())
                            ->required(fn (Get $get): bool => filled($get('password')) || filled($get('passwordConfirmation')) || ($get('email') !== $this->getUser()->getAttributeValue('email')))
                            ->live(onBlur: true)
                            ->dehydrated(false),

                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->autocomplete('new-password')
                            ->revealable(filament()->arePasswordsRevealable())
                            ->rule(Password::default())
                            ->required(fn (Get $get): bool => filled($get('passwordConfirmation')))
                            ->same('passwordConfirmation')
                            ->validationMessages([
                                'same' => 'The new password and confirm new password must match.',
                            ])
                            ->live(onBlur: true)
                            ->dehydrated(fn (#[SensitiveParameter] $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (#[SensitiveParameter] $state): string => Hash::make($state)),

                        TextInput::make('passwordConfirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->autocomplete('new-password')
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required(fn (Get $get): bool => filled($get('password')))
                            ->same('password')
                            ->validationMessages([
                                'same' => 'The confirm new password must match the new password.',
                            ])
                            ->live(onBlur: true)
                            ->dehydrated(false),
                    ]),
            ]);
    }

    protected function afterSave(): void
    {
        $this->data['currentPassword'] = null;
        $this->data['password'] = null;
        $this->data['passwordConfirmation'] = null;
    }
}
