<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(__('messages.confirm_delete_user'))
                ->modalDescription(__('messages.confirm_delete_user_description'))
                ->modalSubmitActionLabel(__('messages.delete'))
                ->hidden(fn (User $record): bool => $record->getKey() === Auth::id()),
        ];
    }
}
