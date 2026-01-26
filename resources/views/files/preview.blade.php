
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

            <!-- Filtered Rows (hidden by default) -->
            <div class="px-3 py-3 hover:bg-[#334155] transition-colors cursor-pointer" id="filtered-rows-card" style="display: none;">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-[#1e40af] rounded-md flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                    </div>
                    <div class="sidebar-content ml-3 flex-1">
                        <div class="text-[#94a3b8] text-[10px] font-semibold uppercase tracking-wider">Filtered Rows</div>
                        <div class="text-white text-xl font-bold" id="filtered-row-count">0</div>
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-[#1e293b] flex items-center">
                        <svg class="w-6 h-6 mr-2 text-[#475569]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Data Preview & Cleaning
                    </h1>
                    <p class="text-[#64748b] text-sm mt-1 ml-8">Click any cell to edit • Use filters to refine your view • Use cleaning tools to fix data quality issues</p>
                </div>
                <button id="toggle-cleaning-tools" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span id="tools-btn-text">Show Cleaning Tools</span>
                </button>
            </div>
        </div>

        <!-- Advanced Cleaning Tools Panel -->
        <div id="cleaning-tools-panel" class="mb-5 bg-white border border-[#e2e8f0] rounded-lg shadow-lg hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-[#1e293b] mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Advanced Data Cleaning Tools
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Missing Values Imputation -->
                    <div class="border border-[#e2e8f0] rounded-lg p-4">
                        <h3 class="font-semibold text-[#1e293b] mb-3 flex items-center">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                            Handle Missing Values
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Column</label>
                                <select id="impute-column" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">Select Column</option>
                                    @foreach($result['columns_list'] as $col)
                                        <option value="{{ $col }}">{{ $col }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Method</label>
                                <select id="impute-method" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="mean">Mean (for numeric)</option>
                                    <option value="median">Median (for numeric)</option>
                                    <option value="mode">Mode (most frequent)</option>
                                    <option value="forward_fill">Forward Fill</option>
                                    <option value="backward_fill">Backward Fill</option>
                                    <option value="interpolate">Interpolate</option>
                                    <option value="constant">Constant Value</option>
                                    <option value="remove_rows">Remove Rows</option>
                                    <option value="remove_column">Remove Column</option>
                                </select>
                            </div>
                            <div id="impute-value-container" class="hidden">
                                <label class="block text-sm text-gray-600 mb-1">Constant Value</label>
                                <input type="text" id="impute-value" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Enter value">
                            </div>
                            <button onclick="applyImputation()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                                Apply Imputation
                            </button>
                        </div>
                    </div>

                    <!-- Outlier Handling -->
                    <div class="border border-[#e2e8f0] rounded-lg p-4">
                        <h3 class="font-semibold text-[#1e293b] mb-3 flex items-center">
                            <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                            Handle Outliers
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Column</label>
                                <select id="outlier-column" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">Select Column</option>
                                    @foreach($result['columns_list'] as $col)
                                        @if(($result['column_stats'][$col]['data_type'] ?? '') === 'number' || ($result['column_stats'][$col]['data_type'] ?? '') === 'text-number')
                                            <option value="{{ $col }}">{{ $col }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Method</label>
                                <select id="outlier-method" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="remove">Remove Outliers</option>
                                    <option value="cap">Cap at IQR Bounds</option>
                                    <option value="winsorize">Winsorize (Percentile Cap)</option>
                                    <option value="transform_log">Log Transform</option>
                                    <option value="transform_sqrt">Square Root Transform</option>
                                </select>
                            </div>
                            <div id="winsorize-percentiles" class="hidden space-y-2">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Lower Percentile</label>
                                    <input type="number" id="lower-percentile" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="5" min="0" max="50">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Upper Percentile</label>
                                    <input type="number" id="upper-percentile" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" value="95" min="50" max="100">
                                </div>
                            </div>
                            <button onclick="applyOutlierHandling()" class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-semibold">
                                Apply Outlier Handling
                            </button>
                        </div>
                    </div>

                    <!-- Duplicate Removal -->
                    <div class="border border-[#e2e8f0] rounded-lg p-4">
                        <h3 class="font-semibold text-[#1e293b] mb-3 flex items-center">
                            <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                            Remove Duplicates
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Scope</label>
                                <select id="duplicate-scope" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="all">All Columns (Complete Duplicates)</option>
                                    <option value="selected">Selected Columns</option>
                                </select>
                            </div>
                            <div id="duplicate-columns-container" class="hidden">
                                <label class="block text-sm text-gray-600 mb-1">Select Columns</label>
                                <select id="duplicate-columns" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" size="4">
                                    @foreach($result['columns_list'] as $col)
                                        <option value="{{ $col }}">{{ $col }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple</p>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Keep</label>
                                <select id="duplicate-keep" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="first">First Occurrence</option>
                                    <option value="last">Last Occurrence</option>
                                </select>
                            </div>
                            <button onclick="applyDuplicateRemoval()" class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-sm font-semibold">
                                Remove Duplicates
                            </button>
                        </div>
                    </div>

                    <!-- Normalization -->
                    <div class="border border-[#e2e8f0] rounded-lg p-4">
                        <h3 class="font-semibold text-[#1e293b] mb-3 flex items-center">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                            Normalize Column
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Column</label>
                                <select id="normalize-column" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">Select Column</option>
                                    @foreach($result['columns_list'] as $col)
                                        @if(($result['column_stats'][$col]['data_type'] ?? '') === 'number' || ($result['column_stats'][$col]['data_type'] ?? '') === 'text-number')
                                            <option value="{{ $col }}">{{ $col }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Method</label>
                                <select id="normalize-method" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="min_max">Min-Max (0-1)</option>
                                    <option value="z_score">Z-Score (Standardize)</option>
                                    <option value="robust">Robust (Median & MAD)</option>
                                </select>
                            </div>
                            <button onclick="applyNormalization()" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-semibold">
                                Normalize
                            </button>
                        </div>
                    </div>

                    <!-- Bulk Operations -->
                    <div class="border border-[#e2e8f0] rounded-lg p-4">
                        <h3 class="font-semibold text-[#1e293b] mb-3 flex items-center">
                            <span class="w-2 h-2 bg-indigo-500 rounded-full mr-2"></span>
                            Bulk Operations
                        </h3>
                        <div class="space-y-3">
                            <button onclick="removeAllEmptyRows()" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-semibold">
                                Remove All Empty Rows
                            </button>
                            <button onclick="removeAllEmptyColumns()" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-semibold">
                                Remove All Empty Columns
                            </button>
                            <button onclick="imputeAllMissing()" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-semibold">
                                Auto-Impute All Missing (Smart)
                            </button>
                        </div>
                    </div>

                    <!-- Operations History -->
                    <div class="border border-[#e2e8f0] rounded-lg p-4">
                        <h3 class="font-semibold text-[#1e293b] mb-3 flex items-center">
                            <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                            Recent Operations
                        </h3>
                        <div id="operations-history" class="space-y-2 max-h-48 overflow-y-auto text-sm">
                            <p class="text-gray-500 text-center py-4">No operations yet</p>
                        </div>
                    </div>
                </div>
            </div>
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
                        <!-- Rows will be rendered dynamically via virtual scrolling -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const FILE_ID = {{ $file->id }};
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    // Store all data
    const allData = @json($result['data']);
    const columnsList = @json($result['columns_list']);
    let columnStats = @json($result['column_stats']);
    let outlierMap = @json($result['outlier_map']);
    let activeFilters = {};
    let valueFilters = {};
    let pendingValueFilters = {};
    let valueCounts = {};

    // Virtual scrolling configuration
    const ROW_HEIGHT = 41; // Approximate row height in pixels
    const BUFFER_ROWS = 20; // Number of rows to render above and below viewport
    let startIndex = 0;
    let endIndex = 100; // Initial rows to render
    let scrollContainer = null;
    let tableBody = null;

    // Initialize virtual scrolling
    function initVirtualScrolling() {
        tableBody = document.getElementById('table-body');
        const tableContainer = tableBody.closest('.overflow-auto');

        if (!tableContainer) return;

        // Create spacer elements
        const topSpacer = document.createElement('tr');
        topSpacer.id = 'top-spacer';
        topSpacer.innerHTML = '<td colspan="' + columnsList.length + '"></td>';
        topSpacer.style.height = '0px';
        tableBody.appendChild(topSpacer);

        const bottomSpacer = document.createElement('tr');
        bottomSpacer.id = 'bottom-spacer';
        bottomSpacer.innerHTML = '<td colspan="' + columnsList.length + '"></td>';
        bottomSpacer.style.height = '0px';
        tableBody.appendChild(bottomSpacer);

        scrollContainer = tableContainer;

        // Initial render - use filteredData if available, otherwise allData
        const initialData = filteredData !== null ? filteredData : allData;
        renderRows(0, Math.min(endIndex, initialData.length));

        // Scroll event listener
        tableContainer.addEventListener('scroll', handleScroll);

        // Initial scroll handler
        handleScroll();
    }

    // Handle scroll event
    function handleScroll() {
        if (!scrollContainer) return;

        const scrollTop = scrollContainer.scrollTop;
        const containerHeight = scrollContainer.clientHeight;

        // Always use filteredData (will be allData when no filters, or filtered results when filters active)
        const dataToRender = filteredData !== null ? filteredData : allData;

        // Calculate which rows should be visible
        const newStartIndex = Math.max(0, Math.floor(scrollTop / ROW_HEIGHT) - BUFFER_ROWS);
        const visibleRows = Math.ceil(containerHeight / ROW_HEIGHT);
        const newEndIndex = Math.min(dataToRender.length, newStartIndex + visibleRows + (BUFFER_ROWS * 2));

        // Only re-render if viewport changed significantly
        if (Math.abs(newStartIndex - startIndex) > 10 || Math.abs(newEndIndex - endIndex) > 10) {
            startIndex = newStartIndex;
            endIndex = newEndIndex;
            renderRows(startIndex, endIndex);
        }
    }

    // Render visible rows
    function renderRows(start, end) {
        if (!tableBody) return;

        // Always use filteredData (will be allData when no filters, or filtered results when filters active)
        const dataToRender = filteredData !== null ? filteredData : allData;

        // Get spacers
        const topSpacer = document.getElementById('top-spacer');
        const bottomSpacer = document.getElementById('bottom-spacer');
        const rowsToRender = [];

        // Update top spacer
        if (topSpacer) {
            topSpacer.style.height = (start * ROW_HEIGHT) + 'px';
        }

        // Render visible rows
        for (let i = start; i < end; i++) {
            if (i >= dataToRender.length) break;

            const row = dataToRender[i];
            // Get original index for cell updates
            const originalIndex = allData.indexOf(row);
            const rowIndex = originalIndex >= 0 ? originalIndex : i;
            const rowElement = createRowElement(row, rowIndex, i);
            rowsToRender.push(rowElement);
        }

        // Remove old rows (keep spacers)
        const existingRows = Array.from(tableBody.querySelectorAll('tr.data-row'));
        existingRows.forEach(row => {
            const rowIdx = parseInt(row.dataset.viewIndex);
            if (isNaN(rowIdx) || rowIdx < start || rowIdx >= end) {
                row.remove();
            }
        });

        // Insert new rows before bottom spacer
        if (bottomSpacer && rowsToRender.length > 0) {
            rowsToRender.forEach(row => {
                tableBody.insertBefore(row, bottomSpacer);
            });
        }

        // Update bottom spacer
        if (bottomSpacer) {
            const remainingRows = Math.max(0, dataToRender.length - end);
            bottomSpacer.style.height = (remainingRows * ROW_HEIGHT) + 'px';
        }

        // Reattach event listeners to new rows
        attachCellEditListeners();
    }

    // Create a row element
    function createRowElement(row, rowIndex, viewIndex) {
        const tr = document.createElement('tr');
        tr.className = ((viewIndex !== undefined ? viewIndex : rowIndex) % 2 === 0 ? 'bg-white' : 'bg-[#f8fafc]') + ' data-row transition-colors';
        tr.dataset.rowIndex = rowIndex; // Original index for updates
        if (viewIndex !== undefined) {
            tr.dataset.viewIndex = viewIndex; // View index for filtering
        }

        columnsList.forEach(col => {
            const value = row[col] ?? '';
            const isEmpty = value === '' || value === null;
            const isOutlier = outlierMap[rowIndex] && outlierMap[rowIndex][col];

            const td = document.createElement('td');
            td.className = `border border-[#e2e8f0] p-2.5 excel-cell cursor-pointer hover:bg-[#dbeafe] transition-all ${isEmpty ? 'bg-[#f1f5f9]' : ''} ${isOutlier ? 'bg-[#faf5ff] text-[#6b21a8] font-semibold' : ''} editable-cell text-sm text-[#1e293b]`;
            td.dataset.row = rowIndex;
            td.dataset.column = col;
            td.dataset.value = value;
            td.dataset.isEmpty = isEmpty ? '1' : '0';
            td.dataset.isOutlier = isOutlier ? '1' : '0';

            const span = document.createElement('span');
            span.className = 'cell-display';
            if (isEmpty) {
                const emptySpan = document.createElement('span');
                emptySpan.className = 'text-[#94a3b8] italic text-xs';
                emptySpan.textContent = 'Empty';
                span.appendChild(emptySpan);
            } else {
                const valueSpan = document.createElement('span');
                valueSpan.className = 'cell-value';
                valueSpan.textContent = value;
                span.appendChild(valueSpan);
            }

            td.appendChild(span);
            tr.appendChild(td);
        });

        return tr;
    }

    function buildValueCounts() {
        valueCounts = {};
        columnsList.forEach(col => valueCounts[col] = {});

        // Build counts from all data, not just rendered rows
        allData.forEach(row => {
            columnsList.forEach(col => {
                const value = row[col] ?? '';
                const isEmpty = value === '' || value === null;
                if (!isEmpty && value) {
                    valueCounts[col][value] = (valueCounts[col][value] || 0) + 1;
                }
            });
        });
    }

    // Attach cell edit listeners
    function attachCellEditListeners() {
        document.querySelectorAll('.editable-cell').forEach(cell => {
            // Remove existing listeners if any
            const newCell = cell.cloneNode(true);
            cell.parentNode.replaceChild(newCell, cell);

            newCell.addEventListener('click', function() {
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
                            // Update allData
                            if (allData[rowIndex]) {
                                allData[rowIndex][column] = result.updated_value || null;
                            }

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

                            // Rebuild counts and options after data change
                            buildValueCounts();
                            buildValueOptions();
                            clearFilterCache(); // Clear filter cache since data changed

                            updateOutlierHighlights();
                            updateColumnHeaders();
                            updateSummaryCards(result);

                            populateValueFilters();
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
    }

    // Initialize virtual scrolling on page load
    document.addEventListener('DOMContentLoaded', function() {
        buildValueCounts();
        buildValueOptions(); // Pre-compute value options for fast filtering
        populateValueFilters();
        // Initialize filteredData with all data (no filters applied yet)
        filteredData = getFilteredData(); // This will return allData since no filters are set
        initFilterWorker(); // Initialize Web Worker for large datasets
        initVirtualScrolling();

        // Show cleaning tools if ?clean=true in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('clean') === 'true') {
            const panel = document.getElementById('cleaning-tools-panel');
            const btnText = document.getElementById('tools-btn-text');
            if (panel) {
                panel.classList.remove('hidden');
                if (btnText) btnText.textContent = 'Hide Cleaning Tools';
            }
        }
    });

    function populateValueFilters() {
        columnsList.forEach(col => {
            const dropdown = document.querySelector(`.value-filter-dropdown[data-column="${col}"]`);
            if (!dropdown) return;

            const listContainer = dropdown.querySelector('.value-list-container');
            if (!listContainer) return;

            listContainer.innerHTML = '';

            // Count empty cells from allData instead of DOM
            const emptyCells = allData.filter(row => {
                const value = row[col] ?? '';
                return value === '' || value === null;
            }).length;

            // Get all unique values for this column from allData
            const values = Array.from(allValueOptions[col] || []).sort();

            if (values.length === 0 && emptyCells === 0) {
                listContainer.innerHTML = '<div class="p-3 text-center text-slate-500 text-sm font-medium">No values available</div>';
                valueFilters[col] = new Set();
                pendingValueFilters[col] = new Set();
                return;
            }

            const fragment = document.createDocumentFragment();

            // Add empty cells option if any exist
            if (emptyCells > 0) {
                const emptyItem = document.createElement('div');
                emptyItem.className = 'flex items-center py-2 px-2 cursor-pointer rounded-md hover:bg-indigo-50 transition-colors';
                const checkboxId = `val-${col.replace(/[^a-zA-Z0-9]/g, '_')}-empty`;
                emptyItem.innerHTML = `
                    <input type="checkbox" id="${checkboxId}" checked data-value="__EMPTY__" class="mr-2 cursor-pointer w-4 h-4">
                    <label for="${checkboxId}" class="cursor-pointer text-sm flex-1 select-none font-medium"><em class="text-slate-500">(Empty)</em> <span class="text-slate-400">(${emptyCells})</span></label>
                `;
                fragment.appendChild(emptyItem);
            }

            // Add value options
            values.forEach((value, index) => {
                const item = document.createElement('div');
                item.className = 'flex items-center py-2 px-2 cursor-pointer rounded-md hover:bg-indigo-50 transition-colors';
                const safeValue = String(value).replace(/"/g, '&quot;');
                const checkboxId = `val-${col.replace(/[^a-zA-Z0-9]/g, '_')}-${index}`;
                const count = valueCounts[col] ? (valueCounts[col][value] || 0) : 0;
                item.innerHTML = `
                    <input type="checkbox" id="${checkboxId}" checked data-value="${safeValue}" class="mr-2 cursor-pointer w-4 h-4">
                    <label for="${checkboxId}" class="cursor-pointer text-sm flex-1 select-none font-medium">${value} <span class="text-slate-400">(${count})</span></label>
                `;
                fragment.appendChild(item);
            });

            listContainer.appendChild(fragment);

            // Initialize filters with all values selected
            const allValues = new Set(values);
            if (emptyCells > 0) {
                allValues.add('__EMPTY__');
            }
            valueFilters[col] = allValues;
            pendingValueFilters[col] = new Set(allValues);
        });
    }

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

    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-value-container]')) {
            document.querySelectorAll('.value-filter-dropdown').forEach(d => d.classList.add('hidden'));
        }
    });

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

    document.querySelectorAll('.btn-apply').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const container = this.closest('.value-filter-dropdown');
            const col = container.dataset.column;
            const checkboxes = container.querySelectorAll('input[type="checkbox"]:checked');
            const selectedValues = new Set();

            checkboxes.forEach(cb => selectedValues.add(cb.dataset.value));

            valueFilters[col] = selectedValues;

            const button = document.querySelector(`.value-filter-button[data-column="${col}"]`);
            const badge = button ? button.querySelector('.filter-badge') : null;
            const totalCheckboxes = container.querySelectorAll('input[type="checkbox"]').length;

            if (badge) {
                if (selectedValues.size < totalCheckboxes && selectedValues.size > 0) {
                    badge.textContent = selectedValues.size;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            container.classList.add('hidden');
            clearFilterCache(); // Clear cache when filters change
            applyFilters();
        });
    });

    document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            const column = this.dataset.column;
            const filterType = this.value;
            if (filterType === 'all') {
                delete activeFilters[column];
            } else {
                activeFilters[column] = filterType;
            }
            clearFilterCache(); // Clear cache when filters change

            // Debug logging
            console.log('Filter changed:', { column, filterType, activeFilters, valueFilters });

            applyFilters();
        });
    });

    // Filtered data array for virtual scrolling and performance optimization
    let filteredData = null; // null means not initialized, empty array means filtered to nothing
    let filterCache = null;
    let filterCacheKey = '';
    let allValueOptions = {}; // Cache of all possible values per column

    // Ensure allValueOptions is initialized before use
    if (!allValueOptions || Object.keys(allValueOptions).length === 0) {
        allValueOptions = {};
    }

    // Pre-compute value options for faster filtering
    function buildValueOptions() {
        allValueOptions = {};
        columnsList.forEach(col => {
            allValueOptions[col] = new Set();
        });

        allData.forEach(row => {
            columnsList.forEach(col => {
                const value = row[col] ?? '';
                const isEmpty = value === '' || value === null;
                if (!isEmpty) {
                    allValueOptions[col].add(value);
                }
            });
        });
    }

    // Get filter cache key for memoization
    function getFilterCacheKey() {
        const activeKeys = Object.keys(activeFilters)
            .filter(k => activeFilters[k] !== 'all')
            .sort()
            .map(k => `${k}:${activeFilters[k]}`)
            .join('|');

        const valueKeys = Object.keys(valueFilters)
            .filter(k => valueFilters[k] && valueFilters[k].size > 0)
            .sort()
            .map(k => {
                const vals = Array.from(valueFilters[k]).sort().join(',');
                return `${k}:[${vals}]`;
            })
            .join('|');

        return `active:${activeKeys}|values:${valueKeys}`;
    }

    // Optimized filter function with caching and early exits
    function getFilteredData() {
        // Get cache key first
        const currentCacheKey = getFilterCacheKey();

        // Check if we have any active filters
        const hasActiveFilters = Object.keys(activeFilters || {}).some(col => activeFilters[col] && activeFilters[col] !== 'all');
        const hasValueFilters = Object.keys(valueFilters || {}).some(col => {
            const selected = valueFilters[col];
            if (!selected || !(selected instanceof Set)) return false;
            const total = (allValueOptions[col] && allValueOptions[col].size) || 0;
            return selected.size > 0 && selected.size < total;
        });

        // No filters active - return all data
        if (!hasActiveFilters && !hasValueFilters) {
            filterCache = allData;
            filterCacheKey = currentCacheKey;
            console.log('No filters active, returning all data');
            return allData;
        }

        console.log('Filters active - Active filters:', hasActiveFilters, 'Value filters:', hasValueFilters);

        // Check cache
        if (filterCache && filterCacheKey === currentCacheKey) {
            return filterCache;
        }

        // Build filter predicates for better performance
        const activeFilterPredicates = [];
        Object.entries(activeFilters).forEach(([column, filterType]) => {
            if (filterType === 'all') return;

            activeFilterPredicates.push((row, rowIndex) => {
                const value = row[column] ?? '';
                const isEmpty = value === '' || value === null;
                const isOutlier = outlierMap[rowIndex] && outlierMap[rowIndex][column];

                switch (filterType) {
                    case 'empty':
                        return isEmpty;
                    case 'outliers':
                        return isOutlier;
                    case 'duplicates':
                        const count = valueCounts[column] ? (valueCounts[column][value] || 0) : 0;
                        return count > 1 && !isEmpty;
                    default:
                        return true;
                }
            });
        });

        const valueFilterPredicates = [];
        Object.entries(valueFilters).forEach(([column, selectedValues]) => {
            if (!selectedValues || selectedValues.size === 0) return;

            const totalOptions = allValueOptions[column] ? allValueOptions[column].size : 0;
            // If all values selected, no filtering needed
            if (selectedValues.size >= totalOptions) return;

            valueFilterPredicates.push((row) => {
                const value = row[column] ?? '';
                const isEmpty = value === '' || value === null;

                if (isEmpty) {
                    return selectedValues.has('__EMPTY__');
                } else {
                    return selectedValues.has(value);
                }
            });
        });

        // Apply filters with early exit optimization
        filterCache = allData.filter((row, rowIndex) => {
            // Apply active filters first (usually faster)
            for (const predicate of activeFilterPredicates) {
                if (!predicate(row, rowIndex)) {
                    return false;
                }
            }

            // Apply value filters (only if no active filter already rejected)
            if (valueFilterPredicates.length > 0) {
                for (const predicate of valueFilterPredicates) {
                    if (!predicate(row)) {
                        return false;
                    }
                }
            }

            return true;
        });

        filterCacheKey = currentCacheKey;
        return filterCache;
    }

    // Web Worker for background filtering (for large datasets)
    let filterWorker = null;
    let isFiltering = false;
    let filterProgressBar = null;

    // Initialize Web Worker if available and dataset is large
    function initFilterWorker() {
        if (typeof Worker !== 'undefined' && allData.length > 10000) {
            try {
                // Use absolute path for worker
                const workerPath = '{{ asset("js/filter-worker.js") }}' || '/js/filter-worker.js';
                filterWorker = new Worker(workerPath);
                filterWorker.onmessage = function(e) {
                    const { command, filteredData: workerFilteredData, rowCount, processed, total, progress } = e.data;

                    if (command === 'FILTER_PROGRESS') {
                        updateFilterProgress(processed, total, progress);
                    } else if (command === 'FILTER_COMPLETE') {
                        isFiltering = false;
                        hideFilterProgress();

                        // Assign the filtered data from worker
                        filteredData = workerFilteredData;

                        // Reset scroll position and re-render
                        startIndex = 0;
                        endIndex = Math.min(100, filteredData.length);

                        if (scrollContainer) {
                            scrollContainer.scrollTop = 0;
                        }

                        renderRows(startIndex, endIndex);
                        updateFilteredRowCount();
                    }
                };

                filterWorker.onerror = function(error) {
                    console.error('Filter worker error:', error);
                    // Fallback to regular filtering
                    filterWorker = null;
                    applyFiltersRegular();
                };
            } catch (e) {
                console.warn('Web Workers not available, using regular filtering');
                filterWorker = null;
            }
        }
    }

    // Create filter progress UI
    function createFilterProgressBar() {
        if (filterProgressBar) return;

        filterProgressBar = document.createElement('div');
        filterProgressBar.id = 'filter-progress-bar';
        filterProgressBar.className = 'fixed top-0 left-0 right-0 bg-blue-600 text-white text-center py-2 z-50 shadow-lg';
        filterProgressBar.style.display = 'none';
        filterProgressBar.innerHTML = `
            <div class="flex items-center justify-center gap-3">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span class="text-sm font-semibold">Filtering data... <span id="filter-progress-text">0%</span></span>
            </div>
            <div class="mt-1 h-1 bg-blue-800">
                <div id="filter-progress-fill" class="h-full bg-white transition-all duration-300" style="width: 0%"></div>
            </div>
        `;
        document.body.appendChild(filterProgressBar);
    }

    function showFilterProgress() {
        if (!filterProgressBar) createFilterProgressBar();
        filterProgressBar.style.display = 'block';
    }

    function updateFilterProgress(processed, total, progress) {
        if (!filterProgressBar) return;
        const textEl = document.getElementById('filter-progress-text');
        const fillEl = document.getElementById('filter-progress-fill');
        if (textEl) textEl.textContent = `${progress}% (${processed.toLocaleString()}/${total.toLocaleString()} rows)`;
        if (fillEl) fillEl.style.width = progress + '%';
    }

    function hideFilterProgress() {
        if (filterProgressBar) {
            filterProgressBar.style.display = 'none';
            const fillEl = document.getElementById('filter-progress-fill');
            if (fillEl) fillEl.style.width = '0%';
        }
    }

    // Adaptive debounce based on dataset size
    function getDebounceTime() {
        if (allData.length > 50000) return 300;
        if (allData.length > 10000) return 200;
        if (allData.length > 1000) return 150;
        return 100;
    }

    // Regular filtering (for small datasets or fallback)
    function applyFiltersRegular() {
        try {
            console.log('Applying filters - Active:', activeFilters, 'Value:', Object.keys(valueFilters || {}).reduce((acc, k) => {
                acc[k] = valueFilters[k] ? Array.from(valueFilters[k]) : [];
                return acc;
            }, {}));

            filteredData = getFilteredData();

            // Ensure filteredData is always an array
            if (!Array.isArray(filteredData)) {
                console.warn('Filtered data is not an array, using allData');
                filteredData = allData;
            }

            console.log('Filtered data length:', filteredData.length, 'from', allData.length);

            // Reset scroll position and re-render
            startIndex = 0;
            endIndex = Math.min(100, filteredData.length);

            if (scrollContainer) {
                scrollContainer.scrollTop = 0;
            }

            renderRows(startIndex, endIndex);
            updateFilteredRowCount();
        } catch (error) {
            console.error('Error in applyFiltersRegular:', error, error.stack);
            // Fallback to showing all data
            filteredData = allData;
            if (scrollContainer) {
                scrollContainer.scrollTop = 0;
            }
            renderRows(0, Math.min(100, allData.length));
            updateFilteredRowCount();
        }
    }

    // Debounced filter application for better performance
    let filterTimeout = null;
    let filterRAF = null;
    function applyFilters() {
        // Clear any pending filter applications
        if (filterTimeout) clearTimeout(filterTimeout);
        if (filterRAF) cancelAnimationFrame(filterRAF);

        // Cancel any ongoing worker filtering
        if (filterWorker && isFiltering) {
            filterWorker.terminate();
            initFilterWorker(); // Reinitialize worker
        }

        // Use Web Worker for large datasets (> 10000 rows)
        if (filterWorker && allData.length > 10000) {
            isFiltering = true;
            showFilterProgress();

            filterTimeout = setTimeout(() => {
                filterWorker.postMessage({
                    command: 'FILTER',
                    data: allData,
                    filters: {
                        activeFilters: activeFilters,
                        valueFilters: valueFilters
                    },
                    columnsList: columnsList,
                    outlierMap: outlierMap,
                    valueCounts: valueCounts,
                    allValueOptions: allValueOptions
                });
            }, 50);
        } else {
            // Use regular filtering for small/medium datasets (<= 10000 rows)
            const debounceTime = getDebounceTime();
            filterTimeout = setTimeout(() => {
                filterRAF = requestAnimationFrame(() => {
                    try {
                        applyFiltersRegular();
                    } catch (error) {
                        console.error('Filter error:', error);
                        // Fallback: show all data if filter fails
                        filteredData = allData;
                        renderRows(0, Math.min(100, allData.length));
                    }
                });
            }, debounceTime);
        }
    }

    // Clear filter cache when data changes
    function clearFilterCache() {
        filterCache = null;
        filterCacheKey = '';
    }

    // Update filtered row count in sidebar
    function updateFilteredRowCount() {
        const countEl = document.getElementById('filtered-row-count');
        const cardEl = document.getElementById('filtered-rows-card');
        if (countEl && cardEl && filteredData !== null) {
            if (filteredData.length !== allData.length) {
                countEl.textContent = filteredData.length.toLocaleString();
                cardEl.style.display = 'flex';
            } else {
                cardEl.style.display = 'none';
            }
        } else if (cardEl) {
            cardEl.style.display = 'none';
        }
    }

    // Cell edit listeners are attached via attachCellEditListeners() in virtual scrolling

    function updateOutlierHighlights() {
        // Re-render visible rows to update outlier highlights
        if (typeof startIndex !== 'undefined' && typeof endIndex !== 'undefined') {
            renderRows(startIndex, endIndex);
        }
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
                <span class="text-xs px-2.5 py-1 rounded-md border-2 bg-white text-slate-700 border-slate-300 font-semibold shadow-sm">
                    ${dataType.charAt(0).toUpperCase() + dataType.slice(1)}
                </span>
                ${stats.empty_count > 0 ?
                `<span class="text-xs px-2.5 py-1 rounded-md border-2 bg-rose-50 text-rose-800 border-rose-300 font-semibold shadow-sm">${stats.empty_count} empty</span>` :
                `<span class="text-xs px-2.5 py-1 rounded-md border-2 bg-emerald-50 text-emerald-800 border-emerald-300 font-semibold shadow-sm">✓ Complete</span>`
            }
                ${stats.duplicate_count > 0 ?
                `<span class="text-xs px-2.5 py-1 rounded-md border-2 bg-amber-50 text-amber-800 border-amber-300 font-semibold shadow-sm">${stats.duplicate_count} duplicates</span>` : ''
            }
                ${stats.outlier_count > 0 ?
                `<span class="text-xs px-2.5 py-1 rounded-md border-2 bg-purple-50 text-purple-800 border-purple-300 font-semibold shadow-sm">${stats.outlier_count} outliers</span>` :
                `<span class="text-xs px-2.5 py-1 rounded-md border-2 bg-purple-50 text-purple-800 border-purple-300 font-semibold shadow-sm">No outliers</span>`
            }
            `;

            uniqueCount.textContent = `💎 ${stats.unique_values.toLocaleString()} unique values`;

            const currentValue = filterDropdown.value;
            filterDropdown.innerHTML = `
                <option value="all">🔽 All Rows</option>
                ${stats.outlier_count > 0 ? `<option value="outliers">📊 Outliers (${stats.outlier_count})</option>` : ''}
                ${stats.duplicate_count > 0 ? `<option value="duplicates">📋 Duplicates (${stats.duplicate_count})</option>` : ''}
                ${stats.empty_count > 0 ? `<option value="empty">⚠️ Empty Cells (${stats.empty_count})</option>` : ''}
            `;
            filterDropdown.value = currentValue;
        });
    }

    function updateSummaryCards(result) {
        document.getElementById('sidebar-duplicate-count').textContent = result.total_duplicate_rows;
        const totalEmpty = Object.values(result.column_stats).reduce((sum, stat) => sum + stat.empty_count, 0);
        document.getElementById('sidebar-empty-count').textContent = totalEmpty.toLocaleString();
    }

    // Cleaning Tools JavaScript
    let operationsHistory = [];

    // Toggle cleaning tools panel
    document.getElementById('toggle-cleaning-tools').addEventListener('click', function() {
        const panel = document.getElementById('cleaning-tools-panel');
        const btnText = document.getElementById('tools-btn-text');
        panel.classList.toggle('hidden');
        btnText.textContent = panel.classList.contains('hidden') ? 'Show Cleaning Tools' : 'Hide Cleaning Tools';
    });

    // Show/hide impute value input
    document.getElementById('impute-method').addEventListener('change', function() {
        const container = document.getElementById('impute-value-container');
        container.classList.toggle('hidden', this.value !== 'constant');
    });

    // Show/hide winsorize percentiles
    document.getElementById('outlier-method').addEventListener('change', function() {
        const container = document.getElementById('winsorize-percentiles');
        container.classList.toggle('hidden', this.value !== 'winsorize');
    });

    // Show/hide duplicate columns selector
    document.getElementById('duplicate-scope').addEventListener('change', function() {
        const container = document.getElementById('duplicate-columns-container');
        container.classList.toggle('hidden', this.value !== 'selected');
    });

    // Apply cleaning operation
    async function applyCleaningOperation(operations) {
        try {
            const response = await fetch(`/api/file/${FILE_ID}/clean`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ operations })
            });


            const result = await response.json();

            if (result.success) {
                // Reload page to show updated data
                window.location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Cleaning failed:', error);
            alert('Failed to apply cleaning operation. Please try again.');
        }
    }

    // Add to operations history
    function addToHistory(operation) {
        operationsHistory.unshift(operation);
        const historyEl = document.getElementById('operations-history');
        if (operationsHistory.length === 1) {
            historyEl.innerHTML = '';
        }
        const item = document.createElement('div');
        item.className = 'p-2 bg-gray-50 rounded text-xs';
        item.textContent = `${new Date().toLocaleTimeString()} - ${operation}`;
        historyEl.insertBefore(item, historyEl.firstChild);

        // Keep only last 10 operations
        if (operationsHistory.length > 10) {
            operationsHistory.pop();
            if (historyEl.lastChild) historyEl.removeChild(historyEl.lastChild);
        }
    }

    // Imputation
    function applyImputation() {
        const column = document.getElementById('impute-column').value;
        const method = document.getElementById('impute-method').value;
        const value = document.getElementById('impute-value').value;

        if (!column) {
            alert('Please select a column');
            return;
        }

        if (method === 'constant' && !value) {
            alert('Please enter a constant value');
            return;
        }

        const operation = {
            type: 'impute_missing',
            column: column,
            method: method
        };

        if (method === 'constant') {
            operation.value = value;
        }

        addToHistory(`Imputed ${column} using ${method}`);
        applyCleaningOperation([operation]);
    }

    // Outlier handling
    function applyOutlierHandling() {
        const column = document.getElementById('outlier-column').value;
        const method = document.getElementById('outlier-method').value;
        const lowerPercentile = document.getElementById('lower-percentile')?.value;
        const upperPercentile = document.getElementById('upper-percentile')?.value;

        if (!column) {
            alert('Please select a column');
            return;
        }

        const operation = {
            type: 'handle_outliers',
            column: column,
            method: method
        };

        if (method === 'winsorize' && lowerPercentile && upperPercentile) {
            operation.lower_percentile = parseFloat(lowerPercentile);
            operation.upper_percentile = parseFloat(upperPercentile);
        }

        addToHistory(`Handled outliers in ${column} using ${method}`);
        applyCleaningOperation([operation]);
    }

    // Duplicate removal
    function applyDuplicateRemoval() {
        const scope = document.getElementById('duplicate-scope').value;
        const keep = document.getElementById('duplicate-keep').value;
        const columnsSelect = document.getElementById('duplicate-columns');

        let columns = null;
        if (scope === 'selected') {
            columns = Array.from(columnsSelect.selectedOptions).map(opt => opt.value);
            if (columns.length === 0) {
                alert('Please select at least one column');
                return;
            }
        }

        const operation = {
            type: 'remove_duplicates',
            method: keep,
            columns: columns
        };

        addToHistory(`Removed duplicates (${scope === 'all' ? 'all columns' : columns.length + ' columns'})`);
        applyCleaningOperation([operation]);
    }

    // Normalization
    function applyNormalization() {
        const column = document.getElementById('normalize-column').value;
        const method = document.getElementById('normalize-method').value;

        if (!column) {
            alert('Please select a column');
            return;
        }

        const operation = {
            type: method === 'z_score' ? 'standardize' : 'normalize',
            column: column,
            method: method
        };

        addToHistory(`Normalized ${column} using ${method}`);
        applyCleaningOperation([operation]);
    }

    // Bulk operations
    function removeAllEmptyRows() {
        if (!confirm('This will remove all rows that have any empty cells. Continue?')) return;

        const operations = columnsList.map(col => ({
            type: 'remove_rows_with_missing',
            column: col
        }));

        addToHistory('Removed all rows with empty cells');
        applyCleaningOperation(operations);
    }

    function removeAllEmptyColumns() {
        if (!confirm('This will remove all columns that are completely empty. Continue?')) return;

        const operations = columnsList
            .filter(col => {
                const stats = columnStats[col];
                return stats && stats.empty_count === allData.length;
            })
            .map(col => ({
                type: 'remove_column',
                column: col
            }));

        if (operations.length === 0) {
            alert('No completely empty columns found');
            return;
        }

        addToHistory(`Removed ${operations.length} empty column(s)`);
        applyCleaningOperation(operations);
    }

    function imputeAllMissing() {
        if (!confirm('This will automatically impute all missing values using smart methods (mean for numeric, mode for text). Continue?')) return;

        const operations = [];
        columnsList.forEach(col => {
            const stats = columnStats[col];
            if (stats && stats.empty_count > 0) {
                let method = 'mean';
                if (stats.data_type === 'text') {
                    method = 'mode';
                } else if (stats.data_type === 'text-number') {
                    method = 'median';
                }
                operations.push({
                    type: 'impute_missing',
                    column: col,
                    method: method
                });
            }
        });

        if (operations.length === 0) {
            alert('No missing values found');
            return;
        }

        addToHistory(`Auto-imputed missing values in ${operations.length} column(s)`);
        applyCleaningOperation(operations);
    }
</script>

</body>
</html>
