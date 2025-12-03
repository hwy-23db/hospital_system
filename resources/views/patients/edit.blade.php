@extends('layouts.app')

@section('content')
<div class="container-fluid py-5 bg-light">
    <div class="container-xl">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">Edit Patient Record</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('patients.index') }}" class="text-decoration-none text-muted">Patients</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Record</li>
                    </ol>
                </nav>
            </div>

            <div>
                <a href="{{ route('patients.index') }}" class="btn btn-white border shadow-sm rounded-pill px-4">
                    <i class="fas fa-arrow-left me-2 small"></i>Back to List
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 px-lg-5">
                        <div class="d-flex align-items-center">
                            <div class="avatar-initials bg-primary text-white rounded-circle me-3">
                                {{ substr($patient->name ?? 'P', 0, 1) }}
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">{{ $patient->name ?? 'Unknown' }}</h5>
                                <span class="badge bg-light text-secondary border">ID: {{ $patient->patient_id ?? 'PT0002' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        <form method="POST" action="{{ route('patients.update', $patient->id ?? '1') }}" class="needs-validation" novalidate>
                            @csrf
                            @method('PUT')

                            <h6 class="text-uppercase text-muted fw-bold small mb-4 tracking-wide">Personal Details</h6>

                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label for="name" class="form-label text-muted small fw-bold">Full Name</label>
                                    <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ $patient->name ?? 'Lara' }}" required>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-3">
                                    <label for="age" class="form-label text-muted small fw-bold">Age</label>
                                    <input type="number" class="form-control form-control-lg @error('age') is-invalid @enderror"
                                           id="age" name="age" value="{{ $patient->age ?? 36 }}" min="0" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="sex" class="form-label text-muted small fw-bold">Gender</label>
                                    <select class="form-select form-select-lg @error('sex') is-invalid @enderror" id="sex" name="sex" required>
                                        <option value="">Select</option>
                                        <option value="Male" {{ ($patient->sex ?? 'Female') == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ ($patient->sex ?? 'Female') == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ ($patient->sex ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label text-muted small fw-bold">Phone Number</label>
                                    <input type="tel" class="form-control form-control-lg @error('phone') is-invalid @enderror"
                                           id="phone" name="phone" value="{{ $patient->phone ?? '0911122221' }}" required>
                                </div>
                            </div>

                            <hr class="border-light mb-5">
                            <h6 class="text-uppercase text-muted fw-bold small mb-4 tracking-wide">Medical Details</h6>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label for="admission_date" class="form-label text-muted small fw-bold">Admission Date</label>
                                    <input type="date" class="form-control form-control-lg @error('admission_date') is-invalid @enderror"
                                           id="admission_date" name="admission_date" value="{{ $patient->admission_date ?? '2025-06-10' }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="discharge_date" class="form-label text-muted small fw-bold">Discharge Date</label>
                                    <input type="date" class="form-control form-control-lg @error('discharge_date') is-invalid @enderror"
                                           id="discharge_date" name="discharge_date" value="{{ $patient->discharge_date ?? '' }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="ward" class="form-label text-muted small fw-bold">Ward</label>
                                    <input type="text" class="form-control form-control-lg" id="ward" name="ward"
                                           value="{{ $patient->ward ?? '' }}" placeholder="e.g. Ward 2A">
                                </div>

                                <div class="col-md-6">
                                    <label for="condition" class="form-label text-muted small fw-bold">Medical Condition</label>
                                    <select class="form-select form-select-lg @error('condition') is-invalid @enderror" id="condition" name="condition" required>
                                        <option value="">Select Condition</option>
                                        <option value="General Medicine" {{ ($patient->condition ?? 'General Medicine') == 'General Medicine' ? 'selected' : '' }}>General Medicine</option>
                                        <option value="Cardiology" {{ ($patient->condition ?? '') == 'Cardiology' ? 'selected' : '' }}>Cardiology</option>
                                        <option value="Neurology" {{ ($patient->condition ?? '') == 'Neurology' ? 'selected' : '' }}>Neurology</option>
                                        <option value="Orthopedics" {{ ($patient->condition ?? '') == 'Orthopedics' ? 'selected' : '' }}>Orthopedics</option>
                                        <option value="Pediatrics" {{ ($patient->condition ?? '') == 'Pediatrics' ? 'selected' : '' }}>Pediatrics</option>
                                        <option value="Surgery" {{ ($patient->condition ?? '') == 'Surgery' ? 'selected' : '' }}>Surgery</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label text-muted small fw-bold">Status</label>
                                    <select class="form-select form-select-lg @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="Admitted" {{ ($patient->status ?? '') == 'Admitted' ? 'selected' : '' }}>Admitted</option>
                                        <option value="Under Treatment" {{ ($patient->status ?? '') == 'Under Treatment' ? 'selected' : '' }}>Under Treatment</option>
                                        <option value="Recovered" {{ ($patient->status ?? 'Recovered') == 'Recovered' ? 'selected' : '' }}>Recovered</option>
                                        <option value="Discharged" {{ ($patient->status ?? '') == 'Discharged' ? 'selected' : '' }}>Discharged</option>
                                        <option value="Critical" {{ ($patient->status ?? '') == 'Critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="doctor" class="form-label text-muted small fw-bold">Assigned Doctor</label>
                                    <input type="text" class="form-control form-control-lg" id="doctor" name="doctor"
                                           value="{{ $patient->doctor->name ?? 'Not Assigned' }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="block text-gray-600 font-medium mb-1">Case Of Death</label>
                                    <input type="text" name="cause_of_death" value="{{ $patient->cause_of_death ?? '' }}" class="w-full border border-gray-300 rounded px-3 py-2">
                                </div>

                                <div class="col-6">
                                    <label for="notes" class="form-label text-muted small fw-bold">Medical Notes</label>
                                    <textarea class="form-control bg-light border-0" id="notes" name="notes" rows="3" placeholder="Add notes here...">{{ $patient->notes ?? '' }}</textarea>
                                </div>

                                <div class="col-md-12">
                                <label class="block text-gray-600 font-medium mb-1">Treatment Records</label>
                                {{-- <div class="border border-gray-300 rounded px-3 py-2 bg-light w-full"> --}}
                                    <ul class="list-group w-full border border-gray-300 rounded px-3 py-2">
                                        @forelse($patient->treatments ?? [] as $record)
                                            <li class="list-group-item">{{ $record->treatment_type }}</li>
                                        @empty
                                            <li class="list-group-item text-muted">No treatment records yet.</li>
                                        @endforelse
                                    </ul>
                                {{-- </div> --}}
                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-4 mt-4 border-top">
                                <button type="button" class="btn btn-link text-danger text-decoration-none px-0" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    Delete Record
                                </button>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('patients.index') }}" class="btn btn-light px-4">Cancel</a>
                                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-4">Patient Snapshot</h6>

                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-square bg-blue-light text-primary rounded-3 me-3">
                                <i class="fas fa-bed"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Current Ward</small>
                                <span class="fw-bold">{{ $patient->ward ?? 'Ward 2A' }}</span>
                                <span class="d-block small text-muted">{{ $patient->condition ?? 'General Medicine' }}</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4">
                            <div class="icon-square bg-green-light text-success rounded-3 me-3">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Status</small>
                                <span class="fw-bold">{{ $patient->status ?? 'Recovered' }}</span>
                            </div>
                        </div>

                         <div class="d-flex align-items-start">
                            <div class="icon-square bg-orange-light text-warning rounded-3 me-3">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Admission Duration</small>
                                <span class="fw-bold">5 Days</span>
                                <span class="d-block small text-muted">Since {{ $patient->admission_date ?? '2025-06-10' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h6 class="fw-bold mb-0">Activity Log</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="clean-timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <p class="mb-0 fw-semibold">Patient Admitted</p>
                                    <small class="text-muted">{{ $patient->admission_date ?? '2025-06-10' }}</small>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <p class="mb-0 fw-semibold">Treatment Started</p>
                                    <small class="text-muted">Dr. Smith assigned</small>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <p class="mb-0 fw-semibold">Status Updated</p>
                                    <small class="text-muted">Changed to Recovered</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-5 text-center">
                <div class="mb-4">
                    <i class="fas fa-trash-alt text-danger display-4 opacity-25"></i>
                </div>
                <h4 class="fw-bold mb-2">Delete Patient Record?</h4>
                <p class="text-muted mb-4">
                    Are you sure you want to remove <strong>{{ $patient->name ?? 'this patient' }}</strong>?
                    This action cannot be undone.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('patients.destroy', $patient->id ?? '1') }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger px-4">Yes, Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Clean Custom Utilities */
    .avatar-initials {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
    }

    .tracking-wide { letter-spacing: 0.05em; }

    /* Clean Input Styling */
    .form-control, .form-select {
        border-color: #e5e7eb;
        font-size: 0.95rem;
        padding: 0.75rem 1rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
    }

    /* Icon Squares */
    .icon-square {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .bg-blue-light { background-color: rgba(13, 110, 253, 0.1); }
    .bg-green-light { background-color: rgba(25, 135, 84, 0.1); }
    .bg-orange-light { background-color: rgba(255, 193, 7, 0.1); }

    /* Minimal Timeline */
    .clean-timeline {
        position: relative;
        padding-left: 10px;
        border-left: 2px solid #f3f4f6;
    }
    .timeline-item {
        position: relative;
        padding-left: 25px;
        padding-bottom: 25px;
    }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-marker {
        position: absolute;
        left: -6px;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px #e5e7eb;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', e => e.target.value = e.target.value.replace(/\D/g, ''));

        const admission = document.getElementById('admission_date');
        const discharge = document.getElementById('discharge_date');
        admission.addEventListener('change', () => {
            discharge.min = admission.value;
            if(discharge.value && discharge.value < admission.value) discharge.value = admission.value;
        });

        // Bootstrap Validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    });
</script>
@endsection
