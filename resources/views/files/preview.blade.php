<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Preview - Editable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
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
                    Data Preview (Editable)
                </h3>
            </div>

            <div class="overflow-auto max-h-[70vh]">
                <table class="min-w-full border-collapse text-sm data-table">
                    <thead id="table-header">
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
                            <th class="border-r border-slate-200 p-4 text-left excel-cell relative group" data-column="{{ $col }}">
                                <div class="flex items-center mb-3">
                                    <span class="type-badge">{{ $typeLabel }}</span>
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

                                <div class="flex flex-wrap gap-1.5 mt-2 header-badges">
                                    <span class="stat-badge {{ $typeColor }} border data-type-badge">
                                        {{ ucfirst(str_replace('-', ' ', $stats['data_type'] ?? 'unknown')) }}
                                    </span>
                                    @if(($stats['empty_count'] ?? 0) > 0)
                                        <span class="stat-badge bg-rose-100 text-rose-800 border border-rose-300 empty-badge">
                                            {{ $stats['empty_count'] }} empty
                                        </span>
                                    @else
                                        <span class="stat-badge bg-emerald-100 text-emerald-800 border border-emerald-300 empty-badge">
                                            Complete
                                        </span>
                                    @endif
                                    @if(($stats['duplicate_count'] ?? 0) > 0)
                                        <span class="stat-badge bg-amber-100 text-amber-800 border border-amber-300 duplicate-badge">
                                            {{ $stats['duplicate_count'] }} duplicates
                                        </span>
                                    @endif
                                    @if(($stats['outlier_count'] ?? 0) > 0)
                                        <span class="stat-badge bg-purple-100 text-purple-800 border border-purple-300 outlier-badge">
                                            {{ $stats['outlier_count'] }} outliers
                                        </span>
                                    @else
                                        <span class="stat-badge bg-purple-100 text-purple-800 border border-purple-300 outlier-badge">
                                            No outliers Detect
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-600 mt-2 font-semibold unique-count">
                                    {{ number_format($stats['unique_values'] ?? 0) }} unique values
                                </div>
                            </th>
                        @endforeach
                    </tr>
                    </thead>

                    <tbody id="table-body">
                    @foreach($result['data'] as $index => $row)
                        <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-slate-50' }}" data-row-index="{{ $index }}">
                            @foreach($result['columns_list'] as $col)
                                @php
                                    $value = $row[$col] ?? '';
                                    $isEmpty = $value === '' || $value === null;
                                    $isOutlier = isset($result['outlier_map'][$index][$col]);
                                @endphp
                                <td class="border border-slate-200 p-3 excel-cell editable-cell {{ $isEmpty ? 'empty-cell' : '' }} {{ $isOutlier ? 'bg-purple-100 text-purple-900 font-semibold' : '' }}"
                                    data-row="{{ $index }}"
                                    data-column="{{ $col }}"
                                    data-value="{{ $value }}">
                                    @if($isEmpty)
                                        <span class="text-slate-400 italic text-xs cell-display">Empty</span>
                                    @else
                                        <span class="cell-value cell-display">{{ $value }}</span>
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
        <div class="grid grid-cols-1 gap-5">
            <div class="stat-card">
                <div class="stat-label">Total Rows</div>
                <div class="stat-value text-slate-800">{{ number_format($result['rows']) }}</div>
                <div class="text-xs text-slate-500 mt-1">Complete dataset records</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Data Columns</div>
                <div class="stat-value text-slate-800">{{ $result['columns'] }}</div>
                <div class="text-xs text-slate-500 mt-1">Attributes tracked</div>
            </div>

            <div class="stat-card border-amber-200 bg-amber-50">
                <div class="stat-label text-amber-900">Duplicate Rows</div>
                <div class="stat-value text-amber-900" id="duplicate-count">{{ $result['total_duplicate_rows'] }}</div>
                <div class="text-xs text-amber-700 mt-1">Requires attention</div>
            </div>

            <div class="stat-card border-rose-200 bg-rose-50">
                @php
                    $totalEmpty = collect($result['column_stats'])->sum('empty_count');
                @endphp
                <div class="stat-label text-rose-900">Empty Cells</div>
                <div class="stat-value text-rose-900" id="empty-count">{{ number_format($totalEmpty) }}</div>
                <div class="text-xs text-rose-700 mt-1">Missing data points</div>
            </div>
        </div>
    </div>
</div>

<script>
    const FILE_ID = {{ $file->id }};
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    // Store current data state
    let currentData = @json($result['data']);
    let columnStats = @json($result['column_stats']);
    let outlierMap = @json($result['outlier_map']);

    // Handle cell click to edit
    document.querySelectorAll('.editable-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            if (this.querySelector('.cell-input')) return; // Already editing

            const currentValue = this.dataset.value;
            const displaySpan = this.querySelector('.cell-display');

            // Create input
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'cell-input';
            input.value = currentValue;

            // Hide display span
            displaySpan.style.display = 'none';
            this.appendChild(input);
            input.focus();
            input.select();

            // Handle save
            const saveEdit = async () => {
                const newValue = input.value.trim();
                const rowIndex = parseInt(this.dataset.row);
                const column = this.dataset.column;

                // If value unchanged, just cancel
                if (newValue === currentValue) {
                    input.remove();
                    displaySpan.style.display = '';
                    return;
                }

                // Show loading
                this.classList.add('updating');
                input.disabled = true;

                try {
                    const response = await fetch(`/files/${FILE_ID}/cell`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            row_index: rowIndex,
                            column: column,
                            value: newValue
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Update cell display
                        this.dataset.value = result.updated_value || '';
                        const isEmpty = !result.updated_value;

                        if (isEmpty) {
                            displaySpan.innerHTML = '<span class="text-slate-400 italic text-xs">Empty</span>';
                            this.classList.add('empty-cell');
                            this.classList.remove('bg-purple-100', 'text-purple-900', 'font-semibold');
                        } else {
                            displaySpan.innerHTML = `<span class="cell-value">${result.updated_value}</span>`;
                            this.classList.remove('empty-cell');
                        }

                        // Update stats
                        columnStats = result.column_stats;
                        outlierMap = result.outlier_map;

                        // Update all affected cells for outliers
                        updateOutlierHighlights();

                        // Update header badges
                        updateColumnHeaders();

                        // Update summary cards
                        updateSummaryCards(result);

                        // Flash success
                        this.classList.add('success-flash');
                        setTimeout(() => this.classList.remove('success-flash'), 500);
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Update failed:', error);
                    alert('Failed to update cell. Please try again.');
                } finally {
                    this.classList.remove('updating');
                    input.remove();
                    displaySpan.style.display = '';
                }
            };

            // Save on Enter or blur
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    saveEdit();
                } else if (e.key === 'Escape') {
                    input.remove();
                    displaySpan.style.display = '';
                }
            });

            input.addEventListener('blur', saveEdit);
        });
    });

    function updateOutlierHighlights() {
        document.querySelectorAll('.editable-cell').forEach(cell => {
            const rowIndex = cell.dataset.row;
            const column = cell.dataset.column;
            const isOutlier = outlierMap[rowIndex] && outlierMap[rowIndex][column];

            if (isOutlier) {
                cell.classList.add('bg-purple-100', 'text-purple-900', 'font-semibold');
            } else {
                cell.classList.remove('bg-purple-100', 'text-purple-900', 'font-semibold');
            }
        });
    }

    function updateColumnHeaders() {
        document.querySelectorAll('th[data-column]').forEach(header => {
            const column = header.dataset.column;
            const stats = columnStats[column];

            if (!stats) return;

            const badgesContainer = header.querySelector('.header-badges');
            const uniqueCount = header.querySelector('.unique-count');

            // Type colors map
            const typeColors = {
                'number': 'bg-slate-100 text-slate-700 border-slate-300',
                'text': 'bg-slate-100 text-slate-700 border-slate-300',
                'date': 'bg-slate-100 text-slate-700 border-slate-300',
                'text-number': 'bg-slate-100 text-slate-700 border-slate-300',
                'empty': 'bg-slate-50 text-slate-600 border-slate-200'
            };

            const typeColor = typeColors[stats.data_type] || 'bg-slate-100 text-slate-700 border-slate-300';
            const dataType = stats.data_type.replace('-', ' ');

            // Update badges
            badgesContainer.innerHTML = `
            <span class="stat-badge ${typeColor} border">
                ${dataType.charAt(0).toUpperCase() + dataType.slice(1)}
            </span>
            ${stats.empty_count > 0 ?
                `<span class="stat-badge bg-rose-100 text-rose-800 border border-rose-300">${stats.empty_count} empty</span>` :
                `<span class="stat-badge bg-emerald-100 text-emerald-800 border border-emerald-300">Complete</span>`
            }
            ${stats.duplicate_count > 0 ?
                `<span class="stat-badge bg-amber-100 text-amber-800 border border-amber-300">${stats.duplicate_count} duplicates</span>` : ''
            }
            ${stats.outlier_count > 0 ?
                `<span class="stat-badge bg-purple-100 text-purple-800 border border-purple-300">${stats.outlier_count} outliers</span>` :
                `<span class="stat-badge bg-purple-100 text-purple-800 border border-purple-300">No outliers Detect</span>`
            }
        `;

            uniqueCount.textContent = `${stats.unique_values.toLocaleString()} unique values`;
        });
    }

    function updateSummaryCards(result) {
        document.getElementById('duplicate-count').textContent = result.total_duplicate_rows;

        const totalEmpty = Object.values(result.column_stats).reduce((sum, stat) => sum + stat.empty_count, 0);
        document.getElementById('empty-count').textContent = totalEmpty.toLocaleString();
    }
</script>

</body>
</html>
