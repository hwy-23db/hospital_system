# Admission Endpoints - Role Restrictions

Complete breakdown of role-based access control for all admission-related endpoints.

---

## Summary Table

| Endpoint | Method | Root | Admission | Doctor | Nurse |
|----------|--------|------|-----------|--------|-------|
| List Admissions | GET | ✅ All | ✅ All | ✅ Assigned only | ✅ Assigned only |
| Get Admission Details | GET | ✅ All | ✅ All | ✅ Assigned only | ✅ Assigned only |
| Create Admission | POST | ✅ | ✅ | ❌ | ❌ |
| Update Admission | PUT/PATCH | ✅ All fields | ✅ All fields | ✅ Medical fields only (assigned) | ❌ |
| Discharge Patient | POST | ✅ | ❌ | ✅ Assigned only | ❌ |
| Confirm Death | POST | ✅ | ❌ | ✅ Assigned only | ❌ |
| Convert to Inpatient | POST | ✅ | ✅ | ✅ Assigned only | ❌ |
| Admission Statistics | GET | ✅ | ✅ | ❌ | ❌ |
| Get Patient Admission History | GET | ✅ All | ✅ All | ✅ Assigned only | ✅ Assigned only |

---

## Detailed Endpoint Restrictions

### 1. List Admissions
**Endpoint:** `GET /api/admissions`

| Role | Access | Filter Applied |
|------|--------|----------------|
| `root_user` | ✅ All admissions | None - sees everything |
| `admission` | ✅ All admissions | None - sees everything |
| `doctor` | ✅ Only assigned admissions | `where('doctor_id', user_id)` |
| `nurse` | ✅ Only assigned admissions | `where('nurse_id', user_id)` |
| Other roles | ❌ 403 Forbidden | - |

**Additional Filters Available:**
- `?status=admitted|discharged|deceased|transferred`
- `?admission_type=inpatient|outpatient`
- `?per_page=15` (pagination)

---

### 2. Get Admission Details
**Endpoint:** `GET /api/admissions/{id}`

| Role | Access | Condition |
|------|--------|-----------|
| `root_user` | ✅ All admissions | No restrictions |
| `admission` | ✅ All admissions | No restrictions |
| `doctor` | ✅ Only if assigned | `admission->doctor_id === user->id` |
| `nurse` | ✅ Only if assigned | `admission->nurse_id === user->id` |
| Other roles | ❌ 403 Forbidden | - |

**Access Check Logic:**
```php
canAccessAdmission($user, $admission):
- root_user/admission: true (always)
- doctor: admission->doctor_id === user->id
- nurse: admission->nurse_id === user->id
- default: false
```

---

### 3. Create Admission (Admit Patient)
**Endpoint:** `POST /api/patients/{patientId}/admit`

| Role | Access | Notes |
|------|--------|-------|
| `root_user` | ✅ | Can create any admission |
| `admission` | ✅ | Can create any admission |
| `doctor` | ❌ | Cannot create admissions |
| `nurse` | ❌ | Cannot create admissions |
| Other roles | ❌ | Cannot create admissions |

**Error Response (403):**
```json
{
    "message": "Unauthorized. Only admission staff can create admissions."
}
```

---

### 4. Update Admission
**Endpoint:** `PUT /api/admissions/{id}` or `PATCH /api/admissions/{id}`

#### Access Control

| Role | Can Update? | Scope |
|------|-------------|-------|
| `root_user` | ✅ | All admissions, all fields |
| `admission` | ✅ | All admissions, all fields |
| `doctor` | ✅ | Only assigned admissions, medical fields only |
| `nurse` | ❌ | Cannot update admissions |

#### Field Restrictions by Role

**Root/Admission Staff:**
- ✅ **All fields** can be updated (except restricted fields like `status`, `admission_type` which use dedicated endpoints)
- ✅ Can update staff assignment (`doctor_id`, `nurse_id`)
- ✅ Can update administrative fields (ward, bed, service, etc.)
- ✅ Can update medical fields

**Doctor (Assigned Only):**
- ✅ **Medical fields only:**
  - `initial_diagnosis`
  - `drug_allergy_noted`
  - `remarks`
  - `discharge_date`
  - `discharge_time`
  - `discharge_diagnosis`
  - `other_diagnosis`
  - `external_cause_of_injury`
  - `clinician_summary`
  - `surgical_procedure`
  - `discharge_type`
  - `discharge_status`
  - `discharge_instructions`
  - `follow_up_instructions`
  - `follow_up_date`
  - `cause_of_death`
  - `autopsy`
  - `time_of_death`
  - `certified_by`
  - `approved_by`
  - `attending_doctor_name`
  - `attending_doctor_signature`
  - `status` (though this should use dedicated endpoints)
- ❌ **Cannot update:**
  - Staff assignment (`doctor_id`, `nurse_id`)
  - Administrative fields (ward, bed, service, admission_date, etc.)
  - Patient assignment (`patient_id`)

**Nurse:**
- ❌ Cannot update admissions (read-only access)

**Access Check:**
```php
canUpdateAdmission($user, $admission):
- root_user/admission: true (always)
- doctor: admission->doctor_id === user->id
- nurse: false (always)
- default: false
```

**Error Responses:**
- 403: "Unauthorized. You do not have permission to update this admission."
- 422: "Ward cannot be specified for outpatient admissions." (if trying to set ward on outpatient)

---

### 5. Discharge Patient
**Endpoint:** `POST /api/admissions/{id}/discharge`

| Role | Access | Condition |
|------|--------|-----------|
| `root_user` | ✅ | Can discharge any admission |
| `admission` | ❌ | Cannot discharge patients |
| `doctor` | ✅ | Only assigned admissions |
| `nurse` | ❌ | Cannot discharge patients |

**Doctor Restriction:**
- Must be assigned as `doctor_id` on the admission
- Check: `admission->doctor_id === user->id`

**Error Responses:**
- 403: "Unauthorized. Only doctors can discharge patients."
- 403: "Unauthorized. You can only discharge patients assigned to you." (doctor not assigned)
- 400: "Cannot discharge. Patient is deceased." (if status is deceased)
- 400: "Patient is not currently admitted. Current status: {status}" (if already discharged/transferred)

---

### 6. Confirm Death
**Endpoint:** `POST /api/admissions/{id}/confirm-death`

| Role | Access | Condition |
|------|--------|-----------|
| `root_user` | ✅ | Can confirm death on any admission |
| `admission` | ❌ | Cannot confirm death |
| `doctor` | ✅ | Only assigned admissions |
| `nurse` | ❌ | Cannot confirm death |

**Doctor Restriction:**
- Must be assigned as `doctor_id` on the admission
- Check: `admission->doctor_id === user->id`

**Error Responses:**
- 403: "Unauthorized. Only doctors can confirm death."
- 403: "Unauthorized. You can only confirm death for patients assigned to you." (doctor not assigned)
- 400: "Patient death has already been confirmed in a previous admission."
- 400: "Cannot confirm death on a discharged admission." (must be active)
- 400: "Cannot confirm death on a transferred admission." (must be active)
- 400: "Death can only be confirmed for active admissions (status: admitted)."

**Special Behavior:**
- When death is confirmed, **other active admissions** are automatically closed (status: `discharged`, `discharge_status: dead`)
- Only the admission where death is confirmed gets `status: deceased`

---

### 7. Convert Outpatient to Inpatient
**Endpoint:** `POST /api/admissions/{id}/convert-to-inpatient`

| Role | Access | Condition |
|------|--------|-----------|
| `root_user` | ✅ | Can convert any admission |
| `admission` | ✅ | Can convert any admission |
| `doctor` | ✅ | Only assigned admissions |
| `nurse` | ❌ | Cannot convert |

**Doctor Restriction:**
- Must be assigned as `doctor_id` on the admission
- Check: `admission->doctor_id === user->id`

**Error Responses:**
- 403: "Unauthorized. Only admission staff or doctors can convert to inpatient."
- 403: "Unauthorized. You can only convert admissions assigned to you." (doctor not assigned)
- 400: "Cannot convert to inpatient. Patient is deceased."
- 400: "This admission is already an inpatient admission."
- 400: "Cannot convert a closed outpatient visit. Current status: {status}"
- 400: "Cannot convert to inpatient. Patient already has an active inpatient admission."

---

### 8. Admission Statistics
**Endpoint:** `GET /api/admissions/statistics`

| Role | Access | Notes |
|------|--------|-------|
| `root_user` | ✅ | Full statistics access |
| `admission` | ✅ | Full statistics access |
| `doctor` | ❌ | Cannot access statistics |
| `nurse` | ❌ | Cannot access statistics |
| Other roles | ❌ | Cannot access statistics |

**Error Response (403):**
```json
{
    "message": "Unauthorized."
}
```

**Statistics Include:**
- Total admissions
- Currently admitted (all, inpatient, outpatient)
- Discharged this month
- Admissions this month
- Counts by status
- Counts by type

---

### 9. Get Patient Admission History
**Endpoint:** `GET /api/patients/{id}/admissions`

| Role | Access | Filter Applied |
|------|--------|----------------|
| `root_user` | ✅ All admissions | None - sees all patient's admissions |
| `admission` | ✅ All admissions | None - sees all patient's admissions |
| `doctor` | ✅ Only assigned admissions | `where('doctor_id', user_id)` |
| `nurse` | ✅ Only assigned admissions | `where('nurse_id', user_id)` |
| Other roles | ❌ 403 Forbidden | - |

**Note:** This endpoint is in `PatientController`, but it returns admission data, so included here for completeness.

---

## Role Capability Summary

### Root User (`root_user`)
- ✅ **Full access** to all admission operations
- ✅ Can view, create, update, discharge, confirm death, convert, view statistics
- ✅ No restrictions on any admission

### Admission Staff (`admission`)
- ✅ Can view all admissions
- ✅ Can create admissions
- ✅ Can update all admissions (all fields)
- ✅ Can convert outpatient to inpatient
- ✅ Can view statistics
- ❌ **Cannot** discharge patients
- ❌ **Cannot** confirm death

### Doctor (`doctor`)
- ✅ Can view **only assigned admissions** (where `doctor_id = user_id`)
- ✅ Can update **only assigned admissions** (medical fields only)
- ✅ Can discharge **only assigned admissions**
- ✅ Can confirm death **only assigned admissions**
- ✅ Can convert **only assigned admissions** (outpatient to inpatient)
- ❌ Cannot create admissions
- ❌ Cannot view statistics
- ❌ Cannot update staff assignment or administrative fields

### Nurse (`nurse`)
- ✅ Can view **only assigned admissions** (where `nurse_id = user_id`)
- ❌ **Read-only access** - cannot create, update, discharge, confirm death, or convert
- ❌ Cannot view statistics

---

## Key Business Rules

1. **Only ONE active inpatient per patient** - Enforced at creation and conversion
2. **Multiple active outpatients allowed** - Normal workflow
3. **Death confirmation closes other active admissions** - Automatically sets them to `discharged` with `discharge_status: dead`
4. **Only the death admission gets `status: deceased`** - Preserves which admission patient died in
5. **Doctors can only modify assigned admissions** - Prevents unauthorized access
6. **Nurses are read-only** - Cannot modify any admission data

---

## Common Error Codes

| Code | Meaning | Common Scenarios |
|------|---------|------------------|
| 403 | Forbidden | Wrong role, not assigned, read-only role trying to modify |
| 400 | Bad Request | Business rule violation (e.g., multiple inpatients, deceased patient) |
| 404 | Not Found | Admission doesn't exist |
| 422 | Validation Error | Invalid data, wrong field types, business rule violation |

---

## Quick Reference: What Each Role Can Do

| Action | Root | Admission | Doctor | Nurse |
|--------|------|-----------|--------|-------|
| View all admissions | ✅ | ✅ | ❌ | ❌ |
| View assigned admissions | ✅ | ✅ | ✅ | ✅ |
| Create admission | ✅ | ✅ | ❌ | ❌ |
| Update any admission | ✅ | ✅ | ❌ | ❌ |
| Update assigned admission (medical) | ✅ | ✅ | ✅ | ❌ |
| Update assigned admission (admin) | ✅ | ✅ | ❌ | ❌ |
| Discharge any patient | ✅ | ❌ | ❌ | ❌ |
| Discharge assigned patient | ✅ | ❌ | ✅ | ❌ |
| Confirm death (any) | ✅ | ❌ | ❌ | ❌ |
| Confirm death (assigned) | ✅ | ❌ | ✅ | ❌ |
| Convert to inpatient (any) | ✅ | ✅ | ❌ | ❌ |
| Convert to inpatient (assigned) | ✅ | ✅ | ✅ | ❌ |
| View statistics | ✅ | ✅ | ❌ | ❌ |

