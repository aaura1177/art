@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
    <h2>Add New User</h2>
    </div>

    <div>
    <hr />
    <form method="POST" action="{{ url('/setting/createuser') }}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group">

        <!-- first row -->
        <div class="row mt-3">
          <div class="col-4">
          <label class="control-label">{{ __('First Name') }}</label>
          <input type="text" class="form-control" name="firstname" required="required" />
          </div>
          <div class="col-4">
          <label class="control-label">{{ __('Last Name') }}</label>
          <input type="text" class="form-control" name="lastname" required="required" />
          </div>
          <div class="col-4">
          <label class="control-label">{{ __('E-Mail') }}</label>
          <input type="email" class="form-control" name="email" required="required" />
          </div>
        </div>

        <!-- second row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Password') }}</label>
            <div class="position-relative">
                <input type="password" class="form-control pe-5" name="password" id="password" required="required" />
              <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                <i class="fa fa-eye"></i>
              </button>
            </div>
          </div>
            <div class="col-4">
              <label class="control-label">{{ __('Confirm Password') }}</label>
              <div class="position-relative">
                <input type="password" class="form-control pe-5" name="password_confirm" id="confirmpassword" required="required" />
                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
            
        </div>

        <!-- third row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Select Role') }}</label>
            
            <select name="roles[]" class="form-control" multiple onchange="ShowFields(this)">
                @foreach($roles as $key => $value)
                    <option value="{{ $key }}" data-name="{{$value}}">{{ $value }}</option>
                @endforeach
            </select>


          </div>
       
          <div class="col-4" id="supplier_select" style="display:none;">
            <label class="control-label">{{ __('Supplier') }}</label>
            <a href="{{ url('/supplier/create')}}" style="float: right;" target="_blank"> (+New)</a>
            <select type="text" class="selectpicker" data-live-search="true" name="supplier_id">
                <option value="" selected disabled>Select Supplier</option>
                @if(isset($supplier)) 
                  @foreach($supplier as $key => $supplier)
                    <option value="{{$supplier->id}}">
                      {{$supplier->c_name}}
                    </option>
                  @endforeach 
                @endif
            </select>
          </div>
        </div>

        <div class="row mt-3">
          <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Add User</button>
        </div>
      </div>
    </form>

      <!-- <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="0" /><span>Admin</span><br>

      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="1" /><span>Factory</span><br>

      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="2" /><span>Pricing Manager</span><br>

      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="3" /><span>Office</span><br>

      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="4" /><span>Packing</span><br>

      <label class="control-label"></label>
      <input id="supplier" type="radio" name="role" required="required"
        style="cursor: pointer; margin-right: 0.5rem;" value="5" /><span>Supplier</span><br>

      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="6" /><span>Quality</span><br>
      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="9" /><span>Stockadmin</span><br>
      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="8" /><span>UK Manager</span><br>
      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="10" /><span>US Manager</span><br>
      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="11" /><span>EU Manager</span><br>
      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="12" /><span>CANADA Manager</span><br>
      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="13" /><span>CALIFORNIA Manager</span><br>

      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="14" /><span>Procurement Manager</span><br>

      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="15" /><span>Warehouse User</span><br>

      <label class="control-label"></label>
      <input type="radio" name="role" required="required" style="cursor: pointer; margin-right: 0.5rem;"
        value="16" /><span>Accounts</span><br> -->




      
    </div>
  </div>
@endsection

@section('footer')

  <!-- Script Start for Show PASSWORD -->
  <script type="text/javascript">

    function ShowFields(selectElement) 
    {
        // Get all selected options
        const selectedOptions = Array.from(selectElement.selectedOptions);
        // Check if any selected option is "supplier"
        const isSupplierSelected = selectedOptions.some(option => option.dataset.name === "supplier");
        // Toggle visibility based on selection
        document.getElementById("supplier_select").style.display = isSupplierSelected ? "block" : "none";
    }

    $(function () {
    $("input[name='role']").click(function () {
      if ($("#supplier:checked").val()) {
      $("#supplier_select").fadeIn();
      } else {
      $("#supplier_select").fadeOut();
      }
    });

    $(document).on('click', '.password-toggle-btn', function () {
      var $btn = $(this);
      var $input = $btn.siblings('input');
      var $icon = $btn.find('i');
      if ($input.attr('type') === 'password') {
        $input.attr('type', 'text');
        $icon.addClass('text-primary');
      } else {
        $input.attr('type', 'password');
        $icon.removeClass('text-primary');
      }
    });
    });

    function Validate() {
    var password = document.getElementById("password").value;
    var confirmPassword = document.getElementById("confirmpassword").value;
    if (password != confirmPassword) {
      alert("Passwords do not match.");
      return false;
    }
    return true;
    }
  </script>
  <!-- Script End -->

@endsection