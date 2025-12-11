<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    /**
     * Get department with display names.
     */
    public static function withDisplayNames(): array
    {
        return [
            'medical_oncology' => 'Medical Oncology',
            'surgical_oncology' => 'Surgical Oncology',
            'radiation_oncology' => 'Radiation Oncology',
            'gynecologic_oncology' => 'Gynecologic Oncology',
            'pediatric_oncology' => 'Pediatric Oncology',
            'hematology_oncology' => 'Hematology/Oncology',
            'pathology' => 'Pathology',
            'radiology' => 'Radiology',
            'nuclear_medicine' => 'Nuclear Medicine',
            'laboratory' => 'Laboratory',
            'pharmacy' => 'Pharmacy',
            'emergency' => 'Emergency Department',
            'intensive_care_unit' => 'Intensive Care Unit',
            'palliative_care' => 'Palliative Care',
            'pain_management' => 'Pain Management',
            'nutrition_support' => 'Nutrition Support',
            'psychology' => 'Psychology',
            'social_services' => 'Social Services',
            'rehabilitation' => 'Rehabilitation',
            'outpatient_clinic' => 'Outpatient Clinic',
        ];
    }
}
