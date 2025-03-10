<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport d'erreurs - Supervision</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-bottom: 3px solid #5c6ac4;
        }
        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-box {
            flex: 0 0 30%;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 10px;
        }
        .stat-box.total {
            border-left: 5px solid #5c6ac4;
        }
        .stat-box.critical {
            border-left: 5px solid #e53e3e;
        }
        .stat-box.warning {
            border-left: 5px solid #ed8936;
        }
        .project-section {
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
        }
        .project-header {
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e2e8f0;
            font-weight: bold;
        }
        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .error-item {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        .error-item:last-child {
            border-bottom: none;
        }
        .error-level {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 10px;
        }
        .error-level.error {
            background-color: #fed7d7;
            color: #e53e3e;
        }
        .error-level.warning {
            background-color: #feebc8;
            color: #dd6b20;
        }
        .error-level.info {
            background-color: #bee3f8;
            color: #3182ce;
        }
        .error-level.debug {
            background-color: #e9d8fd;
            color: #805ad5;
        }
        .error-message {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .error-details {
            font-size: 13px;
            color: #718096;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #718096;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport d'erreurs - Supervision</h1>
        <p>Rapport généré le {{ now()->format('d/m/Y à H:i') }} pour la période depuis {{ $since }}</p>
    </div>

    <div class="stats">
        <div class="stat-box total">
            <h3>Total d'erreurs</h3>
            <div style="font-size: 24px; font-weight: bold;">{{ $totalErrors }}</div>
        </div>
        <div class="stat-box critical">
            <h3>Erreurs critiques</h3>
            <div style="font-size: 24px; font-weight: bold;">{{ $criticalErrors }}</div>
        </div>
        <div class="stat-box warning">
            <h3>Avertissements</h3>
            <div style="font-size: 24px; font-weight: bold;">{{ $warningErrors }}</div>
        </div>
    </div>

    @foreach($errorsByProject as $projectName => $projectErrors)
    <div class="project-section">
        <div class="project-header">
            {{ $projectName }} ({{ count($projectErrors) }} erreurs)
        </div>
        <ul class="error-list">
            @foreach($projectErrors as $error)
            <li class="error-item">
                <div>
                    <span class="error-level {{ $error->level }}">{{ $error->level }}</span>
                    <span>{{ $error->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="error-message">{{ Str::limit($error->error_message, 100) }}</div>
                <div class="error-details">
                    @if($error->file_path)
                        Fichier: {{ basename($error->file_path) }} 
                        @if($error->line) 
                            (ligne {{ $error->line }})
                        @endif
                    @endif
                    @if($error->environment)
                        | Environnement: {{ $error->environment }}
                    @endif
                    @if($error->occurrences > 1)
                        | Occurrences: {{ $error->occurrences }}
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endforeach

    <div class="footer">
        <p>Ce rapport a été généré automatiquement par le système Supervision.</p>
        <p>Pour accéder au tableau de bord complet, <a href="{{ url('/') }}">cliquez ici</a>.</p>
    </div>
</body>
</html>
