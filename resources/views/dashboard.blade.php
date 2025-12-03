@extends('layouts.app')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom CSS for Modern Design -->
    <style>
        body {
            background-color: #f4f6f9; /* Light gray background */
        }
        .main-content-area {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }

        .modern-card {
            border: none;
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
        }
        .card-link {
            text-decoration: none;
            color: inherit;
        }
        .card-body {
            padding: 1.5rem;
            display: flex;
            align-items: center;
        }

        .card-icon {
            font-size: 2.5rem;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-right: 1.5rem;
        }
        .card-icon i {
            color: #fff;
        }
        .card-text-content h2 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        .card-text-content p {
            font-size: 0.9rem;
            color: #8492a6;
            margin: 0;
        }

        /* Individual Card Colors */
        .card-total-patients .card-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-new-today .card-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .card-gender .card-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .card-users .card-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px; /* Fixed height for the chart */
            width: 100%;
        }
    </style>
</head>
<body>
@section('content')
<div class="container-fluid py-4">
    <div class="main-content-area">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0">Hospital Dashboard</h3>
            <button class="btn btn-primary">
                <i class="fas fa-download me-2"></i>Export Report
            </button>
        </div>


        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Patients (Clickable) -->
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('patients.index') }}" class="card-link">
                    <div class="card modern-card card-total-patients">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-text-content">
                                <h2>{{ $patientCount ?? 0 }}</h2>
                                <p>Total Patients</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            {{-- <!-- New Today -->
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('doctor.index') }}" class="card-link">
                    <div class="card modern-card card-total-patients">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-text-content">
                                <h2>{{ $doctorCount ?? 0 }}</h2>
                                <p>Total Doctors</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6">
                <a href="{{ route('nurse.index') }}" class="card-link">
                    <div class="card modern-card card-total-patients">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-text-content">
                                <h2>{{ $nurseCount ?? 0 }}</h2>
                                <p>Total Nurses</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div> --}}

           <div class="col-lg-3 col-md-6">
                <a href="{{ route('users.index') }}" class="card-link">
                    <div class="card modern-card card-total-patients">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-text-content">
                                <h2>{{ $usersCount ?? 0 }}</h2>
                                <p>System Users</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Chart -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-4">Patients Per Month</h5>
                <div class="chart-container">
                    <canvas id="patientsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('patientsChart');
        if (ctx) {
            const months = @json($months ?? []);
            const monthlyPatients = @json($monthlyPatients ?? []);

            // Create gradient
            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(102, 126, 234, 0.4)'); // Purple with alpha
            gradient.addColorStop(1, 'rgba(102, 126, 234, 0)');   // Transparent purple

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'New Patients',
                        data: monthlyPatients,
                        borderColor: '#667eea',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#667eea',
                        pointHoverBackgroundColor: '#667eea',
                        pointHoverBorderColor: '#ffffff',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.03)',
                                drawBorder: false,
                            },
                            ticks: {
                                precision: 0,
                                color: '#8492a6'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#8492a6'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#ffffff',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: '#ddd',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            intersect: false,
                            mode: 'index',
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
</body>
</html>
