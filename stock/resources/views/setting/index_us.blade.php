@extends('layouts.app')

@section('content')
    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Settings</h2>
        </div>

        <div>
            <hr />
            <h5>1. Company Details</h5>
            <form method="POST" action="{{ url('/setting/updateDetailsUs') }}">
                @csrf

                <!-- Form Starts -->
                <div class="form-group">

                    <!-- first row -->


                    <!-- second row -->
                    <div class="row mt-3">

                        <div class="col-4">
                            <label class="control-label">{{ __('Address1') }}</label>
                            <input type="text" class="form-control" name="address1" required="required"
                                value="{{ $setting->address1 }}" />
                        </div>
                        <div class="col-4">
                            <label class="control-label">{{ __('Address2') }}</label>
                            <input type="text" class="form-control" name="address2" required="required"
                                value="{{ $setting->address2 }}" />
                        </div>
                        <div class="col-4">
                            <label class="control-label">{{ __('City') }}</label>
                            <input type="text" class="form-control" name="city" required="required"
                                value="{{ $setting->city }}" />
                        </div>
                    </div>

                    <!-- third row -->
                    <div class="row mt-3">
                        <div class="col-4">
                            <label class="control-label">{{ __('State') }}</label>
                            <input type="text" class="form-control" name="state" required="required"
                                value="{{ $setting->state }}" />
                        </div>
                        <div class="col-4">
                            <label class="control-label">{{ __('Country') }}</label>
                            <input type="text" class="form-control" name="country" required="required"
                                value="{{ $setting->country }}" />
                        </div>
                        <div class="col-4">
                            <label class="control-label">{{ __('Postcode') }}</label>
                            <input type="text" class="form-control" name="postcode" required="required"
                                value="{{ $setting->postcode }}" />
                        </div>
                    </div>



                    <!-- sixth row -->
                    <div class="row mt-3">
                        <div class="col-4">
                            <label class="control-label">{{ __('Phone 1') }}</label>
                            <input type="text" class="form-control" name="phone1" required="required"
                                value="{{ $setting->phone1 }}" />
                        </div>

                        <div class="col-4">
                            <label class="control-label">{{ __('VAT') }}</label>
                            <input type="text" class="form-control" name="vat" required="required"
                                value="{{ $setting->vat }}" />
                        </div>

                    </div>

                    <!-- sixth row -->
                    <div class="row mt-3">
                        <div class="col-8">
                            <label class="control-label">{{ __('Bank details Pound') }}</label>
                            <textarea class="form-control" name="bank_details" required="required">{{ $setting->bank_details }}</textarea>
                        </div>

                    </div>

                    <div class="row mt-3">
                        <div class="col-8">
                            <label class="control-label">{{ __('Bank details Euro') }}</label>
                            <textarea class="form-control" name="bank_details_euro" required="required">{{ $setting->bank_details_euro }}</textarea>
                        </div>

                    </div>

                    <div class="row mt-3">
                        <div class="col-8">
                            <label class="control-label">{{ __('Bank details Dollar') }}</label>
                            <textarea class="form-control" name="bank_details_dollar" required="required">{{ $setting->bank_details_dollar }}</textarea>
                        </div>

                    </div>

                    <div class="row mt-3">
                        <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Certificate -->
        <div class="mt-5">
            <hr />
            <h5>5. Certificate</h5>

            <form method="POST" action="{{ url('/setting/updateCertificateDetailsUs') }}">
                @csrf

                <!-- Form Starts -->
                <div class="form-group">

                    <!-- first row -->
                    <div class="row mt-3">
                        <div class="col-4">
                            <img class="img-thumbnail" src="{{ Storage::disk('s3')->url('stock/vriksh-logo.png') }}" alt="Vriksh Logo" />

                        </div>
                    </div>
                    <!-- second row -->
                    <div class="row mt-3">
                        <div class="col-4">
                            <label class="control-label">{{ __('Name') }}</label>
                            <input type="text" class="form-control" name="name"
                                value="{{ $certificate->name ?? ''  }}" />

                        </div>
                    </div>
                    <!-- second row -->
                    <div class="row mt-3">
                        <div class="col-8">
                            <label class="control-label">{{ __('Description') }}</label>
                            <textarea class="form-control" name="description">{{ $certificate->description ?? ''  }}</textarea>

                        </div>
                    </div>

                    <div class="row mt-3">
                        <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Update
                            Certificate</button>
                    </div>
                </div>
            </form>
        </div>


    </div>
@endsection

@section('footer')
    <!-- Script Start for Show PASSWORD -->
    <script type="text/javascript">
        function showPassword() {
            var x = document.getElementById("password");
            var i = document.getElementById("eye");
            if (x.type === "password") {
                x.type = "text";
                i.style.color = "#337ab7";

            } else {
                x.type = "password";
                i.style.color = "#000";
            }
        }

        function Validate() {

            var password = document.getElementById("password").value;
            console.log(password);
            var confirmPassword = document.getElementById("confirmpassword").value;
            console.log(confirmpassword);
            if (password != confirmPassword) {
                alert("Comfirm Passwords not match.");
                return false;
            }
            return true;
        }
    </script>
    <!-- Script End -->
@endsection
