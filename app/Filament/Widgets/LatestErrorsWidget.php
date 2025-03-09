<?php

namespace App\Filament\Widgets;

use App\Models\ErrorLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestErrorsWidget extends BaseWidget
{
    protected static ?int $sort = 20;
    
    protected int|string|array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                ErrorLog::query()
                    ->latest('error_timestamp')
                    ->where('status', 'new')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Projet')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Message')
                    ->limit(50)
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
                    }),
                
                Tables\Columns\TextColumn::make('environment')
                    ->label('Env'),
                
                Tables\Columns\TextColumn::make('error_timestamp')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('file_path')
                    ->label('Fichier')
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('line')
                    ->label('Ligne'),
                
                Tables\Columns\TextColumn::make('occurrences')
                    ->label('Occ.'),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsInProgress')
                    ->label('En cours')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->action(fn ($record) => $record->markAsInProgress()),
                
                Tables\Actions\Action::make('markAsResolved')
                    ->label('Résolu')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(fn ($record) => $record->markAsResolved()),
                
                Tables\Actions\Action::make('markAsIgnored')
                    ->label('Ignorer')
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->action(fn ($record) => $record->markAsIgnored()),
            ])
            ->paginated(false)
            ->heading('Dernières erreurs non traitées');
    }
}
