<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Post;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Filament\Resources\PostResource\Pages\ListPosts;
use App\Filament\Resources\PostResource\Pages\CreatePost;
use Filament\Forms\Components\Group; // Correct import

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = "heroicon-o-newspaper";

    // Removed: use Filament\Forms\Components\Group;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Main content column
                Group::make()
                    ->schema([
                        Forms\Components\Section::make("Content")->schema([
                            Forms\Components\TextInput::make("title")
                                // ... same as before
                                ->live(onBlur: true)
                                ->afterStateUpdated(
                                    fn($state, callable $set) => $set(
                                        "slug",
                                        Str::slug($state),
                                    ),
                                ),
                            Forms\Components\TextInput::make("slug")
                                // ... same as before
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\RichEditor::make(
                                "content",
                            )->columnSpanFull(),
                        ]),
                    ])
                    ->columnSpan(["lg" => 2]),

                // Sidebar column
                Group::make()
                    ->schema([
                        Forms\Components\Section::make("Metadata")->schema([
                            Forms\Components\FileUpload::make("featured_image")
                                ->image()
                                ->disk("public"),
                            Forms\Components\Select::make("category_id")
                                ->relationship("category", "name")
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make("tags")
                                ->multiple()
                                ->relationship("tags", "name")
                                ->searchable()
                                ->preload(),
                        ]),
                    ])
                    ->columnSpan(["lg" => 1]),

                Forms\Components\Hidden::make("user_id")
                    ->default(fn() => Auth::id())
                    ->dehydrated(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make("featured_image")
                    ->label("Image")
                    ->disk("public")
                    ->circular() // ✨ Use circular images for a cleaner look
                    ->toggleable(isToggledHiddenByDefault: true), // Hide by default to save space

                Tables\Columns\TextColumn::make("title")
                    ->searchable()
                    ->sortable()
                    ->limit(50) // Limit title length to prevent wrapping
                    ->tooltip(fn($record) => $record->title), // Show full title on hover

                Tables\Columns\TextColumn::make("status")
                    ->badge()
                    ->colors([
                        "success" => "publish",
                        "warning" => "draft",
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make("category.name")
                    ->label("Category")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make("user.name")
                    ->label("Author")
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Often useful but can be hidden

                Tables\Columns\TextColumn::make("tags.name")
                    ->label("Tags")
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make("created_at")
                    ->label("Published On")
                    ->dateTime("d M Y") // Format the date for readability
                    ->sortable(),

                Tables\Columns\TextColumn::make("slug")
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // 💡 Hide slug by default
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("category")
                    ->relationship("category", "name")
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make("tags")
                    ->multiple()
                    ->relationship("tags", "name")
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make("status") // 💡 Added status filter
                    ->options([
                        "draft" => "Draft",
                        "publish" => "Published",
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // ✨ Group actions for a cleaner UI
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make("publish")
                        ->label("Publish")
                        ->icon("heroicon-o-check-circle")
                        ->color("success")
                        ->requiresConfirmation()
                        ->action(
                            fn($record) => $record->update([
                                "status" => "publish",
                            ]),
                        )
                        ->visible(fn($record) => $record->status === "draft"),
                    Tables\Actions\Action::make("unpublish")
                        ->label("Unpublish")
                        ->icon("heroicon-o-x-circle")
                        ->color("warning")
                        ->requiresConfirmation()
                        ->action(
                            fn($record) => $record->update([
                                "status" => "draft",
                            ]),
                        )
                        ->visible(fn($record) => $record->status === "publish"),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // ✨ Added bulk action for publishing
                    Tables\Actions\BulkAction::make("publish")
                        ->label("Publish selected")
                        ->icon("heroicon-o-check-circle")
                        ->action(
                            fn($records) => $records->each->update([
                                "status" => "publish",
                            ]),
                        )
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort("created_at", "desc"); // Sort by newest posts by default
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
            "index" => ListPosts::route("/"),
            "create" => CreatePost::route("/create"),
            "edit" => EditPost::route("/{record}/edit"),
        ];
    }
}
