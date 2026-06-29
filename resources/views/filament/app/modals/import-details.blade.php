@if ($import === null)
    <p class="text-sm text-gray-500 dark:text-gray-400">Importação não encontrada.</p>
@else
    <div class="space-y-4 text-sm">
        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Programa</dt>
                <dd class="font-medium text-gray-950 dark:text-white">{{ $import->program?->name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Arquivo</dt>
                <dd class="break-all font-medium text-gray-950 dark:text-white">{{ $import->original_filename }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                <dd>
                    <x-filament::badge :color="$import->status->color()">
                        {{ $import->status->label() }}
                    </x-filament::badge>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Linhas</dt>
                <dd class="tabular-nums text-gray-950 dark:text-white">
                    {{ $import->processed_rows }} processadas / {{ $import->failed_rows }} com erro
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Iniciado</dt>
                <dd class="text-gray-950 dark:text-white">{{ $import->started_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Concluído</dt>
                <dd class="text-gray-950 dark:text-white">{{ $import->finished_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>
            </div>
        </dl>

        @if (filled($import->error_message))
            <div>
                <p class="mb-1 font-medium text-red-600 dark:text-red-400">Erros do processamento</p>
                <pre class="max-h-60 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-50 p-3 text-xs leading-relaxed text-gray-700 dark:bg-white/5 dark:text-gray-300">{{ $import->error_message }}</pre>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">Nenhum erro registrado.</p>
        @endif
    </div>
@endif
