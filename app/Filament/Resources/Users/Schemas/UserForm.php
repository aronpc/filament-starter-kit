<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make()
                    ->heading(__('messages.basic_information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('fields.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label(__('fields.password'))
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->revealable()
                            ->maxLength(255)
                            ->helperText(__('messages.password_helper')),
                    ]),

                Section::make()
                    ->heading(__('messages.roles_and_permissions'))
                    ->schema([
                        CheckboxList::make('roles')
                            ->label(__('fields.roles'))
                            ->relationship('roles', 'name')
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->visible(fn (): bool => Auth::user()?->can('view_any_role') ?? false),
            ]);
    }
}
