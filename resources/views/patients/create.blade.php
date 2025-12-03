@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-lg-12">

            <div class="card shadow-sm border-0 rounded">
                <div class="card-header bg-primary text-dark">
                    <h3 class="mb-0">{{ isset($patient) ? 'Edit Patient' : 'Add Patient' }}</h3>
                </div>
                <div class="card-body p-4">

                    <form action="{{ isset($patient) ? route('patients.update', $patient->id) : route('patients.store') }}" method="POST">
                        @csrf
                        @if(isset($patient)) @method('PUT') @endif

                        <div class="row g-3">

                            <!-- BASIC INFO -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Name *</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $patient->name ?? '') }}" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Sex</label>
                                <select name="sex" class="form-select">
                                    <option value="">Select</option>
                                    <option value="Male" {{ old('sex', $patient->sex ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('sex', $patient->sex ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Age</label>
                                <input type="number" name="age" class="form-control" value="{{ old('age', $patient->age ?? '') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="{{ old('dob', $patient->dob ?? '') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Permanent Address</label>
                                <textarea name="permanent_address" class="form-control" rows="2">{{ old('permanent_address', $patient->permanent_address ?? '') }}</textarea>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Marital Status</label>
                                <select name="marital_status" class="form-select">
                                    <option value="">Select</option>
                                    @foreach(['Single','Married','Divorced','Widowed'] as $status)
                                        <option value="{{ $status }}" {{ old('marital_status', $patient->marital_status ?? '') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Ethnic Group</label>
                                <input type="text" name="ethnic_group" class="form-control" value="{{ old('ethnic_group', $patient->ethnic_group ?? '') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Religion</label>
                                <input type="text" name="religion" class="form-control" value="{{ old('religion', $patient->religion ?? '') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Occupation</label>
                                <input type="text" name="occupation" class="form-control" value="{{ old('occupation', $patient->occupation ?? '') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Previous Admission Date</label>
                                <input type="date" name="prev_admission_date" class="form-control" value="{{ old('prev_admission_date', $patient->prev_admission_date ?? '') }}">
                            </div>

                            <!-- CONTACT + RELATIVE INFO -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nearest Relative / Friend Name</label>
                                <input type="text" name="nearest_relative_name" class="form-control" value="{{ old('nearest_relative_name', $patient->nearest_relative_name ?? '') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Relationship</label>
                                <input type="text" name="relationship" class="form-control" value="{{ old('relationship', $patient->relationship ?? '') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Referred By</label>
                                <input type="text" name="referred_by" class="form-control" value="{{ old('referred_by', $patient->referred_by ?? '') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Police Case</label>
                                <select name="police_case" class="form-select">
                                    <option value="">Select</option>
                                    <option value="yes" {{ old('police_case', $patient->police_case ?? '') == 'yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="no" {{ old('police_case', $patient->police_case ?? '') == 'no' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Present Address</label>
                                <textarea name="present_address" class="form-control" rows="2">{{ old('present_address', $patient->present_address ?? '') }}</textarea>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Medical Officer</label>
                                <input type="text" name="medical_officer" class="form-control" value="{{ old('medical_officer', $patient->medical_officer ?? '') }}">
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Contact Phone Number</label>
                                <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $patient->contact_phone ?? '') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Service</label>
                                <input type="text" name="service" class="form-control" value="{{ old('service', $patient->service ?? '') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Ward</label>
                                <input type="text" name="ward" class="form-control" value="{{ old('ward', $patient->ward ?? '') }}">
                            </div>

                            <!-- FAMILY -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Father's Name</label>
                                <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $patient->father_name ?? '') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Admission Date</label>
                                <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', $patient->admission_date ?? '') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Admission Time</label>
                                <input type="time" name="admission_time" class="form-control" value="{{ old('admission_time', $patient->admission_time ?? '') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Mother's Name</label>
                                <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $patient->mother_name ?? '') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Discharge Date</label>
                                <input type="date" name="discharge_date" class="form-control" value="{{ old('discharge_date', $patient->discharge_date ?? '') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Discharge Time</label>
                                <input type="time" name="discharge_time" class="form-control" value="{{ old('discharge_time', $patient->discharge_time ?? '') }}">
                            </div>

                            <!-- MEDICAL DETAILS -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Admitted For</label>
                                <textarea name="admitted_for" class="form-control" rows="2">{{ old('admitted_for', $patient->admitted_for ?? '') }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Drug Sensitivity/Allergy</label>
                                <input type="text" name="drug_allergy" class="form-control" value="{{ old('drug_allergy', $patient->drug_allergy ?? '') }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $patient->remarks ?? '') }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Discharge Diagnosis (Principal Morbid Condition)</label>
                                <input type="text" name="discharge_diagnosis" class="form-control" value="{{ old('discharge_diagnosis', $patient->discharge_diagnosis ?? '') }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Other Diagnosis / Complications & Associated Conditions</label>
                                <input type="text" name="other_diagnosis" class="form-control" value="{{ old('other_diagnosis', $patient->other_diagnosis ?? '') }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">External Cause of Injury</label>
                                <input type="text" name="external_cause_of_injury" class="form-control" value="{{ old('external_cause_of_injury', $patient->external_cause_of_injury ?? '') }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Clinician Summary</label>
                                <input type="text" name="clinician_summary" class="form-control" value="{{ old('clinician_summary', $patient->clinician_summary ?? '') }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Surgical Operation / Procedure</label>
                                <input type="text" name="surgical_procedure" class="form-control" value="{{ old('surgical_procedure', $patient->surgical_procedure ?? '') }}">
                            </div>

                            <!-- TREATMENT SECTION -->
                            <div class="col-12 mt-4">
                                <h5 class="fw-semibold mb-2">Select Treatment Type</h5>
                                <div class="row g-3">
                                    @php
                                        $treatment_types = [
                                            'surgery' => 'Surgery',
                                            'radiotherapy' => 'Radiotherapy',
                                            'chemotherapy' => 'Chemotherapy',
                                            'targeted_therapy' => 'Targeted Therapy',
                                            'hormone_therapy' => 'Hormone Therapy',
                                            'immunotherapy' => 'Immunotherapy',
                                            'intervention_therapy' => 'Intervention Therapy',
                                            'other_treatments' => 'Other Treatments',
                                            'supportive_care' => 'Supportive Care'
                                        ];
                                    @endphp
                                    @foreach($treatment_types as $key => $label)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="treatment_type" value="{{ $key }}" id="treatment_{{ $key }}" {{ old('treatment_type', $patient->treatment_type ?? '') == $key ? 'checked' : '' }}>
                                            <label class="form-check-label fw-normal" for="treatment_{{ $key }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Type Of Discharge</label>
                                <select name="discharge_type" class="form-select">
                                    <option value="">Select</option>
                                    @foreach(['With Approvel','Signed & Gone/Signed & Left','Absconded','Refer To Other Hospital (DC/REFER)' , 'Others' ,'Descharge On Request (D/R)'] as $status)
                                        <option value="{{ $status }}" {{ old('discharge_type', $patient->discharge_type ?? '') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Discharge Status</label>
                                <select name="discharge_status" class="form-select">
                                    <option value="">Select</option>
                                    @foreach(['Recovered/Cured ','Imporved','Not Improved','Expired' , 'Others' ] as $status)
                                        <option value="{{ $status }}" {{ old('discharge_status', $patient->discharge_status ?? '') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cause Of Death</label>
                                <input type="text" name="cause_of_death" class="form-control" value="{{ old('cause_of_death', $patient->surgical_procedure ?? '') }}">
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

                            <!-- ASSIGN DOCTOR & NURSE -->
                            <div class="col-md-6 mt-3">
                                <label class="form-label fw-semibold">Assign Doctor</label>
                                <select name="doctor_id" class="form-select" required>
                                    <option value="">Select Doctor</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" {{ old('doctor_id', $patient->doctor_id ?? '') == $doctor->id ? 'selected' : '' }}>{{ $doctor->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mt-3">
                                <label class="form-label fw-semibold">Assign Nurse</label>
                                <select name="nurse_id" class="form-select" required>
                                    <option value="">Select Nurse</option>
                                    @foreach($nurses as $nurse)
                                        <option value="{{ $nurse->id }}" {{ old('nurse_id', $patient->nurse_id ?? '') == $nurse->id ? 'selected' : '' }}>{{ $nurse->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div><!-- END ROW -->

                        <div class="mt-4 d-flex justify-content-start">
                            <button class="btn btn-primary">Save</button>
                            <a href="{{ route('patients.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                        </div>

                    </form>

                </div><!-- END CARD BODY -->
            </div><!-- END CARD -->

        </div><!-- END COL -->
    </div><!-- END ROW -->
</div><!-- END CONTAINER -->
@endsection
