<!DOCTYPE html>
<html lang="en">
<head></head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Capacity Planning')</title>

    {{-- Bootstrap 5 CSS --}}
    <link href="{{ asset('css/bootstrap.min.css') }}?v={{ config('constants.cache_ver') }}" rel="stylesheet">

    {{-- Select2 CSS --}}
    <link href="{{ asset('css/select2.min.css') }}?v={{ config('constants.cache_ver') }}" rel="stylesheet">

    {{-- Font awesome CSS --}}
    <link href="{{ asset('css/all.min.css') }}?v={{ config('constants.cache_ver') }}" rel="stylesheet">

    <!-- Include flatpickr CSS & JS -->
    <link href="{{ asset('css/flatpickr.min.css') }}?v={{ config('constants.cache_ver') }}" rel="stylesheet">
    {{-- <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet"> --}}

    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"> --}}
    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet"> --}}
    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"> --}}

    {{-- Page-specific styles --}}
    @yield('styles')
</head>
<body>
    {{-- <div class="container my-4"> --}}
        @yield('content')
    {{-- </div> --}}

    {{-- jQuery --}}
    <script src="{{ asset('js/jquery.min.js') }}?v={{ config('constants.cache_ver') }}"></script>

    <!-- jQuery Validation -->
    <script src="{{ asset('js/jquery.validate.min.js') }}?v={{ config('constants.cache_ver') }}"></script>

    <!-- Optional: additional methods (like number, min, max, etc.) -->
    <script src="{{ asset('js/additional-methods.min.js') }}?v={{ config('constants.cache_ver') }}"></script>

    {{-- Bootstrap 5 JS --}}
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}?v={{ config('constants.cache_ver') }}"></script>

    {{-- Select2 JS --}}
    <script src="{{ asset('js/select2.min.js') }}?v={{ config('constants.cache_ver') }}"></script>

    <script src="{{ asset('js/flatpickr.js') }}?v={{ config('constants.cache_ver') }}"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script> --}}

    <!-- Scripts -->
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script> --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script> --}}
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script> --}}


    {{-- Page-specific scripts --}}
    @yield('scripts')
</body>
</html>
