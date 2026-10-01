<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    public ?string $pendingStatus = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status'] = $this->pendingStatus ?? $data['status'] ?? 'draft';

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
                    // Simpan status pilihan modal, lalu delegasikan ke alur
                    // standar CreateRecord::create() agar relasi ikut tersimpan:
                    // handleRecordCreation() + $this->form->model($record)->saveRelationships()
                    $this->pendingStatus = $data['status'];

                    $this->create();
                }),
            $this->getCancelFormAction(),
        ];
    }
}
