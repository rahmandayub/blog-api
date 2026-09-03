<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('saveWithConfirmation')
                ->label('Save')
                ->modalHeading('Post Status')
                ->form([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'publish' => 'Publish',
                        ])
                        ->default('draft')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $formData = $this->mutateFormDataBeforeCreate(array_merge($this->getForm('form')->getState(), $data));
                    $record = $this->handleRecordCreation($formData);

                    Notification::make()
                        ->success()
                        ->title('Post created successfully.')
                        ->send();

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                }),
        ];
    }
}
