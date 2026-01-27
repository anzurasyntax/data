<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Files</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-6xl mx-auto mt-6 md:mt-10 px-3 md:px-0">
    <div class="bg-white p-4 md:p-6 shadow rounded-lg">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Uploaded Files</h1>
                <p class="text-xs md:text-sm text-gray-500 mt-1">
                    Manage your datasets, open the preview, or review their quality reports.
                </p>
            </div>
            <a href="{{ route('files.upload') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 transition">
                Upload new file
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-xs md:text-sm min-w-max">
                <thead class="bg-gray-200">
                <tr>
                    @foreach(['#','Original File Name','File Type','File Path','Size','Uploaded At','Actions'] as $th)
                        <th class="border px-3 md:px-4 py-2 text-left whitespace-nowrap">{{ $th }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @forelse($files as $file)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-3 md:px-4 py-2 text-center align-middle">{{ $loop->iteration }}</td>
                        <td class="border px-3 md:px-4 py-2 align-middle max-w-[220px] md:max-w-none">
                            <a href="{{ route('files.preview', $file->slug) }}" class="text-blue-600 hover:text-blue-800 underline break-words">
                                {{ $file->original_name }}
                            </a>
                        </td>
                        <td class="border px-3 md:px-4 py-2 text-center font-semibold align-middle whitespace-nowrap">
                            {{ strtoupper($file->file_type) }}
                        </td>
                        <td class="border px-3 md:px-4 py-2 align-middle text-[11px] md:text-xs break-all">
                            {{ $file->file_path }}
                        </td>
                        <td class="border px-3 md:px-4 py-2 text-center align-middle whitespace-nowrap">
                            {{ number_format($file->file_size / 1024, 2) }} KB
                        </td>
                        <td class="border px-3 md:px-4 py-2 text-center align-middle whitespace-nowrap">
                            {{ $file->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="border px-3 md:px-4 py-2 text-center align-middle">
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-3">
                                <a href="{{ route('files.quality', $file->slug) }}"
                                   class="text-blue-600 hover:text-blue-800 underline text-xs md:text-sm">
                                    Quality
                                </a>
                                <span class="hidden sm:inline text-gray-300">|</span>
                                <form action="{{ route('files.delete', $file->slug) }}" method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this file?');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800 underline text-xs md:text-sm">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-500 border px-4 py-6 text-sm">
                            No files uploaded yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
