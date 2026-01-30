<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualize · {{ $file->original_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: 'DM Sans', system-ui, sans-serif; }
        .font-display { font-family: 'Outfit', system-ui, sans-serif; }
        .gradient-mesh { background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(20, 184, 166, 0.12), transparent), radial-gradient(ellipse 60% 40% at 100% 50%, rgba(139, 92, 246, 0.06), transparent); }
        .loading-dots span { animation: loadingDot 1.2s ease-in-out infinite both; }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes loadingDot { 0%, 80%, 100% { opacity: 0.3; transform: scale(0.9); } 40% { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen antialiased gradient-mesh">

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 md:py-8" id="visualize-app">
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('files.list') }}" class="hover:text-teal-400">My files</a>
                <span>/</span>
                <a href="{{ route('files.preview', $file->slug) }}" class="hover:text-teal-400 truncate max-w-[200px]" title="{{ $file->original_name }}">{{ $file->original_name }}</a>
                <span>/</span>
                <span class="text-teal-400 font-medium">Visualize</span>
            </nav>
            <h1 class="font-display text-2xl font-bold text-white">Visualize data</h1>
            <p class="text-slate-400 text-sm mt-0.5">Step 1: Choose which graphs and comparisons you want. Step 2: Generate and view.</p>
        </div>
        <a href="{{ route('files.preview', $file->slug) }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-600 text-slate-300 hover:bg-slate-800 text-sm font-medium transition">Back to preview</a>
    </header>

    <div id="loading" class="flex flex-col items-center py-16 text-slate-400">
        <div class="loading-dots flex gap-1.5 mb-3">
            <span class="w-2 h-2 rounded-full bg-teal-500"></span>
            <span class="w-2 h-2 rounded-full bg-teal-500"></span>
            <span class="w-2 h-2 rounded-full bg-teal-500"></span>
        </div>
        <p class="text-sm">Loading columns and suggestions…</p>
    </div>

    <div id="error-message" class="hidden rounded-xl bg-rose-950/50 border border-rose-500/40 p-4 text-rose-200 mb-6">
        <p id="error-text"></p>
    </div>

    {{-- Step 1: Select what to visualize --}}
    <div id="select-step" class="hidden space-y-6">
        <section class="rounded-2xl bg-slate-900/80 border border-slate-700/80 p-5 md:p-6">
            <h2 class="font-display text-lg font-semibold text-white mb-1">Suggested visualizations</h2>
            <p class="text-slate-400 text-sm mb-4">Check the graphs you want. We suggest these based on your columns.</p>
            <div id="suggested-charts-list" class="space-y-2 max-h-64 overflow-y-auto">
                <!-- Filled by JS -->
            </div>
        </section>

        <section class="rounded-2xl bg-slate-900/80 border border-slate-700/80 p-5 md:p-6">
            <h2 class="font-display text-lg font-semibold text-white mb-1">Correlation</h2>
            <p class="text-slate-400 text-sm mb-3">Pick which number columns to compare. We will show how they relate.</p>
            <div id="correlation-select-wrap" class="flex flex-wrap gap-2">
                <!-- Checkboxes for numeric columns -->
            </div>
        </section>

        <section class="rounded-2xl bg-slate-900/80 border border-slate-700/80 p-5 md:p-6">
            <h2 class="font-display text-lg font-semibold text-white mb-1">Regression</h2>
            <p class="text-slate-400 text-sm mb-3">Pick two number columns: X (horizontal) and Y (vertical). We will fit a line and show R².</p>
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">X column</label>
                    <select id="regression-x" class="rounded-lg bg-slate-800 border border-slate-600 text-slate-200 px-3 py-2 text-sm min-w-[140px]">
                        <option value="">— None —</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Y column</label>
                    <select id="regression-y" class="rounded-lg bg-slate-800 border border-slate-600 text-slate-200 px-3 py-2 text-sm min-w-[140px]">
                        <option value="">— None —</option>
                    </select>
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="button" id="btn-generate" class="px-6 py-3 rounded-xl bg-teal-500 hover:bg-teal-600 text-white font-semibold shadow-lg shadow-teal-500/20 transition">
                Generate charts
            </button>
        </div>
    </div>

    {{-- Step 2: Generated charts --}}
    <div id="results-step" class="hidden space-y-8">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-xl font-semibold text-white">Your charts</h2>
            <button type="button" id="btn-back" class="px-4 py-2 rounded-lg border border-slate-600 text-slate-300 hover:bg-slate-800 text-sm font-medium transition">Change selection</button>
        </div>

        <div id="charts-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"></div>

        <section id="correlation-section" class="hidden">
            <h3 class="font-display text-lg font-semibold text-white mb-3">How number columns relate</h3>
            <div class="rounded-2xl bg-slate-900/80 border border-slate-700/80 p-4 overflow-x-auto">
                <div id="correlation-table-wrap"></div>
            </div>
        </section>

        <section id="regression-section" class="hidden">
            <h3 class="font-display text-lg font-semibold text-white mb-3">Regression</h3>
            <div class="rounded-2xl bg-slate-900/80 border border-slate-700/80 p-4">
                <p id="regression-equation" class="text-slate-400 text-sm mb-2"></p>
                <div class="relative" style="min-height: 320px;">
                    <canvas id="regression-chart"></canvas>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    const FILE_SLUG = @json($file->slug);
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let suggestionsData = null;
    let buildData = null;
    let chartInstances = {};
    let regressionChart = null;

    function escapeHtml(s) {
        if (s == null) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function showError(msg) {
        document.getElementById('error-message').classList.remove('hidden');
        document.getElementById('error-text').textContent = msg;
    }
    function hideError() {
        document.getElementById('error-message').classList.add('hidden');
    }

    async function loadSuggestions() {
        const loading = document.getElementById('loading');
        const selectStep = document.getElementById('select-step');
        try {
            const res = await fetch('/files/' + FILE_SLUG + '/visualize-suggestions', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Failed to load suggestions');
            suggestionsData = data;
            loading.classList.add('hidden');
            selectStep.classList.remove('hidden');
            renderSelectStep();
        } catch (e) {
            loading.classList.add('hidden');
            showError(e.message || 'Failed to load suggestions.');
        }
    }

    function renderSelectStep() {
        const list = document.getElementById('suggested-charts-list');
        list.innerHTML = '';
        const suggested = suggestionsData.suggested_charts || [];
        suggested.forEach((s, i) => {
            const type = s.type || 'bar';
            const label = s.chart_label || type;
            const desc = s.description || (s.column || (s.x_column + ' vs ' + s.y_column));
            const key = type === 'scatter' ? (s.x_column + '|' + s.y_column) : (type + '|' + (s.column || ''));
            const div = document.createElement('label');
            div.className = 'flex items-center gap-3 p-2 rounded-lg hover:bg-slate-800/50 cursor-pointer';
            div.innerHTML = '<input type="checkbox" class="suggested-chart rounded border-slate-600 bg-slate-800 text-teal-500 focus:ring-teal-500" data-key="' + escapeHtml(key) + '" data-type="' + escapeHtml(type) + '" data-column="' + escapeHtml(s.column || '') + '" data-x="' + escapeHtml(s.x_column || '') + '" data-y="' + escapeHtml(s.y_column || '') + '">' +
                '<span class="text-sm text-slate-300">' + escapeHtml(label) + ': ' + escapeHtml(desc) + '</span>';
            list.appendChild(div);
        });
        if (suggested.length === 0) {
            list.innerHTML = '<p class="text-slate-500 text-sm">No suggestions. Add number or category columns to your data.</p>';
        }

        const corrWrap = document.getElementById('correlation-select-wrap');
        corrWrap.innerHTML = '';
        const numCols = suggestionsData.suggested_correlation_columns || [];
        numCols.forEach(col => {
            const label = document.createElement('label');
            label.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-600 cursor-pointer hover:border-teal-500/50';
            label.innerHTML = '<input type="checkbox" class="correlation-col rounded border-slate-600 bg-slate-700 text-teal-500" value="' + escapeHtml(col) + '"> <span class="text-sm text-slate-200">' + escapeHtml(col) + '</span>';
            corrWrap.appendChild(label);
        });

        const regX = document.getElementById('regression-x');
        const regY = document.getElementById('regression-y');
        regX.innerHTML = '<option value="">— None —</option>';
        regY.innerHTML = '<option value="">— None —</option>';
        numCols.forEach(col => {
            regX.appendChild(new Option(col, col));
            regY.appendChild(new Option(col, col));
        });
    }

    function getSelectedCharts() {
        const charts = [];
        document.querySelectorAll('.suggested-chart:checked').forEach(cb => {
            const type = cb.dataset.type;
            const column = cb.dataset.column;
            const x = cb.dataset.x;
            const y = cb.dataset.y;
            if (type === 'scatter') {
                if (x && y) charts.push({ type: 'scatter', x_column: x, y_column: y });
            } else if (column) {
                charts.push({ type: type, column: column });
            }
        });
        return charts;
    }

    function getSelectedCorrelationColumns() {
        const cols = [];
        document.querySelectorAll('.correlation-col:checked').forEach(cb => { cols.push(cb.value); });
        return cols;
    }

    function getRegressionSelection() {
        const x = document.getElementById('regression-x').value;
        const y = document.getElementById('regression-y').value;
        if (!x || !y) return {};
        return { x_column: x, y_column: y };
    }

    async function generateCharts() {
        const charts = getSelectedCharts();
        const correlation_columns = getSelectedCorrelationColumns();
        const regression = getRegressionSelection();
        if (charts.length === 0 && correlation_columns.length < 2 && !regression.x_column) {
            showError('Select at least one chart, or two columns for correlation, or X and Y for regression.');
            return;
        }
        hideError();
        document.getElementById('btn-generate').disabled = true;
        document.getElementById('btn-generate').textContent = 'Generating…';
        try {
            const res = await fetch('/files/' + FILE_SLUG + '/visualize-build', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF
                },
                body: JSON.stringify({ charts, correlation_columns, regression, _token: CSRF })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Build failed');
            buildData = data;
            document.getElementById('select-step').classList.add('hidden');
            document.getElementById('results-step').classList.remove('hidden');
            renderResults();
        } catch (e) {
            showError(e.message || 'Failed to generate charts.');
        }
        document.getElementById('btn-generate').disabled = false;
        document.getElementById('btn-generate').textContent = 'Generate charts';
    }

    function renderResults() {
        const grid = document.getElementById('charts-grid');
        grid.innerHTML = '';
        Object.keys(chartInstances).forEach(id => {
            chartInstances[id].destroy();
        });
        chartInstances = {};

        const charts = buildData.charts || [];
        const palette = ['rgba(20, 184, 166, 0.85)', 'rgba(14, 165, 233, 0.85)', 'rgba(245, 158, 11, 0.85)', 'rgba(244, 63, 94, 0.85)', 'rgba(139, 92, 246, 0.85)'];
        charts.forEach((ch, i) => {
            const card = document.createElement('div');
            card.className = 'rounded-2xl bg-slate-900/80 border border-slate-700/80 overflow-hidden';
            const canvasId = 'chart-' + i;
            const title = ch.type === 'scatter' ? (ch.data.x_column + ' vs ' + ch.data.y_column) : (ch.data.column || 'Chart');
            card.innerHTML = '<div class="p-3 border-b border-slate-700"><h3 class="font-display font-semibold text-white text-sm truncate">' + escapeHtml(title) + '</h3></div><div class="p-4" style="min-height: 280px;"><canvas id="' + canvasId + '"></canvas></div>';
            grid.appendChild(card);

            setTimeout(() => {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                if (ch.type === 'scatter') {
                    chartInstances[canvasId] = new Chart(canvas, {
                        type: 'scatter',
                        data: {
                            datasets: [{
                                label: 'Data',
                                data: ch.data.x.map((x, j) => ({ x: x, y: ch.data.y[j] })),
                                backgroundColor: palette[0],
                                borderColor: palette[0].replace('0.85', '1'),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 1.4,
                            scales: {
                                x: { title: { display: true, text: ch.data.x_column }, grid: { color: 'rgba(148,163,184,0.1)' } },
                                y: { title: { display: true, text: ch.data.y_column }, grid: { color: 'rgba(148,163,184,0.1)' } }
                            }
                        }
                    });
                } else {
                    const isPie = ch.type === 'pie' || ch.type === 'doughnut';
                    const sliceColors = ch.data.labels.map((_, j) => palette[j % palette.length]);
                    chartInstances[canvasId] = new Chart(canvas, {
                        type: ch.type,
                        data: {
                            labels: ch.data.labels.map(String),
                            datasets: [{
                                label: ch.data.column || '',
                                data: ch.data.values,
                                backgroundColor: isPie ? sliceColors : palette[0],
                                borderColor: isPie ? sliceColors.map(c => c.replace('0.85', '1')) : palette[0].replace('0.85', '1'),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 1.4,
                            plugins: { legend: { display: isPie } },
                            scales: isPie ? {} : { x: { grid: { color: 'rgba(148,163,184,0.1)' } }, y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.1)' } } }
                        }
                    });
                }
            }, 50);
        });

        const corrSection = document.getElementById('correlation-section');
        const corr = buildData.correlation;
        if (corr && corr.labels && corr.labels.length >= 2) {
            corrSection.classList.remove('hidden');
            const wrap = document.getElementById('correlation-table-wrap');
            let html = '<table class="w-full text-sm border-collapse"><thead><tr><th class="p-2 text-left border border-slate-600 text-slate-400 font-medium"></th>';
            corr.labels.forEach(l => { html += '<th class="p-2 text-center border border-slate-600 text-slate-400 font-medium truncate max-w-[90px]" title="' + escapeHtml(l) + '">' + escapeHtml(l) + '</th>'; });
            html += '</tr></thead><tbody>';
            corr.matrix.forEach((row, i) => {
                html += '<tr><td class="p-2 border border-slate-600 text-slate-300 font-medium truncate max-w-[120px]" title="' + escapeHtml(corr.labels[i]) + '">' + escapeHtml(corr.labels[i]) + '</td>';
                row.forEach((v, j) => {
                    const t = Math.max(-1, Math.min(1, v));
                    const hue = t >= 0 ? 166 : 0;
                    const light = 22 + Math.abs(t) * 28;
                    html += '<td class="p-2 border border-slate-600 text-center text-slate-200 font-medium" style="background: hsl(' + hue + ', 70%, ' + light + '%)">' + Number(v).toFixed(2) + '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table>';
            wrap.innerHTML = html;
        } else {
            corrSection.classList.add('hidden');
        }

        const regSection = document.getElementById('regression-section');
        const reg = buildData.regression;
        if (reg && reg.x_column && reg.y_column && reg.line_x && reg.line_y) {
            regSection.classList.remove('hidden');
            document.getElementById('regression-equation').textContent =
                'Y = ' + reg.intercept + ' + ' + reg.slope + ' × X   (R² = ' + reg.r_squared + ', ' + reg.n_points + ' points)';
            const regCanvas = document.getElementById('regression-chart');
            if (regressionChart) regressionChart.destroy();
            let scatterData = [];
            if (reg.x_points && reg.y_points && reg.x_points.length === reg.y_points.length) {
                scatterData = reg.x_points.map((x, j) => ({ x: x, y: reg.y_points[j] }));
            } else {
                const sc = buildData.charts.find(c => c.type === 'scatter' && c.data.x_column === reg.x_column && c.data.y_column === reg.y_column);
                if (sc && sc.data.x && sc.data.y) scatterData = sc.data.x.map((x, j) => ({ x: x, y: sc.data.y[j] }));
            }
            regressionChart = new Chart(regCanvas, {
                type: 'scatter',
                data: {
                    datasets: [
                        {
                            label: 'Data',
                            data: scatterData,
                            backgroundColor: 'rgba(20, 184, 166, 0.6)',
                            borderColor: 'rgba(20, 184, 166, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Regression line',
                            data: reg.line_x.map((x, j) => ({ x: x, y: reg.line_y[j] })),
                            type: 'line',
                            borderColor: 'rgba(245, 158, 11, 1)',
                            borderWidth: 2,
                            fill: false,
                            pointRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.4,
                    scales: {
                        x: { title: { display: true, text: reg.x_column }, grid: { color: 'rgba(148,163,184,0.1)' } },
                        y: { title: { display: true, text: reg.y_column }, grid: { color: 'rgba(148,163,184,0.1)' } }
                    }
                }
            });
        } else {
            regSection.classList.add('hidden');
        }
    }

    function backToSelect() {
        document.getElementById('results-step').classList.add('hidden');
        document.getElementById('select-step').classList.remove('hidden');
    }

    document.getElementById('btn-generate').addEventListener('click', generateCharts);
    document.getElementById('btn-back').addEventListener('click', backToSelect);
    loadSuggestions();
})();
</script>

</body>
</html>
