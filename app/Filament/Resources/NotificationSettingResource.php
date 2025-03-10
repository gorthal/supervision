<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationSettingResource\Pages;
use App\Models\NotificationSetting;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificationSettingResource extends Resource
{
    protected static ?string $model = NotificationSetting::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    
    protected static ?string $navigationGroup = 'Supervision';
    
    protected static ?int $navigationSort = 30;
    
    protected static ?string $navigationLabel = 'Notification Settings';
    
    protected static ?string $modelLabel = 'Notification Setting';
    
    protected static ?string $pluralModelLabel = 'Notification Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Notification Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slack_webhook_url')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Notification Triggers')
                    ->schema([
                        Forms\Components\Toggle::make('notify_on_error')
                            ->default(true)
                            ->label('Notify on Error'),
                        Forms\Components\Toggle::make('notify_on_warning')
                            ->default(true)
                            ->label('Notify on Warning'),
                        Forms\Components\Toggle::make('notify_on_info')
                            ->default(false)
                            ->label('Notify on Info'),
                        Forms\Components\Select::make('notification_frequency')
                            ->options([
                                'immediate' => 'Immediate',
                                'hourly' => 'Hourly Digest',
                                'daily' => 'Daily Digest',
                            ])
                            ->default('immediate')
                            ->required(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Project')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('Project (Optional)')
                            ->options(Project::all()->pluck('name', 'id'))
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->default('Global'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('notification_frequency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Project')
                    ->options(fn () => Project::all()->pluck('name', 'id'))
                    ->indicator('Project'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListNotificationSettings::route('/'),
            'create' => Pages\CreateNotificationSetting::route('/create'),
            'edit' => Pages\EditNotificationSetting::route('/{record}/edit'),
        ];
    }
}
