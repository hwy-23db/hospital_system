<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { background: #f5f7fb; }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: #307de9;
            color: #fff;
            position: fixed;
            padding: 25px 20px;
        }
        .sidebar a {
            color: #e6e9ee;
            text-decoration: none;
            display: block;
            margin: 12px 0;
            padding: 10px;
            border-radius: 8px;
        }
        .sidebar a.active, .sidebar a:hover {
            background: #f2f4f7;
            color: #222020;
        }
        .main-content {
            margin-left: 260px;
            padding: 25px;
        }
    </style>
</head>

<body class="font-sans antialiased">

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">

        <div class="sidebar">

    <h4 class="fw-bold mb-4">Hospital System</h4>

    <!-- Logged-in user -->
    <div class="mb-4">
        <div class="fw-semibold">{{ auth()->user()->name }}</div>
        <div class="text-secondary small">{{ auth()->user()->email }}</div>
    </div>

    <!-- Dashboard (all roles) -->
    <a href="/dashboard" class="{{ request()->is('dashboard') ? 'active' : '' }}">🏥 Dashboard</a>

    <!-- Admin Links -->
    @if(auth()->user()->role === 'admin')
        <a href="/patients" class="{{ request()->is('patients*') ? 'active' : '' }}">🧑‍⚕️ Patients</a>
        <a href="/doctor" class="{{ request()->is('doctor*') ? 'active' : '' }}">🧑‍⚕️ Doctors</a>
        <a href="/nurse" class="{{ request()->is('nurse*') ? 'active' : '' }}">🧑‍⚕️ Nurses</a>
        <a href="/users" class="{{ request()->is('users*') ? 'active' : '' }}">👤 Users</a>
    @endif

    <!-- Doctor Links -->
        @if(auth()->user()->role === 'doctor')
            <a href="{{ route('patients.index') }}" class="{{ request()->is('patients*') ? 'active' : '' }}">
                🧑‍⚕️ My Patients
            </a>
        @endif


    <!-- Nurse Links -->
        @if(auth()->user()->role === 'nurse')
            <a href="{{ route('patients.index') }}"
            class="{{ request()->is('patients*') ? 'active' : '' }}">
            🧑‍⚕️ My Patients
            </a>
        @endif


    <hr class="text-secondary">

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button class="btn btn-danger w-100">Logout</button>
    </form>

</div>


        <!-- MAIN CONTENT -->
        <div class="main-content">

            @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow mb-3">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
            @endisset

            <main>
                @yield('content')
            </main>
        </div>

    </div>

</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</html>
