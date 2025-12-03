<!DOCTYPE html>
<html>
<head>
    <title>Preview & Column Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-900 text-white p-8">

<h2 class="text-3xl font-bold mb-6">Column Settings</h2>

<form method="POST">
    @csrf

    <input type="hidden" name="file_path" value="{{ $file_path }}">
    <input type="hidden" name="file_type" value="{{ $file_type }}">

    <table class="w-full bg-gray-800 rounded-lg overflow-hidden">
        <thead>
        <tr class="bg-gray-700">
            <th class="p-3">Column Name</th>
            <th class="p-3">Detected Type</th>
            <th class="p-3">Select Type</th>
            <th class="p-3">Treat Zero as False?</th>
        </tr>
        </thead>

        <tbody>
        @foreach($columns as $col)
            <tr class="border-b border-gray-700">
                <td class="p-3">{{ $col['name'] }}</td>
                <td class="p-3">
                    {{ $col['data_type'] }}
                </td>

                <td class="p-3">
                    <select name="column_types[{{ $col['name'] }}]"
                            class="bg-gray-700 p-2 rounded">
                        <option value="int">{{ $col['data_type'] }}</option>
                        <option value="int">Integer</option>
                        <option value="float">Float</option>
                        <option value="text">Text</option>
                        <option value="date">Date</option>
                    </select>
                </td>

                <td class="p-3 text-center">
                    <input type="checkbox" name="zero_false[{{ $col['name'] }}]">
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <button class="mt-6 bg-blue-600 px-6 py-3 rounded-lg font-bold">
        Continue →
    </button>

</form>

</body>
</html>
