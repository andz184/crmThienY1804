@extends('adminlte::master')

@section('adminlte_css_pre')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="{{ asset('css/adminlte-custom-dark.css') }}">
@stop

@section('classes_body', 'layout-fixed layout-navbar-fixed dark-mode')

@section('body')
    <div class="wrapper">
        @include('layouts.partials.navbar')
        @include('layouts.partials.sidebar')

        <div class="content-wrapper">
            @yield('content')
        </div>

        @include('layouts.partials.footer')
    </div>
@stop

@section('adminlte_js')
    <script>
        // Add any custom JavaScript here
        $(document).ready(function() {
            // Initialize any plugins or custom functionality
        });
    </script>
@stop
