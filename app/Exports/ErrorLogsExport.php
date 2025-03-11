<?php

namespace App\Exports;

use App\Models\ErrorLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ErrorLogsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return ErrorLog::query()
            ->with('project')
            ->latest('error_timestamp');
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Projet',
            'Message d\'erreur',
            'Fichier',
            'Ligne',
            'Niveau',
            'Environnement',
            'Statut',
            'Occurrences',
            'Date',
            'Notes',
            'Commentaire',
        ];
    }

    /**
     * @param ErrorLog $errorLog
     * @return array
     */
    public function map($errorLog): array
    {
        $statusLabels = [
            'new' => 'Nouveau',
            'in_progress' => 'En cours',
            'resolved' => 'Résolu',
            'ignored' => 'Ignoré',
        ];

        $levelLabels = [
            'debug' => 'Debug',
            'info' => 'Info',
            'notice' => 'Notice',
            'warning' => 'Warning',
            'error' => 'Error',
            'critical' => 'Critical',
            'alert' => 'Alert',
            'emergency' => 'Emergency',
        ];

        $environmentLabels = [
            'production' => 'Production',
            'staging' => 'Staging',
            'testing' => 'Testing',
            'local' => 'Local',
        ];

        return [
            $errorLog->id,
            $errorLog->project->name ?? 'N/A',
            $errorLog->error_message,
            $errorLog->file_path,
            $errorLog->line,
            $levelLabels[$errorLog->level] ?? $errorLog->level,
            $environmentLabels[$errorLog->environment] ?? $errorLog->environment,
            $statusLabels[$errorLog->status] ?? $errorLog->status,
            $errorLog->occurrences,
            $errorLog->error_timestamp ? $errorLog->error_timestamp->format('d/m/Y H:i:s') : 'N/A',
            $errorLog->notes,
            $errorLog->comment,
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour l'en-tête
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '0072C6'],
                ],
            ],
        ];
    }
}
