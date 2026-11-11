@extends('layouts.app')
@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="d-flex align-items-center gap-3 mb-4">
                        @if($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" class="avatar avatar-lg" alt="">
                        @else
                        <span class="avatar avatar-lg avatar-text">{{ $user->initials }}</span>
                        @endif
                        <div>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->employee_code }} · {{ $user->designation }} · {{ $user->department?->name }}</div>
                            <div class="small">@foreach($user->roles as $r)<span class="badge text-bg-light border">{{ $r->name }}</span> @endforeach</div>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $user->phone) }}"></div>
                    <div class="mb-3"><label class="form-label">Photo</label><input name="avatar" type="file" class="form-control" accept="image/*"></div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">New Password</label><input name="password" type="password" class="form-control" placeholder="Leave blank to keep"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Confirm Password</label><input name="password_confirmation" type="password" class="form-control"></div>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><strong>My Permissions</strong></div>
            <div class="card-body">
                @foreach(collect($user->isSuperAdmin() ? \App\Models\Permission::pluck('name') : $user->permissionNames())->sort() as $p)
                <span class="badge text-bg-light border mb-1">{{ $p }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
