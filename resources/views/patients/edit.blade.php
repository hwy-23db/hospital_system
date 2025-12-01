@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 px-4 px-lg-5">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Edit Patient Record</h3>
            <p class="text-muted mb-0">Update patient information and medical details</p>
        </div>

        <div>
            <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Patients
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Patient Information Form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="avatar-container bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="fas fa-user text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Patient Information</h5>
                            <p class="text-muted small mb-0">ID: {{ $patient->id ?? 'PT0002' }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('patients.update', $patient->id ?? '1') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            {{-- <!-- Patient ID -->
                            <div class="col-md-6">
                                <label for="patient_id" class="form-label fw-semibold">Patient ID</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-id-card text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control" id="patient_id" name="patient_id"
                                           value="{{ $patient->patient_id ?? 'PT0002' }}" readonly>
                                </div>
                            </div> --}}

                            <!-- Full Name -->
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-user text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ $patient->name ?? 'Lara' }}" required>
                                </div>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Age -->
                            <div class="col-md-6">
                                <label for="age" class="form-label fw-semibold">Age <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-calendar text-muted"></i>
                                    </span>
                                    <input type="number" class="form-control @error('age') is-invalid @enderror"
                                           id="age" name="age" value="{{ $patient->age ?? 36 }}" min="0" max="150" required>
                                </div>
                                @error('age')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Gender -->
                            <div class="col-md-6">
                                <label for="sex" class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-venus-mars text-muted"></i>
                                    </span>
                                    <select class="form-select @error('sex') is-invalid @enderror" id="sex" name="sex" required>
                                        <option value="">Select sex</option>
                                        <option value="Male" {{ ($patient->sex ?? 'Female') == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ ($patient->sex ?? 'Female') == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ ($patient->sex ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                                @error('sex')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone Number -->
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-phone text-muted"></i>
                                    </span>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                           id="phone" name="phone" value="{{ $patient->phone ?? '0911122221' }}"
                                           pattern="[0-9]{10,15}" required>
                                </div>
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Admission Date -->
                            <div class="col-md-6">
                                <label for="admission_date" class="form-label fw-semibold">Admission Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-calendar-alt text-muted"></i>
                                    </span>
                                    <input type="date" class="form-control @error('admission_date') is-invalid @enderror"
                                           id="admission_date" name="admission_date"
                                           value="{{ $patient->admission_date ?? '2025-06-10' }}" required>
                                </div>
                                @error('admission_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Discharge Date -->
                            <div class="col-md-6">
                                <label for="discharge_date" class="form-label fw-semibold">Discharge Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-calendar-check text-muted"></i>
                                    </span>
                                    <input type="date" class="form-control @error('discharge_date') is-invalid @enderror"
                                           id="discharge_date" name="discharge_date"
                                           value="{{ $patient->discharge_date ?? '' }}" min="{{ $patient->admission_date ?? '2025-06-10' }}">
                                </div>
                                @error('discharge_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Ward -->
                            <div class="col-md-6">
                                <label for="ward" class="form-label fw-semibold">Ward <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-hospital text-muted"></i>
                                    </span>
                                    <select class="form-select @error('ward') is-invalid @enderror" id="ward" name="ward" required>
                                        <option value="">Select Ward</option>
                                        <option value="Ward 1A" {{ ($patient->ward ?? 'Ward 2A') == 'Ward 1A' ? 'selected' : '' }}>Ward 1A</option>
                                        <option value="Ward 1B" {{ ($patient->ward ?? '') == 'Ward 1B' ? 'selected' : '' }}>Ward 1B</option>
                                        <option value="Ward 2A" {{ ($patient->ward ?? 'Ward 2A') == 'Ward 2A' ? 'selected' : '' }}>Ward 2A</option>
                                        <option value="Ward 2B" {{ ($patient->ward ?? '') == 'Ward 2B' ? 'selected' : '' }}>Ward 2B</option>
                                        <option value="ICU" {{ ($patient->ward ?? '') == 'ICU' ? 'selected' : '' }}>ICU</option>
                                        <option value="Emergency" {{ ($patient->ward ?? '') == 'Emergency' ? 'selected' : '' }}>Emergency</option>
                                    </select>
                                </div>
                                @error('ward')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Condition -->
                            <div class="col-md-6">
                                <label for="condition" class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-stethoscope text-muted"></i>
                                    </span>
                                    <select class="form-select @error('condition') is-invalid @enderror" id="condition" name="condition" required>
                                        <option value="">Select Condition</option>
                                        <option value="General Medicine" {{ ($patient->condition ?? 'General Medicine') == 'General Medicine' ? 'selected' : '' }}>General Medicine</option>
                                        <option value="Cardiology" {{ ($patient->condition ?? '') == 'Cardiology' ? 'selected' : '' }}>Cardiology</option>
                                        <option value="Neurology" {{ ($patient->condition ?? '') == 'Neurology' ? 'selected' : '' }}>Neurology</option>
                                        <option value="Orthopedics" {{ ($patient->condition ?? '') == 'Orthopedics' ? 'selected' : '' }}>Orthopedics</option>
                                        <option value="Pediatrics" {{ ($patient->condition ?? '') == 'Pediatrics' ? 'selected' : '' }}>Pediatrics</option>
                                        <option value="Surgery" {{ ($patient->condition ?? '') == 'Surgery' ? 'selected' : '' }}>Surgery</option>
                                    </select>
                                </div>
                                @error('condition')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-info-circle text-muted"></i>
                                    </span>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="Admitted" {{ ($patient->status ?? '') == 'Admitted' ? 'selected' : '' }}>Admitted</option>
                                        <option value="Under Treatment" {{ ($patient->status ?? '') == 'Under Treatment' ? 'selected' : '' }}>Under Treatment</option>
                                        <option value="Recovered" {{ ($patient->status ?? 'Recovered') == 'Recovered' ? 'selected' : '' }}>Recovered</option>
                                        <option value="Discharged" {{ ($patient->status ?? '') == 'Discharged' ? 'selected' : '' }}>Discharged</option>
                                        <option value="Critical" {{ ($patient->status ?? '') == 'Critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                </div>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Doctor Assigned -->
                            <div class="col-md-6">
                                <label for="doctor" class="form-label fw-semibold">Doctor Assigned</label>
                                <div class="md:col-span-2">

                                <input type="text" value="{{ $patient->doctor->name ?? 'Not Assigned' }}" class="w-full border border-gray-300 rounded px-3 py-2">
                                </div>
                                @error('doctor')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold">Medical Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ $patient->notes ?? '' }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold">Treatment Record</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="treatment_record" name="treatment_record" rows="3">{{ $patient->treatment_record ?? '' }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>



                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between mt-4">
                            <div>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-2"></i>Delete Record
                                </button>
                            </div>
                            <div>
                                <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Patient
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Side Panel -->
        <div class="col-lg-4">
            <!-- Quick Info Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0">Quick Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="badge bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-semibold">Duration of Stay</p>
                            <p class="text-muted small mb-0">{{ $patient->admission_date ?? '2025-06-10' }} - Present</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <div class="badge bg-success bg-opacity-10 text-success rounded-circle p-2 me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-semibold">Current Status</p>
                            <p class="text-muted small mb-0">{{ $patient->status ?? 'Recovered' }}</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="badge bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3">
                            <i class="fas fa-hospital-alt"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-semibold">Ward Information</p>
                            <p class="text-muted small mb-0">{{ $patient->ward ?? 'Ward 2A' }} - {{ $patient->condition ?? 'General Medicine' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0">Recent Activity</h5>
                </div>
                <div class="card-body p-4">
                    <div class="timeline">
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="badge bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div>
                                    <p class="mb-0 fw-semibold">Patient Admitted</p>
                                    <p class="text-muted small mb-0">{{ $patient->admission_date ?? '2025-06-10' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="badge bg-info bg-opacity-10 text-info rounded-circle p-2 me-3">
                                    <i class="fas fa-notes-medical"></i>
                                </div>
                                <div>
                                    <p class="mb-0 fw-semibold">Treatment Started</p>
                                    <p class="text-muted small mb-0">{{ $patient->admission_date ?? '2025-06-11' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="d-flex">
                                <div class="badge bg-success bg-opacity-10 text-success rounded-circle p-2 me-3">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                                <div>
                                    <p class="mb-0 fw-semibold">Status Updated</p>
                                    <p class="text-muted small mb-0">2 days ago</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="badge bg-danger bg-opacity-10 text-danger rounded-circle p-3 me-3">
                        <i class="fas fa-exclamation-triangle fs-4"></i>
                    </div>
                    <div>
                        <p class="mb-0 fw-semibold">Are you sure you want to delete this patient record?</p>
                        <p class="text-muted small mb-0">This action cannot be undone.</p>
                    </div>
                </div>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        Patient: <strong>{{ $patient->name ?? 'Lara' }}</strong> (ID: {{ $patient->patient_id ?? 'PT0002' }})
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('patients.destroy', $patient->id ?? '1') }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Record
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-container {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .input-group-text {
        border-right: none;
    }

    .form-control, .form-select {
        border-left: none;
    }

    .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.1);
    }

    .timeline-item {
        position: relative;
        padding-left: 15px;
    }

    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 40px;
        bottom: -15px;
        width: 1px;
        background-color: #e5e7eb;
    }

    .badge {
        font-weight: 500;
    }
</style>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-format phone number
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                e.target.value = value;
            }
        });

        // Set minimum discharge date to admission date
        const admissionDate = document.getElementById('admission_date');
        const dischargeDate = document.getElementById('discharge_date');

        admissionDate.addEventListener('change', function() {
            dischargeDate.min = this.value;
            if (dischargeDate.value && dischargeDate.value < this.value) {
                dischargeDate.value = this.value;
            }
        });

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
</script>

@endsection
