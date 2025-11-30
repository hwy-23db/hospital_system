@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
            🏥 Patient Records
        </h1>

        <a href="{{ route('patients.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-black px-4 py-2 rounded-lg shadow text-sm">
            + Add Patient
        </a>
    </div>

    <!-- Table Container -->
    <div class="bg-white border border-gray-200 shadow-md rounded-xl overflow-hidden">

        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="bg-blue-50 border-b">
                    <tr class="text-gray-700 text-sm uppercase">
                        <th class="py-3 px-4">Name</th>
                        <th class="py-3 px-4">Age</th>
                        <th class="py-3 px-4">Gender</th>
                        <th class="py-3 px-4">Phone</th>
                        <th class="py-3 px-4">Admission Date</th>
                        <th class="py-3 px-4">Ward</th>
                        <th class="py-3 px-4">Condition</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Actions</th>
                    </tr>
                </thead>

                <tbody class="text-gray-800 text-sm">

                    @foreach ($patients as $patient)
                    <tr class="border-b hover:bg-blue-50/50 transition">

                        <td class="py-4 px-4 font-semibold">
                            <div>{{ $patient->name }}</div>
                            <div class="text-xs text-gray-500">
                                ID: PT{{ str_pad($patient->id, 4, '0', STR_PAD_LEFT) }}
                            </div>
                        </td>

                        <td class="py-4 px-4">{{ $patient->age }}</td>
                        <td class="py-4 px-4">{{ $patient->sex }}</td>
                        <td class="py-4 px-4">{{ $patient->contact_phone }}</td>
                        <td class="py-4 px-4">{{ $patient->admission_date }}</td>
                        <td class="py-4 px-4">{{ $patient->ward }}</td>

                        <td class="py-4 px-4">
                            <span class="px-3 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                {{ $patient->service }}
                            </span>
                        </td>

                        <td class="py-4 px-4">
                            <span class="px-3 py-1 rounded-full text-xs
                                @if($patient->status == 'Recovered') bg-green-100 text-green-700
                                @elseif($patient->status == 'Admitted') bg-yellow-100 text-yellow-700
                                @else bg-red-100 text-red-700 @endif">
                                {{ $patient->discharge_status }}
                            </span>
                        </td>

                        <td class="py-4 px-4 flex gap-2">
                            @php
                                $user = auth()->user();
                                $canEdit = false;

                                // Admin can edit/delete all
                                if ($user->role === 'admin') {
                                    $canEdit = true;
                                }

                                // Nurse can edit assigned patients only
                                if ($user->role === 'nurse') {
                                    $nurse = \App\Models\Nurse::where('email', $user->email)->first();
                                    if ($nurse && $patient->nurse_id === $nurse->id) $canEdit = true;
                                }

                                // Doctor: NO edit rights anymore
                                // (Removed the doctor edit block entirely)
                            @endphp

                            <!-- View button: admin, nurses assigned, doctors assigned -->
                            @if($user->role === 'admin' || $user->role === 'doctor' || $canEdit)
                                <a href="{{ route('patients.show', $patient->id) }}"
                                class="bg-green-600 hover:bg-green-700 text-primary px-3 py-1 rounded-lg shadow text-xs flex items-center gap-1">
                                    View
                                </a>
                            @endif

                            <!-- Edit button: admin + nurses assigned -->
                            @if($canEdit)
                                <a href="{{ route('patients.edit', $patient->id) }}"
                                class="bg-blue-600 hover:bg-blue-700 text-black px-3 py-1 rounded-lg shadow text-xs flex items-center gap-1">
                                    Edit
                                </a>
                            @endif

                            <!-- Delete button: admin only -->
                            @if($user->role === 'admin')
                                <form action="{{ route('patients.destroy', $patient->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Delete this patient?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-lg shadow text-xs flex items-center gap-1">
                                        Delete
                                    </button>
                                </form>
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
