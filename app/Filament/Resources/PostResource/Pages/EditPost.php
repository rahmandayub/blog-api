<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
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
                        ->default(fn ($record) => $record->status ?? 'draft')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $formData = array_merge($this->getForm('form')->getState(), ['status' => $data['status']]);
                    $this->getRecord()->update($formData);
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
