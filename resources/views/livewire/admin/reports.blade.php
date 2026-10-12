@php
    use App\Services\BusinessReport;
    use App\Support\Money;

    $report = $this->report;
    $monthly = $report->monthly();
    $totals = $report->totals();
    $services = $report->byService();
    $maxRevenue = max(1, max(array_column($monthly, 'revenueCents')));
    $currentMonth = $this->business->now()->format('Y-m');
    $statCards = [
        ['Ingresos', Money::format($totals['revenueCents']), 'wallet', 'bg-emerald-50 text-emerald-700', 'Citas confirmadas y atendidas'],
        ['Citas', number_format($totals['appointments']), 'calendar-check', 'bg-teal-50 text-teal-700', 'Sin contar las canceladas'],
        ['Ticket promedio', Money::format($totals['averageTicketCents']), 'tag', 'bg-sky-50 text-sky-700', 'Ingreso por cita cobrada'],
        ['Cancelaciones', number_format($totals['cancellationRate'] * 100, 0).' %', 'x', 'bg-rose-50 text-rose-700', trans_choice(':count cita cancelada|:count citas canceladas', $totals['cancelled'])],
    ];
@endphp

<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Reportes</h1>
            <p class="text-sm text-slate-500 first-letter:uppercase">
                {{ $report->from()->translatedFormat('F Y') }} – {{ $report->until()->subDay()->translatedFormat('F Y') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm" role="group" aria-label="Periodo">
                @foreach (BusinessReport::PERIODS as $period)
                    <button type="button" wire:click="setPeriod({{ $period }})" aria-pressed="{{ $months === $period ? 'true' : 'false' }}"
                            @class([
                                'px-3 py-2 text-sm font-semibold not-first:border-l not-first:border-slate-300',
                                'bg-slate-900 text-white' => $months === $period,
                                'text-slate-700 hover:bg-slate-50' => $months !== $period,
                            ])>{{ $period }} meses</button>
                @endforeach
            </div>
            <a href="{{ route('admin.reports.export', ['meses' => $months]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500 sm:px-4">
                <x-icon name="download" class="size-4" /> Exportar CSV
            </a>
        </div>
    </div>

    <dl class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4" wire:loading.class="opacity-60" wire:target="setPeriod">
        @foreach ($statCards as [$label, $value, $icon, $iconClasses, $hint])
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="hidden size-10 shrink-0 items-center justify-center rounded-lg min-[400px]:flex {{ $iconClasses }}"><x-icon :name="$icon" class="size-5" /></span>
                <div class="min-w-0">
                    <dt class="text-xs leading-4 font-medium text-slate-500">{{ $label }}</dt>
                    <dd class="truncate text-lg font-bold text-slate-900 tabular-nums sm:text-xl">{{ $value }}</dd>
                    <dd class="mt-0.5 text-xs leading-4 text-slate-500">{{ $hint }}</dd>
                </div>
            </div>
        @endforeach
    </dl>

    @if ($report->appointments()->isEmpty())
        <div class="mt-6 flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <span class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><x-icon name="chart" class="size-6" /></span>
            <p class="mt-3 font-medium text-slate-900">Todavía no hay citas en este periodo</p>
            <p class="mt-1 text-sm text-slate-500">Cuando recibas reservas, aquí verás tus ingresos por mes y por servicio.</p>
        </div>
    @else
        <div class="mt-6 grid gap-6 xl:grid-cols-5" wire:loading.class="opacity-60" wire:target="setPeriod">
            {{-- Income per month: one series, so no legend; the title names it --}}
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-3">
                <h2 class="font-semibold text-slate-900">Ingresos por mes</h2>
                <p class="text-sm text-slate-500">Citas confirmadas y atendidas. Pasa el cursor o toca un mes para ver el detalle.</p>

                <div class="relative mt-6">
                    <div class="pointer-events-none absolute inset-x-0 top-0 border-t border-dashed border-slate-200"></div>
                    <p class="pointer-events-none absolute -top-5 left-0 text-xs text-slate-500 tabular-nums">{{ Money::format($maxRevenue) }}</p>
                    <div class="flex h-56 items-end gap-0.5 border-b border-slate-300" role="list" aria-label="Ingresos por mes">
                        @foreach ($monthly as $row)
                            @php
                                $isCurrent = $row['month']->format('Y-m') === $currentMonth;
                                $height = $row['revenueCents'] / $maxRevenue * 100;
                                $detail = ucfirst($row['month']->translatedFormat('F Y')).': '.Money::format($row['revenueCents']).' · '.trans_choice(':count cita|:count citas', $row['appointments']);
                            @endphp
                            <div wire:key="month-{{ $row['month']->format('Y-m') }}" role="listitem" tabindex="0" aria-label="{{ $detail }}"
                                 x-data="{ show: false }" x-on:mouseenter="show = true" x-on:mouseleave="show = false" x-on:focus="show = true" x-on:blur="show = false"
                                 class="group relative flex h-full flex-1 items-end justify-center rounded-t-md focus:outline-none focus-visible:bg-slate-100 hover:bg-slate-50">
                                <div @class([
                                    'w-full max-w-12 rounded-t-[4px] transition-colors',
                                    'bg-teal-600 group-hover:bg-teal-700' => ! $isCurrent,
                                    'bg-teal-600/60 group-hover:bg-teal-600' => $isCurrent,
                                ]) style="height: {{ max($height, $row['revenueCents'] > 0 ? 1 : 0) }}%"></div>
                                <div x-show="show" x-cloak x-transition.opacity.duration.100ms
                                     class="absolute bottom-full left-1/2 z-10 mb-2 w-max max-w-48 -translate-x-1/2 rounded-lg bg-slate-900 px-3 py-2 text-xs text-white shadow-lg">
                                    <p class="font-semibold first-letter:uppercase">{{ $row['month']->translatedFormat('F Y') }}{{ $isCurrent ? ' (en curso)' : '' }}</p>
                                    <p class="tabular-nums">{{ Money::format($row['revenueCents']) }}</p>
                                    <p class="text-slate-300">{{ trans_choice(':count cita|:count citas', $row['appointments']) }} · {{ trans_choice(':count cancelada|:count canceladas', $row['cancelled']) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-0.5">
                        @foreach ($monthly as $row)
                            <p class="flex-1 truncate text-center text-xs text-slate-500">
                                {{ rtrim($row['month']->translatedFormat('M'), '.') }}@if ($row['month']->month === 1 || $loop->first)<span class="hidden sm:inline"> {{ $row['month']->format('y') }}</span>@endif
                            </p>
                        @endforeach
                    </div>
                </div>

                <details class="mt-5 border-t border-slate-100 pt-4 text-sm">
                    <summary class="cursor-pointer font-medium text-teal-700 hover:underline">Ver como tabla</summary>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-left tabular-nums">
                            <thead class="text-xs text-slate-500">
                                <tr><th class="py-2 pr-4 font-medium">Mes</th><th class="py-2 pr-4 text-right font-medium">Citas</th><th class="py-2 pr-4 text-right font-medium">Canceladas</th><th class="py-2 pr-4 text-right font-medium">No asistieron</th><th class="py-2 text-right font-medium">Ingresos</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach (array_reverse($monthly) as $row)
                                    <tr>
                                        <td class="py-2 pr-4 first-letter:uppercase">{{ $row['month']->translatedFormat('F Y') }}</td>
                                        <td class="py-2 pr-4 text-right">{{ $row['appointments'] }}</td>
                                        <td class="py-2 pr-4 text-right">{{ $row['cancelled'] }}</td>
                                        <td class="py-2 pr-4 text-right">{{ $row['noShows'] }}</td>
                                        <td class="py-2 text-right font-medium text-slate-900">{{ Money::format($row['revenueCents']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </section>

            {{-- Income per service --}}
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h2 class="font-semibold text-slate-900">Por servicio</h2>
                <p class="text-sm text-slate-500">Qué servicios dejan más ingresos en el periodo.</p>
                <ul class="mt-5 space-y-4">
                    @foreach ($services as $service)
                        <li wire:key="service-{{ $loop->index }}-{{ $service['name'] }}">
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <p class="truncate font-medium text-slate-900">{{ $service['name'] }}</p>
                                <p class="shrink-0 font-semibold text-slate-900 tabular-nums">{{ Money::format($service['revenueCents']) }}</p>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                                <div class="h-full rounded-full bg-teal-600" style="width: {{ round($service['share'] * 100, 1) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500 tabular-nums">{{ trans_choice(':count cita|:count citas', $service['appointments']) }} · {{ number_format($service['share'] * 100, 0) }} % de los ingresos</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    @endif
</div>
