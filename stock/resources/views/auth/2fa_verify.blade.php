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
    <script>
        @hasrole('ukmanager')
            window.location.href = "{{ url('erp-manager') }}";
        @endhasrole
    </script>

</head>

<body class="gvd-bg">
    <div class="container">
        <div class="row justify-content-md-center">
            <div class="col-md-8 ">
                <div class="card">
                    <div class="card-header">Two Factor Authentication</div>
                    <div class="card-body">
                        <p>Two factor authentication (2FA) strengthens access security by requiring two methods (also
                            referred to as factors) to verify your identity. Two factor authentication protects against
                            phishing, social engineering and password brute force attacks and secures your logins from
                            attackers exploiting weak or stolen credentials.</p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        Enter the pin from Google Authenticator app:<br /><br />
                        <form class="form-horizontal" action="{{ route('2faVerify') }}" method="POST">
                            {{ csrf_field() }}
                            <div class="form-group mb-3{{ $errors->has('one_time_password-code') ? ' has-error' : '' }}">
                                <label for="one_time_password" class="control-label mb-1">One Time Password</label>
                                <input id="one_time_password" name="one_time_password" class="form-control"
                                    style="max-width: 220px;" type="text" required />
                            </div>
                            <button class="btn btn-primary" type="submit">Authenticate</button>


                        </form>
                        <div class=" mt-3">
                            <form method="POST" action="{{ route('logout2fa') }}">
                                @csrf
                                <button class="btn btn-danger" type="submit">Back to Login</button>
                            </form>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('ui-vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('ui-vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('ui-vendor/jquery-easing/jquery.easing.min.js') }}"></script>

</body>

</html>
