<?php

namespace App\Filament\Widgets;

use App\Models\ErrorLog;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ErrorStatsOverview extends BaseWidget
{
    protected static ?int $sort = 10;
    
    protected function getStats(): array
    {
        $totalProjects = Project::where('is_active', true)->count();
        
        $totalErrors = ErrorLog::count();
        $newErrors = ErrorLog::where('status', 'new')->count();
        $inProgressErrors = ErrorLog::where('status', 'in_progress')->count();
        
        $criticalErrors = ErrorLog::whereIn('level', ['critical', 'alert', 'emergency'])->count();
        
        $today = now()->startOfDay();
        $errorsToday = ErrorLog::where('error_timestamp', '>=', $today)->count();
        
        return [
            Stat::make('Projets actifs', $totalProjects)
                ->description('Projets sous surveillance')
                ->icon('heroicon-o-rectangle-stack')
                ->color('info'),
                
            Stat::make('Total des erreurs', $totalErrors)
                ->description('Toutes erreurs confondues')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('gray'),
                
            Stat::make('Nouvelles erreurs', $newErrors)
                ->description('Erreurs non traitées')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger'),
                
            Stat::make('Erreurs en cours', $inProgressErrors)
                ->description('En cours de traitement')
                ->icon('heroicon-o-arrow-path')
                ->color('warning'),
                
            Stat::make('Erreurs critiques', $criticalErrors)
                ->description('Priorité maximale')
                ->icon('heroicon-o-bolt')
                ->color('danger'),
                
            Stat::make('Erreurs aujourd\'hui', $errorsToday)
                ->description('Depuis minuit')
                ->icon('heroicon-o-clock')
                ->color('info'),
        ];
    }
}
