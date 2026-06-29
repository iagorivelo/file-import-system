<x-filament-panels::page>
    <div class="flex flex-col gap-10">
        {{-- Grade de programas (boxes) --}}
        <div class="flex flex-col gap-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Selecione um programa para importar um arquivo.
            </p>

            <div class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-5">
                @forelse ($this->getPrograms() as $program)
                    <button
                        type="button"
                        wire:click="mountAction('import', { program: {{ $program->getKey() }} })"
                        style="background-color: {{ $program->color }}"
                        title="Importar arquivo para {{ $program->name }}"
                        class="group relative flex aspect-square flex-col items-center justify-center gap-3 overflow-hidden rounded-2xl p-4 text-white shadow-sm ring-1 ring-black/5 transition duration-200 hover:-translate-y-1 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                    >
                        <span class="text-5xl font-light leading-none">{{ $program->initial() }}</span>
                        <span class="text-sm font-semibold leading-tight">{{ $program->name }}</span>
                    </button>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                        Nenhum programa disponível no momento.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Importações recentes (atualiza automaticamente) --}}
        <div class="flex flex-col gap-4" wire:poll.5s>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                Importações recentes
            </h2>

            @php($imports = $this->getRecentImports())

            @if ($imports->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Você ainda não realizou importações.
                </p>
            @else
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10 dark:text-gray-400">
                                <th class="px-4 py-3 font-medium">Programa</th>
                                <th class="px-4 py-3 font-medium">Arquivo</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Linhas</th>
                                <th class="px-4 py-3 font-medium">Enviado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($imports as $import)
                                <tr @class([
                                    'transition',
                                    'hover:bg-gray-50 dark:hover:bg-white/5' => ! $import->hasErrors(),
                                    'bg-red-50/60 dark:bg-red-500/10' => $import->hasErrors(),
                                ])>
                                    <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                        {{ $import->program?->name }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        <span class="block">{{ $import->original_filename }}</span>
                                        @if ($import->hasErrors() && filled($import->error_message))
                                            <span
                                                class="mt-0.5 block max-w-xs truncate text-xs text-red-600 dark:text-red-400"
                                                title="{{ $import->error_message }}"
                                            >
                                                {{ str($import->error_message)->before("\n")->limit(90) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-filament::badge :color="$import->status->color()">
                                            {{ $import->status->label() }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-3 tabular-nums text-gray-600 dark:text-gray-300">
                                        {{ $import->processed_rows }} OK / {{ $import->failed_rows }} erro
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $import->created_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            type="button"
                                            wire:click="mountAction('viewImport', { import: {{ $import->getKey() }} })"
                                            class="text-sm font-medium text-green-700 hover:underline dark:text-green-400"
                                        >
                                            Detalhes
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
