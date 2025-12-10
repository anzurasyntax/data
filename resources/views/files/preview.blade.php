<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Preview - Editable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <style>
        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px;
            text-align: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            border-bottom: 2px solid #5a67d8;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .summary-box:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46a0 100%);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .summary-box.no-summary {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: default;
            border-bottom: 2px solid #cbd5e1;
        }
        .summary-box.no-summary:hover {
            background: #e2e8f0;
            box-shadow: none;
        }
        .summary-tooltip {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 16px;
            margin-top: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            z-index: 100;
            display: none;
        }
        .summary-box:hover .summary-tooltip {
            display: block;
        }
        .summary-box.no-summary:hover .summary-tooltip {
            display: none;
        }
        .filter-dropdown {
            width: 100%;
            font-size: 0.875rem;
            padding: 10px 12px;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .filter-dropdown:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .filter-dropdown:hover {
            border-color: #94a3b8;
        }
        .filtered-row {
            display: none !important;
        }
        .column-content {
            padding: 16px;
        }
        table th {
            padding: 0 !important;
            vertical-align: top;
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
                    Data Preview (Editable)
                </h3>
            </div>

            <div class="overflow-auto max-h-[80vh]">
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
                                $hasSummary = !empty($stats['stats']);
                            @endphp
                            <th class="border-r border-slate-200 text-left excel-cell relative" data-column="{{ $col }}">
                                <!-- Summary Box -->
                                <div class="summary-box {{ $hasSummary ? '' : 'no-summary' }}">
                                    Summary
                                    @if($hasSummary)
                                        <div class="summary-tooltip">
                                            <div class="font-semibold text-slate-800 mb-3 text-base border-b pb-2">Statistical Summary</div>
                                            <div class="space-y-2 text-sm text-left">
                                                <div class="flex justify-between py-1">
                                                    <span class="text-slate-600">Minimum:</span>
                                                    <span class="text-slate-900">{{ $stats['stats']['min'] }}</span>
                                                </div>
                                                <div class="flex justify-between py-1">
                                                    <span class="text-slate-600">Maximum:</span>
                                                    <span class="text-slate-900">{{ $stats['stats']['max'] }}</span>
                                                </div>
                                                <div class="flex justify-between py-1">
                                                    <span class="text-slate-600">Mean:</span>
                                                    <span class="text-slate-900">{{ $stats['stats']['mean'] }}</span>
                                                </div>
                                                <div class="flex justify-between py-1">
                                                    <span class="text-slate-600">Median:</span>
                                                    <span class="text-slate-900">{{ $stats['stats']['median'] }}</span>
                                                </div>
                                                <div class="flex justify-between py-1">
                                                    <span class="text-slate-600">Std Dev:</span>
                                                    <span class="text-slate-900">{{ $stats['stats']['std'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Column Content -->
                                <div class="column-content">
                                    <!-- Heading and Badges -->
                                    <div class="mb-4">
                                        <div class="flex items-center mb-3">
                                            <span class="type-badge">{{ $typeLabel }}</span>
                                            <span class="column-name truncate">{{ $col }}</span>
                                        </div>

                                        <div class="flex flex-wrap gap-1.5 header-badges">
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
                                                    No outliers
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-xs text-slate-600 mt-2 font-semibold unique-count">
                                            {{ number_format($stats['unique_values'] ?? 0) }} unique values
                                        </div>
                                    </div>

                                    <!-- Filter Dropdown -->
                                    <div>
                                        <select class="filter-dropdown" data-column="{{ $col }}">
                                            <option value="all">🔽 All Rows</option>
                                            @if(($stats['outlier_count'] ?? 0) > 0)
                                                <option value="outliers">📊 Outliers ({{ $stats['outlier_count'] }})</option>
                                            @endif
                                            @if(($stats['duplicate_count'] ?? 0) > 0)
                                                <option value="duplicates">📋 Duplicates ({{ $stats['duplicate_count'] }})</option>
                                            @endif
                                            @if(($stats['empty_count'] ?? 0) > 0)
                                                <option value="empty">⚠️ Empty Cells ({{ $stats['empty_count'] }})</option>
                                            @endif
                                            <option value="unique">✨ Unique Values</option>
                                        </select>
                                    </div>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                    </thead>

                    <tbody id="table-body">
                    @foreach($result['data'] as $index => $row)
                        <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-slate-50' }} data-row" data-row-index="{{ $index }}">
                            @foreach($result['columns_list'] as $col)
                                @php
                                    $value = $row[$col] ?? '';
                                    $isEmpty = $value === '' || $value === null;
                                    $isOutlier = isset($result['outlier_map'][$index][$col]);
                                @endphp
                                <td class="border border-slate-200 p-3 excel-cell editable-cell {{ $isEmpty ? 'empty-cell' : '' }} {{ $isOutlier ? 'bg-purple-100 text-purple-900 font-semibold' : '' }}"
                                    data-row="{{ $index }}"
                                    data-column="{{ $col }}"
                                    data-value="{{ $value }}"
                                    data-is-empty="{{ $isEmpty ? '1' : '0' }}"
                                    data-is-outlier="{{ $isOutlier ? '1' : '0' }}">
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

    let currentData = @json($result['data']);
    let columnStats = @json($result['column_stats']);
    let outlierMap = @json($result['outlier_map']);
    let activeFilters = {};

    // Pre-compute duplicate and unique value lookups for FAST filtering
    let valueCounts = {};

    function buildValueCounts() {
        const columns = @json($result['columns_list']);
        const allCells = document.querySelectorAll('.editable-cell');

        valueCounts = {};

        columns.forEach(col => {
            valueCounts[col] = {};
        });

        allCells.forEach(cell => {
            const col = cell.dataset.column;
            const val = cell.dataset.value;
            const isEmpty = cell.dataset.isEmpty === '1';

            if (!isEmpty && val) {
                valueCounts[col][val] = (valueCounts[col][val] || 0) + 1;
            }
        });
    }

    // Build lookup table on page load
    buildValueCounts();

    // Initialize filter dropdowns
    document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            const column = this.dataset.column;
            const filterType = this.value;
            activeFilters[column] = filterType;
            applyFilters();
        });
    });

    function applyFilters() {
        const rows = document.querySelectorAll('.data-row');
        const hasActiveFilters = Object.keys(activeFilters).some(col => activeFilters[col] !== 'all');

        // If no filters active, show all rows quickly
        if (!hasActiveFilters) {
            rows.forEach(row => row.classList.remove('filtered-row'));
            return;
        }

        // Use documentFragment for batch DOM updates (faster)
        rows.forEach(row => {
            let shouldShow = true;

            for (const [column, filterType] of Object.entries(activeFilters)) {
                if (filterType === 'all') continue;

                const cell = row.querySelector(`td[data-column="${column}"]`);
                if (!cell) continue;

                const isEmpty = cell.dataset.isEmpty === '1';
                const isOutlier = cell.dataset.isOutlier === '1';
                const cellValue = cell.dataset.value;

                switch (filterType) {
                    case 'empty':
                        if (!isEmpty) shouldShow = false;
                        break;
                    case 'outliers':
                        if (!isOutlier) shouldShow = false;
                        break;
                    case 'duplicates':
                        // Use pre-computed lookup - FAST!
                        const count = valueCounts[column][cellValue] || 0;
                        if (count <= 1 || isEmpty) shouldShow = false;
                        break;
                    case 'unique':
                        // Use pre-computed lookup - FAST!
                        const uniqueCount = valueCounts[column][cellValue] || 0;
                        if (uniqueCount !== 1 || isEmpty) shouldShow = false;
                        break;
                }

                if (!shouldShow) break;
            }

            // Toggle class instead of style manipulation (faster)
            row.classList.toggle('filtered-row', !shouldShow);
        });
    }

    // Handle cell click to edit
    document.querySelectorAll('.editable-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            if (this.querySelector('.cell-input')) return;

            const currentValue = this.dataset.value;
            const displaySpan = this.querySelector('.cell-display');

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'cell-input';
            input.value = currentValue;

            displaySpan.style.display = 'none';
            this.appendChild(input);
            input.focus();
            input.select();

            const saveEdit = async () => {
                const newValue = input.value.trim();
                const rowIndex = parseInt(this.dataset.row);
                const column = this.dataset.column;

                if (newValue === currentValue) {
                    input.remove();
                    displaySpan.style.display = '';
                    return;
                }

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
                        this.dataset.value = result.updated_value || '';
                        const isEmpty = !result.updated_value;

                        this.dataset.isEmpty = isEmpty ? '1' : '0';

                        if (isEmpty) {
                            displaySpan.innerHTML = '<span class="text-slate-400 italic text-xs">Empty</span>';
                            this.classList.add('empty-cell');
                            this.classList.remove('bg-purple-100', 'text-purple-900', 'font-semibold');
                        } else {
                            displaySpan.innerHTML = `<span class="cell-value">${result.updated_value}</span>`;
                            this.classList.remove('empty-cell');
                        }

                        columnStats = result.column_stats;
                        outlierMap = result.outlier_map;

                        updateOutlierHighlights();
                        updateColumnHeaders();
                        updateSummaryCards(result);

                        // Rebuild value counts after data change
                        buildValueCounts();
                        applyFilters();

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

            cell.dataset.isOutlier = isOutlier ? '1' : '0';

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
            const filterDropdown = header.querySelector('.filter-dropdown');
            const summaryBox = header.querySelector('.summary-box');

            const typeColors = {
                'number': 'bg-slate-100 text-slate-700 border-slate-300',
                'text': 'bg-slate-100 text-slate-700 border-slate-300',
                'date': 'bg-slate-100 text-slate-700 border-slate-300',
                'text-number': 'bg-slate-100 text-slate-700 border-slate-300',
                'empty': 'bg-slate-50 text-slate-600 border-slate-200'
            };

            const typeColor = typeColors[stats.data_type] || 'bg-slate-100 text-slate-700 border-slate-300';
            const dataType = stats.data_type.replace('-', ' ');

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
                `<span class="stat-badge bg-purple-100 text-purple-800 border border-purple-300">No outliers</span>`
            }
            `;

            uniqueCount.textContent = `${stats.unique_values.toLocaleString()} unique values`;

            const currentValue = filterDropdown.value;
            filterDropdown.innerHTML = `
                <option value="all">🔽 All Rows</option>
                ${stats.outlier_count > 0 ? `<option value="outliers">📊 Outliers (${stats.outlier_count})</option>` : ''}
                ${stats.duplicate_count > 0 ? `<option value="duplicates">📋 Duplicates (${stats.duplicate_count})</option>` : ''}
                ${stats.empty_count > 0 ? `<option value="empty">⚠️ Empty Cells (${stats.empty_count})</option>` : ''}
                <option value="unique">✨ Unique Values</option>
            `;
            filterDropdown.value = currentValue;

            // Update summary box
            if (stats.stats && Object.keys(stats.stats).length > 0) {
                summaryBox.classList.remove('no-summary');
                summaryBox.innerHTML = `
                    Summary
                    <div class="summary-tooltip">
                        <div class="font-bold text-slate-800 mb-3 text-base border-b pb-2">Statistical Summary</div>
                        <div class="space-y-2 text-sm text-left">
                            <div class="flex justify-between py-1">
                                <span class="text-slate-600">Minimum:</span>
                                <b class="text-slate-900">${stats.stats.min}</b>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-600">Maximum:</span>
                                <b class="text-slate-900">${stats.stats.max}</b>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-600">Mean:</span>
                                <b class="text-slate-900">${stats.stats.mean}</b>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-600">Median:</span>
                                <b class="text-slate-900">${stats.stats.median}</b>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-600">Std Dev:</span>
                                <b class="text-slate-900">${stats.stats.std}</b>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                summaryBox.classList.add('no-summary');
                summaryBox.innerHTML = 'Summary';
            }
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
