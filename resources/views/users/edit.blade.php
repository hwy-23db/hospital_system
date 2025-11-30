@extends('layouts.app')

@section('content')
<style>
    .modern-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .modern-header {
        background: linear-gradient(135deg, #2d8cf0, #3bbdff);
        color: white;
        padding: 18px 24px;
        font-size: 1.25rem;
        font-weight: 600;
        letter-spacing: .5px;
    }
    .modern-label {
        font-weight: 600;
        color: #34495e;
    }
    .modern-input {
        border-radius: 12px !important;
        padding: 10px 14px !important;
        border: 1.5px solid #dce4ec !important;
        transition: all .2s ease;
    }
    .modern-input:focus {
        border-color: #3498db !important;
        box-shadow: 0 0 0 2px rgba(52,152,219,0.2) !important;
    }
    .btn-modern {
        border-radius: 12px;
        padding: 12px 0;
        font-size: 1rem;
        font-weight: 600;
        background: linear-gradient(135deg, #3498db, #2d89ff);
        border: none;
        transition: .2s ease;
    }
    .btn-modern:hover {
        background: linear-gradient(135deg, #2d89ff, #3498db);
        transform: translateY(-1px);
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">

            <div class="modern-card">

                <!-- Header -->
                <div class="modern-header">
                    ✏️ Edit User
                </div>

                <div class="p-4">

                    <form method="POST" action="{{ route('users.update', $user->id) }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="modern-label">Full Name</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                class="form-control modern-input @error('name') is-invalid @enderror"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="modern-label">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                class="form-control modern-input @error('email') is-invalid @enderror"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label for="role" class="modern-label">User Role</label>

                            <select
                                id="role"
                                name="role"
                                class="form-control modern-input @error('role') is-invalid @enderror"
                                required
                            >
                                <option value="" disabled>Select Role</option>

                                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>
                                    Admin
                                </option>

                                <option value="doctor" {{ old('role', $user->role) == 'doctor' ? 'selected' : '' }}>
                                    Doctor
                                </option>

                                <option value="nurse" {{ old('role', $user->role) == 'nurse' ? 'selected' : '' }}>
                                    Nurse
                                </option>

                                {{-- <option value="receptionist" {{ old('role', $user->role) == 'receptionist' ? 'selected' : '' }}>
                                    Receptionist
                                </option>

                                <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>
                                    Staff
                                </option> --}}
                            </select>

                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>



                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="modern-label">
                                Password <span class="text-muted">(leave blank to keep current)</span>
                            </label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control modern-input @error('password') is-invalid @enderror"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="modern-label">Confirm Password</label>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control modern-input"
                            >
                        </div>

                        <button type="submit" class="btn btn-modern w-100">
                            Update User
                        </button>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
