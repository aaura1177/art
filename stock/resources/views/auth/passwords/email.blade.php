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
    <link href="{{ asset('ui-vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Custom fonts for this template-->
    <link href="{{ asset('ui-vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">

    <!-- Custom styles for this template-->
    <link href="{{ asset('css/sb-admin.css') }}" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">

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
              @if (session('status'))
                  <div class="alert alert-success" role="alert">
                      {{ session('status') }}
                  </div>
              @endif

              <form id="password-reset-form">
                  @csrf

                  <div class="form-group">
                    <div class="form-label-group">
                        <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="Email address" required autofocus="autofocus" />
                        <label for="email">{{ __('E-Mail Address') }}</label>

                          @if ($errors->has('email'))
                              <span class="invalid-feedback" role="alert">
                                  <strong>{{ $errors->first('email') }}</strong>
                              </span>
                          @endif
                    </div>
                  </div>

                  <button type="button" onclick="sendResetLink()" class="btn btn-primary w-100">
                      {{ __('Send Password Reset Link') }}
                  </button>
              </form>
          </div>
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="{{ asset('ui-vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('ui-vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

<!-- Core plugin JavaScript-->
<script src="{{ asset('ui-vendor/jquery-easing/jquery.easing.min.js') }}"></script>

<script>
    function sendResetLink() {
        const email = document.getElementById('email').value;

        fetch('{{ url("/api/password/email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ email })
        })
        .then(response => {
            if (response.status === 404) {
                return response.json().then(data => {
                    alert(data.message);  
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.message === 'Password reset link has been sent to your email') {
                
                alert('We have e-mailed your password reset link! .');
            } else if (data.message === 'Failed to send reset link') {
                alert('Failed to send reset link.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to send reset link.');
        });
    }
</script>

</body>

</html>
