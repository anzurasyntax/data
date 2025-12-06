<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }
        .excel-cell {
            min-width: 140px;
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .column-header {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .stat-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            margin: 2px;
            letter-spacing: 0.3px;
        }
        .data-table tbody tr:hover {
            background-color: #f1f5f9;
        }
        .section-card {
            background: white;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .section-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .action-btn {
            transition: all 0.2s;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-slate-50 p-8">

<!-- Page Header -->
<div class="mb-6 bg-white shadow-sm rounded-lg p-6 border border-slate-200">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 mb-1">{{ $file->original_name }}</h2>
            <div class="flex items-center gap-6 text-sm text-slate-600 mt-2">
                <span class="inline-flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <strong class="font-semibold text-slate-700">{{ number_format($result['rows']) }}</strong>&nbsp;rows
                </span>
                <span class="inline-flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    <strong class="font-semibold text-slate-700">{{ $result['columns'] }}</strong>&nbsp;columns
                </span>
                @if($result['total_duplicate_rows'] > 0)
                    <span class="inline-flex items-center text-amber-700 bg-amber-50 px-3 py-1 rounded-full">
                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $result['total_duplicate_rows'] }} duplicate rows detected
                    </span>
                @endif
            </div>
        </div>
        <button class="px-5 py-2.5 bg-slate-700 hover:bg-slate-800 text-white font-semibold rounded-lg shadow-sm action-btn flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export CSV
        </button>
    </div>
</div>

<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 lg:col-span-8 bg-white shadow-sm rounded-lg border border-slate-200 overflow-hidden">
        <div class="bg-slate-700 px-6 py-4 border-b border-slate-600">
            <h3 class="text-lg font-bold text-white flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Data Preview
            </h3>
        </div>

        <div class="overflow-auto max-h-[75vh]">
            <table class="min-w-full border-collapse text-sm data-table">
                <thead>
                <tr class="column-header">
                    @foreach($result['columns_list'] as $col)
                        @php
                            $stats = $result['column_stats'][$col] ?? [];
                            $typeColors = [
                                'number' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'text' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'date' => 'bg-violet-50 text-violet-700 border-violet-200',
                                'text-number' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'empty' => 'bg-slate-50 text-slate-600 border-slate-200'
                            ];
                            $typeIcons = [
                                'number' => '123',
                                'text' => 'ABC',
                                'date' => 'CAL',
                                'text-number' => 'A1',
                                'empty' => '—'
                            ];
                            $typeColor = $typeColors[$stats['data_type']] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                            $typeLabel = $typeIcons[$stats['data_type']] ?? '?';
                        @endphp
                        <th class="border-r border-slate-200 p-4 text-left excel-cell">
                            <div class="font-bold text-slate-800 mb-3 flex items-center text-sm">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded bg-slate-100 text-slate-600 text-xs font-bold mr-2">
                                    {{ $typeLabel }}
                                </span>
                                <span class="truncate">{{ $col }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span class="stat-badge {{ $typeColor }} border">
                                    {{ ucfirst(str_replace('-', ' ', $stats['data_type'] ?? 'unknown')) }}
                                </span>
                                @if(($stats['empty_count'] ?? 0) > 0)
                                    <span class="stat-badge bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $stats['empty_count'] }} empty
                                    </span>
                                @else
                                    <span class="stat-badge bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Complete
                                    </span>
                                @endif
                                @if(($stats['duplicate_count'] ?? 0) > 0)
                                    <span class="stat-badge bg-orange-50 text-orange-700 border border-orange-200">
                                        {{ $stats['duplicate_count'] }} dupes
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500 mt-2 font-medium">
                                {{ number_format($stats['unique_values'] ?? 0) }} unique
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
                            @endphp
                            <td class="border border-slate-200 p-3 excel-cell {{ $isEmpty ? 'bg-rose-50' : '' }}">
                                @if($isEmpty)
                                    <span class="text-slate-400 italic text-xs font-medium">—</span>
                                @else
                                    <span class="text-slate-700">{{ $value }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-span-12 lg:col-span-4 space-y-5">
        <div class="bg-white shadow-sm rounded-lg border border-slate-200 overflow-hidden">
            <div class="bg-slate-700 px-6 py-4 border-b border-slate-600">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Data Overview
                </h3>
            </div>

            <div class="p-5">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-slate-800">{{ number_format($result['rows']) }}</div>
                        <div class="text-xs text-slate-600 mt-1 font-medium">Total Rows</div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-slate-800">{{ $result['columns'] }}</div>
                        <div class="text-xs text-slate-600 mt-1 font-medium">Columns</div>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-amber-700">{{ $result['total_duplicate_rows'] }}</div>
                        <div class="text-xs text-amber-700 mt-1 font-medium">Duplicates</div>
                    </div>
                    <div class="bg-rose-50 border border-rose-200 p-4 rounded-lg">
                        @php
                            $totalEmpty = collect($result['column_stats'])->sum('empty_count');
                        @endphp
                        <div class="text-2xl font-bold text-rose-700">{{ number_format($totalEmpty) }}</div>
                        <div class="text-xs text-rose-700 mt-1 font-medium">Empty Cells</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white shadow-sm rounded-lg border border-slate-200 overflow-hidden">
            <div class="bg-slate-700 px-6 py-4 border-b border-slate-600">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    Data Cleaning
                </h3>
            </div>

            <div class="p-5 space-y-3">
                <div class="section-card p-4 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-rose-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Remove Empty Rows</h4>
                            <p class="text-slate-600 text-xs mt-1">Delete rows containing only null values</p>
                            <button class="mt-3 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs rounded-lg action-btn w-full">
                                Clean Empty Rows
                            </button>
                        </div>
                    </div>
                </div>

                <div class="section-card p-4 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Remove Duplicates</h4>
                            <p class="text-slate-600 text-xs mt-1">Found {{ $result['total_duplicate_rows'] }} duplicate entries</p>
                            <button class="mt-3 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs rounded-lg action-btn w-full">
                                Remove Duplicates
                            </button>
                        </div>
                    </div>
                </div>
                <div class="section-card p-4 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Trim Whitespace</h4>
                            <p class="text-slate-600 text-xs mt-1">Remove extra spaces from all cells</p>
                            <button class="mt-3 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs rounded-lg action-btn w-full">
                                Trim Spaces
                            </button>
                        </div>
                    </div>
                </div>
                <div class="section-card p-4 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Fix Data Types</h4>
                            <p class="text-slate-600 text-xs mt-1">Convert text to proper formats</p>
                            <button class="mt-3 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs rounded-lg action-btn w-full">
                                Convert Types
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-200">
                    <button class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-lg w-full action-btn flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Save Cleaned File
                    </button>
                </div>
            </div>
        </div>

    </div>

</div>

</body>
</html>
