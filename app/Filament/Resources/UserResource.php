<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = "heroicon-o-user";

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make("profile_photo")
                ->label("Photo")
                ->disk("public")
                ->avatar()
                ->image()
                ->hiddenLabel()
                ->columnSpanFull()
                ->disabled(),
            Forms\Components\TextInput::make("name")
                ->maxLength(255)
                ->disabled(),
            Forms\Components\TextInput::make("email")
                ->email()
                ->maxLength(255)
                ->disabled(),
            Forms\Components\Textarea::make("bio")
                ->maxLength(65535)
                ->columnSpanFull()
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make("profile_photo")
                    ->label("Photo")
                    ->disk("public")
                    ->circular()
                    ->height(48)
                    ->width(48)
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make("name")
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn($record) => $record->name),
                Tables\Columns\TextColumn::make("email")
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make("bio")
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->tooltip(fn($record) => $record->bio),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->defaultSort("name", "asc");
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListUsers::route(""),
            "view" => Pages\ViewUser::route("/{record}"),
        ];
    }
}
