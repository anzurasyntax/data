<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insight & Strategy · {{ $file->original_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', system-ui, sans-serif; }
        .font-display { font-family: 'Outfit', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased">

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 md:py-12">
    {{-- Header --}}
    <header class="mb-8 md:mb-10">
        <nav class="text-sm text-slate-500 mb-2">
            <a href="{{ route('files.list') }}" class="hover:text-slate-700">My files</a>
            <span class="mx-1">/</span>
            <a href="{{ route('files.preview', $file->slug) }}" class="hover:text-slate-700 truncate max-w-[200px] inline-block align-bottom" title="{{ $file->original_name }}">{{ $file->original_name }}</a>
            <span class="mx-1">/</span>
            <span class="text-slate-800 font-medium">Insight & Strategy</span>
        </nav>
        <h1 class="font-display text-2xl md:text-3xl font-bold text-slate-900">Insight & Strategy Report</h1>
        <p class="text-slate-600 mt-1">Clear conclusions from your data in simple English. No charts — just findings, risks, and what to do next.</p>
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('files.preview', $file->slug) }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 text-sm font-medium transition">
                Back to preview
            </a>
        </div>
    </header>

    <div id="loading" class="py-16 text-center text-slate-500">
        <p class="font-medium">Analyzing data and generating insights…</p>
        <p class="text-sm mt-1">This may take a few seconds.</p>
    </div>

    <div id="error-message" class="hidden rounded-xl bg-red-50 border border-red-200 p-5 text-red-800 mb-6">
        <p id="error-text"></p>
    </div>

    <div id="report-container" class="hidden space-y-10">
        {{-- Key Findings --}}
        <section>
            <h2 class="font-display text-lg font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4">Key Findings</h2>
            <ul id="key-findings-list" class="space-y-3 list-none pl-0">
                <!-- Filled by JS -->
            </ul>
        </section>

        {{-- Identified Risks --}}
        <section>
            <h2 class="font-display text-lg font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4">Identified Risks</h2>
            <div id="risks-list" class="space-y-4">
                <!-- Filled by JS -->
            </div>
            <p id="no-risks-message" class="hidden text-slate-500 text-sm">No significant risks were identified from the current data.</p>
        </section>

        {{-- Recommended Strategies --}}
        <section>
            <h2 class="font-display text-lg font-semibold text-slate-900 border-b border-slate-200 pb-2 mb-4">Recommended Strategies</h2>
            <ul id="strategies-list" class="space-y-4 list-none pl-0">
                <!-- Filled by JS -->
            </ul>
        </section>
    </div>
</div>

<script>
(function () {
    const FILE_SLUG = @json($file->slug);

    function escapeHtml(s) {
        if (s == null) return '';
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function severityClass(severity) {
        const map = { high: 'bg-red-100 text-red-800 border-red-200', medium: 'bg-amber-100 text-amber-800 border-amber-200', low: 'bg-slate-100 text-slate-700 border-slate-200' };
        return map[severity] || map.low;
    }

    function riskTypeLabel(type) {
        const map = { business: 'Business Risk', data: 'Data Risk', operational: 'Operational Risk' };
        return map[type] || type;
    }

    function priorityClass(priority) {
        const map = { immediate: 'bg-red-100 text-red-800', 'short-term': 'bg-amber-100 text-amber-800', 'long-term': 'bg-slate-100 text-slate-700' };
        return map[priority] || map['long-term'];
    }

    function renderReport(data) {
        // Key Findings
        const findingsList = document.getElementById('key-findings-list');
        findingsList.innerHTML = '';
        (data.key_findings || []).forEach(function (f) {
            const li = document.createElement('li');
            li.className = 'flex gap-3 text-slate-700';
            li.innerHTML = '<span class="text-slate-400 mt-0.5">•</span><span>' + escapeHtml(f.text) + '</span>';
            findingsList.appendChild(li);
        });
        if (!(data.key_findings || []).length) {
            const li = document.createElement('li');
            li.className = 'text-slate-500 italic';
            li.textContent = 'No findings generated.';
            findingsList.appendChild(li);
        }

        // Risks
        const risksList = document.getElementById('risks-list');
        const noRisksMsg = document.getElementById('no-risks-message');
        risksList.innerHTML = '';
        const risks = data.risks || [];
        if (risks.length === 0) {
            noRisksMsg.classList.remove('hidden');
        } else {
            noRisksMsg.classList.add('hidden');
            risks.forEach(function (r) {
                const severity = (r.severity || 'low').toLowerCase();
                const typeLabel = riskTypeLabel((r.type || '').toLowerCase());
                const card = document.createElement('div');
                card.className = 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm';
                card.innerHTML =
                    '<div class="flex flex-wrap items-center gap-2 mb-2">' +
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-md border text-xs font-medium ' + severityClass(severity) + '">' + escapeHtml(severity) + '</span>' +
                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-md border border-slate-200 bg-slate-50 text-slate-600 text-xs font-medium">' + escapeHtml(typeLabel) + '</span>' +
                    '</div>' +
                    '<h3 class="font-display font-semibold text-slate-900 text-sm mb-1">' + escapeHtml(r.title) + '</h3>' +
                    '<p class="text-slate-600 text-sm leading-relaxed">' + escapeHtml(r.description) + '</p>';
                risksList.appendChild(card);
            });
        }

        // Strategies
        const strategiesList = document.getElementById('strategies-list');
        strategiesList.innerHTML = '';
        (data.strategies || []).forEach(function (s) {
            const priority = (s.priority || 'long-term').toLowerCase().replace(' ', '-');
            const li = document.createElement('li');
            li.className = 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm';
            li.innerHTML =
                '<div class="flex flex-wrap items-center gap-2 mb-2">' +
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium ' + priorityClass(priority) + '">' + escapeHtml(s.priority) + '</span>' +
                (s.category ? '<span class="text-slate-500 text-xs">' + escapeHtml(s.category) + '</span>' : '') +
                '</div>' +
                '<h3 class="font-display font-semibold text-slate-900 text-sm mb-2">' + escapeHtml(s.title) + '</h3>' +
                '<p class="text-slate-600 text-sm leading-relaxed">' + escapeHtml(s.reason) + '</p>';
            strategiesList.appendChild(li);
        });
        if (!(data.strategies || []).length) {
            const li = document.createElement('li');
            li.className = 'text-slate-500 italic text-sm';
            li.textContent = 'No strategies generated.';
            strategiesList.appendChild(li);
        }
    }

    async function loadReport() {
        const loading = document.getElementById('loading');
        const container = document.getElementById('report-container');
        const errEl = document.getElementById('error-message');
        const errText = document.getElementById('error-text');
        try {
            const res = await fetch('/files/' + FILE_SLUG + '/insight-strategy-data', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to generate report');
            }
            loading.classList.add('hidden');
            container.classList.remove('hidden');
            renderReport(data);
        } catch (e) {
            loading.classList.add('hidden');
            errEl.classList.remove('hidden');
            errText.textContent = e.message || 'Failed to load the Insight & Strategy report.';
        }
    }

    loadReport();
})();
</script>

</body>
</html>
