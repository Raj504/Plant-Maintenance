@extends('layouts.guest')
@section('title', 'Login')

@section('content')
<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand">
            <span class="brand-icon"><i class="bi bi-gear-wide-connected"></i></span>
            <div>
                <h1 class="h4 mb-0 fw-bold">PlantCare</h1>
                <small class="text-muted">Plant Maintenance &amp; Operations</small>
            </div>
        </div>

        <h2 class="h5 mt-4 mb-3">Sign in to your account</h2>

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
                </div>
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label for="remember" class="form-check-label">Remember me</label>
            </div>
            <button class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</button>
        </form>

        @if(config('app.env') === 'local')
        <div class="demo-logins mt-4">
            <div class="text-muted small mb-2">Demo accounts (password: <code>password</code>)</div>
            <div class="d-flex flex-wrap gap-1">
                @foreach(['admin', 'employee', 'inspector', 'expert', 'approver', 'supervisor', 'technician', 'safety', 'store', 'operator', 'compliance', 'manager', 'management'] as $demo)
                <button type="button" class="btn btn-outline-secondary btn-sm demo-login" data-email="{{ $demo }}@plant.com">{{ $demo }}</button>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    <p class="text-center text-muted small mt-3">&copy; {{ date('Y') }} {{ setting('pm.company_name', config('app.name')) }}</p>
</div>
@endsection
