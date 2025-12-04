<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Data File</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-lg bg-white/10 backdrop-blur-lg shadow-2xl rounded-2xl p-8 border border-white/20">

    <h2 class="text-3xl font-bold text-white text-center mb-6">Upload Your Data File</h2>

    <form action="{{ route('create') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
        @csrf

        <label class="block text-white font-semibold mb-2">Select File Type</label>
        <select name="file_type" id="fileType"
                class="w-full p-3 rounded-lg mb-5 bg-white/20 text-white border border-white/40 focus:ring-2 focus:ring-blue-400"
                required>
            <option class="text-black" value="">-- Select File Type --</option>
            <option class="text-black" value="txt">TXT</option>
            <option class="text-black" value="csv">CSV</option>
            <option class="text-black" value="xml">XML</option>
            <option class="text-black" value="xlsx">XLSX</option>
        </select>

        <label class="block text-white font-semibold mb-2">Upload File</label>
        <input type="file" id="fileInput" name="file" accept=".txt,.csv,.xml,.xlsx"
               class="w-full p-3 rounded-lg bg-white/20 text-white border border-white/40 cursor-pointer"
               required>

        <p id="fileError" class="text-red-300 text-sm mt-1 hidden">Invalid file type selected!</p>

        <button type="submit"
                class="w-full mt-6 bg-blue-600 hover:bg-blue-700 transition-all text-white font-bold py-3 rounded-lg shadow-lg">
            Upload File
        </button>
    </form>
</div>

<script>
    const fileTypeSelect = document.getElementById('fileType');
    const fileInput = document.getElementById('fileInput');
    const fileError = document.getElementById('fileError');

    fileInput.addEventListener('change', function () {
        const allowed = {
            txt: ['text/plain'],
            csv: ['text/csv', 'application/vnd.ms-excel'],
            xml: ['text/xml', 'application/xml'],
            xlsx: ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        };

        const selectedType = fileTypeSelect.value;
        const uploadedFileType = fileInput.files[0]?.type;

        if (selectedType && allowed[selectedType] && !allowed[selectedType].includes(uploadedFileType)) {
            fileError.classList.remove('hidden');
            fileInput.value = "";
        } else {
            fileError.classList.add('hidden');
        }
    });
</script>

</body>
</html>
