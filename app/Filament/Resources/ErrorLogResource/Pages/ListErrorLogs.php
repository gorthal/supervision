<?php

namespace App\Filament\Resources\ErrorLogResource\Pages;

use App\Filament\Resources\ErrorLogResource;
use App\Models\ErrorLog;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ErrorLogsExport;

class ListErrorLogs extends ListRecords
{
    protected static string $resource = ErrorLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('exportExcel')
                ->label('Exporter Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $timestamp = now()->format('Y-m-d_H-i-s');
                    $filename = "error-logs-{$timestamp}.xlsx";
                    
                    // Création du fichier Excel
                    Excel::store(new ErrorLogsExport(), $filename, 'local');
                    
                    // Téléchargement du fichier
                    return response()->download(
                        Storage::path($filename),
                        $filename,
                        ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                    )->deleteFileAfterSend();
                }),
                
            Actions\Action::make('purgeErrors')
                ->label('Purger les erreurs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Purger toutes les erreurs non résolues')
                ->modalDescription('Êtes-vous sûr de vouloir supprimer toutes les erreurs qui ne sont pas en statut "résolu" ? Cette action est irréversible.')
                ->modalSubmitActionLabel('Oui, purger les erreurs')
                ->action(function () {
                    $count = ErrorLog::where('status', '!=', 'resolved')->delete();
                    
                    $this->notify('success', "{$count} erreurs ont été supprimées avec succès.");
                }),
        ];
    }
}
