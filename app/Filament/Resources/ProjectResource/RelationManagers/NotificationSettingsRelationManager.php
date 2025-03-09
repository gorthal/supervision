<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationSettingsRelationManager extends RelationManager
{
    protected static string $relationship = 'notificationSettings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('notify_new')
                            ->label('Notifier les nouvelles erreurs')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('notify_critical')
                            ->label('Notifier les erreurs critiques')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('notify_error')
                            ->label('Notifier les erreurs')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('notify_warning')
                            ->label('Notifier les avertissements')
                            ->default(false),
                    ]),
                
                Forms\Components\Select::make('frequency')
                    ->label('Fréquence')
                    ->options([
                        'realtime' => 'Temps réel',
                        'hourly' => 'Résumé horaire',
                        'daily' => 'Résumé quotidien',
                    ])
                    ->default('realtime')
                    ->required()
                    ->reactive(),
                
                Forms\Components\TimePicker::make('daily_time')
                    ->label('Heure d\'envoi (résumé quotidien)')
                    ->seconds(false)
                    ->default('09:00')
                    ->visible(fn (callable $get) => $get('frequency') === 'daily'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                
                Tables\Columns\IconColumn::make('notify_new')
                    ->label('Nouvelles erreurs')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('notify_critical')
                    ->label('Erreurs critiques')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('notify_error')
                    ->label('Erreurs')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('notify_warning')
                    ->label('Avertissements')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Fréquence')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'realtime' => 'Temps réel',
                        'hourly' => 'Résumé horaire',
                        'daily' => 'Résumé quotidien',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'realtime' => 'danger',
                        'hourly' => 'warning',
                        'daily' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('daily_time')
                    ->label('Heure d\'envoi')
                    ->time('H:i')
                    ->visible(fn ($livewire): bool => $livewire->getTableModel()::query()->where('frequency', 'daily')->exists()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('frequency')
                    ->label('Fréquence')
                    ->options([
                        'realtime' => 'Temps réel',
                        'hourly' => 'Résumé horaire',
                        'daily' => 'Résumé quotidien',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
