<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    /**
     * Get comprehensive reports for dashboard graphs.
     * Only root_user and admission can access.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['root_user', 'admission'])) {
            return response()->json([
                'message' => 'Unauthorized. Only administrators can access reports.'
            ], 403);
        }

        // Get date range from query params (default: last 12 months)
        $startDate = $request->input('start_date', now()->subMonths(12)->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $reports = [
            'summary' => $this->getSummaryStats(),
            'patients' => $this->getPatientStats($startDate, $endDate),
            'admissions' => $this->getAdmissionStats($startDate, $endDate),
            'treatments' => $this->getTreatmentStats($startDate, $endDate),
            'departments' => $this->getDepartmentStats($startDate, $endDate),
            'time_series' => $this->getTimeSeriesData($startDate, $endDate),
            'staff_workload' => $this->getStaffWorkloadStats($startDate, $endDate),
        ];

        Log::info('Reports accessed', [
            'user_id' => $user->id,
            'role' => $user->role,
            'date_range' => ['start' => $startDate, 'end' => $endDate],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Reports retrieved successfully',
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'data' => $reports,
        ]);
    }

    /**
     * Get summary statistics (overall counts)
     */
    private function getSummaryStats(): array
    {
        return [
            'total_patients' => Patient::count(),
            'total_admissions' => Admission::count(),
            'active_admissions' => Admission::where('status', 'admitted')->count(),
            'total_treatments' => TreatmentRecord::count(),
            'total_staff' => User::whereIn('role', ['doctor', 'nurse', 'admission'])->count(),
        ];
    }

    /**
     * Get patient statistics for graphs
     */
    private function getPatientStats(string $startDate, string $endDate): array
    {
        return [
            'by_gender' => Patient::selectRaw('sex, count(*) as count')
                ->groupBy('sex')
                ->pluck('count', 'sex')
                ->toArray(),
            'by_blood_type' => Patient::selectRaw('blood_type, count(*) as count')
                ->groupBy('blood_type')
                ->pluck('count', 'blood_type')
                ->toArray(),
            'by_age_group' => Patient::selectRaw('
                    CASE
                        WHEN age < 18 THEN "0-17"
                        WHEN age BETWEEN 18 AND 30 THEN "18-30"
                        WHEN age BETWEEN 31 AND 50 THEN "31-50"
                        WHEN age BETWEEN 51 AND 70 THEN "51-70"
                        ELSE "71+"
                    END as age_group,
                    count(*) as count
                ')
                ->groupBy('age_group')
                ->pluck('count', 'age_group')
                ->toArray(),
            'by_marital_status' => Patient::selectRaw('marital_status, count(*) as count')
                ->groupBy('marital_status')
                ->pluck('count', 'marital_status')
                ->toArray(),
            'registrations_over_time' => Patient::selectRaw('DATE(created_at) as date, count(*) as count')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => ['date' => $item->date, 'count' => $item->count])
                ->toArray(),
        ];
    }

    /**
     * Get admission statistics for graphs
     */
    private function getAdmissionStats(string $startDate, string $endDate): array
    {
        return [
            'by_type' => Admission::selectRaw('admission_type, count(*) as count')
                ->groupBy('admission_type')
                ->pluck('count', 'admission_type')
                ->toArray(),
            'by_status' => Admission::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_department' => Admission::selectRaw('service, count(*) as count')
                ->whereNotNull('service')
                ->groupBy('service')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'service')
                ->toArray(),
            'admissions_over_time' => Admission::selectRaw('DATE(admission_date) as date, count(*) as count')
                ->whereBetween('admission_date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => ['date' => $item->date, 'count' => $item->count])
                ->toArray(),
            'discharges_over_time' => Admission::selectRaw('DATE(discharge_date) as date, count(*) as count')
                ->whereNotNull('discharge_date')
                ->whereBetween('discharge_date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => ['date' => $item->date, 'count' => $item->count])
                ->toArray(),
            'average_length_of_stay' => Admission::where('status', 'discharged')
                ->whereNotNull('discharge_date')
                ->whereBetween('discharge_date', [$startDate, $endDate])
                ->get()
                ->map(fn($admission) => $admission->length_of_stay)
                ->filter()
                ->average(),
        ];
    }

    /**
     * Get treatment statistics for graphs
     */
    private function getTreatmentStats(string $startDate, string $endDate): array
    {
        return [
            'by_type' => TreatmentRecord::selectRaw('treatment_type, count(*) as count')
                ->groupBy('treatment_type')
                ->pluck('count', 'treatment_type')
                ->toArray(),
            'by_outcome' => TreatmentRecord::selectRaw('outcome, count(*) as count')
                ->whereNotNull('outcome')
                ->groupBy('outcome')
                ->pluck('count', 'outcome')
                ->toArray(),
            'treatments_over_time' => TreatmentRecord::selectRaw('DATE(treatment_date) as date, count(*) as count')
                ->whereNotNull('treatment_date')
                ->whereBetween('treatment_date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => ['date' => $item->date, 'count' => $item->count])
                ->toArray(),
            'treatments_by_month' => TreatmentRecord::selectRaw('
                    DATE_FORMAT(treatment_date, "%Y-%m") as month,
                    count(*) as count
                ')
                ->whereNotNull('treatment_date')
                ->whereBetween('treatment_date', [$startDate, $endDate])
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn($item) => ['month' => $item->month, 'count' => $item->count])
                ->toArray(),
        ];
    }

    /**
     * Get department/service statistics
     */
    private function getDepartmentStats(string $startDate, string $endDate): array
    {
        return [
            'admissions_by_service' => Admission::selectRaw('service, count(*) as count')
                ->whereNotNull('service')
                ->whereBetween('admission_date', [$startDate, $endDate])
                ->groupBy('service')
                ->orderByDesc('count')
                ->get()
                ->map(fn($item) => ['service' => $item->service, 'count' => $item->count])
                ->toArray(),
            'top_departments' => Admission::selectRaw('service, count(*) as count')
                ->whereNotNull('service')
                ->whereBetween('admission_date', [$startDate, $endDate])
                ->groupBy('service')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'service')
                ->toArray(),
        ];
    }

    /**
     * Get time series data for trend analysis
     */
    private function getTimeSeriesData(string $startDate, string $endDate): array
    {
        return [
            'daily_admissions' => Admission::selectRaw('DATE(admission_date) as date, count(*) as count')
                ->whereBetween('admission_date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => ['date' => $item->date, 'count' => $item->count])
                ->toArray(),
            'monthly_admissions' => Admission::selectRaw('
                    DATE_FORMAT(admission_date, "%Y-%m") as month,
                    count(*) as count
                ')
                ->whereBetween('admission_date', [$startDate, $endDate])
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn($item) => ['month' => $item->month, 'count' => $item->count])
                ->toArray(),
            'monthly_discharges' => Admission::selectRaw('
                    DATE_FORMAT(discharge_date, "%Y-%m") as month,
                    count(*) as count
                ')
                ->whereNotNull('discharge_date')
                ->whereBetween('discharge_date', [$startDate, $endDate])
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn($item) => ['month' => $item->month, 'count' => $item->count])
                ->toArray(),
        ];
    }

    /**
     * Get staff workload statistics
     */
    private function getStaffWorkloadStats(string $startDate, string $endDate): array
    {
        return [
            'doctors' => User::where('role', 'doctor')
                ->withCount(['admissionsAsDoctor' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('admission_date', [$startDate, $endDate]);
                }])
                ->orderByDesc('admissions_as_doctor_count')
                ->limit(10)
                ->get()
                ->map(fn($doctor) => [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'admissions_count' => $doctor->admissions_as_doctor_count ?? 0,
                ])
                ->toArray(),
            'nurses' => User::where('role', 'nurse')
                ->withCount(['admissionsAsNurse' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('admission_date', [$startDate, $endDate]);
                }])
                ->orderByDesc('admissions_as_nurse_count')
                ->limit(10)
                ->get()
                ->map(fn($nurse) => [
                    'id' => $nurse->id,
                    'name' => $nurse->name,
                    'admissions_count' => $nurse->admissions_as_nurse_count ?? 0,
                ])
                ->toArray(),
        ];
    }
}

