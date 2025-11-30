@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto px-4 py-6">

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
        👤 User Records
    </h1>

    @if(auth()->user()->role === 'admin')
    <a href="{{ route('users.create') }}"
        class="bg-blue-600 hover:bg-blue-700 text-dark px-4 py-2 rounded-lg shadow text-sm">
        + Add User
    </a>
    @endif
</div>

<!-- Table Container -->
<div class="bg-white border border-gray-200 shadow-md rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-auto text-left">
            <thead class="bg-blue-50 border-b">
                <tr class="text-gray-700 text-sm uppercase">
                    <th class="py-3 px-4">Name</th>
                    <th class="py-3 px-4">Email</th>
                    <th class="py-3 px-4">Role</th>
                    <th class="py-3 px-4">Actions</th>
                </tr>
            </thead>

            <tbody class="text-gray-800 text-sm">
                @foreach ($users as $user)
                <tr class="border-b hover:bg-blue-50/50 transition">

                    <td class="py-4 px-4 font-semibold">
                        <div>{{ $user->name }}</div>
                        <div class="text-xs text-gray-500">
                            ID: PT{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                        </div>
                    </td>

                    <td class="py-4 px-4">{{ $user->email }}</td>

                    <td class="py-4 px-4">{{ $user->role }}</td>

                    <td class="py-4 px-4 flex gap-2">
                        @if(auth()->user()->role === 'admin')
                        <a href="{{ route('users.edit', $user->id) }}"
                            class="bg-blue-600 hover:bg-blue-700 text-dark px-3 py-1 rounded-lg shadow text-xs flex items-center gap-1">
                            ✏️ Edit
                        </a>

                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                              onsubmit="return confirm('Delete this user?')">
                            @csrf
                            @method('DELETE')
                            <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-lg shadow text-xs flex items-center gap-1">
                                🗑 Delete
                            </button>
                        </form>
                        @else
                        <span class="text-gray-500 text-xs">No actions</span>
                        @endif
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


</div>
@endsection
