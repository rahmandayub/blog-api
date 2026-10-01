<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    public ?string $pendingStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($this->pendingStatus)) {
            $data['status'] = $this->pendingStatus;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        // Eksplisit simpan relasi (tags BelongsToMany + references HasMany).
        // Pada alur standar ini sebenarnya sudah dipicu sebagai side-effect
        // Form::getState(), tapi pemanggilan eksplisit menjaga dari
        // perubahan perilaku Filament di masa depan dan memperjelas intent.
        $this->form->model($record)->saveRelationships();

        return $record;
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
                    // Delegasikan ke alur standar EditRecord::save() agar
                    // validasi, transaction, hooks, dan relasi ditangani benar.
                    $this->pendingStatus = $data['status'];

                    $this->save(shouldRedirect: false);

                    $this->pendingStatus = null;

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            $this->getCancelFormAction(),
        ];
    }
}
