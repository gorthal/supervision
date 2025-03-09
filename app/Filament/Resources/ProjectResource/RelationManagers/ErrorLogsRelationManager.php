<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ErrorLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'errorLogs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('error_message')
                    ->label('Message d\'erreur')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('file_path')
                    ->label('Fichier')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('line')
                    ->label('Ligne')
                    ->required()
                    ->numeric(),
                    
                Forms\Components\Select::make('environment')
                    ->label('Environnement')
                    ->options([
                        'production' => 'Production',
                        'staging' => 'Staging',
                        'testing' => 'Testing',
                        'local' => 'Local',
                    ])
                    ->required(),
                    
                Forms\Components\Select::make('level')
                    ->label('Niveau')
                    ->options([
                        'debug' => 'Debug',
                        'info' => 'Info',
                        'notice' => 'Notice',
                        'warning' => 'Warning',
                        'error' => 'Error',
                        'critical' => 'Critical',
                        'alert' => 'Alert',
                        'emergency' => 'Emergency',
                    ])
                    ->required(),
                    
                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options([
                        'new' => 'Nouveau',
                        'in_progress' => 'En cours',
                        'resolved' => 'Résolu',
                        'ignored' => 'Ignoré',
                    ])
                    ->required(),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('error_message')
            ->columns([
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Message d\'erreur')
                    ->searchable()
                    ->limit(50),
                    
                Tables\Columns\TextColumn::make('file_path')
                    ->label('Fichier')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('line')
                    ->label('Ligne')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('level')
                    ->label('Niveau')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'debug' => 'gray',
                        'info' => 'info',
                        'notice' => 'success',
                        'warning' => 'warning',
                        'error', 'critical', 'alert', 'emergency' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('environment')
                    ->label('Environnement')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'danger',
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        'ignored' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('occurrences')
                    ->label('Occurrences')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('error_timestamp')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('Niveau')
                    ->options([
                        'debug' => 'Debug',
                        'info' => 'Info',
                        'notice' => 'Notice',
                        'warning' => 'Warning',
                        'error' => 'Error',
                        'critical' => 'Critical',
                        'alert' => 'Alert',
                        'emergency' => 'Emergency',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'new' => 'Nouveau',
                        'in_progress' => 'En cours',
                        'resolved' => 'Résolu',
                        'ignored' => 'Ignoré',
                    ]),
                    
                Tables\Filters\SelectFilter::make('environment')
                    ->label('Environnement')
                    ->options([
                        'production' => 'Production',
                        'staging' => 'Staging',
                        'testing' => 'Testing',
                        'local' => 'Local',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markAsInProgress')
                    ->label('En cours')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(fn ($record) => $record->markAsInProgress())
                    ->hidden(fn ($record) => $record->status === 'in_progress'),
                    
                Tables\Actions\Action::make('markAsResolved')
                    ->label('Résolu')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($record) => $record->markAsResolved())
                    ->hidden(fn ($record) => $record->status === 'resolved'),
                    
                Tables\Actions\Action::make('markAsIgnored')
                    ->label('Ignorer')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->action(fn ($record) => $record->markAsIgnored())
                    ->hidden(fn ($record) => $record->status === 'ignored'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsInProgress')
                        ->label('Marquer en cours')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->markAsInProgress()),
                        
                    Tables\Actions\BulkAction::make('markAsResolved')
                        ->label('Marquer comme résolu')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->markAsResolved()),
                        
                    Tables\Actions\BulkAction::make('markAsIgnored')
                        ->label('Marquer comme ignoré')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->action(fn ($records) => $records->each->markAsIgnored()),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('error_timestamp', 'desc');
    }
}
