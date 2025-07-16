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

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Post Title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('slug', Str::slug($state));
                                    })
                                    ->placeholder('Enter post title'),
                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Auto-generated from title'),
                                Forms\Components\Hidden::make('user_id')->default(fn() => Auth::id())->dehydrated(),
                            ]),
                        Forms\Components\RichEditor::make('content')
                            ->label('Content')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Write your post content here...'),
                        Forms\Components\FileUpload::make('featured_image')
                            ->label('Featured Image')
                            ->image()
                            ->disk('public') // Explicitly set the disk to 'public'
                            ->directory('posts/featured-images')
                            ->columnSpanFull()
                            ->helperText('Upload the main image for this post')
                            ->deleteUploadedFileUsing(function ($file) {
                                // Delete the old file when a new one is uploaded
                                if ($file && \Illuminate\Support\Facades\Storage::disk('public')->exists($file)) {
                                    \Illuminate\Support\Facades\Storage::disk('public')->delete($file);
                                }
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select category'),
                                Forms\Components\Select::make('tags')
                                    ->label('Tags')
                                    ->multiple()
                                    ->relationship('tags', 'name')
                                    ->searchable()
                                    ->placeholder('Select tags'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Featured Image')
                    ->disk('public')
                    ->height(48)
                    ->width(48),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->sortable(),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('user.name')->label('Author')->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->sortable(),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tags')
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('tags')->multiple()->relationship('tags', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Publish')
                    ->modalDescription('Are you sure you want to publish this post?')
                    ->modalSubmitActionLabel('Publish')
                    ->action(function ($record) {
                        $record->status = 'publish';
                        $record->save();
                    })
                    ->visible(fn($record) => $record->status === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
