<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = "heroicon-o-rectangle-stack";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Main content column
                \Filament\Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(
                            "Category Details",
                        )->schema([
                            Forms\Components\TextInput::make("name")
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (
                                    $state,
                                    callable $set,
                                ) {
                                    $set("slug", Str::slug($state));
                                }),
                            Forms\Components\TextInput::make("slug")
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                        ]),
                    ])
                    ->columnSpan(["lg" => 2]),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn($record) => $record->name),
                Tables\Columns\TextColumn::make("slug")
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([Tables\Filters\TrashedFilter::make()])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])
            ->defaultSort("name", "asc");
    }

    public static function getRelations(): array
    {
        return [
                //
            ];
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListCategories::route("/"),
            "create" => Pages\CreateCategory::route("/create"),
            "edit" => Pages\EditCategory::route("/{record}/edit"),
        ];
    }
}
