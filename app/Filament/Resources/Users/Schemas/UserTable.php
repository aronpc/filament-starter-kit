<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('fields.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label(__('fields.roles'))
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email_verified_at')
                    ->label(__('fields.email_verified_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
