<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#1e1b4b">
    <title>@yield('title', 'Mashaec') · Acceso Rápido</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: #0f0e17; color: #e8e6f0; font-family: 'Sansation', 'Inter', sans-serif; }
        .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.10); border-radius: 1rem; }
        .btn-primary { background: #4f46e5; color: #fff; border-radius: 0.75rem; font-weight: 600; transition: background 0.2s; }
        .btn-primary:active { background: #4338ca; }
        .btn-danger { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); border-radius: 0.75rem; }
        .input { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.75rem; color: #e8e6f0; }
        .input:focus { outline: none; border-color: #4f46e5; background: rgba(79,70,229,0.1); }
        .input::placeholder { color: rgba(232,230,240,0.35); }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <div class="flex-1 flex flex-col max-w-md mx-auto w-full px-4 py-6">
        @yield('content')
    </div>
</body>
</html>
