<x-filament::page>
    <div class="mb-6">
        <div class="bg-primary-50 dark:bg-primary-950 rounded-lg p-4 border border-primary-200 dark:border-primary-800">
            <h3 class="text-primary-700 dark:text-primary-300 font-medium text-lg">Erreur de référence</h3>
            <div class="mt-2 grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Projet</p>
                    <p class="font-medium">{{ $record->project->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fichier</p>
                    <p class="font-medium">{{ $record->file_path }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ligne</p>
                    <p class="font-medium">{{ $record->line }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Occurrences</p>
                    <p class="font-medium">{{ $record->occurrences }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Message d'erreur</p>
                    <div class="mt-1 p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md">
                        <p class="whitespace-pre-wrap">{{ $record->error_message }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mb-4">
        <h2 class="text-xl font-bold">Erreurs similaires</h2>
        <p class="text-gray-500 dark:text-gray-400">Ces erreurs ont été détectées comme similaires à l'erreur de référence.</p>
    </div>
    
    {{ $this->table }}
</x-filament::page>
