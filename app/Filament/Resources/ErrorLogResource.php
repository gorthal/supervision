<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ErrorLogResource\Pages;
use App\Filament\Resources\ErrorLogResource\RelationManagers;
use App\Models\ErrorLog;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ErrorLogResource extends Resource
{
    protected static ?string $model = ErrorLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static ?string $navigationGroup = 'Supervision';
    
    protected static ?int $navigationSort = 20;
    
    protected static ?string $recordTitleAttribute = 'error_message';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->label('Projet')
                    ->options(Project::query()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                
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
                    
                Forms\Components\DateTimePicker::make('error_timestamp')
                    ->label('Date et heure')
                    ->required(),
                
                Forms\Components\TextInput::make('occurrences')
                    ->label('Occurrences')
                    ->required()
                    ->numeric()
                    ->default(1),
                    
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Projet')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Message d\'erreur')
                    ->searchable()
                    ->limit(50),
                    
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
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('file_path')
                    ->label('Fichier')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('line')
                    ->label('Ligne')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('environment')
                    ->label('Environnement')
                    ->searchable()
                    ->sortable(),
                    
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
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('occurrences')
                    ->label('Occurrences')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('error_timestamp')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Projet')
                    ->options(Project::query()->pluck('name', 'id'))
                    ->searchable(),
                
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
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Créé depuis'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Créé jusqu\'à'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('findSimilarErrors')
                    ->label('Erreurs similaires')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->url(fn ($record) => static::getUrl('similar-errors', ['record' => $record])),
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
                    
                    Tables\Actions\BulkAction::make('mergeSimilarErrors')
                        ->label('Fusionner les erreurs similaires')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function ($records) {
                            // Regrouper par combinaison de projet_id, file_path et line
                            $groupedErrors = $records->groupBy(function ($error) {
                                return "{$error->project_id}_{$error->file_path}_{$error->line}";
                            });
                            
                            $mergedCount = 0;
                            
                            foreach ($groupedErrors as $group) {
                                if ($group->count() > 1) {
                                    // Trouver l'erreur la plus récente pour être le représentant du groupe
                                    $primary = $group->sortByDesc('error_timestamp')->first();
                                    $totalOccurrences = $primary->occurrences;
                                    
                                    // Fusionner les autres erreurs dans celle-ci
                                    foreach ($group as $error) {
                                        if ($error->id !== $primary->id) {
                                            $totalOccurrences += $error->occurrences;
                                            $error->delete();
                                            $mergedCount++;
                                        }
                                    }
                                    
                                    // Mettre à jour le nombre d'occurrences
                                    $primary->occurrences = $totalOccurrences;
                                    $primary->save();
                                }
                            }
                            
                            return "Fusion réussie : $mergedCount erreurs similaires ont été fusionnées.";
                        }),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('error_timestamp', 'desc');
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations sur l\'erreur')
                    ->schema([
                        Infolists\Components\TextEntry::make('project.name')
                            ->label('Projet'),
                        Infolists\Components\TextEntry::make('environment')
                            ->label('Environnement')
                            ->badge(),
                        Infolists\Components\TextEntry::make('level')
                            ->label('Niveau')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'debug' => 'gray',
                                'info' => 'info',
                                'notice' => 'success',
                                'warning' => 'warning',
                                'error', 'critical', 'alert', 'emergency' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'new' => 'danger',
                                'in_progress' => 'warning',
                                'resolved' => 'success',
                                'ignored' => 'gray',
                                default => 'gray',
                            }),
                    ])
                    ->columns(4),
                
                Infolists\Components\Section::make('Détails de l\'erreur')
                    ->schema([
                        Infolists\Components\TextEntry::make('error_message')
                            ->label('Message d\'erreur')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('file_path')
                            ->label('Fichier'),
                        Infolists\Components\TextEntry::make('line')
                            ->label('Ligne'),
                    ]),
                
                Infolists\Components\Section::make('Statistiques')
                    ->schema([
                        Infolists\Components\TextEntry::make('occurrences')
                            ->label('Nombre d\'occurrences'),
                        Infolists\Components\TextEntry::make('error_timestamp')
                            ->label('Dernière occurrence')
                            ->dateTime('d/m/Y H:i:s'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Première détection')
                            ->dateTime('d/m/Y H:i:s'),
                    ]),
                
                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
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
            'index' => Pages\ListErrorLogs::route('/'),
            'create' => Pages\CreateErrorLog::route('/create'),
            'view' => Pages\ViewErrorLog::route('/{record}'),
            'edit' => Pages\EditErrorLog::route('/{record}/edit'),
            'similar-errors' => Pages\SimilarErrorLogs::route('/{record}/similar-errors'),
        ];
    }
}
