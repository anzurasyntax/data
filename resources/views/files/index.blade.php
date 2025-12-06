<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Uploaded Files</title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-6xl mx-auto mt-10 bg-white p-6 shadow rounded">

    <h1 class="text-3xl font-bold mb-6 text-gray-800">Uploaded Files</h1>

    <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead>
        <tr class="bg-gray-200">
            <th class="border px-4 py-2">#</th>
            <th class="border px-4 py-2">Original File Name</th>
            <th class="border px-4 py-2">File Type</th>
            <th class="border px-4 py-2">File Path</th>
            <th class="border px-4 py-2">Size</th>
            <th class="border px-4 py-2">Uploaded At</th>
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

                <td class="border px-4 py-2 text-center font-semibold">
                    {{ strtoupper($file->file_type) }}
                </td>

                <td class="border px-4 py-2">
                    {{ $file->file_path }}
                </td>

                <td class="border px-4 py-2 text-center">
                    {{ number_format($file->file_size / 1024, 2) }} KB
                </td>

                <td class="border px-4 py-2 text-center">
                    {{ $file->created_at->format('Y-m-d H:i') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-gray-500 border px-4 py-4">
                    No files uploaded yet.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

</div>
</body>
</html>
