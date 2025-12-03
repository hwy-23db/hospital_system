<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap (CSS only) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- VITE (Tailwind + your CSS + your JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { background: #f5f7fb; }
        .sidebar { width: 250px; height: 100vh; background: #307de9; color: #fff; position: fixed; padding: 25px 20px; overflow-y: auto; }
        .sidebar a { color: #e6e9ee; text-decoration: none; display: block; margin: 12px 0; padding: 10px; border-radius: 8px; transition: 0.2s; }
        .sidebar a.active, .sidebar a:hover { background: #f2f4f7; color: #222020; }
        .main-content { margin-left: 260px; padding: 25px; }
    </style>
</head>

<body class="font-sans">
    <div class="min-h-screen">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <h4 class="fw-bold mb-4">Hospital System</h4>

            <!-- Logged-in user -->
            <div class="mb-4">
                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                <div class="text-light small">{{ auth()->user()->email }}</div>
            </div>

            <!-- Dashboard -->
            <a href="/dashboard" class="{{ request()->is('dashboard') ? 'active' : '' }}">🏥 Dashboard</a>

            <!-- Patient Links -->
            @php
                $role = auth()->user()->role;
            @endphp

            @if($role === 'root_user')
                <a href="{{ route('patients.index') }}" class="{{ request()->is('patients*') ? 'active' : '' }}">🧑‍⚕️ All Patients</a>
                <a href="{{ route('users.index') }}" class="{{ request()->is('users*') ? 'active' : '' }}">👤 Users</a>
            @elseif($role === 'receptionist')
                <a href="{{ route('patients.index') }}" class="{{ request()->is('patients*') ? 'active' : '' }}">🧑‍⚕️ Patients</a>
            @elseif($role === 'doctor' || $role === 'nurse')
                <a href="{{ route('patients.index') }}" class="{{ request()->is('patients*') ? 'active' : '' }}">🧑‍⚕️ My Patients</a>
            @endif

            <hr class="text-light">

            <!-- Logout -->
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="btn btn-danger w-100">Logout</button>
            </form>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            @isset($header)
                <header class="bg-white shadow mb-3 p-3 rounded">{{ $header }}</header>
            @endisset

            <main>
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS (Bundle includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
