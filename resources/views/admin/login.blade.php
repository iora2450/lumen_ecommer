<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Lumens Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-slate-50 min-h-screen grid place-items-center">
    <div class="w-full max-w-sm">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-lg">
            <div class="text-center mb-6">
                <span class="text-2xl font-bold text-[#203749]">L<span class="text-[#FFAE00]">u</span>mens</span>
                <p class="text-sm text-slate-500 mt-1">Panel administrativo</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-800 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-[#FFAE00] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:border-[#FFAE00] focus:outline-none">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remember" class="rounded">
                    Recordarme
                </label>
                <button type="submit"
                        class="w-full rounded-lg bg-[#203749] text-white py-2.5 font-semibold hover:bg-black">
                    Iniciar sesión
                </button>
            </form>

            <div class="mt-4 text-center text-xs text-slate-500">
                <p>Demo: <code>admin@lumens.local</code> / <code>lumens2026</code></p>
            </div>
        </div>
    </div>
</body>
</html>
