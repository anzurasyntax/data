<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-900 to-slate-800">

<div class="w-full max-w-md bg-white/10 backdrop-blur-lg shadow-2xl rounded-2xl p-8 border border-white/20">
    <h2 class="text-3xl font-bold text-white text-center mb-2">Welcome back</h2>
    <p class="text-slate-200 text-center mb-6">Login with your email and password</p>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('auth.login.submit') }}" method="POST" class="space-y-4">
        @csrf

        <div class="space-y-1">
            <label class="block text-white font-semibold">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full p-3 rounded-lg bg-white/20 text-white border border-white/40 focus:ring-2 focus:ring-blue-400 outline-none">
        </div>

        <div class="space-y-1">
            <label class="block text-white font-semibold">Password</label>
            <input type="password" name="password" required
                   class="w-full p-3 rounded-lg bg-white/20 text-white border border-white/40 focus:ring-2 focus:ring-blue-400 outline-none">
        </div>

        <label class="flex items-center gap-2 text-slate-200 text-sm">
            <input type="checkbox" name="remember" class="rounded border-white/40 bg-white/20">
            Remember me
        </label>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-lg transition-all">
            Login
        </button>
    </form>

    <p class="text-slate-200 text-center mt-6 text-sm">
        Don’t have an account?
        <a href="{{ route('auth.register') }}" class="text-blue-300 hover:text-blue-200 underline font-semibold">Create one</a>
    </p>
</div>

</body>
</html>

