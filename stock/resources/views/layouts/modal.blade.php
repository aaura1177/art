<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Global Vision Direct (P) Ltd</title>

    {{-- Print modals: keep pre-upgrade lean asset set (no DataTables / bootstrap-select) --}}
    <link href="{{ asset('ui-vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/modal.css') }}?v={{ @filemtime(public_path('css/modal.css')) ?: time() }}" rel="stylesheet">
    <link href="{{ asset('ui-vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('css/sb-admin.css') }}" rel="stylesheet">

    <script src="{{ asset('ui-vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('ui-vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('ui-vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    @stack('styles')

  </head>