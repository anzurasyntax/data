<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-900 to-slate-800">

<div class="w-full max-w-md bg-white/10 backdrop-blur-lg shadow-2xl rounded-2xl p-8 border border-white/20">
    <h2 class="text-3xl font-bold text-white text-center mb-2">Create account</h2>
    <p class="text-slate-200 text-center mb-6">Sign up to upload and manage your files</p>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('auth.register.submit') }}" method="POST" class="space-y-4">
        @csrf

        <div class="space-y-1">
            <label class="block text-white font-semibold">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="w-full p-3 rounded-lg bg-white/20 text-white border border-white/40 focus:ring-2 focus:ring-blue-400 outline-none">
        </div>

        <div class="space-y-1">
            <label class="block text-white font-semibold">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full p-3 rounded-lg bg-white/20 text-white border border-white/40 focus:ring-2 focus:ring-blue-400 outline-none">
        </div>

        <div class="space-y-1">
            <label class="block text-white font-semibold">Password</label>
            <input type="password" name="password" required
                   class="w-full p-3 rounded-lg bg-white/20 text-white border border-white/40 focus:ring-2 focus:ring-blue-400 outline-none">
        </div>

        <div class="space-y-1">
            <label class="block text-white font-semibold">Confirm Password</label>
            <input type="password" name="password_confirmation" required
                   class="w-full p-3 rounded-lg bg-white/20 text-white border border-white/40 focus:ring-2 focus:ring-blue-400 outline-none">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-lg transition-all">
            Create account
        </button>
    </form>

    <p class="text-slate-200 text-center mt-6 text-sm">
        Already have an account?
        <a href="{{ route('auth.login') }}" class="text-blue-300 hover:text-blue-200 underline font-semibold">Login</a>
    </p>
</div>

</body>
</html>

