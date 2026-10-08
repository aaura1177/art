<!DOCTYPE html>
<html lang="en">

 <head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Global Vision Login</title>

    <!-- Bootstrap core CSS-->
    <link href="{{asset('ui-vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">

    <!-- Custom fonts for this template-->
    <link href="{{asset('ui-vendor/fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css">

    <!-- Custom styles for this template-->
    <link href="{{asset('css/sb-admin.css')}}" rel="stylesheet">

  </head>

<body class="gvd-bg">
<div class="container">

    <div class="img-fluid text-center mx-auto mt-5">
        <img src="{{ Storage::disk('s3')->url('stock/images/gvllogo.png') }}" alt="Global Vision Direct (P) Ltd">
        <h3 class="text-center">Global Vision Direct (P) Ltd</h3>
    </div>

    <div class="card card-login mx-auto mt-5">
        <div class="card-header">{{ __('Reset Password') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="form-group">
                        <div class="form-label-group">
                             <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" required autofocus>
                            
                            <label for="email">{{ __('E-Mail Address') }}</label>

                            @if ($errors->has('email'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-label-group">
                             <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
                            
                            <label for="password">{{ __('Password') }}</label>

                            @if ($errors->has('password'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-label-group">
                             <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                            <label for="password-confirm">{{ __('Confirm Password') }}</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                      {{ __('Reset Password') }}
                    </button>

                    
                </form>
            </div>        
    </div>      
</div>
