@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="fw-bold mb-3">{{ isset($patient) ? 'Edit Patient' : 'Add Patient' }}</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ isset($patient) ? route('patients.update', $patient->id) : route('patients.store') }}" method="POST">
                @csrf
                @if(isset($patient)) @method('PUT') @endif

                <div class="row g-3">

                    <!-- BASIC INFO -->
                    <div class="col-md-4">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control"
                            value="{{ old('name', $patient->name ?? '') }}" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Sex</label>
                        <select name="sex" class="form-select">
                            <option value="">Select</option>
                            <option value="Male" {{ old('sex', $patient->sex ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('sex', $patient->sex ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control"
                            value="{{ old('age', $patient->age ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control"
                            value="{{ old('dob', $patient->dob ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Permanent Address</label>
                        <textarea name="permanent_address" class="form-control">{{ old('permanent_address', $patient->permanent_address ?? '') }}</textarea>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">Select</option>
                            <option value="Single" {{ old('marital_status', $patient->marital_status ?? '') == 'Single' ? 'selected' : '' }}>Single</option>
                            <option value="Married" {{ old('marital_status', $patient->marital_status ?? '') == 'Married' ? 'selected' : '' }}>Married</option>
                            <option value="Divorced" {{ old('marital_status', $patient->marital_status ?? '') == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                            <option value="Widowed" {{ old('marital_status', $patient->marital_status ?? '') == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Ethnic Group</label>
                        <input type="text" name="ethnic_group" class="form-control"
                            value="{{ old('ethnic_group', $patient->ethnic_group ?? '') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Religion</label>
                        <input type="text" name="religion" class="form-control"
                            value="{{ old('religion', $patient->religion ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation" class="form-control"
                            value="{{ old('occupation', $patient->occupation ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Previous Admission Date</label>
                        <input type="date" name="prev_admission_date" class="form-control"
                            value="{{ old('prev_admission_date', $patient->prev_admission_date ?? '') }}">
                    </div>

                    <!-- CONTACT + RELATIVE INFO -->
                    <div class="col-md-4">
                        <label class="form-label">Nearest Relative / Friend Name</label>
                        <input type="text" name="nearest_relative_name" class="form-control"
                            value="{{ old('nearest_relative_name', $patient->nearest_relative_name ?? '') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="relationship" class="form-control"
                            value="{{ old('relationship', $patient->relationship ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Referred By</label>
                        <input type="text" name="referred_by" class="form-control"
                            value="{{ old('referred_by', $patient->referred_by ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Police Case</label>
                        <select name="police_case" class="form-select">
                            <option value="">Select</option>
                            <option value="yes" {{ old('police_case', $patient->police_case ?? '') == 'yes' ? 'selected' : '' }}>Yes</option>
                            <option value="no" {{ old('police_case', $patient->police_case ?? '') == 'no' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Present Address</label>
                        <textarea name="present_address" class="form-control">{{ old('present_address', $patient->present_address ?? '') }}</textarea>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Medical Officer</label>
                        <input type="text" name="medical_officer" class="form-control"
                            value="{{ old('medical_officer', $patient->medical_officer ?? '') }}">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Contact Phone Number</label>
                        <input type="text" name="contact_phone" class="form-control"
                            value="{{ old('contact_phone', $patient->contact_phone ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Service</label>
                        <input type="text" name="service" class="form-control"
                            value="{{ old('service', $patient->service ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Ward</label>
                        <input type="text" name="ward" class="form-control"
                            value="{{ old('ward', $patient->ward ?? '') }}">
                    </div>

                    <!-- FAMILY -->
                    <div class="col-md-4">
                        <label class="form-label">Father's Name</label>
                        <input type="text" name="father_name" class="form-control"
                            value="{{ old('father_name', $patient->father_name ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Admission Date</label>
                        <input type="date" name="admission_date" class="form-control"
                            value="{{ old('admission_date', $patient->admission_date ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Admission Time</label>
                        <input type="time" name="admission_time" class="form-control"
                            value="{{ old('admission_time', $patient->admission_time ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" name="mother_name" class="form-control"
                            value="{{ old('mother_name', $patient->mother_name ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Discharge Date</label>
                        <input type="date" name="discharge_date" class="form-control"
                            value="{{ old('discharge_date', $patient->discharge_date ?? '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Discharge Time</label>
                        <input type="time" name="discharge_time" class="form-control"
                            value="{{ old('discharge_time', $patient->discharge_time ?? '') }}">
                    </div>

                    <!-- MEDICAL DETAILS -->
                    <div class="col-md-6">
                        <label class="form-label">Admitted For</label>
                        <textarea name="admitted_for" class="form-control">{{ old('admitted_for', $patient->admitted_for ?? '') }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Drug Allergy</label>
                        <input type="text" name="drug_allergy" class="form-control"
                            value="{{ old('drug_allergy', $patient->drug_allergy ?? '') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control">{{ old('remarks', $patient->remarks ?? '') }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Discharge Diagnosis (Principal Condition)</label>
                        <input type="text" name="discharge_diagnosis" class="form-control"
                            value="{{ old('discharge_diagnosis', $patient->discharge_diagnosis ?? '') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Other Diagnosis / Complications</label>
                        <input type="text" name="other_diagnosis" class="form-control"
                            value="{{ old('other_diagnosis', $patient->other_diagnosis ?? '') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">External Cause of Injury</label>
                        <input type="text" name="external_cause_of_injury" class="form-control"
                            value="{{ old('external_cause_of_injury', $patient->external_cause_of_injury ?? '') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Clinician Summary</label>
                        <input type="text" name="clinician_summary" class="form-control"
                            value="{{ old('clinician_summary', $patient->clinician_summary ?? '') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Surgical Operation / Procedure</label>
                        <input type="text" name="surgical_procedure" class="form-control"
                            value="{{ old('surgical_procedure', $patient->surgical_procedure ?? '') }}">
                    </div>

                    <!-- FIXED DUPLICATE -->
                    <div class="col-md-6">
                        <label class="form-label">Type Of Discharge</label>
                        <input type="text" name="discharge_type" class="form-control"
                            value="{{ old('discharge_type', $patient->discharge_type ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Discharge Status</label>
                        <input type="text" name="discharge_status" class="form-control"
                            value="{{ old('discharge_status', $patient->discharge_status ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cause Of Death (if applicable)</label>
                        <input type="text" name="cause_of_death" class="form-control"
                            value="{{ old('cause_of_death', $patient->cause_of_death ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Autopsy</label>
                        <select name="autopsy" class="form-select">
                            <option value="">Select</option>
                            <option value="yes" {{ old('autopsy', $patient->autopsy ?? '') == 'yes' ? 'selected' : '' }}>Yes</option>
                            <option value="no" {{ old('autopsy', $patient->autopsy ?? '') == 'no' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Certify By</label>
                        <input type="text" name="certify_by" class="form-control"
                            value="{{ old('certify_by', $patient->certify_by ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Approved By</label>
                        <input type="text" name="approved_by" class="form-control"
                            value="{{ old('approved_by', $patient->approved_by ?? '') }}">
                    </div>

                    <!-- FIXED LAYOUT: Doctor + Nurse + Signature -->
                    <div class="col-md-3">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" class="form-select">
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}"
                                    {{ old('doctor_id', $patient->doctor_id ?? '') == $doctor->id ? 'selected' : '' }}>
                                    {{ $doctor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Nurse</label>
                        <select name="nurse_id" class="form-select">
                            <option value="">Select Nurse</option>
                            @foreach($nurses as $nurse)
                                <option value="{{ $nurse->id }}"
                                    {{ old('nurse_id', $patient->nurse_id ?? '') == $nurse->id ? 'selected' : '' }}>
                                    {{ $nurse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Doctor Name</label>
                        <input type="text" name="doctor_name" class="form-control"
                            value="{{ old('doctor_name', $patient->doctor_name ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Doctor Signature</label>
                        <input type="text" name="doctor_signature" class="form-control"
                            value="{{ old('doctor_signature', $patient->doctor_signature ?? '') }}">
                    </div>

                </div><!-- END ROW -->

                <div class="mt-4">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('patients.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
