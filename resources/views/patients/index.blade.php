@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
            🏥 Patient Records
        </h1>

        @if(auth()->user()->role === 'root_user')
        <a href="{{ route('patients.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow text-sm">
            + Add Patient
        </a>
        @endif

        @if(auth()->user()->role === 'receptionist')
        <a href="{{ route('patients.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow text-sm">
            + Add Patient
        </a>
        @endif
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
                        @php
                            $user = auth()->user();
                            $canEdit = false;
                            $canView = false;

                            if ($user->role === 'root_user') {
                                // root user can do everything
                                $canEdit = true;
                                $canView = true;

                            } elseif ($user->role === 'receptionist') {
                                // receptionist can only view
                                $canView = true;

                            } elseif ($user->role === 'doctor') {
                                // doctor can view + edit ONLY their patients
                                if ($patient->doctor_id == $user->id) {
                                    $canView = true;
                                    $canEdit = true;
                                }

                            } elseif ($user->role === 'nurse') {
                                // nurse can view + edit ONLY their patients
                                if ($patient->nurse_id == $user->id) {
                                    $canView = true;
                                    $canEdit = true;
                                }
                            }
                        @endphp


                        @if($canView)
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
                                @if($canView)
                                    <a href="{{ route('patients.show', $patient->id) }}"
                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-lg shadow text-xs flex items-center gap-1">
                                        View
                                    </a>
                                @endif

                                @if($canEdit)
                                    <a href="{{ route('patients.edit', $patient->id) }}"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg shadow text-xs flex items-center gap-1">
                                        Edit
                                    </a>
                                @endif

                                @if($user->role === 'root_user')
                                    <form action="{{ route('patients.destroy', $patient->id) }}" method="POST" onsubmit="return confirm('Delete this patient?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-lg shadow text-xs flex items-center gap-1">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>

                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
