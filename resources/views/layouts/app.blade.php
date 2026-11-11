<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ setting('pm.company_name', config('app.name')) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<div class="pm-wrapper">

    @include('partials.sidebar')

    <div class="pm-main">
        @include('partials.header')

        <main class="pm-content">
            {{-- Page heading --}}
            @hasSection('page-title')
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 page-head">
                <div>
                    <h1 class="h4 mb-1 fw-semibold">@yield('page-title')</h1>
                    @hasSection('breadcrumb')
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb small mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            @yield('breadcrumb')
                        </ol>
                    </nav>
                    @endif
                </div>
                <div class="d-flex gap-2 flex-wrap">@yield('page-actions')</div>
            </div>
            @endif

            @include('partials.validation-errors')

            @yield('content')
        </main>

        @include('partials.footer')
    </div>
</div>

@include('partials.toast')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
