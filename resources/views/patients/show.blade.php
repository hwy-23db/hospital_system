@extends('layouts.app')

@section('content')

<div class="container mx-auto p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-5xl font-bold text-gray-800">Patient Information</h1>

    </div>


    <div>
            <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Patients
            </a>
    </div> <br>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: Patient Form / Details -->
    <div class="lg:col-span-2 bg-white shadow-lg rounded-lg p-6">
        <form>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Patient ID</label>
                    <input type="text" value="{{ $patient->id ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2" readonly>
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Full Name</label>
                    <input type="text" value="{{ $patient->name }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Age</label>
                    <input type="text" value="{{ $patient->age }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Gender</label>
                    <input type="text" value="{{ $patient->sex }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Phone Number</label>
                    <input type="text" value="{{ $patient->contact_phone }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Admission Date</label>
                    <input type="date" value="{{ $patient->admission_date ?? $patient->created_at->format('Y-m-d') }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Discharge Date</label>
                    <input type="date" value="{{ $patient->discharge_date ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Ward</label>
                    <input type="text" value="{{ $patient->ward ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Summary</label>
                    <input type="text" value="{{ $patient->clinician_summary }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-1">Status</label>
                    <input type="text" value="{{ $patient->discharge_status ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-600 font-medium mb-1">Doctor Assigned</label>
                    <input type="text" value="{{ $patient->doctor->name ?? 'Not Assigned' }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-600 font-medium mb-1">Nurse Assigned</label>
                    <input type="text" value="{{ $patient->nurse->name ?? 'Not Assigned' }}" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-600 font-medium mb-1">Treatment Record</label>
                    <textarea class="w-full border border-gray-300 rounded px-3 py-2" rows="4">{{ $patient->treatment_record ?? '' }}</textarea>
                </div>
            </div>
        </form>
    </div>

</div>

</div>
@endsection
