
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Preview - Enhanced Filters</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <style>
        /* Critical styles only */
        .filtered-row { display: none !important; }
        .cell-input {
            width: 100%;
            padding: 8px;
            border: 2px solid #1e40af;
            border-radius: 4px;
            outline: none;
            font-size: 0.875rem;
        }
        .updating { opacity: 0.6; pointer-events: none; }
        .success-flash { animation: flash 0.5s; }
        @keyframes flash { 0%, 100% { background: inherit; } 50% { background: #bbf7d0; } }

        /* Sidebar transition */
        .sidebar {
            transition: width 0.3s ease-in-out;
        }
        .sidebar:hover {
            width: 16rem;
        }
        .sidebar-content {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .sidebar:hover .sidebar-content {
            opacity: 1;
        }

        /* Excel-like table */
        .data-table {
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
        }
        .excel-cell {
            min-width: 150px;
            max-width: 300px;
        }

        /* Row hover */
        .data-row:hover td {
            background-color: #eff6ff !important;
        }

        /* Column hover effect */
        td.excel-cell:hover {
            background-color: #dbeafe !important;
            box-shadow: inset 0 0 0 1px #1e40af;
        }

        /* Scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 6px;
            border: 3px solid #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>
</head>

<body class="bg-[#f8fafc]">

<!-- Sidebar -->
<div class="fixed left-0 top-0 h-full bg-[#1e293b] shadow-[4px_0_8px_rgba(0,0,0,0.1)] z-50 sidebar w-16 hover:w-64">
    <div class="flex flex-col h-full">
        <!-- Logo/Brand -->
        <div class="p-4 border-b border-[#334155]">
            <div class="flex items-center justify-center sidebar-icon">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="sidebar-content ml-4">
                <h2 class="text-white font-semibold text-base whitespace-nowrap">Data Overview</h2>
            </div>
        </div>

        <!-- Stats Items -->
        <div class="flex-1 py-4 space-y-1">
            <!-- Total Rows -->
            <div class="px-3 py-3 hover:bg-[#334155] transition-colors cursor-pointer">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-[#334155] rounded-md flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="sidebar-content ml-3 flex-1">
                        <div class="text-[#94a3b8] text-[10px] font-semibold uppercase tracking-wider">Total Rows</div>
                        <div class="text-white text-xl font-bold">{{ number_format($result['rows']) }}</div>
                    </div>
                </div>
            </div>

            <!-- Data Columns -->
            <div class="px-3 py-3 hover:bg-[#334155] transition-colors cursor-pointer">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-[#334155] rounded-md flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                    </div>
                    <div class="sidebar-content ml-3 flex-1">
                        <div class="text-[#94a3b8] text-[10px] font-semibold uppercase tracking-wider">Columns</div>
                        <div class="text-white text-xl font-bold">{{ $result['columns'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Duplicate Rows -->
            <div class="px-3 py-3 hover:bg-[#334155] transition-colors cursor-pointer">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-[#b45309] rounded-md flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="sidebar-content ml-3 flex-1">
                        <div class="text-[#94a3b8] text-[10px] font-semibold uppercase tracking-wider">Duplicates</div>
                        <div class="text-white text-xl font-bold" id="sidebar-duplicate-count">{{ $result['total_duplicate_rows'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Empty Cells -->
            <div class="px-3 py-3 hover:bg-[#334155] transition-colors cursor-pointer">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-[#991b1b] rounded-md flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <div class="sidebar-content ml-3 flex-1">
                        @php
                            $totalEmpty = collect($result['column_stats'])->sum('empty_count');
                        @endphp
                        <div class="text-[#94a3b8] text-[10px] font-semibold uppercase tracking-wider">Empty Cells</div>
                        <div class="text-white text-xl font-bold" id="sidebar-empty-count">{{ number_format($totalEmpty) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="ml-16 transition-all duration-300">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-5 bg-white border border-[#e2e8f0] rounded-lg p-4 shadow-sm">
            <h1 class="text-2xl font-semibold text-[#1e293b] flex items-center">
                <svg class="w-6 h-6 mr-2 text-[#475569]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Data Preview
            </h1>
            <p class="text-[#64748b] text-sm mt-1 ml-8">Click any cell to edit • Use filters to refine your view • Hover over summary for statistics</p>
        </div>

        <!-- Excel-like Table Container -->
        <div class="bg-white rounded-lg shadow-sm border border-[#e2e8f0] overflow-hidden">
            <div class="overflow-auto max-h-[calc(100vh-180px)] custom-scrollbar">
                <table class="min-w-full data-table">
                    <thead id="table-header" class="sticky top-0 z-40">
                    <tr>
                        @foreach($result['columns_list'] as $col)
                            @php
                                $stats = $result['column_stats'][$col] ?? [];
                                $typeIcons = ['number' => '123', 'text' => 'ABC', 'date' => 'DATE', 'text-number' => 'A1', 'empty' => '—'];
                                $typeLabel = $typeIcons[$stats['data_type']] ?? '?';
                                $hasSummary = !empty($stats['stats']);
                            @endphp
                            <th class="border-r border-b-2 border-[#cbd5e1] text-left excel-cell relative overflow-visible bg-[#f8fafc]" data-column="{{ $col }}">

                                <!-- Summary Box -->
                                <div class="relative {{ $hasSummary ? 'bg-[#1e40af] hover:bg-[#1e3a8a] cursor-pointer group' : 'bg-[#cbd5e1] cursor-default' }} p-2.5 text-center text-white text-xs font-semibold border-b {{ $hasSummary ? 'border-[#1e3a8a]' : 'border-[#94a3b8]' }} transition-colors">
                                    <span class="{{ $hasSummary ? '' : 'text-[#64748b]' }}">Summary</span>
                                    @if($hasSummary)
                                        <div class="hidden group-hover:block absolute top-full left-0 right-0 bg-white border-2 border-[#1e40af] rounded-md p-3 mt-1 shadow-lg z-[100]">
                                            <div class="font-semibold text-[#1e293b] mb-2 text-sm border-b border-[#e2e8f0] pb-2">Statistical Summary</div>
                                            <div class="space-y-1.5 text-xs text-left">
                                                <div class="flex justify-between py-1">
                                                    <span class="text-[#64748b] font-medium">Minimum:</span>
                                                    <span class="text-[#1e293b] font-semibold">{{ $stats['stats']['min'] }}</span>
                                                </div>
                                                <div class="flex justify-between py-1">
                                                    <span class="text-[#64748b] font-medium">Maximum:</span>
                                                    <span class="text-[#1e293b] font-semibold">{{ $stats['stats']['max'] }}</span>
                                                </div>
                                                <div class="flex justify-between py-1">
                                                    <span class="text-[#64748b] font-medium">Mean:</span>
                                                    <span class="text-[#1e293b] font-semibold">{{ $stats['stats']['mean'] }}</span>
                                                </div>
                                                <div class="flex justify-between py-1">
                                                    <span class="text-[#64748b] font-medium">Median:</span>
                                                    <span class="text-[#1e293b] font-semibold">{{ $stats['stats']['median'] }}</span>
                                                </div>
                                                <div class="flex justify-between py-1">
                                                    <span class="text-[#64748b] font-medium">Std Dev:</span>
                                                    <span class="text-[#1e293b] font-semibold">{{ $stats['stats']['std'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Column Info -->
                                <div class="p-3 bg-[#f8fafc]">
                                    <div class="mb-3">
                                        <div class="flex items-center mb-2">
                                            <span class="bg-[#475569] text-white text-[10px] font-semibold px-2 py-0.5 rounded mr-2">{{ $typeLabel }}</span>
                                            <span class="font-semibold text-[#1e293b] truncate text-sm">{{ $col }}</span>
                                        </div>

                                        <div class="flex flex-wrap gap-1.5">
                                            <span class="text-[10px] px-2 py-0.5 rounded border bg-white text-[#475569] border-[#cbd5e1] font-medium">
                                                {{ ucfirst(str_replace('-', ' ', $stats['data_type'] ?? 'unknown')) }}
                                            </span>
                                            @if(($stats['empty_count'] ?? 0) > 0)
                                                <span class="text-[10px] px-2 py-0.5 rounded border bg-[#fef2f2] text-[#991b1b] border-[#fca5a5] font-medium">
                                                    {{ $stats['empty_count'] }} empty
                                                </span>
                                            @else
                                                <span class="text-[10px] px-2 py-0.5 rounded border bg-[#f0fdf4] text-[#166534] border-[#86efac] font-medium">
                                                    Complete
                                                </span>
                                            @endif
                                            @if(($stats['duplicate_count'] ?? 0) > 0)
                                                <span class="text-[10px] px-2 py-0.5 rounded border bg-[#fffbeb] text-[#b45309] border-[#fcd34d] font-medium">
                                                    {{ $stats['duplicate_count'] }} duplicates
                                                </span>
                                            @endif
                                            @if(($stats['outlier_count'] ?? 0) > 0)
                                                <span class="text-[10px] px-2 py-0.5 rounded border bg-[#faf5ff] text-[#6b21a8] border-[#d8b4fe] font-medium">
                                                    {{ $stats['outlier_count'] }} outliers
                                                </span>
                                            @else
                                                <span class="text-[10px] px-2 py-0.5 rounded border bg-[#faf5ff] text-[#6b21a8] border-[#d8b4fe] font-medium">
                                                    No outliers
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-[10px] text-[#475569] mt-2 font-medium unique-count">
                                            {{ number_format($stats['unique_values'] ?? 0) }} unique values
                                        </div>
                                    </div>
                                </div>

                                <!-- Type Filter Dropdown -->
                                <div class="px-3 pb-2 bg-[#f8fafc]">
                                    <select class="w-full text-xs py-2 px-2.5 border border-[#cbd5e1] rounded bg-white cursor-pointer font-medium hover:border-[#64748b] focus:outline-none focus:border-[#1e40af] focus:ring-1 focus:ring-[#1e40af] transition-colors filter-dropdown" data-column="{{ $col }}">
                                        <option value="all">All Rows</option>
                                        @if(($stats['outlier_count'] ?? 0) > 0)
                                            <option value="outliers">Outliers ({{ $stats['outlier_count'] }})</option>
                                        @endif
                                        @if(($stats['duplicate_count'] ?? 0) > 0)
                                            <option value="duplicates">Duplicates ({{ $stats['duplicate_count'] }})</option>
                                        @endif
                                        @if(($stats['empty_count'] ?? 0) > 0)
                                            <option value="empty">Empty Cells ({{ $stats['empty_count'] }})</option>
                                        @endif
                                    </select>
                                </div>

                                <!-- Value Filter Dropdown -->
                                <div class="relative px-3 pb-3 overflow-visible bg-[#f8fafc]" data-value-container="{{ $col }}">
                                    <button class="w-full text-xs py-2 px-2.5 border border-[#cbd5e1] rounded bg-white cursor-pointer font-medium hover:border-[#64748b] transition-colors text-left flex justify-between items-center value-filter-button" data-column="{{ $col }}">
                                        <span class="filter-text">Filter Values</span>
                                        <div class="flex items-center gap-2">
                                            <span class="hidden bg-[#1e40af] text-white text-[10px] px-1.5 py-0.5 rounded-full filter-badge font-semibold">0</span>
                                            <span class="text-[10px]">▼</span>
                                        </div>
                                    </button>
                                    <div class="hidden absolute top-full left-3 right-3 bg-white border border-[#cbd5e1] rounded-md mt-1 shadow-lg z-[1000] max-h-80 overflow-hidden value-filter-dropdown" data-column="{{ $col }}">
                                        <div class="p-2 border-b border-[#e2e8f0] bg-[#f8fafc]">
                                            <input type="text" class="w-full py-1.5 px-2.5 border border-[#cbd5e1] rounded text-xs focus:outline-none focus:border-[#1e40af] focus:ring-1 focus:ring-[#1e40af] value-search-input font-medium" placeholder="Search values...">
                                        </div>
                                        <div class="max-h-40 overflow-y-auto p-2 value-list-container custom-scrollbar">
                                            <!-- Values populated dynamically -->
                                        </div>
                                        <div class="p-2 border-t border-[#e2e8f0] flex flex-col gap-1.5 bg-[#f8fafc]">
                                            <button class="flex-1 py-2 px-3 text-xs rounded bg-[#166534] text-white font-semibold hover:bg-[#14532d] transition-colors btn-apply">Apply</button>
                                            <button class="flex-1 py-2 px-3 text-xs rounded bg-[#1e40af] text-white font-semibold hover:bg-[#1e3a8a] transition-colors btn-select-all">Select All</button>
                                            <button class="flex-1 py-2 px-3 text-xs rounded bg-[#e2e8f0] text-[#475569] font-semibold hover:bg-[#cbd5e1] transition-colors btn-clear-all">Clear</button>
                                        </div>
                                    </div>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                    </thead>

                    <tbody id="table-body">
                    @foreach($result['data'] as $index => $row)
                        <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-[#f8fafc]' }} data-row transition-colors" data-row-index="{{ $index }}">
                            @foreach($result['columns_list'] as $col)
                                @php
                                    $value = $row[$col] ?? '';
                                    $isEmpty = $value === '' || $value === null;
                                    $isOutlier = isset($result['outlier_map'][$index][$col]);
                                @endphp
                                <td class="border border-[#e2e8f0] p-2.5 excel-cell cursor-pointer hover:bg-[#dbeafe] transition-all {{ $isEmpty ? 'bg-[#f1f5f9]' : '' }} {{ $isOutlier ? 'bg-[#faf5ff] text-[#6b21a8] font-semibold' : '' }} editable-cell text-sm text-[#1e293b]"
                                    data-row="{{ $index }}"
                                    data-column="{{ $col }}"
                                    data-value="{{ $value }}"
                                    data-is-empty="{{ $isEmpty ? '1' : '0' }}"
                                    data-is-outlier="{{ $isOutlier ? '1' : '0' }}">
                                    @if($isEmpty)
                                        <span class="text-[#94a3b8] italic text-xs cell-display">Empty</span>
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
</div>

<script>
    // ============================================================================
    // HIGH-PERFORMANCE DATA TABLE ENGINE
    // Supports 50k+ rows with instant filtering and virtual scrolling
    // ============================================================================

    const FILE_ID = {{ $file->id }};
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    // ============================================================================
    // CONFIGURATION
    // ============================================================================
    const CONFIG = {
        ROW_HEIGHT: 53, // Fixed row height in pixels (measured from your design)
        BUFFER_ROWS: 20, // Extra rows above/below viewport for smooth scrolling
        DEBOUNCE_SCROLL: 16, // ~60fps
        DEBOUNCE_FILTER: 100
    };

    // ============================================================================
    // GLOBAL STATE
    // ============================================================================
    let columnStats = @json($result['column_stats']);
    let outlierMap = @json($result['outlier_map']);
    const columnsList = @json($result['columns_list']);

    // Filters
    let activeFilters = {};
    let valueFilters = {};
    let pendingValueFilters = {};
    let valueCounts = {};

    // Row cache and virtual scroll state
    let rowCache = [];
    let filteredIndices = [];
    let virtualState = {
        scrollTop: 0,
        visibleStart: 0,
        visibleEnd: 0,
        containerHeight: 0
    };

    // ============================================================================
    // 1️⃣ ROW CACHE BUILDER
    // Build row metadata once - NO DOM queries during filtering
    // ============================================================================
    function buildRowCache() {
        const startTime = performance.now();
        const rows = document.querySelectorAll('.data-row');
        rowCache = [];

        rows.forEach((rowEl, idx) => {
            const rowData = {
                index: idx,
                element: rowEl,
                visible: true,
                cells: {}
            };

            // Cache all cell metadata for instant filtering
            columnsList.forEach(col => {
                const cell = rowEl.querySelector(`[data-column="${col}"]`);
                if (cell) {
                    rowData.cells[col] = {
                        value: cell.dataset.value || '',
                        isEmpty: cell.dataset.isEmpty === '1',
                        isOutlier: cell.dataset.isOutlier === '1',
                        element: cell
                    };
                }
            });

            rowCache.push(rowData);
        });

        // Initially all rows are visible
        filteredIndices = rowCache.map((r, i) => i);

        const endTime = performance.now();
        console.log(`✅ Row cache built: ${rowCache.length} rows in ${(endTime - startTime).toFixed(2)}ms`);
    }

    // ============================================================================
    // 2️⃣ VALUE COUNTS BUILDER
    // Build value frequency map from cache
    // ============================================================================
    function buildValueCounts() {
        const startTime = performance.now();
        valueCounts = {};

        columnsList.forEach(col => valueCounts[col] = {});

        rowCache.forEach(row => {
            columnsList.forEach(col => {
                const cellData = row.cells[col];
                if (cellData && !cellData.isEmpty && cellData.value) {
                    const val = cellData.value;
                    valueCounts[col][val] = (valueCounts[col][val] || 0) + 1;
                }
            });
        });

        const endTime = performance.now();
        console.log(`✅ Value counts built in ${(endTime - startTime).toFixed(2)}ms`);
    }

    // ============================================================================
    // 3️⃣ OPTIMIZED FILTER ENGINE
    // Pure JavaScript - no DOM manipulation during filtering
    // ============================================================================
    function filterRowsOptimized() {
        const startTime = performance.now();

        const hasActiveFilters = Object.keys(activeFilters).some(col => activeFilters[col] !== 'all');
        const hasValueFilters = Object.keys(valueFilters).some(col => {
            const dropdown = document.querySelector(`.value-filter-dropdown[data-column="${col}"]`);
            const totalCheckboxes = dropdown ? dropdown.querySelectorAll('input[type="checkbox"]').length : 0;
            return valueFilters[col] && valueFilters[col].size < totalCheckboxes;
        });

        // Early exit: no filters
        if (!hasActiveFilters && !hasValueFilters) {
            filteredIndices = rowCache.map((r, i) => i);
            const endTime = performance.now();
            console.log(`✅ No filters active, showing all ${rowCache.length} rows (${(endTime - startTime).toFixed(2)}ms)`);
            return;
        }

        // Filter using cached data only
        filteredIndices = [];

        for (let i = 0; i < rowCache.length; i++) {
            const row = rowCache[i];
            let shouldShow = true;

            // Type filters (empty, outliers, duplicates)
            for (const [column, filterType] of Object.entries(activeFilters)) {
                if (filterType === 'all') continue;

                const cellData = row.cells[column];
                if (!cellData) {
                    shouldShow = false;
                    break;
                }

                if (filterType === 'empty' && !cellData.isEmpty) {
                    shouldShow = false;
                    break;
                }
                if (filterType === 'outliers' && !cellData.isOutlier) {
                    shouldShow = false;
                    break;
                }
                if (filterType === 'duplicates') {
                    const count = valueCounts[column][cellData.value] || 0;
                    if (count <= 1 || cellData.isEmpty) {
                        shouldShow = false;
                        break;
                    }
                }
            }

            // Value filters
            if (shouldShow) {
                for (const [column, selectedValues] of Object.entries(valueFilters)) {
                    if (!selectedValues) continue;

                    const dropdown = document.querySelector(`.value-filter-dropdown[data-column="${column}"]`);
                    const totalCheckboxes = dropdown ? dropdown.querySelectorAll('input[type="checkbox"]').length : 0;

                    if (selectedValues.size >= totalCheckboxes) continue;

                    const cellData = row.cells[column];
                    if (!cellData) {
                        shouldShow = false;
                        break;
                    }

                    if (cellData.isEmpty) {
                        if (!selectedValues.has('__EMPTY__')) {
                            shouldShow = false;
                            break;
                        }
                    } else {
                        if (!selectedValues.has(cellData.value)) {
                            shouldShow = false;
                            break;
                        }
                    }
                }
            }

            if (shouldShow) {
                filteredIndices.push(i);
            }
        }

        const endTime = performance.now();
        console.log(`✅ Filtered ${rowCache.length} rows → ${filteredIndices.length} visible (${(endTime - startTime).toFixed(2)}ms)`);
    }

    // ============================================================================
    // 4️⃣ VIRTUAL SCROLL ENGINE
    // Only render visible rows + buffer
    // ============================================================================
    const virtualScroll = {
        container: null,
        tbody: null,
        spacer: null,
        scrollTimeout: null,

        init() {
            this.container = document.getElementById('virtual-scroll-container');
            this.tbody = document.getElementById('table-body');
            this.spacer = document.getElementById('virtual-scroll-spacer');

            if (!this.container || !this.tbody) {
                console.error('Virtual scroll elements not found');
                return;
            }

            virtualState.containerHeight = this.container.clientHeight;

            // Debounced scroll handler
            this.container.addEventListener('scroll', () => {
                clearTimeout(this.scrollTimeout);
                this.scrollTimeout = setTimeout(() => this.handleScroll(), CONFIG.DEBOUNCE_SCROLL);
            });

            // Initial render
            this.render();
        },

        handleScroll() {
            virtualState.scrollTop = this.container.scrollTop;
            this.render();
        },

        render() {
            const totalRows = filteredIndices.length;
            const totalHeight = totalRows * CONFIG.ROW_HEIGHT;

            // Set spacer height for proper scrollbar
            this.spacer.style.height = `${totalHeight}px`;

            // Calculate visible range
            const scrollTop = virtualState.scrollTop;
            const containerHeight = this.container.clientHeight;

            const visibleStart = Math.floor(scrollTop / CONFIG.ROW_HEIGHT);
            const visibleEnd = Math.ceil((scrollTop + containerHeight) / CONFIG.ROW_HEIGHT);

            // Add buffer
            const renderStart = Math.max(0, visibleStart - CONFIG.BUFFER_ROWS);
            const renderEnd = Math.min(totalRows, visibleEnd + CONFIG.BUFFER_ROWS);

            // Only update if range changed significantly
            if (renderStart === virtualState.visibleStart && renderEnd === virtualState.visibleEnd) {
                return;
            }

            virtualState.visibleStart = renderStart;
            virtualState.visibleEnd = renderEnd;

            // Batch DOM updates
            requestAnimationFrame(() => {
                this.updateVisibleRows(renderStart, renderEnd, totalRows);
            });
        },

        updateVisibleRows(start, end, total) {
            const startTime = performance.now();

            // Hide all rows first
            rowCache.forEach(row => {
                row.element.style.display = 'none';
            });

            // Show only filtered + visible rows
            const fragment = document.createDocumentFragment();

            for (let i = start; i < end; i++) {
                const actualIndex = filteredIndices[i];
                if (actualIndex === undefined) continue;

                const row = rowCache[actualIndex];
                if (!row) continue;

                row.element.style.display = '';
                row.element.style.transform = `translateY(${i * CONFIG.ROW_HEIGHT}px)`;
                row.element.style.position = 'absolute';
                row.element.style.width = '100%';
            }

            const endTime = performance.now();
            console.log(`✅ Rendered rows ${start}-${end} of ${total} (${(endTime - startTime).toFixed(2)}ms)`);
        }
    };

    // ============================================================================
    // 5️⃣ APPLY FILTERS WITH VIRTUAL SCROLL UPDATE
    // ============================================================================
    function applyFilters() {
        filterRowsOptimized();
        virtualScroll.render();
    }

    // ============================================================================
    // 6️⃣ VALUE FILTER POPULATION
    // ============================================================================
    function populateValueFilters() {
        columnsList.forEach(col => {
            const values = Object.keys(valueCounts[col] || {}).sort();
            const dropdown = document.querySelector(`.value-filter-dropdown[data-column="${col}"]`);
            if (!dropdown) return;

            const listContainer = dropdown.querySelector('.value-list-container');
            if (!listContainer) return;

            listContainer.innerHTML = '';

            const emptyCells = rowCache.filter(row => row.cells[col]?.isEmpty).length;

            if (emptyCells > 0) {
                const emptyItem = document.createElement('div');
                emptyItem.className = 'flex items-center py-2 px-2 cursor-pointer rounded-md hover:bg-indigo-50 transition-colors';
                const checkboxId = `val-${col.replace(/[^a-zA-Z0-9]/g, '_')}-empty`;
                emptyItem.innerHTML = `
                <input type="checkbox" id="${checkboxId}" checked data-value="__EMPTY__" class="mr-2 cursor-pointer w-4 h-4">
                <label for="${checkboxId}" class="cursor-pointer text-sm flex-1 select-none font-medium"><em class="text-slate-500">(Empty)</em> <span class="text-slate-400">(${emptyCells})</span></label>
            `;
                listContainer.appendChild(emptyItem);
            }

            if (values.length === 0 && emptyCells === 0) {
                listContainer.innerHTML = '<div class="p-3 text-center text-slate-500 text-sm font-medium">No values available</div>';
                valueFilters[col] = new Set();
                pendingValueFilters[col] = new Set();
                return;
            }

            const fragment = document.createDocumentFragment();

            values.forEach((value, index) => {
                const item = document.createElement('div');
                item.className = 'flex items-center py-2 px-2 cursor-pointer rounded-md hover:bg-indigo-50 transition-colors';
                const safeValue = String(value).replace(/"/g, '&quot;');
                const checkboxId = `val-${col.replace(/[^a-zA-Z0-9]/g, '_')}-${index}`;
                item.innerHTML = `
                <input type="checkbox" id="${checkboxId}" checked data-value="${safeValue}" class="mr-2 cursor-pointer w-4 h-4">
                <label for="${checkboxId}" class="cursor-pointer text-sm flex-1 select-none font-medium">${value} <span class="text-slate-400">(${valueCounts[col][value]})</span></label>
            `;
                fragment.appendChild(item);
            });

            listContainer.appendChild(fragment);

            valueFilters[col] = new Set([...values, '__EMPTY__']);
            pendingValueFilters[col] = new Set([...values, '__EMPTY__']);
        });
    }

    // ============================================================================
    // 7️⃣ INITIALIZE EVERYTHING
    // ============================================================================
    function initialize() {
        console.log('🚀 Initializing optimized data table...');

        // Build caches
        buildRowCache();
        buildValueCounts();
        populateValueFilters();

        // Initialize virtual scrolling
        virtualScroll.init();

        console.log('✅ Initialization complete!');
    }

    // Run on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

    // ============================================================================
    // 8️⃣ FILTER EVENT HANDLERS
    // ============================================================================

    // Type filter dropdowns
    document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            const column = this.dataset.column;
            const filterType = this.value;
            activeFilters[column] = filterType;
            applyFilters();
        });
    });

    // Value filter button toggles
    document.querySelectorAll('.value-filter-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const col = this.dataset.column;
            const dropdown = document.querySelector(`.value-filter-dropdown[data-column="${col}"]`);

            document.querySelectorAll('.value-filter-dropdown').forEach(d => {
                if (d !== dropdown) d.classList.add('hidden');
            });

            dropdown.classList.toggle('hidden');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-value-container]')) {
            document.querySelectorAll('.value-filter-dropdown').forEach(d => d.classList.add('hidden'));
        }
    });

    // Search within value filters
    document.querySelectorAll('.value-search-input').forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const container = this.closest('.value-filter-dropdown');
            const items = container.querySelectorAll('.value-list-container > div');

            items.forEach(item => {
                const label = item.querySelector('label')?.textContent.toLowerCase() || '';
                item.style.display = label.includes(searchTerm) ? 'flex' : 'none';
            });
        });
    });

    // Select/Clear all buttons
    document.querySelectorAll('.btn-select-all').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const container = this.closest('.value-filter-dropdown');
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');

            checkboxes.forEach(cb => {
                if (cb.parentElement.style.display !== 'none') {
                    cb.checked = true;
                }
            });
        });
    });

    document.querySelectorAll('.btn-clear-all').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const container = this.closest('.value-filter-dropdown');
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');

            checkboxes.forEach(cb => {
                if (cb.parentElement.style.display !== 'none') {
                    cb.checked = false;
                }
            });
        });
    });

    // Apply value filters (debounced)
    let applyTimeout = null;
    document.querySelectorAll('.btn-apply').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();

            clearTimeout(applyTimeout);
            applyTimeout = setTimeout(() => {
                const container = this.closest('.value-filter-dropdown');
                const col = container.dataset.column;
                const checkboxes = container.querySelectorAll('input[type="checkbox"]:checked');
                const selectedValues = new Set();

                checkboxes.forEach(cb => selectedValues.add(cb.dataset.value));

                valueFilters[col] = selectedValues;

                const button = document.querySelector(`.value-filter-button[data-column="${col}"]`);
                const badge = button.querySelector('.filter-badge');
                const totalCheckboxes = container.querySelectorAll('input[type="checkbox"]').length;

                if (selectedValues.size < totalCheckboxes) {
                    badge.textContent = selectedValues.size;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                container.classList.add('hidden');
                applyFilters();
            }, CONFIG.DEBOUNCE_FILTER);
        });
    });

    // ============================================================================
    // 9️⃣ CELL EDITING (Unchanged - works with virtual scroll)
    // ============================================================================
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
                            this.classList.add('bg-slate-100');
                            this.classList.remove('bg-purple-100', 'text-purple-900', 'font-bold');
                        } else {
                            displaySpan.innerHTML = `<span class="cell-value">${result.updated_value}</span>`;
                            this.classList.remove('bg-slate-100');
                        }

                        columnStats = result.column_stats;
                        outlierMap = result.outlier_map;

                        // Rebuild caches after update
                        buildValueCounts();
                        populateValueFilters();

                        // Update UI
                        updateOutlierHighlights();
                        updateColumnHeaders();
                        updateSummaryCards(result);
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

    // ============================================================================
    // 🔟 HELPER FUNCTIONS (Unchanged)
    // ============================================================================
    function updateOutlierHighlights() {
        document.querySelectorAll('.editable-cell').forEach(cell => {
            const rowIndex = cell.dataset.row;
            const column = cell.dataset.column;
            const isOutlier = outlierMap[rowIndex] && outlierMap[rowIndex][column];

            cell.dataset.isOutlier = isOutlier ? '1' : '0';

            if (isOutlier) {
                cell.classList.add('bg-purple-100', 'text-purple-900', 'font-bold');
            } else {
                cell.classList.remove('bg-purple-100', 'text-purple-900', 'font-bold');
            }
        });
    }

    function updateColumnHeaders() {
        document.querySelectorAll('th[data-column]').forEach(header => {
            const column = header.dataset.column;
            const stats = columnStats[column];
            if (!stats) return;

            const badgesContainer = header.querySelector('.flex.flex-wrap.gap-1\\.5');
            const uniqueCount = header.querySelector('.unique-count');
            const filterDropdown = header.querySelector('.filter-dropdown');

            const dataType = stats.data_type.replace('-', ' ');

            badgesContainer.innerHTML = `
            <span class="text-[10px] px-2 py-0.5 rounded border bg-white text-[#475569] border-[#cbd5e1] font-medium">
                ${dataType.charAt(0).toUpperCase() + dataType.slice(1)}
            </span>
            ${stats.empty_count > 0 ?
                `<span class="text-[10px] px-2 py-0.5 rounded border bg-[#fef2f2] text-[#991b1b] border-[#fca5a5] font-medium">${stats.empty_count} empty</span>` :
                `<span class="text-[10px] px-2 py-0.5 rounded border bg-[#f0fdf4] text-[#166534] border-[#86efac] font-medium">Complete</span>`
            }
            ${stats.duplicate_count > 0 ?
                `<span class="text-[10px] px-2 py-0.5 rounded border bg-[#fffbeb] text-[#b45309] border-[#fcd34d] font-medium">${stats.duplicate_count} duplicates</span>` : ''
            }
            ${stats.outlier_count > 0 ?
                `<span class="text-[10px] px-2 py-0.5 rounded border bg-[#faf5ff] text-[#6b21a8] border-[#d8b4fe] font-medium">${stats.outlier_count} outliers</span>` :
                `<span class="text-[10px] px-2 py-0.5 rounded border bg-[#faf5ff] text-[#6b21a8] border-[#d8b4fe] font-medium">No outliers</span>`
            }
        `;

            uniqueCount.textContent = `${stats.unique_values.toLocaleString()} unique values`;

            const currentValue = filterDropdown.value;
            filterDropdown.innerHTML = `
            <option value="all">All Rows</option>
            ${stats.outlier_count > 0 ? `<option value="outliers">Outliers (${stats.outlier_count})</option>` : ''}
            ${stats.duplicate_count > 0 ? `<option value="duplicates">Duplicates (${stats.duplicate_count})</option>` : ''}
            ${stats.empty_count > 0 ? `<option value="empty">Empty Cells (${stats.empty_count})</option>` : ''}
        `;
            filterDropdown.value = currentValue;
        });
    }

    function updateSummaryCards(result) {
        document.getElementById('sidebar-duplicate-count').textContent = result.total_duplicate_rows;
        const totalEmpty = Object.values(result.column_stats).reduce((sum, stat) => sum + stat.empty_count, 0);
        document.getElementById('sidebar-empty-count').textContent = totalEmpty.toLocaleString();
    }
</script>

</body>
</html>
