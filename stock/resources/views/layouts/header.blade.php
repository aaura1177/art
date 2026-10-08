<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="/images/favicon.ico" type="image/x-icon" rel="icon">
    <link href="/images/favicon.ico" type="image/x-icon" rel="shortcut icon">
    <title>Global Vision Direct (P) Ltd</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.partials.vendor-styles')

    <!-- Custom styles for this template-->
    <link href="{{ asset('css/sb-admin.css') }}" rel="stylesheet">

    @include('layouts.partials.vendor-scripts-head')

</head>
<?php
$country = \Request::session()->get('country');
?>

<body id="page-top">

    <nav class="navbar navbar-expand navbar-dark bg-dark static-top">

        <a class="navbar-brand me-1" href="{{ url('/dashboard') }}">Global Vision</a>

        <button class="btn btn-link btn-sm text-white order-1 order-sm-0" id="sidebarToggle" href="#">
            <i class="fas fa-bars"></i>
        </button>



        <!-- Navbar -->
        <ul class="navbar-nav ms-auto me-0 me-md-3 my-2 my-md-0">
            <!--  <li class="nav-item dropdown no-arrow mx-1">
          <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-bell fa-fw"></i>
            <span class="badge badge-danger">9+</span>
          </a>
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="alertsDropdown">
            <a class="dropdown-item" href="#">Action</a>
            <a class="dropdown-item" href="#">Another action</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="#">Something else here</a>
          </div>
        </li> -->
            <!-- <li class="nav-item dropdown no-arrow mx-1">
          <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-envelope fa-fw"></i>
            <span class="badge badge-danger">7</span>
          </a>
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="messagesDropdown">
            <a class="dropdown-item" href="#">Action</a>
            <a class="dropdown-item" href="#">Another action</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="#">Something else here</a>
          </div>
        </li> -->
             @if (auth()->user()->role == 'Admin' || auth()->user()->role == 'admin' ||
                auth()->user()->role == 'Office' || auth()->user()->role == 'office' ||
                auth()->user()->role == 'Stockadmin' || auth()->user()->role == 'stockadmin' || auth()->user()->role == 'Administrative' || auth()->user()->role == 'administrative' ||
                auth()->user()->role == 'compliance' || strtolower(auth()->user()->email ?? '') === 'compliance@artisanfurniture.net')

                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{-- <i class="fas fa-fw fa-tachometer-alt"></i> --}}
                        <i class='fas fa-caret-down'></i>
                        {{ $country == 'india' ? ucfirst($country) : strtoupper($country) }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="{{ url('/setting/setCountry/india') }}">India</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item " href="{{ url('/setting/setCountry/uk') }}">UK
                            <?= $country == 'uk' ? '<i class="fas fa-check"></i>' : '' ?></a>
                        <a class="dropdown-item " href="{{ url('/setting/setCountry/us') }}">US
                            <?= $country == 'us' ? '<i class="fas fa-check"></i>' : '' ?></a>
                        <a class="dropdown-item " href="{{ url('/setting/setCountry/eu') }}">EU
                            <?= $country == 'eu' ? '<i class="fas fa-check"></i>' : '' ?></a>
                            <a class="dropdown-item " href="{{ url('/setting/setCountry/canada') }}">Canada
                                <?= $country == 'canada' ? '<i class="fas fa-check"></i>' : '' ?></a>
                                <a class="dropdown-item " href="{{ url('/setting/setCountry/california') }}">California
                                    <?= $country == 'california' ? '<i class="fas fa-check"></i>' : '' ?></a>
                    </div>
                </li>
            @endif
            @hasrole(['Admin', 'admin', 'Administrative', 'administrative'])
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                         {{  ucfirst(Auth::user()->firstname.' '.Auth::user()->lastname) }}  <i class="fas fa-user-circle fa-fw"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="{{ url('/setting') }}">Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">Logout</a>
                    </div>
                </li>
            @else
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{  ucfirst(Auth::user()->firstname.' '.Auth::user()->lastname) }} <i class="fas fa-user-circle fa-fw"></i>
                        <span>{{ $country }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">Logout</a>
                    </div>
                </li>
            @endhasrole
        </ul>

    </nav>