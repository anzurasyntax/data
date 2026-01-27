<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Data File</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-900 to-slate-800">

<div class="w-full max-w-lg bg-white/10 backdrop-blur-lg shadow-2xl rounded-2xl p-8 border border-white/20">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-3xl font-bold text-white">Upload Your Data File</h2>
            <p class="text-slate-200 text-sm mt-1">Signed in as {{ auth()->user()->email }}</p>
        </div>
        <form action="{{ route('auth.logout') }}" method="POST">
            @csrf
            <button type="submit" class="text-slate-200 hover:text-white underline font-semibold text-sm">
                Logout
            </button>
        </form>
    </div>

    <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div class="space-y-1">
            <label class="block text-white font-semibold">Select File Type</label>
            <select name="file_type" required
                    class="w-full p-3 rounded-lg bg-white/20 text-white border border-white/40 focus:ring-2 focus:ring-blue-400">
                <option class="text-black" value="">-- Select File Type --</option>
                @foreach(['txt','csv','xml','xlsx'] as $type)
                    <option class="text-black" value="{{ $type }}">{{ strtoupper($type) }}</option>
                @endforeach
            </select>
        </div>

        <div class="space-y-2">
            <label class="block text-white font-semibold">Upload File</label>

            {{-- Hidden native input for accessibility & form posting --}}
            <input
                id="file-input"
                type="file"
                name="file"
                required
                accept=".txt,.csv,.xml,.xlsx"
                class="sr-only"
            >

            {{-- Drag & drop zone --}}
            <div
                id="dropzone"
                class="group border-2 border-dashed border-white/40 rounded-xl bg-white/5 hover:bg-white/10 transition-all cursor-pointer px-4 py-6 sm:px-6 sm:py-8 flex flex-col items-center justify-center text-center"
            >
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-blue-500/20 border border-blue-400/60 flex items-center justify-center group-hover:bg-blue-500/30">
                            <svg class="w-7 h-7 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-5l-3-3m0 0l-3 3m3-3v12M4 12a4 4 0 014-4h1m6 0h1a4 4 0 014 4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-left">
                        <p class="text-sm sm:text-base text-slate-100 font-semibold">
                            Drag & drop your file here
                        </p>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1">
                            or <span class="underline decoration-dotted">click to browse</span>
                        </p>
                        <p class="text-[11px] sm:text-xs text-slate-400 mt-2">
                            Supported: .txt, .csv, .xml, .xlsx (max size depends on server limits)
                        </p>
                        <p id="selected-file-name" class="text-[11px] sm:text-xs text-emerald-200 mt-1 hidden">
                            File selected
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-lg transition-all">
            Upload File
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('files.list') }}" class="text-blue-200 hover:text-white underline font-semibold">
            View my uploaded files
        </a>
    </div>
</div>

<script>
    (function () {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('file-input');
        const fileNameEl = document.getElementById('selected-file-name');

        if (!dropzone || !fileInput) return;

        const highlight = () => {
            dropzone.classList.add('border-blue-400', 'bg-white/15');
        };

        const unhighlight = () => {
            dropzone.classList.remove('border-blue-400', 'bg-white/15');
        };

        const handleFiles = (files) => {
            if (!files || !files.length) return;

            const dt = new DataTransfer();
            dt.items.add(files[0]);
            fileInput.files = dt.files;

            if (fileNameEl) {
                fileNameEl.textContent = `Selected: ${files[0].name}`;
                fileNameEl.classList.remove('hidden');
            }
        };

        dropzone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                highlight();
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                unhighlight();
            });
        });

        dropzone.addEventListener('drop', (e) => {
            if (e.dataTransfer && e.dataTransfer.files) {
                handleFiles(e.dataTransfer.files);
            }
        });

        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
    })();
</script>

</body>
</html>
