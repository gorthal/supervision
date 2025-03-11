<?php

namespace App\Filament\Resources\ErrorLogResource\Pages;

use App\Filament\Resources\ErrorLogResource;
use App\Models\ErrorLog;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;

class SimilarErrorLogs extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    
    protected static string $resource = ErrorLogResource::class;

    protected static string $view = 'filament.resources.error-log-resource.pages.similar-error-logs';
    
    public ErrorLog $record;
    
    public function getTitle(): string
    {
        return 'Erreurs similaires à : ' . substr($this->record->error_message, 0, 50) . '...';
    }
    
    public function getSubheading(): string
    {
        return 'Fichier : ' . $this->record->file_path . ' (ligne ' . $this->record->line . ')';
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(ErrorLog::findSimilar($this->record))
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Projet'),
                
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Message d\'erreur')
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('file_path')
                    ->label('Fichier')
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('line')
                    ->label('Ligne'),
                
                Tables\Columns\TextColumn::make('occurrences')
                    ->label('Occurrences')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('error_timestamp')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
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
                    }),
            ])
            ->defaultSort('error_timestamp', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('mergeSelectedErrors')
                    ->label('Fusionner en une seule erreur')
                    ->button()
                    ->icon('heroicon-o-document-duplicate')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Fusionner les erreurs sélectionnées')
                    ->modalDescription('Êtes-vous sûr de vouloir fusionner ces erreurs en une seule ? Cette action est irréversible.')
                    ->modalSubmitActionLabel('Oui, fusionner')
                    ->action(function (array $data, Collection $records): void {
                        // Ajouter l'erreur principale à la collection des erreurs à fusionner
                        $records->push($this->record);
                        
                        // Trouver l'erreur la plus récente comme représentante
                        $primary = $records->sortByDesc('error_timestamp')->first();
                        $totalOccurrences = $primary->occurrences;
                        $mergedCount = 0;
                        
                        // Fusionner les autres erreurs dans l'erreur principale
                        foreach ($records as $error) {
                            if ($error->id !== $primary->id) {
                                $totalOccurrences += $error->occurrences;
                                $mergedCount++;
                                $error->delete();
                            }
                        }
                        
                        // Mettre à jour le compteur d'occurrences
                        $primary->occurrences = $totalOccurrences;
                        $primary->save();
                        
                        // Notifier l'utilisateur
                        Notification::make()
                            ->title('Fusion réussie')
                            ->body($mergedCount . ' erreurs ont été fusionnées en une seule.')
                            ->success()
                            ->send();
                            
                        // Rediriger vers la page de visualisation de l'erreur principale
                        $this->redirect(ErrorLogResource::getUrl('view', ['record' => $primary]));
                    }),
            ])
            ->checkboxes()
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
                ]),
            ]);
    }
}
