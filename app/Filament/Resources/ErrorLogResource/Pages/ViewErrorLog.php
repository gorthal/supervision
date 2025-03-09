<?php

namespace App\Filament\Resources\ErrorLogResource\Pages;

use App\Filament\Resources\ErrorLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewErrorLog extends ViewRecord
{
    protected static string $resource = ErrorLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('markAsInProgress')
                ->label('Marquer en cours')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(fn () => $this->record->markAsInProgress())
                ->hidden(fn () => $this->record->status === 'in_progress'),
                
            Actions\Action::make('markAsResolved')
                ->label('Marquer résolu')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(fn () => $this->record->markAsResolved())
                ->hidden(fn () => $this->record->status === 'resolved'),
                
            Actions\Action::make('markAsIgnored')
                ->label('Marquer ignoré')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action(fn () => $this->record->markAsIgnored())
                ->hidden(fn () => $this->record->status === 'ignored'),
        ];
    }
}
