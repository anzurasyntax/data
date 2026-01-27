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

        <div class="space-y-1">
            <label class="block text-white font-semibold">Upload File</label>
            <input type="file" name="file" required
                   accept=".txt,.csv,.xml,.xlsx"
                   class="w-full p-3 rounded-lg bg-white/20 text-white border border-white/40 cursor-pointer">
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-lg transition-all">
            Upload File
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('process.index') }}" class="text-blue-200 hover:text-white underline font-semibold">
            View my uploaded files
        </a>
    </div>
</div>

</body>
</html>
