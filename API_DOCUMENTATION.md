# Hospital Management System - API Documentation

## Important Note

**This is a FREE HOSPITAL system** - No billing or payment features are included. All medical services are provided free of charge to patients.

## Base URL

All API endpoints are prefixed with `/api`. The base URL depends on your environment configuration:

-   **Development**: `http://localhost/api` (or your configured `APP_URL`)
-   **Production**: `https://your-domain.com/api`

## Authentication

This API uses **Laravel Sanctum** for token-based authentication. Most endpoints require authentication via Bearer token.

### Using the Token

Include the token in the `Authorization` header of your requests:

```
Authorization: Bearer {your_token_here}
```

### Token Expiration

Access tokens expire after **24 hours**. After expiration, you'll need to log in again.

---

## Table of Contents

1. [Authentication Endpoints](#authentication-endpoints)
2. [User Management (Root Only)](#user-management-endpoints)
3. [Patient Management (Demographics)](#patient-management-endpoints)
4. [Admission Management (Hospital Stays)](#admission-management-endpoints)
5. [Treatment Records (Medical History)](#treatment-records-endpoints)
6. [Helper Endpoints](#helper-endpoints)
7. [User Roles & Permissions](#user-roles--permissions)
8. [Data Model Overview](#data-model-overview)

---

## Data Model Overview

The system uses a **proper medical records architecture** with separate tables:

```
┌─────────────────────────────────────────────────────────────────┐
│                        PATIENTS                                  │
│  (Permanent demographic data - one record per person)            │
│  - name, nrc_number, dob, contact info                          │
│  - permanent address, emergency contact                          │
│  - blood type, known allergies, chronic conditions              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ One patient can have MANY admissions
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       ADMISSIONS                                 │
│  (Each hospital stay - multiple per patient possible)            │
│  - admission_number (unique), admission_date                     │
│  - doctor_id, nurse_id (staff assignment)                        │
│  - ward, bed, service, admitted_for                             │
│  - discharge info, status (admitted/discharged/deceased)         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Each admission has MANY treatments
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    TREATMENT RECORDS                             │
│  (Medical treatments during an admission)                        │
│  - treatment_type, medications, dosage                          │
│  - results, outcome, complications                              │
│  - linked to specific admission                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Benefits:**

-   ✅ Multiple hospital stays per patient (readmissions tracked)
-   ✅ Complete medical history preserved
-   ✅ Staff assignment per admission (different doctors each visit)
-   ✅ Accurate statistics (readmission rates, length of stay)

---

## Authentication Endpoints

### 1. Login

**Endpoint:** `POST /api/login`

**Rate Limiting:** 5 attempts per email+IP per 60 seconds

```json
// Request
{
    "email": "user@example.com",
    "password": "YourPassword123!"
}

// Response (200 OK)
{
    "message": "Login successful",
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
    "expires_at": "2024-01-15T10:30:00+00:00",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "doctor"
    }
}
```

### 2. Get Current User

**Endpoint:** `GET /api/user`

**Authentication:** Required

### 3. Update Profile

**Endpoint:** `PUT /api/user/profile` or `PATCH /api/user/profile`

**Authentication:** Required (all users can update their own profile)

```json
// Request (all fields optional)
{
    "name": "John Updated",
    "email": "newemail@example.com",
    "password": "NewSecurePass123!",
    "password_confirmation": "NewSecurePass123!"
}
```

### 4. Logout

**Endpoint:** `POST /api/logout`

**Authentication:** Required

---

## User Management Endpoints

> **Note:** All endpoints in this section require `root_user` role.

### 5. Register User

**Endpoint:** `POST /api/register`

```json
// Request
{
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "role": "doctor" // admission, nurse, or doctor
}
```

### 6. List Users

**Endpoint:** `GET /api/users`

**Query Parameters:** `?deleted=true` for soft-deleted users

### 7. Send Password Reset Link

**Endpoint:** `POST /api/users/forgot-password`

```json
{ "user_id": 2 }
// OR
{ "email": "jane.smith@example.com" }
```

### 8. Delete User (Soft Delete)

**Endpoint:** `DELETE /api/users/{id}`

### 9. Restore User

**Endpoint:** `POST /api/users/{id}/restore`

---

## Patient Management Endpoints

Patients store **permanent demographic data only**. Hospital stays are tracked in Admissions.

### 10. List Patients

**Endpoint:** `GET /api/patients`

**Authorization:** All authenticated users (role-based filtering)

**Role-Based Access:**

| Role        | Access Level           | Description                                  |
| ----------- | ---------------------- | -------------------------------------------- |
| `root_user` | All patients           | Full access to entire patient database       |
| `admission` | All patients           | Full access for registration/admission tasks |
| `doctor`    | Assigned patients only | Patients where user is assigned as doctor    |
| `nurse`     | Assigned patients only | Patients where user is assigned as nurse     |

**How Role-Based Filtering Works:**

-   **For `root_user` / `admission`:**

    -   Returns ALL patients in the system
    -   Shows all admissions for each patient
    -   `list_type: "all"` in response

-   **For `doctor`:**

    -   Returns ONLY patients where the logged-in user is assigned as `doctor_id` in any admission
    -   Shows only the admissions where this doctor is assigned
    -   `list_type: "assigned"` in response

-   **For `nurse`:**
    -   Returns ONLY patients where the logged-in user is assigned as `nurse_id` in any admission
    -   Shows only the admissions where this nurse is assigned
    -   `list_type: "assigned"` in response

**Query Parameters:**

| Parameter            | Type    | Description                         |
| -------------------- | ------- | ----------------------------------- |
| `search`             | string  | Search by name, NRC, or phone       |
| `currently_admitted` | boolean | Filter to patients with active stay |
| `per_page`           | integer | Results per page (default: 15)      |

#### How `currently_admitted` Filter Works

**Purpose:** Filter the patient list to show only patients who have **at least one active admission** (status: `admitted`).

**How It Works:**

1. **Database Query Logic:**

    - Uses Laravel's `whereHas()` relationship query
    - Checks if patient has **any** admission with `status = 'admitted'`
    - Works for **both outpatient and inpatient** admissions
    - Returns patients with **active visits/stays** only

2. **Filter Behavior:**

    | Query Parameter             | Result                                                        |
    | --------------------------- | ------------------------------------------------------------- |
    | `?currently_admitted=true`  | ✅ Shows **ONLY** patients with active admissions             |
    | `?currently_admitted=false` | ❌ Ignored (treated as not provided)                          |
    | Not provided                | ✅ Shows **ALL** patients (with or without active admissions) |

3. **What Counts as "Currently Admitted":**

    ✅ **Included:**

    - Patients with active inpatient admission (`status: 'admitted'`, `admission_type: 'inpatient'`)
    - Patients with active outpatient visit (`status: 'admitted'`, `admission_type: 'outpatient'`)
    - Patients with **multiple active admissions** (e.g., multiple outpatient visits)

    ❌ **Excluded:**

    - Patients with only discharged admissions
    - Patients with only transferred admissions
    - Patients with only deceased admissions
    - Patients with no admissions at all

4. **Technical Implementation:**

    ```php
    // In PatientController::index()
    if ($currentlyAdmitted === 'true') {
        $query->currentlyAdmitted();
    }

    // In Patient Model (scopeCurrentlyAdmitted)
    public function scopeCurrentlyAdmitted($query)
    {
        return $query->whereHas('admissions', function ($q) {
            $q->where('status', 'admitted');
        });
    }
    ```

    **SQL Equivalent:**

    ```sql
    SELECT * FROM patients
    WHERE EXISTS (
        SELECT 1 FROM admissions
        WHERE admissions.patient_id = patients.id
        AND admissions.status = 'admitted'
    )
    ```

5. **Example Scenarios:**

    **Scenario 1: Patient with Active Inpatient**

    ```
    Patient: John Doe
    - Admission #1: discharged (2024-11-01)
    - Admission #2: admitted (2024-12-03) ← ACTIVE

    Result with filter: ✅ INCLUDED
    ```

    **Scenario 2: Patient with Active Outpatient**

    ```
    Patient: Jane Smith
    - Admission #1: admitted (2024-12-05, outpatient) ← ACTIVE

    Result with filter: ✅ INCLUDED
    ```

    **Scenario 3: Patient with Multiple Active Admissions**

    ```
    Patient: Bob Wilson
    - Admission #1: admitted (2024-12-01, outpatient) ← ACTIVE
    - Admission #2: admitted (2024-12-03, outpatient) ← ACTIVE

    Result with filter: ✅ INCLUDED (appears once, but has 2 active admissions)
    ```

    **Scenario 4: Patient with Only Discharged Admissions**

    ```
    Patient: Alice Brown
    - Admission #1: discharged (2024-11-15)
    - Admission #2: discharged (2024-10-20)

    Result with filter: ❌ EXCLUDED
    ```

    **Scenario 5: New Patient (No Admissions)**

    ```
    Patient: Charlie Green
    - No admissions yet

    Result with filter: ❌ EXCLUDED
    ```

6. **Response Data:**

    When filter is applied, **all returned patients** will have:

    - `admissions` array with at least one admission
    - `admissions[].status` = `"admitted"`
    - `is_currently_admitted` = `true` (if this field exists)

7. **Use Cases:**

    - **Ward Management:** "Show me all patients currently in the hospital"
    - **Bed Availability:** "List patients occupying beds right now"
    - **Active Care:** "Which patients need ongoing medical attention?"
    - **Daily Rounds:** "Patients to visit during rounds today"

8. **Performance:**

    - Uses efficient `EXISTS` subquery (not `JOIN`)
    - Only checks admission status, not full admission records
    - Works well even with thousands of patients
    - Can be combined with `search` parameter for filtered active patients

**Example Requests:**

```bash
# Get all currently admitted patients
GET /api/patients?currently_admitted=true

# Get currently admitted patients with pagination
GET /api/patients?currently_admitted=true&per_page=20

# Search for active patients by name
GET /api/patients?currently_admitted=true&search=John

# Get all patients (no filter)
GET /api/patients
```

```json
// Response for root_user/admission (all patients)
{
    "message": "Patients retrieved successfully",
    "list_type": "all",
    "data": {
        "data": [
            {
                "id": 1,
                "name": "John Patient",
                "nrc_number": "12/ABC(N)123456",
                "age": 45,
                "sex": "male",
                "contact_phone": "09123456789",
                "admissions_count": 3,
                "admissions": [
                    {
                        "id": 5,
                        "admission_number": "ADM-2024-000005",
                        "admission_date": "2024-12-03",
                        "admitted_for": "Chest pain",
                        "status": "admitted",
                        "doctor": { "id": 2, "name": "Dr. Smith" },
                        "nurse": { "id": 3, "name": "Nurse Jane" }
                    }
                ]
            }
        ]
    }
}
```

```json
// Response for doctor/nurse (assigned patients only)
{
    "message": "Patients retrieved successfully",
    "list_type": "assigned",
    "data": {
        "data": [
            {
                "id": 1,
                "name": "John Patient",
                "nrc_number": "12/ABC(N)123456",
                "age": 45,
                "sex": "male",
                "contact_phone": "09123456789",
                "admissions_count": 3,
                "admissions": [
                    {
                        "id": 5,
                        "admission_number": "ADM-2024-000005",
                        "admission_date": "2024-12-03",
                        "admitted_for": "Chest pain",
                        "status": "admitted",
                        "doctor": { "id": 2, "name": "Dr. Smith" },
                        "nurse": { "id": 3, "name": "Nurse Jane" }
                    }
                ]
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 5
    }
}
```

**Note:** For doctors/nurses, only admissions where they are assigned are shown in the `admissions` array.

### 11. Search Patients

**Endpoint:** `GET /api/patients/search?q={query}`

**Authorization:** `root_user` or `admission`

**Purpose:** Search for existing patients before creating duplicates. Used by admission staff to check if a patient is already registered in the system.

#### Search Criteria

The search performs a **partial match** (contains) across **3 fields**:

| Field Searched  | Type   | Search Type              | Example Match                                         |
| --------------- | ------ | ------------------------ | ----------------------------------------------------- |
| `name`          | string | Partial match (contains) | "John" matches "John Patient", "Johnson", "Mary John" |
| `nrc_number`    | string | Partial match (contains) | "ABC" matches "12/ABC(N)123456"                       |
| `contact_phone` | string | Partial match (contains) | "09123" matches "09123456789"                         |

**Search Behavior:**

-   Case-insensitive matching
-   Searches across all 3 fields simultaneously (OR logic)
-   Returns results if **any** of the 3 fields contain the search term
-   Maximum 20 results returned
-   Results include current admission status

#### Query Parameters

| Parameter | Type   | Required   | Constraints    | Description                         |
| --------- | ------ | ---------- | -------------- | ----------------------------------- |
| `q`       | string | ✅ **Yes** | min:2, max:255 | Search query (minimum 2 characters) |

#### Example Searches

**Search by name:**

```
GET /api/patients/search?q=John
→ Returns patients with names containing "John"
```

**Search by NRC:**

```
GET /api/patients/search?q=12/ABC
→ Returns patients with NRC containing "12/ABC"
```

**Search by phone:**

```
GET /api/patients/search?q=09123
→ Returns patients with phone containing "09123"
```

**Search by partial NRC:**

```
GET /api/patients/search?q=123456
→ Returns patients with NRC or phone ending in "123456"
```

#### Success Response (200 OK)

```json
{
    "message": "Search results",
    "total": 3,
    "data": [
        {
            "id": 1,
            "name": "John Patient",
            "nrc_number": "12/ABC(N)123456",
            "contact_phone": "09123456789",
            "age": 45,
            "sex": "male",
            "dob": "1979-05-15",
            "admissions_count": 3,
            "admissions": [
                {
                    "id": 5,
                    "patient_id": 1,
                    "admission_number": "ADM-2024-000005",
                    "status": "admitted"
                }
            ],
            "is_currently_admitted": true
        },
        {
            "id": 2,
            "name": "Johnson Smith",
            "nrc_number": "14/XYZ(N)789012",
            "contact_phone": "09987654321",
            "age": 32,
            "sex": "male",
            "dob": "1992-03-20",
            "admissions_count": 1,
            "admissions": [],
            "is_currently_admitted": false
        }
    ]
}
```

#### Response Fields

| Field                          | Type          | Description                          |
| ------------------------------ | ------------- | ------------------------------------ |
| `message`                      | string        | Status message                       |
| `total`                        | integer       | Number of results found (max 20)     |
| `data`                         | array         | Array of patient objects             |
| `data[].id`                    | integer       | Patient ID                           |
| `data[].name`                  | string        | Patient name                         |
| `data[].nrc_number`            | string\|null  | NRC number                           |
| `data[].contact_phone`         | string\|null  | Contact phone                        |
| `data[].age`                   | integer\|null | Patient age                          |
| `data[].sex`                   | string\|null  | Patient gender                       |
| `data[].dob`                   | date\|null    | Date of birth                        |
| `data[].admissions_count`      | integer       | Total number of admissions           |
| `data[].admissions`            | array         | Active admissions only               |
| `data[].is_currently_admitted` | boolean       | Whether patient has active admission |

#### Error Responses

**400 Bad Request** - Query too short

```json
{
    "message": "Search query must be at least 2 characters.",
    "data": []
}
```

**403 Forbidden** - Unauthorized role

```json
{
    "message": "Unauthorized. Only admission staff can search patients."
}
```

#### Use Cases

**Before Creating Patient:**

```
1. Patient walks in
2. Admission staff searches: GET /api/patients/search?q=John
3. If found → View existing patient: GET /api/patients/{id}
4. If not found → Create new: POST /api/patients
```

**Quick Lookup:**

```
→ Search by name: ?q=John
→ Search by NRC: ?q=12/ABC
→ Search by phone: ?q=09123456789
```

#### Notes

-   **Minimum 2 characters** required to prevent overly broad searches
-   **Maximum 20 results** returned for performance
-   **Partial matching** - no need for exact matches
-   **Case-insensitive** - "john" matches "John"
-   Shows **active admissions** with admission number for quick reference
-   Includes `is_currently_admitted` flag to quickly identify if patient is in hospital

### 12. Create Patient

**Endpoint:** `POST /api/patients`

**Authorization:** `root_user` or `admission`

**Purpose:** Create a new patient record with permanent demographic information. Admission-specific data is added when admitting the patient.

#### Request Parameters

| Field                    | Type          | Required   | Constraints | Accepted Values                                                               | Description                                                        |
| ------------------------ | ------------- | ---------- | ----------- | ----------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| **Basic Information**    |
| `name`                   | string        | ✅ **Yes** | max:255     | Any text                                                                      | Patient's full name                                                |
| `nrc_number`             | string        | ✅ **Yes** | NRC format  | e.g., `1/AhGaYa(N)123456`                                                     | NRC number (required, validated against NRC format and uniqueness) |
| `sex`                    | string        | No         | -           | `male`, `female`, `other`                                                     | Patient's gender                                                   |
| `age`                    | integer       | No         | 0-150       | Positive integer                                                              | Patient's age in years                                             |
| `dob`                    | date          | No         | <= today    | YYYY-MM-DD                                                                    | Date of birth (cannot be in future)                                |
| `contact_phone`          | string        | No         | max:20      | e.g., "09123456789"                                                           | Primary contact phone number                                       |
| **Address**              |
| `permanent_address`      | string (JSON) | No         | Validated   | JSON: `{"region": "...", "district": "...", "township": "..."}` or plain text | Permanent residential address (must match Myanmar addresses list)  |
| **Personal Details**     |
| `marital_status`         | string        | No         | -           | `single`, `married`, `divorced`, `widowed`, `other`                           | Marital status                                                     |
| `ethnic_group`           | string        | No         | max:100     | e.g., "Bamar", "Shan"                                                         | Ethnic background                                                  |
| `religion`               | string        | No         | max:100     | e.g., "Buddhist", "Christian"                                                 | Religious affiliation                                              |
| `occupation`             | string        | No         | max:100     | Any text                                                                      | Current occupation                                                 |
| `father_name`            | string        | No         | max:255     | Any text                                                                      | Father's name                                                      |
| `mother_name`            | string        | No         | max:255     | Any text                                                                      | Mother's name                                                      |
| **Emergency Contact**    |
| `nearest_relative_name`  | string        | No         | max:255     | Any text                                                                      | Emergency contact person name                                      |
| `nearest_relative_phone` | string        | No         | max:20      | e.g., "09987654321"                                                           | Emergency contact phone number                                     |
| `relationship`           | string        | No         | max:50      | e.g., "spouse", "parent"                                                      | Relationship to patient                                            |
| **Medical Information**  |
| `blood_type`             | string        | No         | -           | `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`                              | Blood type (must be valid type)                                    |
| `known_allergies`        | string (text) | No         | max:500     | Comma-separated list                                                          | Known drug/food allergies                                          |
| `chronic_conditions`     | string (text) | No         | max:500     | Comma-separated list                                                          | Chronic medical conditions                                         |

**Address & NRC Validation Notes:**

-   `nrc_number` is **required**. Format: `{nrc_code}/{name_en}({citizenship})123456`. Citizenship in `N,F,P,TH,S`. Township code validated when available in the NRC dataset. Use `GET /api/nrc-codes` to populate code/township/citizenship options.

The `permanent_address` field accepts addresses in two formats:

1. **Structured JSON (Recommended):** Validates against Myanmar addresses list

    ```json
    {
        "region": "Yangon Region",
        "district": "East Yangon",
        "township": "Tamwe"
    }
    ```

    - All three fields (region, district, township) must match the Myanmar addresses list
    - Use `GET /api/addresses/myanmar` to get valid options
    - Region, district, and township are validated hierarchically

2. **Plain Text (Backward Compatible):** Accepts any text string
    - For existing data compatibility
    - Structured JSON is preferred for new entries

**Response Format Note:**

The response structure for all endpoints (list patients, get patient details, list admissions, get admission details) **remains unchanged**. However, address fields (`permanent_address` and `present_address`) in responses will contain whatever format was stored:

-   **If structured JSON was sent:** Response will contain a JSON string: `"{\"region\": \"Yangon Region\", \"district\": \"East Yangon\", \"township\": \"Tamwe\"}"`
-   **If plain text was sent:** Response will contain plain text: `"123 Main Street, Yangon"`

Frontend applications should handle both formats when displaying address data. To parse structured addresses:

```javascript
// Handle both formats
let addressDisplay = "";
if (patient.permanent_address) {
    try {
        const address = JSON.parse(patient.permanent_address);
        addressDisplay = `${address.township}, ${address.district}, ${address.region}`;
    } catch (e) {
        // Not JSON, use as plain text
        addressDisplay = patient.permanent_address;
    }
}
```

#### Example Request

```json
{
    "name": "John Patient",
    "nrc_number": "12/ABC(N)123456",
    "sex": "male",
    "age": 45,
    "dob": "1979-05-15",
    "contact_phone": "09123456789",
    "permanent_address": "{\"region\": \"Yangon Region\", \"district\": \"East Yangon\", \"township\": \"Tamwe\"}",
    "marital_status": "married",
    "ethnic_group": "Bamar",
    "religion": "Buddhist",
    "occupation": "Engineer",
    "father_name": "U Kyaw",
    "mother_name": "Daw Aye",
    "nearest_relative_name": "Ma Thin",
    "nearest_relative_phone": "09987654321",
    "relationship": "wife",
    "blood_type": "O+",
    "known_allergies": "Penicillin, Aspirin",
    "chronic_conditions": "Hypertension, Diabetes Type 2"
}
```

#### Minimal Valid Request

```json
{
    "name": "John Patient"
}
```

#### Success Response (201 Created)

```json
{
    "message": "Patient registered successfully",
    "data": {
        "id": 1,
        "name": "John Patient",
        "nrc_number": "12/ABC(N)123456",
        "sex": "male",
        "age": 45,
        "blood_type": "O+",
        "created_at": "2024-12-03T10:00:00.000000Z"
    }
}
```

#### Error Responses

**422 Unprocessable Entity** - Validation errors

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": ["Patient name is required."],
        "nrc_number": ["This NRC number is already registered."],
        "blood_type": ["Invalid blood type."],
        "age": ["The age field must not be greater than 150."],
        "dob": ["Date of birth cannot be in the future."],
        "sex": ["Sex must be male, female, or other."]
    }
}
```

#### Field Notes

-   **Required:** Only `name` is required - all other fields are optional
-   **Unique:** `nrc_number` must be unique across all patients if provided
-   **Enum Fields:** `sex`, `marital_status`, `blood_type` accept only specific values
-   **Text Fields:** `known_allergies` and `chronic_conditions` can contain multiple comma-separated values
-   **Date Constraints:** `dob` cannot be in the future
-   **Numeric Constraints:** `age` must be between 0 and 150

### 13. Get Patient Details

**Endpoint:** `GET /api/patients/{id}`

**Authorization:** Role-based (doctors/nurses see only their assigned patients' admissions)

Returns patient demographics with full admission history including treatment records.

### 14. Update Patient

**Endpoint:** `PUT /api/patients/{id}` or `PATCH /api/patients/{id}`

**Authorization:** `root_user` or `admission`

Only demographic data can be updated. Medical data is in admissions/treatments.

### 15. Get Patient Admission History

**Endpoint:** `GET /api/patients/{id}/admissions`

**Authorization:** Role-based

Returns list of all hospital stays for this patient.

### 16. Delete Patient

**Endpoint:** ~~`DELETE /api/patients/{id}`~~ **DISABLED**

**Authorization:** `root_user` only

**Status:** ❌ **This endpoint is currently disabled**

**Reason:** Patient records must be permanently retained for:

-   Medical and legal compliance
-   Historical record integrity
-   Deceased patient record protection
-   Audit trail requirements

**Note:** If you need to remove a patient from active use, consider marking them as inactive in your frontend logic rather than deleting the record.

---

## Admission Management Endpoints

Admissions track **individual hospital visits and stays**. One patient can have multiple admissions.

### Outpatient vs Inpatient

The system supports **two types of admissions:**

| Type           | Description                                             | Use Case                                      |
| -------------- | ------------------------------------------------------- | --------------------------------------------- |
| **Outpatient** | Patient visits for treatment but doesn't stay overnight | Consultations, minor procedures, follow-ups   |
| **Inpatient**  | Patient is admitted and stays in the hospital           | Requires bed, ward assignment, overnight stay |

**Key Features:**

-   ✅ Patient can have outpatient visit and later convert to inpatient if needed
-   ✅ Multiple outpatient visits don't conflict (patient can visit multiple times)
-   ✅ Only ONE active inpatient admission allowed per patient at a time
-   ✅ Ward and bed assignment required for inpatient, optional for outpatient

### 17. List Admissions

**Endpoint:** `GET /api/admissions`

**Authorization:** Role-based

-   `root_user` / `admission`: See all admissions
-   `doctor`: See only assigned admissions
-   `nurse`: See only assigned admissions

**Query Parameters:**

| Parameter        | Type    | Description                                                 |
| ---------------- | ------- | ----------------------------------------------------------- |
| `status`         | string  | Filter: `admitted`, `discharged`, `deceased`, `transferred` |
| `admission_type` | string  | Filter: `inpatient`, `outpatient`                           |
| `per_page`       | integer | Results per page (default: 15)                              |

**Filter Examples:**

```bash
# Get all active inpatient admissions
GET /api/admissions?status=admitted&admission_type=inpatient

# Get all discharged outpatient visits
GET /api/admissions?status=discharged&admission_type=outpatient

# Get all inpatient admissions (any status)
GET /api/admissions?admission_type=inpatient

# Get all outpatient visits (any status)
GET /api/admissions?admission_type=outpatient

# Combine filters with pagination
GET /api/admissions?status=admitted&admission_type=inpatient&per_page=20
```

```json
// Response
{
    "message": "Admissions retrieved successfully",
    "data": {
        "data": [
            {
                "id": 1,
                "admission_number": "ADM-2024-000001",
                "admission_date": "2024-12-03",
                "admitted_for": "Chest pain",
                "admission_type": "inpatient",
                "status": "admitted",
                "ward": "Cardiology A",
                "bed_number": "12",
                "length_of_stay": 5,
                "patient": {
                    "id": 1,
                    "name": "John Patient",
                    "nrc_number": "12/ABC(N)123456"
                },
                "doctor": { "id": 2, "name": "Dr. Smith" },
                "nurse": { "id": 3, "name": "Nurse Jane" },
                "treatment_records_count": 8
            },
            {
                "id": 2,
                "admission_number": "ADM-2024-000002",
                "admission_date": "2024-12-05",
                "admitted_for": "Follow-up consultation",
                "admission_type": "outpatient",
                "status": "admitted",
                "ward": null,
                "bed_number": null,
                "length_of_stay": 0,
                "patient": {
                    "id": 2,
                    "name": "Jane Doe",
                    "nrc_number": "12/XYZ(N)789012"
                },
                "doctor": { "id": 2, "name": "Dr. Smith" },
                "nurse": { "id": 4, "name": "Nurse Mary" },
                "treatment_records_count": 2
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 2
    }
}
```

**Error Response (400 Bad Request) - Invalid admission_type:**

```json
{
    "message": "Invalid admission_type. Must be \"inpatient\" or \"outpatient\"."
}
```

### 18. Admit Patient (Create Admission)

**Endpoint:** `POST /api/patients/{patientId}/admit`

**Authorization:** `root_user` or `admission`

**Purpose:** Create a new admission (hospital visit or stay) for an existing patient. Can be either outpatient or inpatient.

**Important:** `doctor_id` and `nurse_id` are **User IDs** from the users table (not separate doctor/nurse tables).

#### Staff Assignment Limitations

**Current System Design:**

-   ⚠️ **ONE doctor per admission** - Each admission can have only ONE assigned doctor
-   ⚠️ **ONE nurse per admission** - Each admission can have only ONE assigned nurse
-   Staff can be **reassigned** via the update endpoint (changes the single doctor/nurse)
-   Multiple doctors/nurses **cannot** be assigned to the same admission simultaneously

**Why This Limitation:**

-   Current database structure uses single foreign keys (`doctor_id`, `nurse_id`)
-   Designed for **primary doctor/nurse** assignment model
-   Suitable for most hospital workflows where one doctor is primarily responsible

**Workarounds for Multiple Staff:**

1. **Primary Assignment (Admission Level):**

    - Assign **primary doctor** via `doctor_id` field
    - Assign **primary nurse** via `nurse_id` field
    - These are the main responsible staff for the admission

2. **Additional Staff via Treatment Records:**

    - Each **treatment record** can have its own `doctor_id` and `nurse_id`
    - Different doctors can create treatment records for the same admission
    - Different nurses can be assigned to different treatments
    - This allows multiple doctors/nurses to be involved through treatment documentation

3. **Documentation Fields:**
    - **Consulting doctors:** Document in `remarks` or `clinician_summary` fields
    - **Additional nurses:** Document in `remarks` field
    - **Specialists:** Mention in treatment record notes

**Example Scenario:**

```
Admission Level:
- Primary Doctor: Dr. Smith (doctor_id: 2)
- Primary Nurse: Nurse Jane (nurse_id: 3)

Treatment Records:
- Treatment #1: Dr. Smith (primary doctor)
- Treatment #2: Dr. Johnson (consulting cardiologist) - different doctor_id
- Treatment #3: Nurse Mary (specialized nurse) - different nurse_id

Result: Multiple doctors/nurses involved through treatment records
```

**Future Enhancement Consideration:**
If multiple doctors/nurses per admission are needed, the system would require:

-   A pivot table (e.g., `admission_staff`) for many-to-many relationships
-   Changes to admission creation/update endpoints
-   Updates to access control logic

#### Business Rules

-   **Outpatient:** Patient can have multiple active outpatient visits
-   **Inpatient:** Only ONE active inpatient admission allowed per patient at a time
-   **Discharged patients:** ✅ CAN be readmitted (normal readmission scenario)
-   **Deceased patients:** ❌ **CANNOT be admitted again** - system automatically blocks admission
-   Defaults to `inpatient` if `admission_type` is not specified
-   **Ward/Bed:** Required for inpatient, **BLOCKED for outpatient** (cannot be specified)
-   Auto-generates unique admission number (e.g., ADM-2024-000001)

#### ⚠️ Outpatient vs Inpatient Field Restrictions

**Outpatient Admissions:**

-   ❌ **Ward:** Cannot be specified (prohibited)
-   ❌ **Bed Number:** Cannot be specified (prohibited)
-   ✅ **All other fields:** Can be used normally

**Inpatient Admissions:**

-   ✅ **Ward:** Required (must be provided)
-   ✅ **Bed Number:** Optional (can be provided)
-   ✅ **All other fields:** Can be used normally

**Why This Restriction:**

-   Outpatients don't stay overnight, so they don't need ward/bed assignment
-   Inpatients require ward/bed for room management
-   Prevents data inconsistency (outpatient with ward/bed doesn't make sense)

**Validation Behavior:**

| Field        | Outpatient                                 | Inpatient                          |
| ------------ | ------------------------------------------ | ---------------------------------- |
| `ward`       | ❌ **BLOCKED** - Returns error if provided | ✅ **REQUIRED** - Must be provided |
| `bed_number` | ❌ **BLOCKED** - Returns error if provided | ✅ **OPTIONAL** - Can be provided  |

**Error Examples:**

**Outpatient with Ward (BLOCKED):**

```json
{
    "admission_type": "outpatient",
    "ward": "Ward A" // ❌ ERROR
}
// Response: "Ward cannot be specified for outpatient admissions."
```

**Outpatient with Bed Number (BLOCKED):**

```json
{
    "admission_type": "outpatient",
    "bed_number": "12" // ❌ ERROR
}
// Response: "Bed number cannot be specified for outpatient admissions."
```

**Inpatient without Ward (BLOCKED):**

```json
{
    "admission_type": "inpatient"
    // ward missing  // ❌ ERROR
}
// Response: "Ward is required for inpatient admissions."
```

#### ⚠️ CRITICAL: Deceased Patient Protection

**System Automatically Blocks New Admissions for Deceased Patients**

The system checks if a patient is deceased **before** creating any new admission. If a patient has **any admission with status: `deceased`**, the system will **automatically block** creating a new admission.

**How It Works:**

1. **Check on Admission Creation:**

    - System queries patient's admission history
    - Looks for any admission with `status = 'deceased'`
    - If found → **BLOCKS** new admission creation
    - Returns error with deceased record details

2. **What Happens:**

    | Scenario                               | Result                                                           |
    | -------------------------------------- | ---------------------------------------------------------------- |
    | Patient has deceased admission         | ❌ **BLOCKED** - "Cannot create admission. Patient is deceased." |
    | Patient has only discharged admissions | ✅ **ALLOWED** - Can create new admission                        |
    | Patient has no admissions              | ✅ **ALLOWED** - Can create new admission                        |
    | Patient has active admissions          | ⚠️ **BLOCKED** if trying to create duplicate inpatient           |

3. **Example Scenarios:**

    **Scenario 1: Deceased Patient - BLOCKED**

    ```
    Patient: John Doe
    - Admission #1: deceased (2024-11-15) ← Patient died

    Attempt: Create new admission
    Result: ❌ BLOCKED
    Error: "Cannot create admission. Patient is deceased."
    ```

    **Scenario 2: Discharged Patient - ALLOWED**

    ```
    Patient: Jane Smith
    - Admission #1: discharged (2024-11-10) ← Patient left alive

    Attempt: Create new admission
    Result: ✅ ALLOWED
    New admission created successfully
    ```

    **Scenario 3: Multiple Discharged Admissions - ALLOWED**

    ```
    Patient: Bob Wilson
    - Admission #1: discharged (2024-10-01)
    - Admission #2: discharged (2024-11-15)
    - Admission #3: discharged (2024-12-01)

    Attempt: Create new admission
    Result: ✅ ALLOWED
    New admission created successfully
    ```

4. **Why This Protection Exists:**

    - **Medical Accuracy:** Deceased patients cannot be admitted to the hospital
    - **Data Integrity:** Prevents creating invalid medical records
    - **Legal Compliance:** Death records must be accurate and final
    - **System Logic:** Once a patient is deceased, they remain deceased permanently

5. **Technical Implementation:**

    ```php
    // In AdmissionController::store()
    $deceasedAdmission = $patient->admissions()
        ->where('status', 'deceased')
        ->first();

    if ($deceasedAdmission) {
        return error: "Cannot create admission. Patient is deceased."
    }
    ```

    **SQL Equivalent:**

    ```sql
    SELECT * FROM admissions
    WHERE patient_id = ?
    AND status = 'deceased'
    LIMIT 1
    ```

6. **Important Notes:**

    - ✅ **Old discharged admissions do NOT block** new admissions
    - ❌ **Any deceased admission blocks** ALL new admissions permanently
    - ✅ System checks **entire admission history**, not just current status
    - ❌ Once deceased, patient **cannot be readmitted** (death is final)

#### Request Parameters

| Field                         | Type          | Required                                                 | Constraints                                  | Accepted Values                                                               | Description                                                                         |
| ----------------------------- | ------------- | -------------------------------------------------------- | -------------------------------------------- | ----------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| **Admission Type**            |
| `admission_type`              | string        | No                                                       | Default: `inpatient`                         | `outpatient`, `inpatient`                                                     | Type of admission                                                                   |
| **Staff Assignment**          |
| `doctor_id`                   | integer       | No                                                       | Must exist in users table with role=`doctor` | User ID                                                                       | ID of doctor to assign (from users table)                                           |
| `nurse_id`                    | integer       | No                                                       | Must exist in users table with role=`nurse`  | User ID                                                                       | ID of nurse to assign (from users table)                                            |
| **Required Fields**           |
| `admission_date`              | date          | ✅ **Yes**                                               | <= today                                     | YYYY-MM-DD                                                                    | Date of admission (cannot be future)                                                |
| `admitted_for`                | string        | ✅ **Yes**                                               | max:500                                      | Any text                                                                      | Chief complaint / reason for admission                                              |
| **Location (Inpatient Only)** |
| `ward`                        | string        | **Required for inpatient**<br>**BLOCKED for outpatient** | max:100, prohibited_if:outpatient            | e.g., "Ward A", "ICU"                                                         | Ward/department (required for inpatient, cannot be specified for outpatient)        |
| `bed_number`                  | string        | **Optional for inpatient**<br>**BLOCKED for outpatient** | max:50, prohibited_if:outpatient             | e.g., "12", "A-15"                                                            | Bed number within ward (optional for inpatient, cannot be specified for outpatient) |
| **Additional Details**        |
| `admission_time`              | time          | No                                                       | HH:mm format                                 | e.g., "10:30"                                                                 | Time of admission                                                                   |
| `present_address`             | string (JSON) | No                                                       | Validated                                    | JSON: `{"region": "...", "district": "...", "township": "..."}` or plain text | Current address at time of admission (must match Myanmar addresses list)            |
| `referred_by`                 | string        | No                                                       | max:255                                      | Any text                                                                      | Referring doctor/hospital                                                           |
| `police_case`                 | string        | No                                                       | -                                            | `yes`, `no`                                                                   | Whether this is a police/legal case                                                 |
| `service`                     | string        | No                                                       | max:255                                      | e.g., "Cardiology", "Surgery"                                                 | Medical department/service                                                          |
| `medical_officer`             | string        | No                                                       | max:255                                      | Any text                                                                      | Name of medical officer                                                             |
| **Initial Assessment**        |
| `initial_diagnosis`           | string (text) | No                                                       | max:500                                      | Any text                                                                      | Initial diagnosis/assessment                                                        |
| `drug_allergy_noted`          | string        | No                                                       | max:255                                      | Comma-separated                                                               | Allergies noted at admission                                                        |
| `remarks`                     | string (text) | No                                                       | max:500                                      | Any text                                                                      | Additional remarks/notes                                                            |

**Address Validation Note:**

The `present_address` field accepts addresses in two formats:

1. **Structured JSON (Recommended):** Validates against Myanmar addresses list

    ```json
    {
        "region": "Mandalay Region",
        "district": "Mandalay",
        "township": "Amarapura"
    }
    ```

    - All three fields (region, district, township) must match the Myanmar addresses list
    - Use `GET /api/addresses/myanmar` to get valid options
    - Region, district, and township are validated hierarchically

2. **Plain Text (Backward Compatible):** Accepts any text string
    - For existing data compatibility
    - Structured JSON is preferred for new entries

**Response Format Note:**

The response structure for all endpoints (list admissions, get admission details) **remains unchanged**. However, the `present_address` field in responses will contain whatever format was stored:

-   **If structured JSON was sent:** Response will contain a JSON string: `"{\"region\": \"Mandalay Region\", \"district\": \"Mandalay\", \"township\": \"Amarapura\"}"`
-   **If plain text was sent:** Response will contain plain text: `"456 Current Address"`

Frontend applications should handle both formats when displaying address data.

#### Example Requests

**Inpatient Admission (Full Example):**

```json
{
    "admission_type": "inpatient",
    "admission_date": "2024-12-03",
    "admission_time": "10:30",
    "admitted_for": "Chest pain and shortness of breath",
    "doctor_id": 2, // User ID from users table (role must be 'doctor')
    "nurse_id": 3, // User ID from users table (role must be 'nurse')
    "present_address": "{\"region\": \"Mandalay Region\", \"district\": \"Mandalay\", \"township\": \"Amarapura\"}",
    "referred_by": "Township Hospital",
    "police_case": "no",
    "service": "Cardiology",
    "ward": "Ward A", // Required for inpatient
    "bed_number": "12",
    "medical_officer": "Dr. Smith",
    "initial_diagnosis": "Suspected acute coronary syndrome",
    "drug_allergy_noted": "Penicillin",
    "remarks": "Patient stable on arrival"
}
```

**Outpatient Visit (Minimal Example):**

```json
{
    "admission_type": "outpatient",
    "admission_date": "2024-12-03",
    "admitted_for": "Follow-up consultation",
    "doctor_id": 2 // User ID from users table
}
```

**Outpatient Visit (Full Example):**

````json
{
    "admission_type": "outpatient",
    "admission_date": "2024-12-03",
    "admission_time": "14:00",
    "admitted_for": "Follow-up consultation",
    "doctor_id": 2,  // User ID (role must be 'doctor')
    "nurse_id": 3,   // User ID (role must be 'nurse')
    "service": "Cardiology",
    "initial_diagnosis": "Post-discharge follow-up",
    "remarks": "Patient doing well",
    "present_address": "{\"region\": \"Yangon Region\", \"district\": \"West Yangon(Downtown)\", \"township\": \"Sanchaung\"}"
}

#### Success Response (201 Created)

```json
{
    "message": "Patient registered successfully as inpatient admission",
    "data": {
        "id": 1,
        "admission_number": "ADM-2024-000001",  // Auto-generated unique number
        "admission_type": "inpatient",
        "admission_date": "2024-12-03",
        "admission_time": "10:30",
        "status": "admitted",
        "ward": "Ward A",
        "bed_number": "12",
        "patient": {
            "id": 1,
            "name": "John Patient",
            "nrc_number": "12/ABC(N)123456",
            "contact_phone": "09123456789"
        },
        "doctor": {
            "id": 2,              // This is the user.id
            "name": "Dr. Smith",
            "email": "dr.smith@hospital.com"
        },
        "nurse": {
            "id": 3,              // This is the user.id
            "name": "Nurse Jane",
            "email": "nurse.jane@hospital.com"
        }
    }
}
````

#### Error Responses

**400 Bad Request** - Patient is deceased (CANNOT be admitted)

```json
{
    "message": "Cannot create admission. Patient is deceased.",
    "deceased_record": {
        "admission_number": "ADM-2024-000003",
        "date_of_death": "2024-11-15",
        "cause_of_death": "Cardiac arrest"
    }
}
```

**400 Bad Request** - Patient already has active inpatient

```json
{
    "message": "Patient already has an active inpatient admission.",
    "current_admission": {
        "id": 5,
        "admission_number": "ADM-2024-000005",
        "admission_date": "2024-11-30",
        "admitted_for": "Previous condition"
    }
}
```

**422 Unprocessable Entity** - Validation errors

**Example 1: Missing ward for inpatient**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "admission_date": ["Admission date cannot be in the future."],
        "admitted_for": ["Reason for admission is required."],
        "ward": ["Ward is required for inpatient admissions."],
        "doctor_id": ["The selected doctor does not exist."],
        "nurse_id": ["The selected user is not a nurse."]
    }
}
```

**Example 2: Ward/bed provided for outpatient (BLOCKED)**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "ward": ["Ward cannot be specified for outpatient admissions."],
        "bed_number": [
            "Bed number cannot be specified for outpatient admissions."
        ]
    }
}
```

#### Important Notes

**About Staff Assignment:**

-   `doctor_id` and `nurse_id` are **User IDs** from the `users` table
-   System validates that `doctor_id` points to a user with `role='doctor'`
-   System validates that `nurse_id` points to a user with `role='nurse'`
-   Use `GET /api/staff/doctors` to get available doctors
-   Use `GET /api/staff/nurses` to get available nurses

**Getting Staff IDs:**

```bash
# Get list of doctors with their IDs
GET /api/staff/doctors
→ Returns: [{ "id": 2, "name": "Dr. Smith", ... }, ...]

# Get list of nurses with their IDs
GET /api/staff/nurses
→ Returns: [{ "id": 3, "name": "Nurse Jane", ... }, ...]

# Then use those IDs in admission
POST /api/patients/{id}/admit
{ "doctor_id": 2, "nurse_id": 3, ... }
```

**Workflow Example:**

```
1. Search patient: GET /api/patients/search?q=John
2. Get doctors: GET /api/staff/doctors → doctor_id: 2
3. Get nurses: GET /api/staff/nurses → nurse_id: 3
4. Admit patient: POST /api/patients/1/admit
   {
     "doctor_id": 2,    // User ID where role='doctor'
     "nurse_id": 3,     // User ID where role='nurse'
     "admitted_for": "...",
     ...
   }
```

### 19. Get Admission Details

**Endpoint:** `GET /api/admissions/{id}`

**Authorization:** Role-based

| Role        | Access                                               |
| ----------- | ---------------------------------------------------- |
| `root_user` | ✅ All admissions                                    |
| `admission` | ✅ All admissions                                    |
| `doctor`    | ✅ Only admissions where they are assigned as doctor |
| `nurse`     | ✅ Only admissions where they are assigned as nurse  |

Returns full admission details with patient information, assigned staff, and all treatment records.

#### Success Response (200 OK)

```json
{
    "message": "Admission retrieved successfully",
    "data": {
        "id": 5,
        "patient_id": 1,
        "admission_number": "ADM-2024-000005",
        "admission_type": "inpatient",
        "admission_date": "2024-12-03",
        "admission_time": "14:30:00",
        "present_address": "{\"region\": \"Yangon Region\", \"district\": \"East Yangon\", \"township\": \"Tamwe\"}",
        "admitted_for": "Chest pain and shortness of breath",
        "referred_by": "Dr. John Referrer",
        "police_case": "no",
        "service": "Cardiology",
        "ward": "Cardiology Ward A",
        "bed_number": "12",
        "medical_officer": "Dr. Medical Officer",
        "initial_diagnosis": "Suspected myocardial infarction. Patient presents with chest pain radiating to left arm.",
        "drug_allergy_noted": "Penicillin - causes rash",
        "remarks": "Patient arrived via ambulance. Family notified.",
        "discharge_date": null,
        "discharge_time": null,
        "discharge_diagnosis": null,
        "other_diagnosis": null,
        "external_cause_of_injury": null,
        "clinician_summary": null,
        "surgical_procedure": null,
        "discharge_type": null,
        "discharge_status": null,
        "discharge_instructions": null,
        "follow_up_instructions": null,
        "follow_up_date": null,
        "cause_of_death": null,
        "autopsy": null,
        "time_of_death": null,
        "certified_by": null,
        "approved_by": null,
        "attending_doctor_name": null,
        "attending_doctor_signature": null,
        "status": "admitted",
        "doctor_id": 2,
        "nurse_id": 3,
        "length_of_stay": 5,
        "created_at": "2024-12-03T14:30:00.000000Z",
        "updated_at": "2024-12-05T09:15:00.000000Z",
        "patient": {
            "id": 1,
            "name": "John Patient",
            "nrc_number": "12/ABC(N)123456",
            "sex": "male",
            "age": 45,
            "dob": "1979-05-15",
            "contact_phone": "09123456789",
            "permanent_address": "{\"region\": \"Yangon Region\", \"district\": \"West Yangon(Downtown)\", \"township\": \"Sanchaung\"}",
            "marital_status": "married",
            "ethnic_group": "Bamar",
            "religion": "Buddhist",
            "occupation": "Engineer",
            "created_at": "2024-11-01T10:00:00.000000Z",
            "updated_at": "2024-12-03T14:30:00.000000Z"
        },
        "doctor": {
            "id": 2,
            "name": "Dr. Smith",
            "email": "dr.smith@hospital.com"
        },
        "nurse": {
            "id": 3,
            "name": "Nurse Jane",
            "email": "nurse.jane@hospital.com"
        },
        "treatment_records": [
            {
                "id": 12,
                "admission_id": 5,
                "patient_id": 1,
                "treatment_type": "diagnostic",
                "treatment_name": "ECG (Electrocardiogram)",
                "description": "12-lead ECG to assess cardiac rhythm and detect abnormalities",
                "notes": "Patient was calm during procedure",
                "medications": null,
                "dosage": null,
                "treatment_date": "2024-12-03",
                "treatment_time": "15:00:00",
                "results": "Normal sinus rhythm. No acute ST elevation. Minor T-wave inversions in leads V4-V6.",
                "findings": "No evidence of acute myocardial infarction. Possible old inferior wall changes.",
                "outcome": "completed",
                "pre_procedure_notes": null,
                "post_procedure_notes": null,
                "complications": null,
                "doctor_id": 2,
                "nurse_id": 3,
                "created_at": "2024-12-03T15:00:00.000000Z",
                "updated_at": "2024-12-03T15:00:00.000000Z",
                "doctor": {
                    "id": 2,
                    "name": "Dr. Smith"
                },
                "nurse": {
                    "id": 3,
                    "name": "Nurse Jane"
                }
            },
            {
                "id": 13,
                "admission_id": 5,
                "patient_id": 1,
                "treatment_type": "medication",
                "treatment_name": "Aspirin 100mg",
                "description": "Daily aspirin for cardiac protection",
                "notes": "Patient has no history of GI bleeding",
                "medications": "Aspirin 100mg, Atorvastatin 20mg",
                "dosage": "Aspirin: 100mg once daily. Atorvastatin: 20mg once daily at bedtime.",
                "treatment_date": "2024-12-03",
                "treatment_time": "16:00:00",
                "results": "Patient tolerating medications well. No adverse reactions.",
                "findings": null,
                "outcome": "ongoing",
                "pre_procedure_notes": null,
                "post_procedure_notes": null,
                "complications": null,
                "doctor_id": 2,
                "nurse_id": 3,
                "created_at": "2024-12-03T16:00:00.000000Z",
                "updated_at": "2024-12-03T16:00:00.000000Z",
                "doctor": {
                    "id": 2,
                    "name": "Dr. Smith"
                },
                "nurse": {
                    "id": 3,
                    "name": "Nurse Jane"
                }
            },
            {
                "id": 14,
                "admission_id": 5,
                "patient_id": 1,
                "treatment_type": "diagnostic",
                "treatment_name": "Complete Blood Count (CBC)",
                "description": "Routine blood test to check blood cell counts",
                "notes": "Fasting blood sample",
                "medications": null,
                "dosage": null,
                "treatment_date": "2024-12-04",
                "treatment_time": "08:00:00",
                "results": "Hemoglobin: 14.2 g/dL (normal). White blood cell count: 7,500/μL (normal). Platelet count: 250,000/μL (normal).",
                "findings": "All blood parameters within normal range. No signs of infection or anemia.",
                "outcome": "completed",
                "pre_procedure_notes": null,
                "post_procedure_notes": null,
                "complications": null,
                "doctor_id": 2,
                "nurse_id": 3,
                "created_at": "2024-12-04T08:00:00.000000Z",
                "updated_at": "2024-12-04T08:00:00.000000Z",
                "doctor": {
                    "id": 2,
                    "name": "Dr. Smith"
                },
                "nurse": {
                    "id": 3,
                    "name": "Nurse Jane"
                }
            }
        ]
    }
}
```

#### Response Fields Explanation

**Admission Fields:**

| Field                    | Type    | Description                               | Example Value                                               |
| ------------------------ | ------- | ----------------------------------------- | ----------------------------------------------------------- |
| `id`                     | integer | Admission ID                              | `5`                                                         |
| `patient_id`             | integer | Patient ID                                | `1`                                                         |
| `admission_number`       | string  | Unique admission number                   | `"ADM-2024-000005"`                                         |
| `admission_type`         | string  | Type of admission                         | `"inpatient"` or `"outpatient"`                             |
| `admission_date`         | date    | Date of admission                         | `"2024-12-03"`                                              |
| `admission_time`         | time    | Time of admission                         | `"14:30:00"`                                                |
| `status`                 | string  | Current status                            | `"admitted"`, `"discharged"`, `"deceased"`, `"transferred"` |
| `admitted_for`           | string  | Reason for admission                      | `"Chest pain and shortness of breath"`                      |
| `ward`                   | string  | Ward assignment (inpatient only)          | `"Cardiology Ward A"`                                       |
| `bed_number`             | string  | Bed number (inpatient only)               | `"12"`                                                      |
| `length_of_stay`         | integer | Number of days since admission (computed) | `5`                                                         |
| `doctor_id` / `nurse_id` | integer | Assigned staff user IDs                   | `2`, `3`                                                    |
| `discharge_date`         | date    | Discharge date (if discharged)            | `null` or `"2024-12-08"`                                    |
| `cause_of_death`         | string  | Cause of death (if deceased)              | `null` or `"Cardiac arrest"`                                |
| ...                      | ...     | (See full response for all fields)        | ...                                                         |

**Nested Objects:**

-   **`patient`**: Full patient demographic information
-   **`doctor`**: Assigned doctor details (`id`, `name`, `email`)
-   **`nurse`**: Assigned nurse details (`id`, `name`, `email`)
-   **`treatment_records`**: Array of all treatment records for this admission, ordered by `treatment_date` (newest first)

#### Error Responses

**403 Forbidden - User not assigned to admission:**

```json
{
    "message": "Unauthorized. You do not have access to this admission."
}
```

**404 Not Found:**

```json
{
    "message": "Admission not found."
}
```

### 20. Update Admission

**Endpoint:** `PUT /api/admissions/{id}` or `PATCH /api/admissions/{id}`

**Authorization:**

-   `root_user` / `admission`: Can update administrative and medical fields
-   `doctor`: Can update MEDICAL fields only (for assigned admissions)

**Purpose:** Update admission details during the patient's stay, including staff assignment (`doctor_id`, `nurse_id`). For status changes, use dedicated endpoints.

**Note:** Staff assignment is done via this endpoint, not a separate endpoint. Staff is assigned when creating an admission, and can be reassigned later using this update endpoint.

#### ⚠️ IMPORTANT: Restricted Fields

The following fields **CANNOT** be changed via this endpoint. Use dedicated endpoints instead:

| Field              | Restriction | Use This Endpoint Instead                                                  |
| ------------------ | ----------- | -------------------------------------------------------------------------- |
| `status`           | ❌ BLOCKED  | `POST /admissions/{id}/discharge` or `POST /admissions/{id}/confirm-death` |
| `admission_type`   | ❌ BLOCKED  | `POST /admissions/{id}/convert-to-inpatient`                               |
| `discharge_type`   | ❌ BLOCKED  | `POST /admissions/{id}/discharge`                                          |
| `discharge_status` | ❌ BLOCKED  | `POST /admissions/{id}/discharge`                                          |
| `discharge_date`   | ❌ BLOCKED  | `POST /admissions/{id}/discharge`                                          |
| `discharge_time`   | ❌ BLOCKED  | `POST /admissions/{id}/discharge`                                          |
| `cause_of_death`   | ❌ BLOCKED  | `POST /admissions/{id}/confirm-death`                                      |
| `time_of_death`    | ❌ BLOCKED  | `POST /admissions/{id}/confirm-death`                                      |
| `autopsy`          | ❌ BLOCKED  | `POST /admissions/{id}/confirm-death`                                      |
| `certified_by`     | ❌ BLOCKED  | Set automatically by discharge/death endpoints                             |
| `approved_by`      | ❌ BLOCKED  | Set automatically by discharge/death endpoints                             |

#### Admission Status Restrictions

| Current Status  | What Can Be Updated                                                                              |
| --------------- | ------------------------------------------------------------------------------------------------ |
| **admitted**    | ✅ All allowed fields                                                                            |
| **discharged**  | ⚠️ Limited: `remarks`, `follow_up_instructions`, `follow_up_date`, `discharge_instructions` only |
| **deceased**    | ❌ Almost nothing (only `remarks` for minor corrections)                                         |
| **transferred** | ⚠️ Limited: `remarks` only                                                                       |

#### Role-Based Field Access

| Field Category         | Fields                                                                                                                                                                | Root/Admission | Doctor (Assigned) |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------- | ----------------- |
| **Staff Assignment**   | `doctor_id`, `nurse_id`                                                                                                                                               | ✅             | ❌                |
| **Admission Details**  | `admission_date`, `admission_time`, `present_address`, `admitted_for`, `referred_by`, `police_case`, `service`, `ward`, `bed_number`, `medical_officer`               | ✅             | ❌                |
| **Medical Assessment** | `initial_diagnosis`, `drug_allergy_noted`, `remarks`, `clinician_summary`, `surgical_procedure`, `other_diagnosis`, `external_cause_of_injury`, `discharge_diagnosis` | ✅             | ✅                |
| **Follow-up Info**     | `discharge_instructions`, `follow_up_instructions`, `follow_up_date`                                                                                                  | ✅             | ✅                |
| **Certification**      | `attending_doctor_name`, `attending_doctor_signature`                                                                                                                 | ✅             | ✅                |

#### Complete List of Updatable Fields

**All fields below can be updated via this endpoint.** All are optional (use `PATCH` for partial updates).

**Staff Assignment (Root/Admission only):**

-   `doctor_id` - Reassign doctor
-   `nurse_id` - Reassign nurse

**Admission Details (Root/Admission only):**

-   `admission_date` - Change admission date
-   `admission_time` - Change admission time
-   `present_address` - Update current address
-   `admitted_for` - Update chief complaint
-   `referred_by` - Update referral source
-   `police_case` - Update police case status (`yes`/`no`)
-   `service` - Change department/service
-   `ward` - Transfer to different ward
-   `bed_number` - Change bed number
-   `medical_officer` - Update medical officer

**Medical Assessment (Root/Admission/Doctor):**

-   `initial_diagnosis` - Initial assessment diagnosis
-   `drug_allergy_noted` - Known allergies
-   `remarks` - Progress notes, comments
-   `clinician_summary` - Complete clinical summary
-   `surgical_procedure` - Procedures performed
-   `other_diagnosis` - Additional diagnoses
-   `external_cause_of_injury` - For injury cases
-   `discharge_diagnosis` - Final diagnosis

**Follow-up Information (Root/Admission/Doctor, works after discharge too):**

-   `discharge_instructions` - Instructions for patient
-   `follow_up_instructions` - Follow-up care details
-   `follow_up_date` - Next appointment date

**Certification (Root/Admission/Doctor):**

-   `attending_doctor_name` - Attending physician name
-   `attending_doctor_signature` - Signature for records

#### Request Parameters (Detailed)

| Field                        | Type          | Constraints               | Accepted Values                                                               | Description                                         |
| ---------------------------- | ------------- | ------------------------- | ----------------------------------------------------------------------------- | --------------------------------------------------- |
| **Staff Assignment**         |
| `doctor_id`                  | integer       | Must exist in users table | User ID (role=`doctor`)                                                       | Reassign doctor                                     |
| `nurse_id`                   | integer       | Must exist in users table | User ID (role=`nurse`)                                                        | Reassign nurse                                      |
| **Admission Details**        |
| `admission_date`             | date          | YYYY-MM-DD                | Past or today                                                                 | Update admission date                               |
| `admission_time`             | time          | HH:mm                     | e.g., "10:30"                                                                 | Update admission time                               |
| `present_address`            | string (JSON) | Validated                 | JSON: `{"region": "...", "district": "...", "township": "..."}` or plain text | Current address (must match Myanmar addresses list) |
| `admitted_for`               | string        | max:500                   | Any text                                                                      | Chief complaint                                     |
| `referred_by`                | string        | max:255                   | Any text                                                                      | Referring doctor/hospital                           |
| `police_case`                | string        | -                         | `yes`, `no`                                                                   | Police case status                                  |
| `service`                    | string        | max:255                   | Any text                                                                      | Department/service                                  |
| `ward`                       | string        | max:100                   | Any text                                                                      | Ward name                                           |
| `bed_number`                 | string        | max:50                    | Any text                                                                      | Bed number                                          |
| `medical_officer`            | string        | max:255                   | Any text                                                                      | Medical officer name                                |
| **Medical Assessment**       |
| `initial_diagnosis`          | string        | max:500                   | Any text                                                                      | Initial diagnosis                                   |
| `drug_allergy_noted`         | string        | max:255                   | Any text                                                                      | Allergies                                           |
| `remarks`                    | string        | max:500                   | Any text                                                                      | Additional remarks/notes                            |
| `clinician_summary`          | string        | max:1000                  | Any text                                                                      | Clinical summary                                    |
| `surgical_procedure`         | string        | max:500                   | Any text                                                                      | If surgery performed                                |
| `other_diagnosis`            | string        | max:500                   | Any text                                                                      | Other conditions found                              |
| `external_cause_of_injury`   | string        | max:500                   | Any text                                                                      | If injury case                                      |
| `discharge_diagnosis`        | string        | max:500                   | Any text                                                                      | Final diagnosis                                     |
| **Follow-up Information**    |
| `discharge_instructions`     | string        | max:1000                  | Any text                                                                      | Instructions for patient                            |
| `follow_up_instructions`     | string        | max:500                   | Any text                                                                      | Follow-up care instructions                         |
| `follow_up_date`             | date          | >= today                  | YYYY-MM-DD                                                                    | Next appointment date                               |
| **Certification**            |
| `attending_doctor_name`      | string        | max:255                   | Any text                                                                      | Attending doctor                                    |
| `attending_doctor_signature` | string        | max:255                   | Any text                                                                      | Signature                                           |

#### Example Requests

**Example 1: Complete Administrative Update (Root/Admission Staff)**

Update all administrative and location details:

```json
{
    "admission_date": "2024-12-01",
    "admission_time": "14:30",
    "doctor_id": 5,
    "nurse_id": 7,
    "ward": "ICU",
    "bed_number": "3",
    "service": "Cardiology",
    "medical_officer": "Dr. Kyaw Soe",
    "present_address": "{\"region\": \"Yangon Region\", \"district\": \"North Yangon\", \"township\": \"Insein\"}",
    "admitted_for": "Chest pain and shortness of breath",
    "referred_by": "Yangon General Hospital - Dr. Zaw Win",
    "police_case": "no"
}
```

**Example 2: Comprehensive Medical Documentation (Doctor)**

Update all medical assessment and clinical information:

```json
{
    "initial_diagnosis": "Acute ST-elevation myocardial infarction (STEMI)",
    "drug_allergy_noted": "Penicillin (rash), Sulfa drugs (anaphylaxis), NSAIDs (gastritis)",
    "remarks": "Patient responded well to thrombolytic therapy. Chest pain resolved. Vital signs stable. Planning for angiography.",
    "discharge_diagnosis": "Acute myocardial infarction with successful PCI to LAD",
    "other_diagnosis": "Type 2 diabetes mellitus (controlled), Hypertension stage 2, Hyperlipidemia",
    "external_cause_of_injury": "N/A - Medical condition",
    "clinician_summary": "65yo male with known HTN, DM, presented with crushing central chest pain radiating to left arm for 2 hours. ECG showed ST elevation in leads V1-V4. Troponin I elevated at 15.2 ng/mL. Emergency cardiac catheterization revealed 95% stenosis of proximal LAD. Successful PCI with drug-eluting stent placement. Post-procedure recovery uneventful. Patient stable on dual antiplatelet therapy.",
    "surgical_procedure": "Percutaneous coronary intervention (PCI) with drug-eluting stent to left anterior descending artery (LAD)"
}
```

**Example 3: Staff Reassignment**

Reassign doctor and nurse when staff rotation occurs:

```json
{
    "doctor_id": 12,
    "nurse_id": 18,
    "remarks": "Staff reassignment due to shift change. New doctor briefed on patient condition."
}
```

**Example 4: Ward/Bed Transfer**

Update when patient moves to different ward or bed:

```json
{
    "ward": "General Ward A",
    "bed_number": "42",
    "remarks": "Patient transferred from ICU to general ward. Condition improved and stable."
}
```

**Example 5: Add Clinical Progress Notes**

Doctor adds daily progress notes during admission:

```json
{
    "remarks": "Day 3 post-PCI: Patient ambulating well. No chest pain. BP 130/80, HR 72. Continue current medications. Discharge planned for tomorrow if stable."
}
```

**Example 6: Update Follow-up Information (After Discharge)**

After patient is discharged, update or modify follow-up instructions:

```json
{
    "follow_up_instructions": "1. Return to Cardiology OPD in 2 weeks\n2. Bring all medications and discharge summary\n3. Fasting required for lipid profile\n4. ECG and Echo scheduled",
    "follow_up_date": "2024-12-20",
    "discharge_instructions": "1. Take Aspirin 100mg + Clopidogrel 75mg daily\n2. Continue BP medications as prescribed\n3. Strict cardiac diet - low salt, low fat\n4. Light exercise - walking 15-20 mins daily\n5. Monitor BP daily\n6. Avoid smoking and alcohol\n7. Seek immediate care if chest pain returns",
    "remarks": "Patient called to confirm follow-up appointment date."
}
```

**Example 7: Update Medical Officer and Service**

Update service department or medical officer in charge:

```json
{
    "service": "Cardiothoracic Surgery",
    "medical_officer": "Dr. Than Htut",
    "remarks": "Patient referred to cardiothoracic surgery for CABG evaluation."
}
```

**Example 8: Complete Clinical Documentation Before Discharge**

Prepare complete documentation with all clinical details:

```json
{
    "clinician_summary": "Patient admitted with STEMI. Underwent successful PCI. Hospital course uncomplicated. Discharged on DAPT, statin, ACE inhibitor, and beta-blocker.",
    "surgical_procedure": "PCI with DES to LAD",
    "discharge_diagnosis": "STEMI treated with PCI",
    "other_diagnosis": "Type 2 DM, Hypertension, Dyslipidemia",
    "discharge_instructions": "Complete medication list provided. Cardiac rehab referral given. Lifestyle modifications discussed.",
    "follow_up_instructions": "Cardiology OPD 2 weeks, Labs in 1 month",
    "follow_up_date": "2024-12-20",
    "attending_doctor_name": "Dr. Aung Kyaw Myo",
    "attending_doctor_signature": "Dr. Aung Kyaw Myo, MD (Cardiology), License #12345"
}
```

**Example 9: Minimal Single Field Update**

Quick update of just one field:

```json
{
    "bed_number": "25"
}
```

**Example 10: Update Multiple Medical Fields**

Update several medical fields at once:

```json
{
    "initial_diagnosis": "Updated after lab results: Diabetic ketoacidosis",
    "drug_allergy_noted": "Added: Metformin (lactic acidosis)",
    "remarks": "Blood glucose improving with insulin therapy. Ketones clearing."
}
```

---

**⚠️ IMPORTANT: For Status Changes, Use Dedicated Endpoints**

| To Change Status     | DO NOT Use `/update` | Instead Use                                      |
| -------------------- | -------------------- | ------------------------------------------------ |
| Discharge patient    | ❌                   | `POST /api/admissions/{id}/discharge`            |
| Confirm death        | ❌                   | `POST /api/admissions/{id}/confirm-death`        |
| Convert to inpatient | ❌                   | `POST /api/admissions/{id}/convert-to-inpatient` |

Attempting to change these via `/update` will be **automatically blocked**.

#### Success Response (200 OK)

```json
{
    "message": "Admission updated successfully",
    "data": {
        "id": 1,
        "admission_number": "ADM-2024-000001",
        "admission_type": "inpatient",
        "admission_date": "2024-12-03",
        "admission_time": "10:30",
        "status": "admitted",
        "ward": "Ward C",
        "bed_number": "20",
        "service": "Neurology",
        "initial_diagnosis": "Acute myocardial infarction",
        "drug_allergy_noted": "Penicillin, Sulfa drugs",
        "remarks": "Patient showing signs of improvement",
        "patient": {
            "id": 1,
            "name": "John Doe",
            "nrc_number": "12/ABC(N)123456"
        },
        "doctor": {
            "id": 5,
            "name": "Dr. Aung Kyaw",
            "email": "dr.aung@hospital.com"
        },
        "nurse": {
            "id": 7,
            "name": "Nurse May",
            "email": "nurse.may@hospital.com"
        }
    }
}
```

#### Error Responses

**400 Bad Request** - Attempting to modify deceased admission

```json
{
    "message": "Cannot modify deceased admission records. Death records are immutable. Attempted to change: ward, bed_number"
}
```

**400 Bad Request** - Attempting to change status directly

```json
{
    "message": "Cannot set status to discharged via update. Use POST /api/admissions/{id}/discharge endpoint instead."
}
```

**400 Bad Request** - Attempting to modify discharged admission (blocked fields)

```json
{
    "message": "Limited updates allowed on discharged admissions. Can only update: remarks, follow_up_instructions, follow_up_date, discharge_instructions. Attempted to change: ward, doctor_id"
}
```

**403 Forbidden** - Doctor trying to update non-medical fields

```json
{
    "message": "Unauthorized. You do not have permission to update this admission."
}
```

**403 Forbidden** - Doctor trying to update unassigned admission

```json
{
    "message": "Unauthorized. You can only update admissions assigned to you."
}
```

**404 Not Found**

```json
{
    "message": "Admission not found."
}
```

**422 Unprocessable Entity** - Validation errors

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "doctor_id": ["The selected doctor does not exist."],
        "follow_up_date": ["Follow-up date must be today or in the future."]
    }
}
```

#### Important Notes

**Role Restrictions:**

-   **Root/Admission:** Full access to allowed fields
-   **Doctor:** Only medical and follow-up fields (and only for their assigned admissions)
-   **Nurse:** Cannot update admissions (read-only access)

**Status-Based Restrictions:**

| Status        | What Can Be Updated                                                                      |
| ------------- | ---------------------------------------------------------------------------------------- |
| `admitted`    | ✅ All allowed fields                                                                    |
| `discharged`  | ⚠️ Only: `remarks`, `follow_up_instructions`, `follow_up_date`, `discharge_instructions` |
| `deceased`    | ❌ Only: `remarks` (minor corrections only)                                              |
| `transferred` | ⚠️ Only: `remarks`                                                                       |

**Common Use Cases (See Examples Above for Details):**

| Use Case                   | Status Required | Example #  | Fields Updated                          |
| -------------------------- | --------------- | ---------- | --------------------------------------- |
| **Complete Admin Update**  | `admitted`      | Example 1  | All administrative fields               |
| **Clinical Documentation** | `admitted`      | Example 2  | All medical assessment fields           |
| **Staff Reassignment**     | `admitted`      | Example 3  | `doctor_id`, `nurse_id`                 |
| **Ward/Bed Transfer**      | `admitted`      | Example 4  | `ward`, `bed_number`                    |
| **Daily Progress Notes**   | `admitted`      | Example 5  | `remarks`                               |
| **Update Follow-up**       | `discharged`    | Example 6  | `follow_up_*`, `discharge_instructions` |
| **Change Service/MO**      | `admitted`      | Example 7  | `service`, `medical_officer`            |
| **Pre-Discharge Docs**     | `admitted`      | Example 8  | All clinical summary fields             |
| **Quick Single Update**    | Any             | Example 9  | Any single field                        |
| **Update Medical Info**    | `admitted`      | Example 10 | Multiple medical fields                 |

**For Status Changes, Use Dedicated Endpoints:**

| To Do This           | Use This Endpoint                                |
| -------------------- | ------------------------------------------------ |
| Discharge patient    | `POST /api/admissions/{id}/discharge`            |
| Confirm death        | `POST /api/admissions/{id}/confirm-death`        |
| Convert to inpatient | `POST /api/admissions/{id}/convert-to-inpatient` |

**Best Practices:**

-   Use `PATCH` for partial updates (single/few fields)
-   Use `PUT` for complete record updates (many fields)
-   **NEVER** try to change `status` via this endpoint - use dedicated endpoints
-   Update `discharge_date` when finalizing discharge
-   Always provide `follow_up_date` for discharged patients who need follow-up
-   Use `remarks` field for ongoing medical notes during admission

### 21. Convert Outpatient to Inpatient

**Endpoint:** `POST /api/admissions/{id}/convert-to-inpatient`

**Authorization:** `root_user`, `admission`, or `doctor` (assigned only)

**Use Case:** When an active outpatient visit requires overnight stay and needs to be converted to inpatient admission.

#### ⚠️ Important Restrictions

**What CAN be converted:**

-   ✅ **Active outpatient visits only** (status: `admitted`)
-   ✅ Must be current/ongoing visit (not historical)

**What CANNOT be converted:**

-   ❌ **Discharged outpatient visits** - Returns error: "Cannot convert a closed outpatient visit. Current status: discharged"
-   ❌ **Deceased patient admissions** - Returns error: "Cannot convert to inpatient. Patient is deceased."
-   ❌ **Transferred admissions** - Returns error: "Cannot convert a closed outpatient visit. Current status: transferred"
-   ❌ **Already inpatient admissions** - Returns error: "This admission is already an inpatient admission."
-   ❌ **Patient has active inpatient** - Returns error: "Cannot convert to inpatient. Patient already has an active inpatient admission." (Only ONE active inpatient allowed per patient)
-   ❌ **Old/historical admissions** - Any admission that is not currently active (status ≠ `admitted`)

**Business Logic:**

-   Conversion only makes sense for **current, active visits** where the patient is still in the hospital
-   Once an outpatient visit is closed (discharged/transferred), it cannot be retroactively converted
-   Historical records should remain unchanged for data integrity

```json
// Request
{
    "ward": "Ward B",
    "bed_number": "15",
    "admission_time": "16:30",
    "remarks": "Condition worsened, requires observation"
}

// Response (200 OK)
{
    "message": "Outpatient visit successfully converted to inpatient admission",
    "data": {
        "id": 5,
        "admission_number": "ADM-2024-000005",
        "admission_type": "inpatient",  // Changed from outpatient
        "admission_date": "2024-12-03",
        "ward": "Ward B",
        "bed_number": "15",
        "status": "admitted",
        "patient": { "id": 1, "name": "John Patient" },
        "doctor": { "id": 2, "name": "Dr. Smith" }
    }
}
```

#### Error Responses

**400 Bad Request** - Trying to convert a discharged/closed outpatient visit

```json
{
    "message": "Cannot convert a closed outpatient visit. Current status: discharged"
}
```

**Scenario:** Patient had an outpatient visit last month that was already discharged. You cannot retroactively convert it to inpatient.

**400 Bad Request** - Trying to convert a deceased patient's admission

```json
{
    "message": "Cannot convert to inpatient. Patient is deceased.",
    "death_record": {
        "admission_number": "ADM-2024-000003",
        "date_of_death": "2024-11-15",
        "cause_of_death": "Cardiac arrest"
    }
}
```

**Scenario:** Patient is deceased. Even if they have an old active outpatient visit, it cannot be converted.

**400 Bad Request** - Already an inpatient admission

```json
{
    "message": "This admission is already an inpatient admission."
}
```

**Scenario:** Trying to convert an admission that is already marked as inpatient.

**400 Bad Request** - Patient already has an active inpatient admission

```json
{
    "message": "Cannot convert to inpatient. Patient already has an active inpatient admission.",
    "current_inpatient": {
        "id": 3,
        "admission_number": "ADM-2024-000003",
        "admission_date": "2024-12-01",
        "admitted_for": "Pneumonia",
        "ward": "Ward A",
        "bed_number": "10"
    },
    "note": "Please discharge the existing inpatient admission first, or convert this outpatient visit after the current inpatient is closed."
}
```

**Scenario:** Patient has an active inpatient admission (status: `admitted`, type: `inpatient`). Only ONE active inpatient is allowed per patient. You must discharge the existing inpatient first before converting the outpatient visit to inpatient.

**Example:**

-   Patient has Inpatient #1 (admitted on Dec 1, still active)
-   Patient has Outpatient #2 (admitted on Dec 5, still active)
-   Trying to convert Outpatient #2 to inpatient → ❌ BLOCKED
-   Solution: Discharge Inpatient #1 first, then convert Outpatient #2

**400 Bad Request** - Trying to convert transferred admission

```json
{
    "message": "Cannot convert a closed outpatient visit. Current status: transferred"
}
```

**Scenario:** Patient was transferred to another facility. The admission is closed and cannot be converted.

**403 Forbidden** - Doctor trying to convert unassigned admission

```json
{
    "message": "Unauthorized. You can only convert admissions assigned to you."
}
```

**404 Not Found**

```json
{
    "message": "Admission not found."
}
```

**422 Unprocessable Entity** - Missing required ward

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "ward": ["The ward field is required."]
    }
}
```

#### Why Old Admissions Cannot Be Converted

**Data Integrity:**

-   Historical records should remain accurate - if a visit was outpatient when it happened, it should stay that way
-   Converting old records would create inaccurate medical history

**Business Logic:**

-   Conversion is meant for **current situations** where a patient's condition changes during an active visit
-   Example: Patient comes for outpatient consultation, but condition worsens and requires admission
-   Once a visit is closed (discharged/transferred), the patient is no longer in the hospital, so conversion doesn't make sense

**Example Scenarios:**

✅ **Valid Conversion:**

```
1. Patient arrives for outpatient consultation (status: admitted, type: outpatient)
2. During visit, doctor determines patient needs overnight observation
3. Convert to inpatient → Patient gets ward/bed assignment
```

❌ **Invalid Conversion (Old Admission):**

```
1. Patient had outpatient visit last month (status: discharged, type: outpatient)
2. Someone tries to convert it now → BLOCKED
3. Reason: Visit is already closed, patient is not in hospital
```

❌ **Invalid Conversion (Deceased Patient):**

```
1. Patient had active outpatient visit
2. Patient dies in different admission
3. Someone tries to convert old outpatient visit → BLOCKED
4. Reason: Patient is deceased, cannot admit deceased patients
```

### 22. Discharge Patient

**Endpoint:** `POST /api/admissions/{id}/discharge`

**Authorization:** `root_user` or `doctor` (assigned only)

**Purpose:** Close an active admission (both outpatient and inpatient) by recording discharge information and updating status to `discharged`.

#### Applicable to Both Outpatient and Inpatient

✅ **Works for:**

-   **Inpatient admissions** - Patient staying in hospital, being discharged
-   **Outpatient visits** - Patient visit completed, closing the visit record

**Business Logic:**

-   Both admission types need to be "closed" when the patient leaves
-   Discharge records important information: diagnosis, instructions, follow-up
-   Sets `status` to `discharged` and records `discharge_date` and `discharge_time`

**Requirements:**

-   Admission must have `status: 'admitted'` (active)
-   Cannot discharge already discharged/transferred/deceased admissions
-   Doctor must be assigned to the admission (if doctor role)

#### Request Body

```json
{
    "discharge_type": "normal",
    "discharge_status": "improved",
    "discharge_diagnosis": "Acute bronchitis - resolved",
    "clinician_summary": "Patient recovered fully after 5-day treatment.",
    "discharge_instructions": "Continue oral antibiotics for 3 days",
    "follow_up_instructions": "Return if symptoms recur",
    "follow_up_date": "2024-12-15"
}
```

#### Request Parameters

| Field                    | Type   | Required | Accepted Values                                        | Description                    |
| ------------------------ | ------ | -------- | ------------------------------------------------------ | ------------------------------ |
| `discharge_type`         | string | ✅ Yes   | `normal`, `against_advice`, `absconded`, `transferred` | How patient left               |
| `discharge_status`       | string | ✅ Yes   | `improved`, `unchanged`, `worse`                       | Patient condition at discharge |
| `discharge_diagnosis`    | string | No       | Any text (max:500)                                     | Final diagnosis                |
| `clinician_summary`      | string | No       | Any text (max:1000)                                    | Clinical summary               |
| `discharge_instructions` | string | No       | Any text (max:1000)                                    | Instructions for patient       |
| `follow_up_instructions` | string | No       | Any text (max:500)                                     | Follow-up care details         |
| `follow_up_date`         | date   | No       | >= today                                               | Next appointment date          |

#### Success Response (200 OK)

```json
{
    "message": "Patient discharged successfully",
    "data": {
        "id": 5,
        "admission_number": "ADM-2024-000005",
        "admission_type": "inpatient", // or "outpatient"
        "status": "discharged",
        "discharge_date": "2024-12-10",
        "discharge_time": "15:30",
        "discharge_type": "normal",
        "discharge_status": "improved",
        "patient": {
            "id": 1,
            "name": "John Patient"
        }
    }
}
```

#### Error Responses

**400 Bad Request** - Already discharged

```json
{
    "message": "Patient is not currently admitted. Current status: discharged"
}
```

**400 Bad Request** - Patient is deceased

```json
{
    "message": "Cannot discharge. Patient is deceased. Use confirm death endpoint instead."
}
```

**403 Forbidden** - Doctor not assigned

```json
{
    "message": "Unauthorized. You can only discharge patients assigned to you."
}
```

#### Use Cases

**Outpatient Discharge Example:**

```
1. Patient comes for consultation (outpatient visit created)
2. Doctor examines and treats patient
3. Visit complete → Discharge with follow-up instructions
4. Status changes: admitted → discharged
```

**Inpatient Discharge Example:**

```
1. Patient admitted as inpatient (stays in ward)
2. Treatment provided over several days
3. Patient ready to go home → Discharge with medications/instructions
4. Status changes: admitted → discharged
5. Bed becomes available
```

**Discharge Types Explained:**

| Type             | When to Use                                      |
| ---------------- | ------------------------------------------------ |
| `normal`         | Standard discharge - patient completed treatment |
| `against_advice` | Patient left against medical advice              |
| `absconded`      | Patient left without informing staff             |
| `transferred`    | Patient transferred to another facility          |

### 23. Confirm Death

**Endpoint:** `POST /api/admissions/{id}/confirm-death`

**Authorization:** `root_user` or `doctor` (assigned only)

**Purpose:** Record patient death for an **active admission** (status: `admitted`). This endpoint is used when a patient dies while still admitted to the hospital.

#### ⚠️ CRITICAL RESTRICTIONS

**What CAN be confirmed as deceased:**

-   ✅ **Active admissions only** (status: `admitted`)
-   ✅ Both inpatient and outpatient admissions
-   ✅ Patient must be currently in the hospital
-   ✅ **New admissions even if patient has old discharged admissions** - Old discharged admissions do NOT block confirming death on a new active admission

**What CANNOT be confirmed as deceased:**

-   ❌ **Discharged admissions** - Returns error: "Cannot confirm death on a discharged admission"
-   ❌ **Transferred admissions** - Returns error: "Cannot confirm death on a transferred admission"
-   ❌ **Already deceased admissions** - Returns error: "Death has already been confirmed"
-   ❌ **Patients already deceased** - Returns error: "Patient death has already been confirmed in a previous admission"

**Important Clarification:**

-   **Old discharged admissions do NOT prevent** confirming death on a **new active admission**
-   System only checks if patient is **already deceased** (has admission with status: `deceased`)
-   System does NOT check for old discharged admissions when confirming death on a new admission
-   Example: Patient discharged in November, readmitted in December, dies in December → Can confirm death on December admission ✅

#### Why Discharged Admissions Cannot Be Confirmed as Deceased

**Business Logic:**

-   If a patient was **discharged alive**, they cannot be retroactively marked as deceased on that same admission
-   Death confirmation is for patients who **die while in the hospital** (active admission)
-   If a patient dies **after discharge**, that is a different medical event and should be handled through proper medical records, not by modifying a discharged admission

**Data Integrity:**

-   Discharged admissions represent patients who left the hospital alive
-   Retroactively changing a discharged admission to deceased would create inaccurate medical history
-   Death records must be accurate and reflect the actual time and circumstances of death

#### Request Body

```json
{
    "cause_of_death": "Cardiac arrest secondary to acute myocardial infarction",
    "autopsy": "pending",
    "time_of_death": "2024-12-08T14:30:00",
    "certified_by": "Dr. Smith"
}
```

#### Request Parameters

| Field            | Type     | Required | Accepted Values        | Description                     |
| ---------------- | -------- | -------- | ---------------------- | ------------------------------- |
| `cause_of_death` | string   | ✅ Yes   | Any text (max:255)     | Medical cause of death          |
| `autopsy`        | string   | No       | `yes`, `no`, `pending` | Autopsy status                  |
| `time_of_death`  | datetime | No       | YYYY-MM-DD HH:mm:ss    | Time of death (defaults to now) |
| `certified_by`   | string   | No       | Any text (max:255)     | Certifying doctor name          |

#### Success Response (200 OK)

```json
{
    "message": "Patient death confirmed",
    "data": {
        "id": 5,
        "admission_number": "ADM-2024-000005",
        "status": "deceased",
        "discharge_date": "2024-12-08",
        "discharge_time": "14:30",
        "discharge_status": "dead",
        "cause_of_death": "Cardiac arrest secondary to acute myocardial infarction",
        "time_of_death": "2024-12-08 14:30:00",
        "autopsy": "pending",
        "certified_by": "Dr. Smith",
        "patient": {
            "id": 1,
            "name": "John Patient"
        }
    }
}
```

#### Error Responses

**400 Bad Request** - Trying to confirm death on discharged admission

```json
{
    "message": "Cannot confirm death on a discharged admission. Death can only be confirmed for active admissions (status: admitted).",
    "current_status": "discharged",
    "discharge_date": "2024-12-05",
    "note": "If patient died after discharge, this should be recorded in a new admission or through proper medical records, not by modifying a discharged admission."
}
```

**400 Bad Request** - Trying to confirm death on transferred admission

```json
{
    "message": "Cannot confirm death on a transferred admission. Death can only be confirmed for active admissions (status: admitted).",
    "current_status": "transferred",
    "note": "Patient was transferred to another facility. Death confirmation should be handled by the receiving facility."
}
```

**400 Bad Request** - Patient already deceased in another admission

```json
{
    "message": "Patient death has already been confirmed in a previous admission.",
    "previous_death_record": {
        "admission_number": "ADM-2024-000003",
        "date_of_death": "2024-11-15",
        "cause_of_death": "Cardiac arrest"
    }
}
```

**400 Bad Request** - Death already confirmed for this admission

```json
{
    "message": "Death has already been confirmed for this admission."
}
```

**400 Bad Request** - Admission not active

```json
{
    "message": "Death can only be confirmed for active admissions (status: admitted). Current status: discharged"
}
```

**403 Forbidden** - Doctor not assigned

```json
{
    "message": "Unauthorized. You can only confirm death for patients assigned to you."
}
```

#### Use Cases

**Valid Scenario:**

```
1. Patient admitted (status: admitted)
2. Patient's condition worsens
3. Patient dies while in hospital
4. Doctor confirms death → Status changes: admitted → deceased
```

**Invalid Scenario (Discharged):**

```
1. Patient admitted (status: admitted)
2. Patient discharged (status: discharged) - left hospital alive
3. Patient dies at home later
4. Someone tries to confirm death on discharged admission → BLOCKED
5. Reason: Cannot retroactively mark discharged admission as deceased
```

**Invalid Scenario (Transferred):**

```
1. Patient admitted (status: admitted)
2. Patient transferred to another facility (status: transferred)
3. Patient dies at other facility
4. Someone tries to confirm death on transferred admission → BLOCKED
5. Reason: Death should be confirmed by receiving facility
```

**Valid Scenario (Readmission After Discharge):**

```
1. Patient has OLD admission (status: discharged) - discharged alive
2. Patient returns to hospital later
3. Patient gets NEW admission (status: admitted) - active admission
4. Patient dies during this NEW admission
5. Confirm death on NEW admission → ✅ ALLOWED
6. Reason: New admission is active, old discharged admission doesn't block it
```

**Important Notes:**

-   **Old discharged admissions do NOT block** confirming death on new active admissions
-   System only checks if patient is **already deceased** (status: deceased), not if they have old discharged admissions
-   Each admission is independent - death can be confirmed on any active admission
-   Patient can have multiple admissions in their history (discharged, transferred, etc.) and still get a new admission where death can be confirmed

**Example Timeline:**

```
Timeline:
2024-11-01: Admission #1 created (inpatient)
2024-11-10: Admission #1 discharged (status: discharged) - patient left alive
2024-12-05: Admission #2 created (inpatient) - patient readmitted
2024-12-08: Patient dies during Admission #2
2024-12-08: Confirm death on Admission #2 → ✅ SUCCESS

Result:
- Admission #1: status = "discharged" (unchanged, patient left alive)
- Admission #2: status = "deceased" (death confirmed)
- Patient record: isDeceased() = true (has deceased admission)
```

### 24. Delete Admission

**Endpoint:** ~~`DELETE /api/admissions/{id}`~~ **DISABLED**

**Authorization:** `root_user` only

**Status:** ❌ **This endpoint is currently disabled**

**Reason:** Admission records must be permanently retained for:

-   Medical and legal compliance
-   Historical record integrity
-   Audit trail requirements
-   Treatment record preservation
-   Death record protection (deceased admissions cannot be deleted)

**Note:** If you need to remove an admission from active use, consider marking it with appropriate status (discharged, transferred) rather than deleting the record.

### 25. Admission Statistics

**Endpoint:** `GET /api/admissions/statistics`

**Authorization:** `root_user` or `admission`

```json
// Response
{
    "data": {
        "total_admissions": 150,
        "currently_admitted": 23,
        "currently_admitted_inpatient": 18,
        "currently_admitted_outpatient": 5,
        "discharged_this_month": 45,
        "admissions_this_month": 52,
        "by_status": {
            "admitted": 23,
            "discharged": 120,
            "deceased": 5,
            "transferred": 2
        },
        "by_type": {
            "inpatient": 130,
            "outpatient": 20
        }
    }
}
```

---

## Treatment Records Endpoints

Treatment records are linked to **specific admissions**.

### 26. List Treatment Records

**Endpoint:** `GET /api/admissions/{admissionId}/treatments`

**Authorization:** Role-based (must have access to the admission)

| Role        | Access                                                 |
| ----------- | ------------------------------------------------------ |
| `root_user` | ✅ All treatment records                               |
| `admission` | ✅ All treatment records                               |
| `doctor`    | ✅ Only treatment records of their assigned admissions |
| `nurse`     | ✅ Only treatment records of their assigned admissions |

**Purpose:** Retrieve all treatment records for a specific admission. Records are ordered by treatment date (newest first), then by creation date.

#### Success Response (200 OK)

```json
{
    "message": "Treatment records retrieved successfully",
    "admission_id": 5,
    "admission_number": "ADM-2024-000005",
    "patient_id": 1,
    "patient_name": "John Patient",
    "total": 3,
    "data": [
        {
            "id": 14,
            "admission_id": 5,
            "patient_id": 1,
            "treatment_type": "diagnostic",
            "treatment_name": "Complete Blood Count (CBC)",
            "description": "Routine blood test to check blood cell counts",
            "notes": "Patient was fasting for 12 hours before test",
            "medications": null,
            "dosage": null,
            "treatment_date": "2024-12-05",
            "treatment_time": "09:30:00",
            "results": "Hemoglobin: 14.2 g/dL (normal). White blood cell count: 7,500/μL (normal). Platelet count: 250,000/μL (normal).",
            "findings": "All blood parameters within normal range. No signs of infection or anemia.",
            "outcome": "completed",
            "pre_procedure_notes": null,
            "post_procedure_notes": null,
            "complications": null,
            "doctor_id": 2,
            "nurse_id": 3,
            "created_at": "2024-12-05T09:30:00.000000Z",
            "updated_at": "2024-12-05T09:30:00.000000Z",
            "doctor": {
                "id": 2,
                "name": "Dr. Smith",
                "email": "dr.smith@hospital.com"
            },
            "nurse": {
                "id": 3,
                "name": "Nurse Jane",
                "email": "nurse.jane@hospital.com"
            }
        },
        {
            "id": 13,
            "admission_id": 5,
            "patient_id": 1,
            "treatment_type": "medication",
            "treatment_name": "Antibiotic Therapy",
            "description": "Intravenous antibiotic administration for suspected infection",
            "notes": "Patient allergic to penicillin - using alternative antibiotic (Ceftriaxone)",
            "medications": "Ceftriaxone 1g, Metronidazole 500mg",
            "dosage": "Ceftriaxone: 1g IV every 24 hours. Metronidazole: 500mg IV every 8 hours. Course duration: 7 days",
            "treatment_date": "2024-12-03",
            "treatment_time": "10:00:00",
            "results": "Patient responding well to antibiotic therapy. White blood cell count decreasing. No signs of allergic reaction.",
            "findings": "Initial WBC count was elevated (15,000/μL), indicating possible infection. After 48 hours of treatment, WBC count normalized to 8,000/μL.",
            "outcome": "ongoing",
            "pre_procedure_notes": null,
            "post_procedure_notes": null,
            "complications": null,
            "doctor_id": 2,
            "nurse_id": 3,
            "created_at": "2024-12-03T10:00:00.000000Z",
            "updated_at": "2024-12-05T10:00:00.000000Z",
            "doctor": {
                "id": 2,
                "name": "Dr. Smith",
                "email": "dr.smith@hospital.com"
            },
            "nurse": {
                "id": 3,
                "name": "Nurse Jane",
                "email": "nurse.jane@hospital.com"
            }
        },
        {
            "id": 8,
            "admission_id": 5,
            "patient_id": 1,
            "treatment_type": "surgery",
            "treatment_name": "Appendectomy",
            "description": "Laparoscopic removal of inflamed appendix",
            "notes": "Emergency surgery due to acute appendicitis. Patient stable throughout procedure.",
            "medications": "General anesthesia: Propofol, Fentanyl. Post-op: Morphine for pain management, Cefazolin for infection prevention",
            "dosage": "Propofol: 2mg/kg IV. Fentanyl: 50mcg IV. Morphine: 5mg every 4 hours as needed. Cefazolin: 1g IV every 8 hours for 24 hours",
            "treatment_date": "2024-12-04",
            "treatment_time": "14:00:00",
            "results": "Surgery completed successfully. Appendix removed without perforation. No complications during procedure.",
            "findings": "Acute appendicitis confirmed. No peritonitis or abscess formation. Appendix was inflamed but intact.",
            "outcome": "successful",
            "pre_procedure_notes": "Patient fasted for 12 hours. Pre-operative assessment completed. Vital signs stable: BP 120/80, HR 75, O2 Sat 98%. Consent obtained.",
            "post_procedure_notes": "Patient recovered from anesthesia without complications. Vital signs stable. Pain well controlled with morphine. Incision site clean, no signs of infection.",
            "complications": null,
            "doctor_id": 2,
            "nurse_id": 3,
            "created_at": "2024-12-04T14:00:00.000000Z",
            "updated_at": "2024-12-04T16:30:00.000000Z",
            "doctor": {
                "id": 2,
                "name": "Dr. Smith",
                "email": "dr.smith@hospital.com"
            },
            "nurse": {
                "id": 3,
                "name": "Nurse Jane",
                "email": "nurse.jane@hospital.com"
            }
        }
    ]
}
```

#### Response Structure

| Field              | Type    | Description                                       |
| ------------------ | ------- | ------------------------------------------------- |
| `message`          | string  | Success message                                   |
| `admission_id`     | integer | ID of the admission                               |
| `admission_number` | string  | Unique admission number (e.g., "ADM-2024-000005") |
| `patient_id`       | integer | ID of the patient                                 |
| `patient_name`     | string  | Name of the patient                               |
| `total`            | integer | Total number of treatment records                 |
| `data`             | array   | Array of treatment record objects                 |

#### Treatment Record Object Structure

Each object in the `data` array contains:

| Field                  | Type     | Description                                  | Notes                                  |
| ---------------------- | -------- | -------------------------------------------- | -------------------------------------- |
| `id`                   | integer  | Treatment record ID                          |                                        |
| `admission_id`         | integer  | Admission this treatment belongs to          |                                        |
| `patient_id`           | integer  | Patient ID                                   | Denormalized for easier queries        |
| `treatment_type`       | string   | Type of treatment                            | See treatment types below              |
| `treatment_name`       | string   | Specific name of treatment                   | Can be null                            |
| `description`          | string   | Detailed description                         | Can be null                            |
| `notes`                | string   | General notes                                | Can be null                            |
| `medications`          | string   | Medications administered                     | Can be null                            |
| `dosage`               | string   | Dosage information                           | Can be null                            |
| `treatment_date`       | date     | Date when treatment was performed            | Format: `YYYY-MM-DD`                   |
| `treatment_time`       | time     | Time when treatment was performed            | Format: `HH:mm:ss`                     |
| `results`              | string   | Results of the treatment                     | Can be null                            |
| `findings`             | string   | Medical findings                             | Can be null                            |
| `outcome`              | string   | Treatment outcome                            | See outcomes below                     |
| `pre_procedure_notes`  | string   | Notes before procedure                       | Can be null (for surgeries/procedures) |
| `post_procedure_notes` | string   | Notes after procedure                        | Can be null (for surgeries/procedures) |
| `complications`        | string   | Any complications that occurred              | Can be null                            |
| `doctor_id`            | integer  | ID of doctor who performed/oversaw treatment | Can be null                            |
| `nurse_id`             | integer  | ID of nurse who assisted                     | Can be null                            |
| `created_at`           | datetime | When the record was created                  | ISO 8601 format                        |
| `updated_at`           | datetime | When the record was last updated             | ISO 8601 format                        |
| `doctor`               | object   | Doctor details (nested relationship)         | `{ id, name, email }`                  |
| `nurse`                | object   | Nurse details (nested relationship)          | `{ id, name, email }`                  |

#### Treatment Types

Valid `treatment_type` values:

-   `surgery`
-   `radiotherapy`
-   `chemotherapy`
-   `targeted_therapy`
-   `hormone_therapy`
-   `immunotherapy`
-   `intervention_therapy`
-   `medication`
-   `physical_therapy`
-   `supportive_care`
-   `diagnostic`
-   `consultation`
-   `procedure`
-   `other`

#### Treatment Outcomes

Valid `outcome` values:

-   `pending` - Treatment not yet completed
-   `successful` - Treatment completed successfully
-   `partial` - Partial success
-   `unsuccessful` - Treatment did not achieve desired result
-   `ongoing` - Treatment still in progress
-   `completed` - Treatment finished (neutral outcome)

#### Ordering

Treatment records are ordered by:

1. **Treatment date** (descending - newest first)
2. **Creation date** (descending - newest first)

This means the most recent treatments appear first in the list.

#### Example: Empty List Response

If an admission has no treatment records:

```json
{
    "message": "Treatment records retrieved successfully",
    "admission_id": 5,
    "admission_number": "ADM-2024-000005",
    "patient_id": 1,
    "patient_name": "John Patient",
    "total": 0,
    "data": []
}
```

#### Error Responses

**403 Forbidden - User not assigned to admission:**

```json
{
    "message": "Unauthorized. You do not have access to this admission's treatment records."
}
```

**404 Not Found - Admission:**

```json
{
    "message": "Admission not found."
}
```

### 27. Create Treatment Record

**Endpoint:** `POST /api/admissions/{admissionId}/treatments`

**Authorization:** `root_user` or `doctor` (assigned only)

**Purpose:** Create a new treatment record for an **active admission** (status: `admitted`). Treatment records document medical procedures, medications, and care provided during a patient's hospital stay.

#### ⚠️ CRITICAL RESTRICTIONS

**What CAN receive treatment records:**

-   ✅ **Active admissions only** (status: `admitted`)
-   ✅ Both inpatient and outpatient active admissions
-   ✅ Patient must be currently in the hospital

**What CANNOT receive treatment records:**

-   ❌ **Discharged admissions** - Returns error: "Cannot add treatment records to a closed admission. Status: discharged"
-   ❌ **Deceased admissions** - Returns error: "Cannot add treatment records to a closed admission. Status: deceased"
-   ❌ **Transferred admissions** - Returns error: "Cannot add treatment records to a closed admission. Status: transferred"

#### Why Closed Admissions Cannot Receive Treatment Records

**Business Logic:**

-   Treatment records document care provided **during** an active hospital stay
-   Once an admission is closed (discharged/deceased/transferred), no new treatments can be added
-   Historical treatment records should remain unchanged for data integrity
-   Adding treatments to closed admissions would create inaccurate medical timelines

**Data Integrity:**

-   Treatment records are linked to specific admission periods
-   Closed admissions represent completed medical episodes
-   Retroactively adding treatments would distort medical history
-   Treatment records must reflect actual time of care delivery

**Example Scenarios:**

**Valid Scenario:**

```
1. Patient admitted (status: admitted)
2. Doctor provides treatment during stay
3. Create treatment record → ✅ ALLOWED
4. Treatment recorded in system
```

**Invalid Scenario (Discharged):**

```
1. Patient admitted (status: admitted)
2. Patient discharged (status: discharged)
3. Doctor tries to add treatment record → ❌ BLOCKED
4. Error: "Cannot add treatment records to a closed admission. Status: discharged"
```

**Invalid Scenario (Deceased):**

```
1. Patient admitted (status: admitted)
2. Patient dies (status: deceased)
3. Doctor tries to add treatment record → ❌ BLOCKED
4. Error: "Cannot add treatment records to a closed admission. Status: deceased"
```

**Important Notes:**

-   Treatment records can be **viewed** for closed admissions (historical records)
-   Treatment records can be **updated** for closed admissions (corrections/clarifications)
-   Treatment records **cannot be created** for closed admissions (only active admissions)

#### Request Parameters

| Field                  | Type   | Required | Constraints            | Accepted Values           | Description                                           |
| ---------------------- | ------ | -------- | ---------------------- | ------------------------- | ----------------------------------------------------- |
| `treatment_type`       | string | ✅ Yes   | -                      | See treatment types below | Type of treatment performed                           |
| `treatment_name`       | string | No       | max:255                | Any text                  | Specific name of treatment                            |
| `description`          | string | No       | max:1000               | Any text                  | Detailed description                                  |
| `notes`                | string | No       | max:1000               | Any text                  | General notes                                         |
| `medications`          | string | No       | max:500                | Any text                  | Medications administered                              |
| `dosage`               | string | No       | max:255                | Any text                  | Dosage information                                    |
| `treatment_date`       | date   | No       | -                      | YYYY-MM-DD                | Date of treatment (auto-set to today if not provided) |
| `treatment_time`       | time   | No       | -                      | HH:MM                     | Time of treatment                                     |
| `results`              | string | No       | max:1000               | Any text                  | Treatment results                                     |
| `findings`             | string | No       | max:1000               | Any text                  | Medical findings                                      |
| `outcome`              | string | No       | -                      | See outcomes below        | Treatment outcome                                     |
| `pre_procedure_notes`  | string | No       | max:1000               | Any text                  | Notes before procedure                                |
| `post_procedure_notes` | string | No       | max:1000               | Any text                  | Notes after procedure                                 |
| `complications`        | string | No       | max:500                | Any text                  | Any complications                                     |
| `attachments`          | file[] | No       | max:10 files, 5MB each | PDF only                  | Medical document attachments                          |

**File Upload Notes:**

-   Maximum 10 PDF files per treatment record
-   Each file limited to 5MB
-   Files are stored securely and accessible via `attachment_urls` in responses
-   Files can be removed individually using DELETE endpoint

```json
// Request Example (FormData for file uploads)
{
    "treatment_type": "diagnostic",
    "treatment_name": "MRI Brain Scan",
    "description": "Magnetic resonance imaging of brain",
    "notes": "Patient reported headaches",
    "treatment_date": "2024-12-03",
    "outcome": "completed"
    // attachments: [file1.pdf, file2.pdf, ...] (uploaded via FormData)
}
```

**Treatment Types:**

-   `surgery`, `radiotherapy`, `chemotherapy`, `targeted_therapy`
-   `hormone_therapy`, `immunotherapy`, `intervention_therapy`
-   `medication`, `physical_therapy`, `supportive_care`
-   `diagnostic`, `consultation`, `procedure`, `other`

**Outcomes:**

-   `pending`, `successful`, `partial`, `unsuccessful`, `ongoing`, `completed`

#### Success Response (201 Created)

```json
{
    "message": "Treatment record created successfully",
    "data": {
        "id": 1,
        "admission_id": 5,
        "patient_id": 1,
        "treatment_type": "diagnostic",
        "treatment_name": "MRI Brain Scan",
        "treatment_date": "2024-12-03",
        "outcome": "completed",
        "attachments": [
            {
                "filename": "mri_report.pdf",
                "path": "treatment-attachments/1733256789_abc123_mri_report.pdf",
                "size": 2457600,
                "uploaded_at": "2024-12-03T14:30:00.000000Z"
            }
        ],
        "attachment_urls": [
            {
                "filename": "mri_report.pdf",
                "path": "treatment-attachments/1733256789_abc123_mri_report.pdf",
                "url": "http://localhost:8000/storage/treatment-attachments/1733256789_abc123_mri_report.pdf",
                "size": 2457600,
                "uploaded_at": "2024-12-03T14:30:00.000000Z"
            }
        ],
        "doctor": {
            "id": 2,
            "name": "Dr. Smith",
            "email": "dr.smith@hospital.com"
        },
        "admission": {
            "id": 5,
            "admission_number": "ADM-2024-000005"
        },
        "patient": {
            "id": 1,
            "name": "John Doe"
        }
    }
}
```

#### Error Responses

**400 Bad Request** - Trying to add treatment to discharged admission

```json
{
    "message": "Cannot add treatment records to a closed admission. Status: discharged"
}
```

**400 Bad Request** - Trying to add treatment to deceased admission

```json
{
    "message": "Cannot add treatment records to a closed admission. Status: deceased"
}
```

**400 Bad Request** - Trying to add treatment to transferred admission

```json
{
    "message": "Cannot add treatment records to a closed admission. Status: transferred"
}
```

**403 Forbidden** - Doctor not assigned to admission

```json
{
    "message": "Unauthorized. You can only add treatment records for admissions assigned to you."
}
```

**404 Not Found** - Admission not found

```json
{
    "message": "Admission not found."
}
```

**422 Unprocessable Entity** - Validation errors

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "treatment_type": ["The treatment type field is required."],
        "treatment_name": ["The treatment name field is required."]
    }
}
```

### 28. Get Treatment Record

**Endpoint:** `GET /api/admissions/{admissionId}/treatments/{recordId}`

**Authorization:** Role-based access

| Role        | Access                                                 |
| ----------- | ------------------------------------------------------ |
| `root_user` | ✅ All treatment records                               |
| `admission` | ✅ All treatment records                               |
| `doctor`    | ✅ Only treatment records of their assigned admissions |
| `nurse`     | ✅ Only treatment records of their assigned admissions |

**Purpose:** Retrieve detailed information about a specific treatment record, including all treatment details, results, and assigned staff.

#### Success Response (200 OK)

```json
{
    "message": "Treatment record retrieved successfully",
    "data": {
        "id": 12,
        "admission_id": 5,
        "patient_id": 1,
        "treatment_type": "diagnostic",
        "treatment_name": "Complete Blood Count (CBC)",
        "description": "Routine blood test to check blood cell counts, hemoglobin levels, and detect any abnormalities",
        "notes": "Patient was fasting for 12 hours before test. No complications during blood draw.",
        "medications": null,
        "dosage": null,
        "treatment_date": "2024-12-05",
        "treatment_time": "09:30:00",
        "results": "Hemoglobin: 14.2 g/dL (normal range: 12-16 g/dL). White blood cell count: 7,500/μL (normal range: 4,000-11,000/μL). Platelet count: 250,000/μL (normal range: 150,000-450,000/μL). Red blood cell count: 4.8 million/μL (normal range: 4.5-5.5 million/μL).",
        "findings": "All blood parameters within normal range. No signs of infection, anemia, or bleeding disorders. Patient's blood counts are healthy.",
        "outcome": "completed",
        "pre_procedure_notes": "Patient informed about the procedure. Consent obtained. No known allergies to blood draw procedures.",
        "post_procedure_notes": "Blood sample collected successfully. No bruising or bleeding at puncture site. Patient tolerated procedure well.",
        "complications": null,
        "doctor_id": 2,
        "nurse_id": 3,
        "created_at": "2024-12-05T09:30:00.000000Z",
        "updated_at": "2024-12-05T10:15:00.000000Z",
        "doctor": {
            "id": 2,
            "name": "Dr. Smith",
            "email": "dr.smith@hospital.com"
        },
        "nurse": {
            "id": 3,
            "name": "Nurse Jane",
            "email": "nurse.jane@hospital.com"
        }
    }
}
```

#### Response Fields Explanation

| Field                  | Type     | Description                                       | Example Value                                       |
| ---------------------- | -------- | ------------------------------------------------- | --------------------------------------------------- |
| `id`                   | integer  | Treatment record ID                               | `12`                                                |
| `admission_id`         | integer  | Admission this treatment belongs to               | `5`                                                 |
| `patient_id`           | integer  | Patient ID (denormalized for easier queries)      | `1`                                                 |
| `treatment_type`       | string   | Type of treatment                                 | `"diagnostic"`, `"medication"`, `"surgery"`, etc.   |
| `treatment_name`       | string   | Specific name of treatment                        | `"Complete Blood Count (CBC)"`                      |
| `description`          | string   | Detailed description of treatment                 | `"Routine blood test..."`                           |
| `notes`                | string   | General notes about the treatment                 | `"Patient was fasting..."`                          |
| `medications`          | string   | Medications administered (if applicable)          | `"Aspirin 100mg, Atorvastatin 20mg"`                |
| `dosage`               | string   | Dosage information (if applicable)                | `"Aspirin: 100mg once daily"`                       |
| `treatment_date`       | date     | Date when treatment was performed                 | `"2024-12-05"`                                      |
| `treatment_time`       | time     | Time when treatment was performed                 | `"09:30:00"`                                        |
| `results`              | string   | Results of the treatment                          | `"Hemoglobin: 14.2 g/dL..."`                        |
| `findings`             | string   | Medical findings from treatment                   | `"All blood parameters within normal range..."`     |
| `outcome`              | string   | Treatment outcome status                          | `"completed"`, `"successful"`, `"pending"`, etc.    |
| `pre_procedure_notes`  | string   | Notes before procedure (for surgeries/procedures) | `"Patient informed about the procedure..."`         |
| `post_procedure_notes` | string   | Notes after procedure (for surgeries/procedures)  | `"Blood sample collected successfully..."`          |
| `complications`        | string   | Any complications that occurred                   | `null` or `"Minor bleeding at injection site"`      |
| `doctor_id`            | integer  | ID of doctor who performed/oversaw treatment      | `2`                                                 |
| `nurse_id`             | integer  | ID of nurse who assisted                          | `3`                                                 |
| `created_at`           | datetime | When the record was created                       | `"2024-12-05T09:30:00.000000Z"`                     |
| `updated_at`           | datetime | When the record was last updated                  | `"2024-12-05T10:15:00.000000Z"`                     |
| `doctor`               | object   | Doctor details (nested relationship)              | `{ "id": 2, "name": "Dr. Smith", "email": "..." }`  |
| `nurse`                | object   | Nurse details (nested relationship)               | `{ "id": 3, "name": "Nurse Jane", "email": "..." }` |

#### Example: Surgical Procedure Treatment Record

```json
{
    "message": "Treatment record retrieved successfully",
    "data": {
        "id": 8,
        "admission_id": 5,
        "patient_id": 1,
        "treatment_type": "surgery",
        "treatment_name": "Appendectomy",
        "description": "Laparoscopic removal of inflamed appendix",
        "notes": "Emergency surgery due to acute appendicitis. Patient stable throughout procedure.",
        "medications": "General anesthesia: Propofol, Fentanyl. Post-op: Morphine for pain management, Cefazolin for infection prevention",
        "dosage": "Propofol: 2mg/kg IV. Fentanyl: 50mcg IV. Morphine: 5mg every 4 hours as needed. Cefazolin: 1g IV every 8 hours for 24 hours",
        "treatment_date": "2024-12-04",
        "treatment_time": "14:00:00",
        "results": "Surgery completed successfully. Appendix removed without perforation. No complications during procedure. Patient transferred to recovery room in stable condition.",
        "findings": "Acute appendicitis confirmed. No peritonitis or abscess formation. Appendix was inflamed but intact. No signs of rupture.",
        "outcome": "successful",
        "pre_procedure_notes": "Patient fasted for 12 hours. Pre-operative assessment completed. Vital signs stable: BP 120/80, HR 75, O2 Sat 98%. Consent obtained. Surgical team briefed. Operating room prepared.",
        "post_procedure_notes": "Patient recovered from anesthesia without complications. Vital signs stable. Pain well controlled with morphine. Incision site clean, no signs of infection. Patient alert and oriented. Discharge planning initiated.",
        "complications": null,
        "doctor_id": 2,
        "nurse_id": 3,
        "created_at": "2024-12-04T14:00:00.000000Z",
        "updated_at": "2024-12-04T16:30:00.000000Z",
        "doctor": {
            "id": 2,
            "name": "Dr. Smith",
            "email": "dr.smith@hospital.com"
        },
        "nurse": {
            "id": 3,
            "name": "Nurse Jane",
            "email": "nurse.jane@hospital.com"
        }
    }
}
```

#### Example: Medication Treatment Record

```json
{
    "message": "Treatment record retrieved successfully",
    "data": {
        "id": 15,
        "admission_id": 5,
        "patient_id": 1,
        "treatment_type": "medication",
        "treatment_name": "Antibiotic Therapy",
        "description": "Intravenous antibiotic administration for suspected infection",
        "notes": "Patient allergic to penicillin - using alternative antibiotic (Ceftriaxone)",
        "medications": "Ceftriaxone 1g, Metronidazole 500mg",
        "dosage": "Ceftriaxone: 1g IV every 24 hours. Metronidazole: 500mg IV every 8 hours. Course duration: 7 days",
        "treatment_date": "2024-12-03",
        "treatment_time": "10:00:00",
        "results": "Patient responding well to antibiotic therapy. White blood cell count decreasing. No signs of allergic reaction.",
        "findings": "Initial WBC count was elevated (15,000/μL), indicating possible infection. After 48 hours of treatment, WBC count normalized to 8,000/μL. Patient's condition improving.",
        "outcome": "ongoing",
        "pre_procedure_notes": null,
        "post_procedure_notes": null,
        "complications": null,
        "doctor_id": 2,
        "nurse_id": 3,
        "created_at": "2024-12-03T10:00:00.000000Z",
        "updated_at": "2024-12-05T10:00:00.000000Z",
        "doctor": {
            "id": 2,
            "name": "Dr. Smith",
            "email": "dr.smith@hospital.com"
        },
        "nurse": {
            "id": 3,
            "name": "Nurse Jane",
            "email": "nurse.jane@hospital.com"
        }
    }
}
```

#### Error Responses

**403 Forbidden - User not assigned to admission:**

```json
{
    "message": "Unauthorized. You do not have access to this admission's treatment records."
}
```

**404 Not Found - Admission:**

```json
{
    "message": "Admission not found."
}
```

**404 Not Found - Treatment Record:**

```json
{
    "message": "Treatment record not found."
}
```

### 29. Update Treatment Record

**Endpoint:** `PUT /api/admissions/{admissionId}/treatments/{recordId}` or `PATCH /api/admissions/{admissionId}/treatments/{recordId}`

**Authorization:** `root_user` or `doctor` (assigned only)

| Role        | Access                                                 |
| ----------- | ------------------------------------------------------ |
| `root_user` | ✅ Can update any treatment record                     |
| `admission` | ❌ Cannot update treatment records                     |
| `doctor`    | ✅ Only treatment records of their assigned admissions |
| `nurse`     | ❌ Cannot update treatment records (read-only access)  |

**Purpose:** Update an existing treatment record with new information, results, or outcomes. Commonly used to record treatment results, update findings, or change outcome status.

#### Request Parameters (All Optional)

| Field                  | Type    | Description                            | Accepted Values                                                                                                                                                                                                                    |
| ---------------------- | ------- | -------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `treatment_type`       | string  | Type of treatment                      | `surgery`, `radiotherapy`, `chemotherapy`, `targeted_therapy`, `hormone_therapy`, `immunotherapy`, `intervention_therapy`, `medication`, `physical_therapy`, `supportive_care`, `diagnostic`, `consultation`, `procedure`, `other` |
| `treatment_name`       | string  | Specific name of treatment             | Max 255 characters                                                                                                                                                                                                                 |
| `description`          | string  | Detailed description                   | Max 1000 characters                                                                                                                                                                                                                |
| `notes`                | string  | General notes                          | Max 1000 characters                                                                                                                                                                                                                |
| `medications`          | string  | Medications administered               | Max 500 characters                                                                                                                                                                                                                 |
| `dosage`               | string  | Dosage information                     | Max 255 characters                                                                                                                                                                                                                 |
| `treatment_date`       | date    | Date of treatment                      | Format: `YYYY-MM-DD`                                                                                                                                                                                                               |
| `treatment_time`       | time    | Time of treatment                      | Format: `HH:mm` (24-hour)                                                                                                                                                                                                          |
| `results`              | string  | Treatment results                      | Max 1000 characters                                                                                                                                                                                                                |
| `findings`             | string  | Medical findings                       | Max 1000 characters                                                                                                                                                                                                                |
| `outcome`              | string  | Treatment outcome                      | `pending`, `successful`, `partial`, `unsuccessful`, `ongoing`, `completed`                                                                                                                                                         |
| `pre_procedure_notes`  | string  | Notes before procedure                 | Max 1000 characters                                                                                                                                                                                                                |
| `post_procedure_notes` | string  | Notes after procedure                  | Max 1000 characters                                                                                                                                                                                                                |
| `complications`        | string  | Any complications that occurred        | Max 500 characters                                                                                                                                                                                                                 |
| `doctor_id`            | integer | Doctor who performed/oversaw treatment | Must exist in users table                                                                                                                                                                                                          |
| `nurse_id`             | integer | Nurse who assisted                     | Must exist in users table                                                                                                                                                                                                          |

#### Example Requests

**Update treatment results and outcome:**

```bash
PATCH /api/admissions/5/treatments/12
Authorization: Bearer {doctor_token}
Content-Type: application/json

{
    "results": "Blood test shows normal hemoglobin levels (14.2 g/dL). White blood cell count within normal range.",
    "findings": "No abnormalities detected. Patient responding well to treatment.",
    "outcome": "successful"
}
```

**Update medication dosage:**

```bash
PATCH /api/admissions/5/treatments/12
Authorization: Bearer {doctor_token}
Content-Type: application/json

{
    "medications": "Amoxicillin 500mg, Paracetamol 500mg",
    "dosage": "Amoxicillin: 3 times daily for 7 days. Paracetamol: as needed for pain.",
    "notes": "Patient allergic to penicillin - switched to alternative antibiotic"
}
```

**Record surgical procedure results:**

```bash
PUT /api/admissions/5/treatments/8
Authorization: Bearer {doctor_token}
Content-Type: application/json

{
    "treatment_type": "surgery",
    "treatment_name": "Appendectomy",
    "pre_procedure_notes": "Patient fasted for 12 hours. Vital signs stable. Consent obtained.",
    "post_procedure_notes": "Surgery completed successfully. No complications during procedure. Patient transferred to recovery.",
    "results": "Appendix removed successfully. No perforation observed.",
    "findings": "Acute appendicitis confirmed. No peritonitis.",
    "outcome": "successful",
    "complications": "None"
}
```

**Update diagnostic test findings:**

```bash
PATCH /api/admissions/5/treatments/15
Authorization: Bearer {doctor_token}
Content-Type: application/json

{
    "treatment_type": "diagnostic",
    "treatment_name": "Chest X-Ray",
    "findings": "Clear lung fields. No infiltrates or masses. Heart size normal. No pleural effusion.",
    "results": "Normal chest X-ray. No evidence of pneumonia or other abnormalities.",
    "outcome": "completed"
}
```

#### Success Response (200 OK)

```json
{
    "message": "Treatment record updated successfully",
    "data": {
        "id": 12,
        "admission_id": 5,
        "patient_id": 1,
        "treatment_type": "diagnostic",
        "treatment_name": "Complete Blood Count",
        "description": "Routine blood test",
        "notes": "Patient was fasting",
        "medications": null,
        "dosage": null,
        "treatment_date": "2024-12-05",
        "treatment_time": "09:30",
        "results": "Blood test shows normal hemoglobin levels (14.2 g/dL). White blood cell count within normal range.",
        "findings": "No abnormalities detected. Patient responding well to treatment.",
        "outcome": "successful",
        "pre_procedure_notes": null,
        "post_procedure_notes": null,
        "complications": null,
        "doctor_id": 2,
        "nurse_id": 3,
        "created_at": "2024-12-05T09:30:00.000000Z",
        "updated_at": "2024-12-05T14:45:00.000000Z",
        "doctor": {
            "id": 2,
            "name": "Dr. Smith",
            "email": "dr.smith@hospital.com"
        },
        "nurse": {
            "id": 3,
            "name": "Nurse Jane",
            "email": "nurse.jane@hospital.com"
        }
    }
}
```

#### Error Responses

**401 Unauthorized (Not logged in):**

```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden (Nurse trying to update):**

```json
{
    "message": "Unauthorized. Only doctors can update treatment records."
}
```

**403 Forbidden (Doctor not assigned to admission):**

```json
{
    "message": "Unauthorized. You can only update treatment records for admissions assigned to you."
}
```

**404 Not Found (Admission):**

```json
{
    "message": "Admission not found."
}
```

**404 Not Found (Treatment Record):**

```json
{
    "message": "Treatment record not found."
}
```

**422 Validation Error:**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "treatment_type": ["Invalid treatment type."],
        "outcome": ["Invalid outcome value."]
    }
}
```

#### Common Use Cases

| Scenario                        | Fields to Update                                                          |
| ------------------------------- | ------------------------------------------------------------------------- |
| Record lab test results         | `results`, `findings`, `outcome`                                          |
| Update medication regimen       | `medications`, `dosage`, `notes`                                          |
| Document surgical outcome       | `pre_procedure_notes`, `post_procedure_notes`, `outcome`, `complications` |
| Change treatment status         | `outcome` (e.g., `pending` → `successful`)                                |
| Add post-treatment observations | `post_procedure_notes`, `findings`                                        |
| Record complications            | `complications`, `notes`                                                  |

### 30. Remove Treatment Record Attachment

**Endpoint:** `DELETE /api/admissions/{admissionId}/treatments/{recordId}/attachments/{filename}`

**Authorization:** `root_user` or `doctor` (assigned only)

**Description:** Remove a specific PDF attachment from a treatment record. The file is permanently deleted from storage.

**Path Parameters:**

-   `admissionId` (integer): Admission ID
-   `recordId` (integer): Treatment record ID
-   `filename` (string): Exact filename of the attachment to remove

#### Success Response (200 OK)

```json
{
    "message": "Attachment removed successfully",
    "data": {
        "id": 1,
        "treatment_type": "diagnostic",
        "treatment_name": "MRI Brain Scan",
        "attachments": [],
        "attachment_urls": []
    }
}
```

#### Error Responses

**403 Forbidden** - Unauthorized user

```json
{
    "message": "Unauthorized. Only doctors can remove attachments."
}
```

**404 Not Found** - Attachment not found

```json
{
    "message": "Attachment not found."
}
```

### 31. Delete Treatment Record

**Endpoint:** ~~`DELETE /api/admissions/{admissionId}/treatments/{recordId}`~~ **DISABLED**

**Status:** This endpoint has been disabled for data integrity and medical compliance.

**Reason:** Medical treatment records must be retained for legal and medical compliance. Treatment records are part of the patient's permanent medical history and cannot be deleted. If a record needs correction, use the **Update Treatment Record** endpoint instead.

**Alternative:** Use `PATCH /api/admissions/{admissionId}/treatments/{recordId}` to update or correct treatment records.

---

## Helper Endpoints

### 31. Get Doctors List

**Endpoint:** `GET /api/staff/doctors`

**Authorization:** `root_user` or `admission`

### 32. Get Nurses List

**Endpoint:** `GET /api/staff/nurses`

**Authorization:** `root_user` or `admission`

### 33. Get Treatment Types

**Endpoint:** `GET /api/treatment-options/types`

**Authorization:** All authenticated users

### 34. Get Treatment Outcomes

**Endpoint:** `GET /api/treatment-options/outcomes`

**Authorization:** All authenticated users

### 35. Get Myanmar Addresses

**Endpoint:** `GET /api/addresses/myanmar`

**Authorization:** All authenticated users

**Description:** Returns a structured list of all Myanmar regions, districts, and townships for address selection in patient and admission forms.

**Response Structure:**
The response follows a three-level hierarchy: **Region → District → Township**

**Detailed Response Example:**

```json
{
    "message": "Myanmar addresses retrieved successfully",
    "data": {
        "Magway Region": {
            "Gangaw": ["Gangaw", "Kyaukhtu", "Saw", "Tilin"],
            "Magway": [
                "Chauck",
                "Magway",
                "Myothit",
                "Natmauk",
                "Taungdwingyi",
                "Yenangyaung"
            ],
            "Minbu": ["Minbu", "Ngape", "Pwintbyu", "Salin", "Sidoktaya"],
            "Pakokku": ["Myaing", "Pakokku", "Pauk", "Seikphyu", "Yesagyo"],
            "Thayet": [
                "Aunglan",
                "Kamma",
                "Mindon",
                "Minhla",
                "Sinbaungwe",
                "Thayet"
            ]
        },
        "Mandalay Region": {
            "Kyaukse": ["Kyaukse", "Myittha", "Sintgaing", "Tada-U"],
            "Mandalay": [
                "Amarapura",
                "Aungmyethazan",
                "Chanayethazan",
                "Chanmyathazi",
                "Mahaaungmye",
                "Patheingyi",
                "Pyigyidagun"
            ],
            "Meiktila": ["Mahlaing", "Meiktila", "Thazi", "Wundwin"],
            "Myingyan": ["Myingyan", "Natogyi", "Nganzun", "Thaungtha"],
            "Nyaung-U": ["Kyaukpadaung", "Ngathayauk", "Nyaung-U"],
            "Pyinoolwin": [
                "Madaya",
                "Mogok",
                "Pyinoolwin",
                "Singu",
                "Tagaung",
                "Thabeikkyin"
            ],
            "Yamethin": ["Pyawbwe", "Yamethin"]
        },
        "Naypyidaw Union Territory": {
            "Dekkhina(South Naypyidaw)": [
                "Dekkhinathiri",
                "Lewe",
                "Pyinmana",
                "Zabuthiri"
            ],
            "Ottara(North Naypyidaw)": [
                "Ottarathiri",
                "Pobbathiri",
                "Tatkon",
                "Zeyarthiri"
            ]
        },
        "Kayah State": {
            "Bawlakhe": ["Bawlakhe", "Hpasawng", "Mese", "Ywathit"],
            "Loikaw": ["Demoso", "Hpruso", "Loikaw", "Shadaw"]
        },
        "Shan State": {
            "Kengtung": [
                "Kengtung",
                "Mine Pauk",
                "Minelar",
                "Mong Khet",
                "Mong La",
                "Mong Yang"
            ],
            "Mong Hpayak": ["Mineyu", "Mong Hpayak", "Mong Yawng"],
            "Mong Hsat": [
                "Minekoke",
                "Monehta",
                "Mong Hsat",
                "Mong Ping",
                "Mong Tong",
                "Ponparkyin",
                "Tontar"
            ],
            "Tachileik": ["Kyaing Lap", "Tachileik", "Talay"],
            "Kunlong": ["Kunlong"],
            "Kyaukme": [
                "Hsipaw",
                "Kyaukme",
                "Mantong",
                "Minelon",
                "Minengaw",
                "Namhsan",
                "Namtu",
                "Nawnghkio"
            ],
            "Lashio": ["Hsenwi", "Lashio", "Mongyai", "Tangyan"],
            "Laukkaing": ["Chinshwehaw", "Konkyan", "Laukkaing", "Mawhtike"],
            "Mu Se": [
                "Kutkai",
                "Manhero",
                "Monekoe",
                "Mu Se",
                "Namhkam",
                "Pansai",
                "Tamoenye"
            ],
            "Hopang": ["Hopang", "Mongmao", "Namtit", "Pangwaun", "Panlong"],
            "Matman": [
                "Man Kan",
                "Matman",
                "Namphan",
                "Pangsang Township (Pan"
            ],
            "Mongmit": ["Mabein", "Mongmit"],
            "Langkho": [
                "Homane",
                "Kengtaung",
                "Langkho",
                "Mawkmai",
                "Mong Nai",
                "Mong Pan"
            ],
            "Loilen": [
                "Karli",
                "Kholan",
                "Kunhing",
                "Kyethi",
                "Lai-Hka",
                "Loilen",
                "Minenaung",
                "Minesan",
                "Mong Hsu",
                "Mong Kung",
                "Nansang",
                "Panglong"
            ],
            "Taunggyi": [
                "Hopong",
                "Hsi Hseng",
                "Indaw",
                "Kalaw",
                "Kyauktalongyi",
                "Lawksawk",
                "Naungtayar",
                "Nyaungshwe",
                "Pekon",
                "Pingdaya",
                "Pinlaung",
                "Taunggyi",
                "Ywangan"
            ]
        },
        "Ayeyarwady Region": {
            "Hinthada": [
                "Hinthada",
                "Ingapu",
                "Kyangin",
                "Lemyethna",
                "Myanaung",
                "Zalun"
            ],
            "Labutta": ["Labutta", "Mawlamyinegyun", "Pyinsalu"],
            "Ma-ubin": ["Danuphyu", "Ma-ubin", "Nyaungdon", "Pantanaw"],
            "Myaungmya": ["Einme", "Myaungmya", "Wakema"],
            "Pathein": [
                "Hainggyikyun",
                "Kangyidaunk",
                "Kyaunggon",
                "Kyonpyaw",
                "Ngapudaw",
                "Ngathaingchaung",
                "Ngayokaung",
                "Ngwehsaung",
                "Pathein",
                "Shwethaungyan",
                "Thabaung",
                "Yekyi"
            ],
            "Pyapon": ["Ahmar", "Bogale", "Dedaye", "Kyaiklat", "Pyapon"]
        },
        "Bago Region": {
            "Bago": [
                "Aungmyin",
                "Bago",
                "Daik-U",
                "Hpayargyi",
                "Intagaw",
                "Kawa",
                "Kyauktaga",
                "Madauk",
                "Nyaunglebin",
                "Peinzalot",
                "Penwegon",
                "Pyuntaza",
                "Shwegyin",
                "Thanatpin",
                "Waw"
            ],
            "Taungoo": [
                "Kanyutkwin",
                "Kaytumadi",
                "Kyaukkyi",
                "Kywebwe",
                "Mone",
                "Myohla",
                "Natthangwin",
                "Nyaungbinthar",
                "Oktwin",
                "Pyu",
                "Swa",
                "Tantabin",
                "Taungoo",
                "Thagara",
                "Yaeni",
                "Yedashe"
            ],
            "Pyay": [
                "Innma",
                "Okshipin",
                "Padaung",
                "Padigone",
                "Paukkaung",
                "Paungdale",
                "Paungde",
                "Pyay",
                "Shwedaung",
                "Sinmeswe",
                "Thegon"
            ],
            "Thayarwady": [
                "Gyobingauk",
                "Letpadan",
                "Minhla",
                "Monyo",
                "Nattalin",
                "Okpho",
                "Ooethegone",
                "Sitkwin",
                "Tapun",
                "Tharrawaddy",
                "Thonze",
                "Zigon"
            ]
        },
        "Yangon Region": {
            "East Yangon": [
                "Botataung",
                "City",
                "Dagon Seikkan",
                "Dawbon",
                "East Dagon",
                "Mingala Taungnyunt",
                "North Dagon",
                "North Okkalapa",
                "Pazundaung",
                "South Dagon",
                "South Okkalapa",
                "Tamwe",
                "Thaketa",
                "Thingangyun",
                "Yankin"
            ],
            "North Yangon": [
                "City",
                "Hlaingthaya",
                "Hlegu",
                "Hmawbi",
                "Htantabin",
                "Insein",
                "Mingaladon",
                "Rural",
                "Shwepyitha",
                "Taikkyi"
            ],
            "South Yangon": [
                "City",
                "Cocokyun",
                "Dala",
                "Kawhmu",
                "Kayan",
                "Kungyangon",
                "Kyauktan",
                "Rural",
                "Seikkyi Kanaungto",
                "Tada",
                "Thanlyin",
                "Thongwa",
                "Twante"
            ],
            "West Yangon(Downtown)": [
                "Ahlon",
                "Bahan",
                "City",
                "Dagon",
                "Hlaing",
                "Kamayut",
                "Kyauktada",
                "Kyimyindaing",
                "Lanmadaw",
                "Latha",
                "Mayangon",
                "Pabedan",
                "Sanchaung",
                "Seikkan"
            ]
        },
        "Kachin State": {
            "Bhamo": [
                "Bhamo",
                "Dotphoneyan",
                "Lwegel",
                "Mansi",
                "Momauk",
                "Myohla",
                "Shwegu"
            ],
            "Mohnyin": ["Hopin", "Hpakant", "Kamine", "Mogaung", "Mohnyin"],
            "Myitkyina": [
                "Chipwi",
                "Hsadone",
                "Hsawlaw",
                "Hsinbo",
                "Injangyang",
                "Kanpaikti",
                "Myitkyina",
                "Panwa",
                "Shinbwayyan",
                "Tanai",
                "Waingmaw"
            ],
            "Putao": [
                "Kawnglanghpu",
                "Machanbaw",
                "Nogmung",
                "Pannandin",
                "Putao",
                "Sumprabum"
            ]
        },
        "Sagaing Region": {
            "Hkamti": [
                "Donhee",
                "Hkamti",
                "Homalin",
                "Htanparkway",
                "Lahe",
                "Leshi Township (Lay",
                "Mobaingluk",
                "Nanyun",
                "Pansaung",
                "Sonemara"
            ],
            "Kanbalu": ["Kanbalu", "Kyunhla", "Taze", "Ye-U"],
            "Kale": ["Kale", "Kalewa", "Mingin"],
            "Katha": [
                "Banmauk",
                "Indaw",
                "Katha",
                "Kawlin",
                "Pinlebu",
                "Tigyaing",
                "Wuntho"
            ],
            "Mawlaik": ["Mawlaik", "Paungbyin"],
            "Monywa": ["Ayadaw", "Budalin", "Chaung-U", "Monywa"],
            "Sagaing": ["Myaung", "Myinmu", "Sagaing"],
            "Shwebo": ["Khin-U", "Kyaukmyaung", "Shwebo", "Tabayin", "Wetlet"],
            "Tamu": ["Khampat", "Myothit", "Tamu"],
            "Yinmabin": ["Kani", "Pale", "Salingyi", "Yinmabin"]
        },
        "Kayin State": {
            "Hpa-an": [
                "Bawgali",
                "Hlaignbwe",
                "Hpa-an",
                "Leiktho",
                "Paingkyon",
                "Shan Ywathit",
                "Thandaunggyi"
            ],
            "Hpapun": ["Hpapun", "Kamamaung"],
            "Kawkareik": [
                "Kawkareik",
                "Kyaidon",
                "Kyain Seikgyi",
                "Payarthonezu"
            ],
            "Myawaddy": ["Myawaddy", "Sugali", "Wawlaymyaing"]
        },
        "Mon State": {
            "Mawlamyine": [
                "Chaungzon",
                "Khawzar",
                "Kyaikkhami",
                "Kyaikmaraw",
                "Lamine",
                "Mawlamyine",
                "Mudon",
                "Thanbyuzayat",
                "Ye"
            ],
            "Thaton": [
                "Bilin",
                "Kyaikto",
                "Mottama",
                "Paung",
                "Suvannawadi",
                "Thaton",
                "Zingyeik"
            ]
        },
        "Tanintharyi Region": {
            "Dawei": [
                "Dawei",
                "Kaleinaung",
                "Launglon",
                "Myitta",
                "Thayetchaung",
                "Yebyu"
            ],
            "Kawthoung": [
                "Bokpyin",
                "Karathuri",
                "Kawthoung",
                "Khamaukgyi",
                "Pyigyimandaing"
            ],
            "Myeik": ["Kyunsu", "Myeik", "Palauk", "Palaw", "Tanintharyi"]
        },
        "Chin State": {
            "Falam": ["Cikha", "Falam", "Rikhuadal", "Tiddim", "Ton Zang"],
            "Hakha": ["Hakha", "Htantlang"],
            "Mindat": [
                "Kanpetlet",
                "Matupi",
                "Mindat",
                "Paletwa",
                "Reazu",
                "Sami"
            ]
        },
        "Rakhine State": {
            "Kyaukpyu": ["Ann", "Kyaukpyu", "Manaung", "Ramree"],
            "Maungdaw": ["Buthidaung", "Maungdaw", "Taungpyoletwe"],
            "Sittwe": ["Pauktaw", "Ponnagyun", "Rathedaung", "Sittwe"],
            "Thandwe": ["Gaw", "Kyeintali", "Maei", "Thandwe", "Toungup"],
            "Mrauk-U": ["Kyauktaw", "Minbya", "Mrauk-U", "Myebon"]
        }
    }
}
```

**Data Structure:**

-   **Top Level (Keys)**: Regions/States (e.g., "Magway Region", "Mandalay Region", "Yangon Region")
-   **Second Level (Keys)**: Districts (e.g., "Gangaw", "Magway", "Mandalay")
-   **Third Level (Values)**: Array of Townships (e.g., ["Gangaw", "Kyaukhtu", "Saw", "Tilin"])

**Usage:** This endpoint provides address data for dropdowns when creating patients (permanent address) or admissions (present address). The structure is: **Region → District → Township**.

**Frontend Implementation Example:**

```javascript
// Fetch addresses
const response = await fetch("/api/addresses/myanmar", {
    headers: {
        Authorization: `Bearer ${token}`,
    },
});
const { data } = await response.json();

// Populate Region dropdown
const regions = Object.keys(data); // ["Magway Region", "Mandalay Region", ...]

// When region is selected, populate District dropdown
const districts = Object.keys(data[selectedRegion]); // ["Gangaw", "Magway", ...]

// When district is selected, populate Township dropdown
const townships = data[selectedRegion][selectedDistrict]; // ["Gangaw", "Kyaukhtu", ...]
```

---

## User Roles & Permissions

### 36. Get NRC Codes

**Endpoint:** `GET /api/nrc-codes`

**Authorization:** All authenticated users

**Description:** Returns NRC township codes grouped by `nrc_code`, plus allowed `citizenships` (`N,F,P,TH,S`) for building NRC numbers.

**Response:**

```json
{
    "message": "NRC codes retrieved successfully",
    "citizenships": ["N", "F", "P", "TH", "S"],
    "data": [
        {
            "id": "1",
            "name_en": "AhGaYa",
            "name_mm": "(အဂယ) အင်ဂျန်းယန်",
            "nrc_code": "1"
        },
        {
            "id": "2",
            "name_en": "BaMaNa",
            "name_mm": "(ဗမန) ဗန်းမော်",
            "nrc_code": "1"
        }
        // ... full list
    ]
}
```

**Usage:** Frontend should fetch this to populate NRC dropdowns: `nrc_code`, `name_en` (township), `citizenship`. Then append a 6-digit number to form `nrc_number` as `{code}/{name_en}({citizenship})123456`.

### 37. Get Hospital Departments

**Endpoint:** `GET /api/departments`

**Authorization:** All authenticated users

**Description:** Returns hospital departments for cancer hospital admission forms and department selection dropdowns.

**Response:**

```json
{
    "message": "Hospital departments retrieved successfully",
    "data": {
        "medical_oncology": "Medical Oncology",
        "surgical_oncology": "Surgical Oncology",
        "radiation_oncology": "Radiation Oncology",
        "gynecologic_oncology": "Gynecologic Oncology",
        "pediatric_oncology": "Pediatric Oncology",
        "hematology_oncology": "Hematology/Oncology",
        "pathology": "Pathology",
        "radiology": "Radiology",
        "nuclear_medicine": "Nuclear Medicine",
        "laboratory": "Laboratory",
        "pharmacy": "Pharmacy",
        "emergency": "Emergency Department",
        "intensive_care_unit": "Intensive Care Unit",
        "palliative_care": "Palliative Care",
        "pain_management": "Pain Management",
        "nutrition_support": "Nutrition Support",
        "psychology": "Psychology",
        "social_services": "Social Services",
        "rehabilitation": "Rehabilitation",
        "outpatient_clinic": "Outpatient Clinic"
    }
}
```

**Frontend Usage:**

```javascript
const response = await fetch("/api/departments", {
    headers: { Authorization: `Bearer ${token}` },
});
const { data: departments } = await response.json();

// Populate department dropdown
Object.entries(departments).forEach(([key, label]) => {
    const option = document.createElement("option");
    option.value = key;
    option.textContent = label;
    departmentSelect.appendChild(option);
});
```

### 38. Get Hospital Wards and Rooms

**Endpoint:** `GET /api/wards`

**Authorization:** All authenticated users

**Description:** Returns hospital wards with their associated room numbers for admission forms. Room numbers are organized by ward for hierarchical selection.

**Response:**

```json
{
    "message": "Hospital wards retrieved successfully",
    "data": {
        "oncology_ward_a": {
            "name": "Oncology Ward A",
            "rooms": [
                "101",
                "102",
                "103",
                "104",
                "105",
                "106",
                "107",
                "108",
                "109",
                "110"
            ]
        },
        "oncology_ward_b": {
            "name": "Oncology Ward B",
            "rooms": [
                "201",
                "202",
                "203",
                "204",
                "205",
                "206",
                "207",
                "208",
                "209",
                "210"
            ]
        },
        "surgical_ward": {
            "name": "Surgical Ward",
            "rooms": ["301", "302", "303", "304", "305", "306", "307", "308"]
        },
        "icu_oncology": {
            "name": "Oncology ICU",
            "rooms": ["401", "402", "403", "404", "405", "406"]
        },
        "palliative_care_ward": {
            "name": "Palliative Care Ward",
            "rooms": ["501", "502", "503", "504", "505", "506"]
        },
        "pediatric_oncology": {
            "name": "Pediatric Oncology Ward",
            "rooms": ["601", "602", "603", "604", "605"]
        },
        "day_care_unit": {
            "name": "Day Care Unit",
            "rooms": [
                "701",
                "702",
                "703",
                "704",
                "705",
                "706",
                "707",
                "708",
                "709",
                "710"
            ]
        },
        "isolation_ward": {
            "name": "Isolation Ward",
            "rooms": ["801", "802", "803", "804"]
        }
    }
}
```

**Frontend Usage:**

```javascript
const response = await fetch("/api/wards", {
    headers: { Authorization: `Bearer ${token}` },
});
const { data: wards } = await response.json();

// Populate ward dropdown
Object.entries(wards).forEach(([key, wardData]) => {
    const option = document.createElement("option");
    option.value = key;
    option.textContent = wardData.name;
    wardSelect.appendChild(option);
});

// When ward is selected, populate room dropdown
wardSelect.addEventListener("change", (e) => {
    const selectedWard = e.target.value;
    const rooms = wards[selectedWard]?.rooms || [];

    roomSelect.innerHTML = '<option value="">Select Room</option>';
    rooms.forEach((roomNumber) => {
        const option = document.createElement("option");
        option.value = roomNumber;
        option.textContent = `Room ${roomNumber}`;
        roomSelect.appendChild(option);
    });
});
```

#### Additional Endpoint: Get Rooms for Specific Ward

**Endpoint:** `GET /api/wards/{wardKey}/rooms`

**Authorization:** All authenticated users

**Description:** Returns room numbers for a specific ward. Useful for dynamic loading when ward selection changes.

**Response:**

```json
{
    "message": "Rooms retrieved successfully",
    "data": ["101", "102", "103", "104", "105"]
}
```

### Permission Matrix

| Feature                     | Root          | Admission | Doctor           | Nurse            |
| --------------------------- | ------------- | --------- | ---------------- | ---------------- |
| **User Management**         |               |           |                  |                  |
| Create/Delete/Restore Users | ✅            | ❌        | ❌               | ❌               |
| **Patient Demographics**    |               |           |                  |                  |
| View All Patients           | ✅            | ✅        | ❌               | ❌               |
| View Assigned Patients      | ✅            | ✅        | ✅ Assigned only | ✅ Assigned only |
| Create/Update Patients      | ✅            | ✅        | ❌               | ❌               |
| Delete Patients             | ❌ (Disabled) | ❌        | ❌               | ❌               |
| **Admissions**              |               |           |                  |                  |
| View All Admissions         | ✅            | ✅        | ❌               | ❌               |
| View Assigned Admissions    | ✅            | ✅        | ✅               | ✅               |
| Create Admissions           | ✅            | ✅        | ❌               | ❌               |
| Update Admissions (all)     | ✅            | ✅        | ❌               | ❌               |
| Update Admissions (medical) | ✅            | ❌        | Assigned only    | ❌               |
| Assign Staff                | ✅            | ✅        | ❌               | ❌               |
| Discharge Patients          | ✅            | ❌        | Assigned only    | ❌               |
| Confirm Death               | ✅            | ❌        | Assigned only    | ❌               |
| Delete Admissions           | ❌ (Disabled) | ❌        | ❌               | ❌               |
| **Treatment Records**       |               |           |                  |                  |
| View Treatments             | ✅            | ✅        | Assigned only    | Assigned only    |
| Create/Update Treatments    | ✅            | ❌        | Assigned only    | ❌               |
| Delete Treatments           | ❌ (Disabled) | ❌        | ❌               | ❌               |

---

## Example Workflows

### Admission Staff Workflow

```
1. Patient arrives
2. Search for existing patient: GET /api/patients/search?q=John
3. If not found, create patient: POST /api/patients
4. Get available doctors: GET /api/staff/doctors
5. Get available nurses: GET /api/staff/nurses
6. Admit patient: POST /api/patients/{id}/admit
```

### Doctor Workflow

```
1. Login as doctor
2. View assigned patients: GET /api/patients
   → Returns only patients assigned to this doctor (list_type: "assigned")
3. View assigned admissions: GET /api/admissions?status=admitted
   → Returns only admissions where doctor is assigned
4. View admission details: GET /api/admissions/{id}
5. Add treatment: POST /api/admissions/{id}/treatments
6. Update treatment results: PATCH /api/admissions/{id}/treatments/{rid}
7. When ready, discharge: POST /api/admissions/{id}/discharge
```

### Nurse Workflow

```
1. Login as nurse
2. View assigned patients: GET /api/patients
   → Returns only patients assigned to this nurse (list_type: "assigned")
3. View assigned admissions: GET /api/admissions?status=admitted
   → Returns only admissions where nurse is assigned
4. View admission details: GET /api/admissions/{id}
5. View treatment records: GET /api/admissions/{id}/treatments
   (Read-only access - cannot add or modify treatments)
```

### Readmission Scenario

```
1. Patient returns after previous discharge
2. Search patient: GET /api/patients/search?q=NRC-number
3. View patient history: GET /api/patients/{id}/admissions
4. Create NEW admission: POST /api/patients/{id}/admit
   - Previous admission records preserved
   - New admission number generated (ADM-2024-000XXX)
```

### Outpatient to Inpatient Conversion

```
1. Patient arrives for consultation
2. Register as outpatient:
   POST /api/patients/{id}/admit
   {
     "admission_type": "outpatient",
     "admitted_for": "Chest pain consultation",
     "doctor_id": 2,
     ...
   }

3. Doctor examines patient
4. Condition requires overnight stay
5. Doctor converts to inpatient:
   POST /api/admissions/{id}/convert-to-inpatient
   {
     "ward": "Ward A",
     "bed_number": "15",
     "remarks": "Requires observation"
   }

6. Patient now has bed assignment and can be treated as inpatient
7. Add treatments: POST /api/admissions/{id}/treatments
8. When ready, discharge: POST /api/admissions/{id}/discharge
```

---

## Error Handling

| Status | Description                               |
| ------ | ----------------------------------------- |
| 400    | Bad Request (invalid data, business rule) |
| 401    | Unauthorized (invalid/missing token)      |
| 403    | Forbidden (role doesn't have permission)  |
| 404    | Not Found (resource doesn't exist)        |
| 422    | Validation Error (invalid input)          |
| 429    | Too Many Requests (rate limited)          |

---

## Security Features

1. **Token-based Authentication** - Laravel Sanctum
2. **Role-based Access Control** - Per-endpoint authorization
3. **Assignment-based Access** - Doctors/nurses only see their patients
4. **Rate Limiting** - Login endpoint protected
5. **Input Validation** - All inputs validated
6. **Audit Logging** - All significant actions logged
7. **Soft Delete** - Data recovery possible
8. **Staff Validation** - System verifies doctor/nurse roles on assignment

---

**Last Updated:** December 2024  
**API Version:** 2.0
