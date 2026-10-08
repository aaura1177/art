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
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <div class="card card-login mx-auto mt-5">
        <div class="card-header">{{ __('Login') }}</div>
          <div class="card-body">
              <form method="POST" action="{{ route('login')}}">
                  @csrf

                  <div class="form-group mb-3">
                    <div class="form-label-group">
                        <input type="email" id="inputEmail" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="Email address" required="required" autofocus="autofocus">
                        <label for="inputEmail">{{ __('E-Mail Address') }}</label>

                        @if ($errors->has('email'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('email') }}</strong>
                            </span>
                        @endif
                    </div>
                  </div>
        
                  <div class="form-group mb-3">
                    <div class="form-label-group">
                        <input type="password" id="inputPassword" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="Password" required="required">
                        <label for="inputPassword">{{ __('Password') }}</label>
                        
                        @if ($errors->has('password'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                        @endif

                    </div>
                  </div>

                  <div class="form-group mb-3">
                    <div class="checkbox">
                        <label>
                          <input type="checkbox" name="remember" id="remember" class="{{ old('remember') ? 'checked' : '' }}" value="remember-me">
                          {{ __('Remember Me') }}
                       </label>
                    </div>
                  </div>


                  <button type="submit" class="btn btn-primary w-100">
                      {{ __('Login') }}
                  </button>  
              </form>
              <div class="text-center">
                <a class="d-block small mt-3" href="{{ route('password.request') }}">
                    {{ __('Forgot Your Password?') }}
                </a>
              </div>
          </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="{{asset('ui-vendor/jquery/jquery.min.js')}}"></script>
<script src="{{asset('ui-vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

<!-- Core plugin JavaScript-->
<script src="{{asset('ui-vendor/jquery-easing/jquery.easing.min.js')}}"></script>

  </body>

</html>