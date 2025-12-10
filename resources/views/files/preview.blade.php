<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }
        .excel-cell {
            min-width: 160px;
            max-width: 320px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .column-header {
            background: #f8fafc;
            border-bottom: 2px solid #334155;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .stat-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 4px;
            margin: 2px;
            letter-spacing: 0.3px;
        }
        .data-table tbody tr {
            transition: background-color 0.15s ease;
        }
        .data-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 24px;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            border-color: #94a3b8;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin: 12px 0 8px 0;
        }
        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .table-container {
            background: white;
            border: 1px solid #e2e8f0;
        }
        .header-section {
            background: #1e293b;
            padding: 20px 28px;
            border-bottom: 1px solid #334155;
        }
        .type-badge {
            width: 36px;
            height: 36px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            margin-right: 10px;
            background: white;
            color: #475569;
        }
        .column-name {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            letter-spacing: -0.01em;
        }
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: white;
            letter-spacing: -0.01em;
        }
        .empty-cell {
            background: #fef2f2;
        }
        .cell-value {
            color: #334155;
            font-weight: 500;
            font-size: 13px;
        }
        .icon-svg {
            width: 20px;
            height: 20px;
            stroke-width: 2;
        }
    </style>
</head>

<body class="bg-slate-100 p-8">

<div class="grid grid-cols-12 gap-6">

    <div class="col-span-10">
        <div class="table-container">
            <div class="header-section">
                <h3 class="section-title flex items-center">
                    <svg class="icon-svg mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Data Preview
                </h3>
            </div>

            <div class="overflow-auto max-h-[70vh]">
                <table class="min-w-full border-collapse text-sm data-table">
                    <thead>
                    <tr class="column-header">
                        @foreach($result['columns_list'] as $col)
                            @php
                                $stats = $result['column_stats'][$col] ?? [];
                                $typeColors = [
                                    'number' => 'bg-slate-100 text-slate-700 border-slate-300',
                                    'text' => 'bg-slate-100 text-slate-700 border-slate-300',
                                    'date' => 'bg-slate-100 text-slate-700 border-slate-300',
                                    'text-number' => 'bg-slate-100 text-slate-700 border-slate-300',
                                    'empty' => 'bg-slate-50 text-slate-600 border-slate-200'
                                ];
                                $typeIcons = [
                                    'number' => '123',
                                    'text' => 'ABC',
                                    'date' => 'DATE',
                                    'text-number' => 'A1',
                                    'empty' => '—'
                                ];
                                $typeColor = $typeColors[$stats['data_type']] ?? 'bg-slate-100 text-slate-700 border-slate-300';
                                $typeLabel = $typeIcons[$stats['data_type']] ?? '?';
                            @endphp
                            <th class="border-r border-slate-200 p-4 text-left excel-cell relative group">
                            <div class="flex items-center mb-3">
                                    <span class="type-badge">
                                        {{ $typeLabel }}
                                    </span>
                                    <span class="column-name truncate">{{ $col }}</span>
                                </div>
                                @if(!empty($stats['stats']))
                                    <div class="absolute hidden group-hover:block bg-white border border-slate-300 shadow-lg rounded p-3 text-xs z-50 w-48">
                                        <div><b>Min:</b> {{ $stats['stats']['min'] }}</div>
                                        <div><b>Max:</b> {{ $stats['stats']['max'] }}</div>
                                        <div><b>Mean:</b> {{ $stats['stats']['mean'] }}</div>
                                        <div><b>Median:</b> {{ $stats['stats']['median'] }}</div>
                                        <div><b>Std:</b> {{ $stats['stats']['std'] }}</div>
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <span class="stat-badge {{ $typeColor }} border">
                                        {{ ucfirst(str_replace('-', ' ', $stats['data_type'] ?? 'unknown')) }}
                                    </span>
                                    @if(($stats['empty_count'] ?? 0) > 0)
                                        <span class="stat-badge bg-rose-100 text-rose-800 border border-rose-300">
                                            {{ $stats['empty_count'] }} empty
                                        </span>
                                    @else
                                        <span class="stat-badge bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            Complete
                                        </span>
                                    @endif
                                    @if(($stats['duplicate_count'] ?? 0) > 0)
                                        <span class="stat-badge bg-amber-100 text-amber-800 border border-amber-300">
                                            {{ $stats['duplicate_count'] }} duplicates
                                        </span>
                                    @endif
                                    @if(($stats['outlier_count'] ?? 0) > 0)
                                        <span class="stat-badge bg-purple-100 text-purple-800 border border-purple-300">
                                            {{ $stats['outlier_count'] }} outliers
                                        </span>
                                    @else
                                        <span class="stat-badge bg-purple-100 text-purple-800 border border-purple-300">
                                            No outliers Detect
                                        </span>
                                    @endif

                                </div>
                                <div class="text-xs text-slate-600 mt-2 font-semibold">
                                    {{ number_format($stats['unique_values'] ?? 0) }} unique values
                                </div>
                            </th>
                        @endforeach
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($result['data'] as $index => $row)
                        <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-slate-50' }}">
                            @foreach($result['columns_list'] as $col)
                                @php
                                    $value = $row[$col] ?? '';
                                    $isEmpty = $value === '' || $value === null;
                                    $isOutlier = isset($result['outlier_map'][$index][$col]);
                                @endphp
                                <td class="border border-slate-200 p-3 excel-cell {{ $isEmpty ? 'empty-cell' : '' }} {{ $isOutlier ? 'bg-purple-100 text-purple-900 font-semibold' : '' }}">
                                    @if($isEmpty)
                                        <span class="text-slate-400 italic text-xs">Empty</span>
                                    @else
                                        <span class="cell-value">{{ $value }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-span-2">
        <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-5">
            <!-- Total Rows Card -->
            <div class="stat-card">
                <div class="stat-label">Total Rows</div>
                <div class="stat-value text-slate-800">{{ number_format($result['rows']) }}</div>
                <div class="text-xs text-slate-500 mt-1">Complete dataset records</div>
            </div>

            <!-- Columns Card -->
            <div class="stat-card">
                <div class="stat-label">Data Columns</div>
                <div class="stat-value text-slate-800">{{ $result['columns'] }}</div>
                <div class="text-xs text-slate-500 mt-1">Attributes tracked</div>
            </div>

            <!-- Duplicates Card -->
            <div class="stat-card border-amber-200 bg-amber-50">
                <div class="stat-label text-amber-900">Duplicate Rows</div>
                <div class="stat-value text-amber-900">{{ $result['total_duplicate_rows'] }}</div>
                <div class="text-xs text-amber-700 mt-1">Requires attention</div>
            </div>

            <!-- Empty Cells Card -->
            <div class="stat-card border-rose-200 bg-rose-50">
                @php
                    $totalEmpty = collect($result['column_stats'])->sum('empty_count');
                @endphp
                <div class="stat-label text-rose-900">Empty Cells</div>
                <div class="stat-value text-rose-900">{{ number_format($totalEmpty) }}</div>
                <div class="text-xs text-rose-700 mt-1">Missing data points</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
