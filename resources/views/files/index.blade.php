<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Uploaded Files</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-6xl mx-auto mt-10 bg-white p-6 shadow rounded">

    <h1 class="text-3xl font-bold mb-6 text-gray-800">Uploaded Files</h1>

    <table class="w-full border-collapse text-sm">
        <thead class="bg-gray-200">
        <tr>
            @foreach(['#','Original File Name','File Type','File Path','Size','Uploaded At','Actions'] as $th)
                <th class="border px-4 py-2">{{ $th }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse($files as $file)
            <tr class="hover:bg-gray-50">
                <td class="border px-4 py-2 text-center">{{ $loop->iteration }}</td>
                <td class="border px-4 py-2">
                    <a href="{{ route('process.show', $file->id) }}" class="text-blue-600 underline">
                        {{ $file->original_name }}
                    </a>
                </td>
                <td class="border px-4 py-2 text-center font-semibold">{{ strtoupper($file->file_type) }}</td>
                <td class="border px-4 py-2">{{ $file->file_path }}</td>
                <td class="border px-4 py-2 text-center">{{ number_format($file->file_size / 1024, 2) }} KB</td>
                <td class="border px-4 py-2 text-center">{{ $file->created_at->format('Y-m-d H:i') }}</td>
                <td class="border px-4 py-2 text-center">
                    <div class="flex items-center justify-center gap-3">
                        <a href="{{ route('files.quality', $file->id) }}" 
                           class="text-blue-600 hover:text-blue-800 underline text-sm">
                            Quality
                        </a>
                        <span class="text-gray-300">|</span>
                        <form action="{{ route('files.destroy', $file->id) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete this file?');" 
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 underline text-sm">
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-gray-500 border px-4 py-4">
                    No files uploaded yet.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

</div>

</body>
</html>
