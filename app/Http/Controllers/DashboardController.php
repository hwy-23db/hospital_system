<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\User;
use App\Models\doctor;
use App\Models\nurse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Total patients
        $patientCount = Patient::count();

        // New patients today
        $todayPatients = Patient::whereDate('created_at', Carbon::today())->count();

       //doctor count
    //    $doctorCount = Doctor::count();

    //    //nurse count
    //     $nurseCount = Nurse::count();

        // Users count (optional)
        $usersCount = User::count();

        // Optional: patients per month for chart
        $months = [];
        $monthlyPatients = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date('F', mktime(0, 0, 0, $i, 1));
            $monthlyPatients[] = Patient::whereMonth('created_at', $i)->count();
        }

        return view('dashboard', compact(
            'patientCount',
            'todayPatients',
            //'maleCount',
            //'femaleCount',
            'usersCount',
            'months',
            'monthlyPatients',
            // 'doctorCount',
            // 'nurseCount',
        ));
    }
}
