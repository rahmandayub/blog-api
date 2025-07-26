<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class ProfilePage extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-user-circle";
    protected static ?string $title = "My Profile";
    protected static ?string $slug = "profile";
    protected static ?string $navigationLabel = "Profile";
    protected static ?string $navigationGroup = "Account";

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            "name" => $user->name,
            "bio" => $user->bio,
            "profile_photo" => $user->profile_photo,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(
                            "Profile Information",
                        )->schema([
                            TextInput::make("name")
                                ->label("Name")
                                ->required()
                                ->maxLength(255),
                            Textarea::make("bio")
                                ->label("Bio")
                                ->rows(3)
                                ->maxLength(500),
                        ]),
                    ])
                    ->columnSpan(["lg" => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make("Profile Photo")->schema(
                            [
                                FileUpload::make("profile_photo")
                                    ->label("Upload Photo")
                                    ->image()
                                    ->imageCropAspectRatio("1:1")

                                    ->hint(
                                        "For best results, upload a square photo.",
                                    )
                                    ->disk("public")
                                    ->directory("profile-photos")
                                    ->maxSize(2048),
                            ],
                        ),
                    ])
                    ->columnSpan(["lg" => 1]),
            ])
            ->columns(3)
            ->statePath("data");
    }

    public function submit()
    {
        $user = User::find(Auth::id());
        $validated = $this->form->getState();

        $user->name = $validated["name"];
        $user->bio = $validated["bio"] ?? null;
        $user->profile_photo = $validated["profile_photo"] ?? null;
        $user->save();

        Notification::make()
            ->title("Profile updated successfully!")
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make("save")
                ->label("Save Changes")
                ->submit("submit")
                ->color("primary"),
        ];
    }
    protected static string $view = "filament.pages.profile-page";
}
