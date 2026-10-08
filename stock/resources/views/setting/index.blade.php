@extends('layouts.app')

@section('content')
<style>

</style>
  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Settings</h2>
    </div>

    <div>
      <hr />
      <h5>1. Company Details</h5>
      <form method="POST" action="{{ url('/setting/updateDetails') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

            <!-- first row -->
            <div class="row mt-3">
              <div class="col-4">
                <label class="control-label">{{ __('Company Name') }}</label>
                <input type="text" class="form-control" name="c_name" required="required" value="{{$setting->c_name}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('PAN') }}</label>
                  <input type="text" class="form-control toUpperCase" name="pan" required="required" value="{{$setting->pan}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('GSTIN') }}</label>
                  <input type="text" class="form-control toUpperCase" name="gstin" required="required" value="{{$setting->gstin}}" />
              </div>
            </div>

            <!-- second row -->
            <div class="row mt-3">

              <div class="col-4">
                  <label class="control-label">{{ __('Address1') }}</label>
                  <input type="text" class="form-control" name="address1" required="required" value="{{$setting->address1}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Address2') }}</label>
                  <input type="text" class="form-control" name="address2" required="required" value="{{$setting->address2}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('City') }}</label>
                  <input type="text" class="form-control" name="city" required="required" value="{{$setting->city}}" />
              </div>
            </div>

            <!-- third row -->
            <div class="row mt-3">
              <div class="col-4">
                  <label class="control-label">{{ __('State') }}</label>
                  <input type="text" class="form-control" name="state" required="required" value="{{$setting->state}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Country') }}</label>
                  <input type="text" class="form-control" name="country" required="required" value="{{$setting->country}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Postcode') }}</label>
                  <input type="text" class="form-control" name="postcode" required="required" value="{{$setting->postcode}}" />
              </div>
            </div>

            <!-- fourth row -->
            <div class="row mt-3">
              <div class="col-4">
                  <label class="control-label">{{ __('IEC Code') }}</label>
                  <input type="text" class="form-control toUpperCase" name="iec" required="required" value="{{$setting->iec}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('RBI Code') }}</label>
                  <input type="text" class="form-control toUpperCase" name="rbi" required="required" value="{{$setting->rbi}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('REX Registration') }}</label>
                  <input type="text" class="form-control toUpperCase" name="gsp" required="required" value="{{$setting->gsp}}" />
              </div>
            </div>

            <!-- fifth row -->
            <div class="row mt-3">
              <div class="col-4">
                  <label class="control-label">{{ __('LUT Ref') }}</label>
                  <input type="text" class="form-control toUpperCase" name="lut" required="required" value="{{$setting->lut}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Website') }}</label>
                  <input type="text" class="form-control" name="website" required="required" value="{{$setting->website}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Email') }}</label>
                  <input type="email" class="form-control" name="email" required="required" value="{{$setting->email}}" />
              </div>
            </div>

            <!-- sixth row -->
            <div class="row mt-3">
              <div class="col-4">
                    <label class="control-label">{{ __('Phone 1') }}</label>
                    <input type="text" class="form-control" name="phone1" required="required" value="{{$setting->phone1}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Phone 2') }}</label>
                  <input type="text" class="form-control" name="phone2" value="{{$setting->phone2}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Factory Address') }}</label>
                  <input type="text" class="form-control" name="factory_address" value="{{$setting->factory_address}}" />
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-4">
                    <label class="control-label">{{ __('Carbon Electricty Emission Factor') }}</label>
                    <input type="number" step="any"class="form-control" name="electricity_factor" required="required" value="{{$setting->electricity_factor}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Carbon Distance Emission Factor') }}</label>
                  <input type="number" step="any"class="form-control" name="distance_factor" value="{{$setting->distance_factor}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Carbon Distribution Emission Factor') }}</label>
                  <input type="number" step="any"class="form-control" name="distribution_factor" value="{{$setting->distribution_factor}}" />
              </div>

            </div>
            <div class="row mt-3">
              <div class="col-4">
                    <label class="control-label">{{ __('Petrol Price') }}</label>
                    <input type="number" step="any"class="form-control" name="petrol_price" required="required" value="{{$setting->petrol_price}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Diesel Price') }}</label>
                  <input type="number" step="any"class="form-control" name="diesel_price" value="{{$setting->diesel_price}}" />
              </div>

                   <div class="col-4">
                  <label class="control-label">{{ __('Contractor bill — new finishing rate from') }}</label>
                  <input type="date" class="form-control" name="contractor_finishing_new_rate_from" title="Invoices on or after this date use current finishing rate; before this date use old finishing rate where configured." value="{{ $setting->contractor_finishing_new_rate_from ? \Carbon\Carbon::parse($setting->contractor_finishing_new_rate_from)->format('Y-m-d') : '' }}" />
                  <small class="text-muted">Leave empty to fall back to 2026-04-01.</small>
              </div>

              

            </div>

            <div class="row mt-4">
              <div class="col-12">
                <h6 class="mb-2">Invoice Declaration Templates</h6>
                <small class="text-muted d-block mb-2">Used as defaults for new invoices. Existing invoices keep the declaration saved on them.</small>
              </div>
              <div class="col-12 mt-2">
                <label class="control-label">{{ __('Export Invoice Declaration') }}</label>
                <textarea class="form-control" name="invoice_declaration_export" rows="4" required="required">{{ old('invoice_declaration_export', $setting->invoice_declaration_export) }}</textarea>
              </div>
              <div class="col-12 mt-3">
                <label class="control-label">{{ __('Local / Marketplace Invoice Declaration') }}</label>
                <textarea class="form-control" name="invoice_declaration_local" rows="3" required="required">{{ old('invoice_declaration_local', $setting->invoice_declaration_local) }}</textarea>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-auto">
              <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
              </div>
            </div>
        </div>
      </form>
    </div>

    <!-- company logo -->
    <div class="mt-5">
      <hr />
      <h5>2. Company Logo</h5>
      <div class="row mt-3">
        <div class="col-4">
        @php
                $imageName = $setting->logoUrl; // directly the name like 'image1.jpg'
            @endphp

            @if(array_key_exists($imageName, $fileMap))
                <img class="img-thumbnail"  src="{{ $fileMap[$imageName] }}" alt="Logo Image">
            @else
                <p>Image not found</p>
            @endif
        </div>
      </div>



      <!-- update logo -->
      <div class="row mt-3">
        <div class="col-4">
          <form method="POST" action="{{ url('/setting/updateLogo') }}" enctype="multipart/form-data">
             @csrf
            <div class="form-group">
              <input type="file" class="form-control-file" name="logoUrl" required>
            </div>
            <input class="file btn btn-primary mt-1 w-auto" type="submit" value="Upload Logo" name="upload" /> <br/>
          </form>
        </div>
      </div>
    </div>


      <div class="mt-5">
      <hr />
      <h5>2. Company Sign</h5>
      <div class="row mt-3">
        <div class="col-4">
        @php
                $imageName = $setting->sign_url; // directly the name like 'image1.jpg'
            @endphp

            @if(array_key_exists($imageName, $fileMap))
                <img class="img-thumbnail"  src="{{ $fileMap[$imageName] }}" alt="Logo Image">
            @else
                <p>Image not found</p>
            @endif
        </div>
      </div>

      <!-- update logo -->
      <div class="row mt-3">
        <div class="col-4">
          <form method="POST" action="{{ url('/setting/updateSign') }}" enctype="multipart/form-data">
             @csrf
            <div class="form-group">
              <input type="file" class="form-control-file" name="sign_url" required>
            </div>
            <input class="file btn btn-primary mt-1 w-auto" type="submit" value="Upload Sign" name="upload" /> <br/>
          </form>
        </div>
      </div>
    </div>

    <!-- Login -->
    <div class="mt-5">
      <hr />
      <h5>3. Login E-Mail</h5>
      <form method="POST" action="{{ url('/setting/updateEmail') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <input type="text" class="form-control" name="email" required="required" value="{{$user->email}}" />
            </div>
          </div>

          <!-- submit button -->
          <div class="row mt-3">
            <div class="col-auto">
            <button type="submit" class="btn btn-primary mt-3">Change Login E-mail</button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Change password -->
    <div class="mt-5">
      <hr />
      <h5>4. Change Password</h5>

      <form method="POST" action="{{ url('/setting/updatePassword') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <div class="position-relative">
                <input type="password" class="form-control pe-5" name="oldPass" required="required" placeholder="Old Password" />
                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-4">
              <div class="position-relative">
                <input type="password" class="form-control pe-5" name="newPass" required="required" placeholder="New Password" />
                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="col-4">
              <div class="position-relative">
                <input type="password" class="form-control pe-5" name="newPassConfirm" required="required" placeholder="Confirm Password" />
                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-secondary px-3 py-0 border-0 password-toggle-btn" tabindex="-1" aria-label="Show password">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="row mt-3">
            <button type="submit" class="btn btn-primary mt-3" onclick="return validatePasswordForm(this)">Change Password</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Certificate -->
    <div class="mt-5">
      <hr />
      <h5>5. Certificate</h5>

      <form method="POST" action="{{ url('/setting/updateCertificateDetails') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <img class="img-thumbnail" src="{{ asset('uploads/vriksh-logo.png') }}" alt="Vriksh Logo" />
            </div>
          </div>
          <!-- second row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Name') }}</label>
              <input type="text" class="form-control" name="name" value="{{$certificate->name}}" />
            </div>
          </div>
          <!-- second row -->
          <div class="row mt-3">
            <div class="col-8">
                <label class="control-label">{{ __('Description') }}</label>
                <textarea class="form-control" name="description">{{$certificate->description}}</textarea>
            </div>
          </div>

          <div class="row mt-3">
            <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Update Certificate</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Default Pricing Settings -->
    <div class="mt-5">
      <hr />
      <h5>6. Default Pricing Settings</h5>

      <form method="POST" action="{{ url('/setting/updatePricingSettings') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

          <!-- 2nd row -->
          <div class="row mt-3">
            <div class="col-3">
                <label class="control-label">{{ __('Corner and L rates') }}</label>
                <input type="number" min="0" class="form-control" name="corner_rate" id="corner_rate" step="any" value="{{$setting['corner_rate']}}" />
            </div>
            <div class="col-3">
                <label class="control-label">{{ __('Packaging per sqinch rate') }}</label>
                <input type="number" min="0" class="form-control" name="packaging_per_sqinch_rate" id="packaging_per_sqinch_rate" step="any" value="{{$setting['packaging_per_sqinch_rate']}}" />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('WholeSale Packaging cost setting') }}</label>
                <input type="number" min="0" class="form-control" name="wspackaging" id="wspackaging" step="any" value="{{$setting['wspackaging']}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Dropship Packaging Cost setting') }}</label>
                <input type="number" min="0" class="form-control" name="dspackaging" id="dspackaging" step="any" value="{{$setting['dspackaging']}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('India Shipping Cost setting') }}</label>
                <input type="number" min="0" class="form-control" name="ishippingcost" id="ishippingcost" step="any" value="{{$setting['ishippingcost']}}" />
            </div>
          </div>

          <div class="row mt-3">
            <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Update Pricing Settings</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Volumetric Weight Settings -->
    <div class="mt-5">
      <hr />
      <h5>7. Volumetric Weight Settings</h5>

      <form method="POST" action="{{ url('/setting/updatePricingSettings') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">
          <div class="row mt-3">
            <div class="col-12">
              <h6>Volumetric Weight Settings by Country</h6>
            </div>
          </div>

          <!-- UK Row -->
          <div class="row mt-3">
            <div class="col-3">
              <label class="control-label">{{ __('UK (kg)') }}</label>
              <input type="number" min="0" class="form-control" name="uk_volumetric_weight_kg" id="uk_volumetric_weight_kg" step="any" value="{{$setting['uk_volumetric_weight_kg'] ?? ''}}" />
            </div>
          </div>

          <!-- US Row -->
          <div class="row mt-3">
            <div class="col-3">
              <label class="control-label">{{ __('US (LBS)') }}</label>
              <input type="number" min="0" class="form-control" name="us_volumetric_weight_lbs" id="us_volumetric_weight_lbs" step="any" value="{{$setting['us_volumetric_weight_lbs'] ?? ''}}" />
            </div>
          </div>

          <!-- EU Row -->
          <div class="row mt-3">
            <div class="col-3">
              <label class="control-label">{{ __('EU (kg)') }}</label>
              <input type="number" min="0" class="form-control" name="eu_volumetric_weight_kg" id="eu_volumetric_weight_kg" step="any" value="{{$setting['eu_volumetric_weight_kg'] ?? ''}}" />
            </div>
          </div>

          <!-- Canada Row -->
          <div class="row mt-3">
            <div class="col-3">
              <label class="control-label">{{ __('Canada (LBS)') }}</label>
              <input type="number" min="0" class="form-control" name="canada_volumetric_weight_lbs" id="canada_volumetric_weight_lbs" step="any" value="{{$setting['canada_volumetric_weight_lbs'] ?? ''}}" />
            </div>
          </div>

          <!-- California Row -->
          <div class="row mt-3">
            <div class="col-3">
              <label class="control-label">{{ __('California (kg)') }}</label>
              <input type="number" min="0" class="form-control" name="california_volumetric_weight_kg" id="california_volumetric_weight_kg" step="any" value="{{$setting['california_volumetric_weight_kg'] ?? ''}}" />
            </div>
          </div>

          <!-- Australia Row -->
          <div class="row mt-3">
            <div class="col-3">
              <label class="control-label">{{ __('Australia (kg)') }}</label>
              <input type="number" min="0" class="form-control" name="australia_volumetric_weight_kg" id="australia_volumetric_weight_kg" step="any" value="{{$setting['australia_volumetric_weight_kg'] ?? ''}}" />
            </div>
          </div>

          <!-- India Row -->
          <div class="row mt-3">
            <div class="col-3">
              <label class="control-label">{{ __('India (kg)') }}</label>
              <input type="number" min="0" class="form-control" name="india_volumetric_weight_kg" id="india_volumetric_weight_kg" step="any" value="{{$setting['india_volumetric_weight_kg'] ?? 5000}}" />
            </div>
          </div>

          <div class="row mt-3">
            <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Update Volumetric Weight Settings</button>
          </div>
        </div>
      </form>
    </div>

  </div>

  <!-- USA Stock -->
    <div class="mt-5">
      <hr />
      <h5>8. USA Stock</h5>

      <form method="POST" action="{{ url('/setting/updateUsaRates') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('US Product 1') }}</label>
                <input type="text" class="form-control" name="us_product_1" step="any" value="{{$setting->us_product_1}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('US Product 2') }}</label>
                <input type="text" class="form-control" name="us_product_2" step="any" value="{{$setting->us_product_2}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 1 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="in2047" step="any" value="{{$setting->in2047}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 2 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="in2108" step="any" value="{{$setting->in2108}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Remaining Stock') }}</label>
                <input type="number" min="0" class="form-control" name="usa_remaining_stock" step="any" value="{{$setting->usa_remaining_stock}}" />
            </div>
          </div>

          <div class="row mt-3">
            <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Update USA Stock</button>
          </div>
        </div>
      </form>
    </div>


    <div class="mt-5">
      <hr />
      <h5>9. EU Stock</h5>

      <form method="POST" action="{{ url('/setting/updateEuRates') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('EU Product 1') }}</label>
                <input type="text" class="form-control" name="eu_product_1" step="any" value="{{$setting->eu_product_1}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('EU Product 2') }}</label>
                <input type="text" class="form-control" name="eu_product_2" step="any" value="{{$setting->eu_product_2}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 1 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="eu_product_1_stock" step="any" value="{{$setting->eu_product_1_stock}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 2 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="eu_product_2_stock" step="any" value="{{$setting->eu_product_2_stock}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Remaining Stock') }}</label>
                <input type="number" min="0" class="form-control" name="eu_remaining_stock" step="any" value="{{$setting->eu_remaining_stock}}" />
            </div>
          </div>

          <div class="row mt-3">
            <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Update EU Stock</button>
          </div>
        </div>
      </form>
    </div>

    <div class="mt-5">
      <hr />
      <h5>10. Canada Stock</h5>

      <form method="POST" action="{{ url('/setting/updateCanadaRates') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Canada Product 1') }}</label>
                <input type="text" class="form-control" name="canada_product_1" step="any" value="{{$setting->canada_product_1}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Canada Product 2') }}</label>
                <input type="text" class="form-control" name="canada_product_2" step="any" value="{{$setting->canada_product_2}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 1 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="canada_product_1_stock" step="any" value="{{$setting->canada_product_1_stock}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 2 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="canada_product_2_stock" step="any" value="{{$setting->canada_product_2_stock}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Remaining Stock') }}</label>
                <input type="number" min="0" class="form-control" name="canada_remaining_stock" step="any" value="{{$setting->canada_remaining_stock}}" />
            </div>
          </div>

          <div class="row mt-3">
            <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Update Canada Stock</button>
          </div>
        </div>
      </form>
    </div>

    <div class="mt-5">
      <hr />
      <h5>11. California Stock</h5>

      <form method="POST" action="{{ url('/setting/updateCaliforniaRates') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('California Product 1') }}</label>
                <input type="text" class="form-control" name="california_product_1" step="any" value="{{$setting->california_product_1}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('California Product 2') }}</label>
                <input type="text" class="form-control" name="california_product_2" step="any" value="{{$setting->california_product_2}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 1 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="california_product_1_stock" step="any" value="{{$setting->california_product_1_stock}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 2 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="california_product_2_stock" step="any" value="{{$setting->california_product_2_stock}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Remaining Stock') }}</label>
                <input type="number" min="0" class="form-control" name="california_remaining_stock" step="any" value="{{$setting->california_remaining_stock}}" />
            </div>
          </div>

          <div class="row mt-3">
            <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Update California Stock</button>
          </div>
        </div>
      </form>
    </div>



    
 <div class="mt-5">
      <hr />
      <h5>12. Australia Stock</h5>

      <form method="POST" action="{{ url('/setting/updateAustraliaRates') }}">
        @csrf

        <!-- Form Starts -->
        <div class="form-group"> 
            
          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Australia Product 1') }}</label>
                <input type="text" class="form-control" name="australia_product_1" step="any" value="{{$setting->australia_product_1}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Australia Product 2') }}</label>
                <input type="text" class="form-control" name="australia_product_2" step="any" value="{{$setting->australia_product_2}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 1 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="australia_product_1_stock" step="any" value="{{$setting->australia_product_1_stock}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Product 2 Stock') }}</label>
                <input type="number" min="0" class="form-control" name="australia_product_2_stock" step="any" value="{{$setting->australia_product_2_stock}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Remaining Stock') }}</label>
                <input type="number" min="0" class="form-control" name="australia_remaining_stock" step="any" value="{{$setting->australia_remaining_stock}}" />
            </div>
          </div>

          <div class="row mt-3">
            <button type="submit" class="btn btn-primary mt-3" onclick="return Validate()">Update Australia Stock</button>
          </div>
        </div>      
      </form>
    </div>


    <div class="mt-5" >
      <hr />
      <h5>13. Country Price</h5>

      <form method="POST" action="#">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Profit (%)') }}</label>
                <input type="number"  class="form-control" name="profit_percentage" id="profit_percentage" step="any" value="{{$settings_option['profit_percentage']}}" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Admin Cost (%)') }}</label>
              <input type="number" class="form-control " name="admin_cost_percentage" id="admin_cost_percentage" step="any" value="{{$settings_option['admin_cost_percentage']}}" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('India Admin Cost (%)') }}</label>
              <input type="number" class="form-control" name="india_admin_cost_percentage" id="india_admin_cost_percentage" step="any" value="{{$settings_option['india_admin_cost_percentage'] ?? $settings_option['admin_cost_percentage']}}" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('India Profit (%)') }}</label>
              <input type="number" class="form-control" name="india_profit_percentage" id="india_profit_percentage" step="any" value="{{$settings_option['india_profit_percentage'] ?? $settings_option['profit_percentage']}}" readonly />
            </div>
            </div>
          <div class="row mt-3">


            <div class="col-4">
               <label class="control-label">{{ __('INR To Pound Conversion Rate') }}</label>
                <input type="text" class="form-control" name="inr_to_pound_conversion_rate" id="inr_to_pound_conversion_rate" step="any" value="{{$settings_option['inr_to_pound_conversion_rate']}}" readonly/>
            </div>
             <div class="col-4">
              <label class="control-label">{{ __('INR To Dollar Conversion Rate') }}</label>
                <input type="text" class="form-control" name="inr_to_dollar_conversion_rate" id="inr_to_dollar_conversion_rate" step="any" value="{{$settings_option['inr_to_dollar_conversion_rate']}}" readonly/>
            </div> <div class="col-4">
              <label class="control-label">{{ __('INR To Euro Conversion Rate') }}</label>
                <input type="text" class="form-control" name="inr_to_euro_conversion_rate" id="inr_to_euro_conversion_rate" step="any" value="{{$settings_option['inr_to_euro_conversion_rate']}}" readonly/>
            </div>

<div class="col-4">
              <label class="control-label">{{ __('INR To Canadian Dollar Conversion Rate') }}</label>
                <input type="text" class="form-control" name="inr_to_canadian_conversion_rate" id="inr_to_canadian_conversion_rate" step="any" value="{{$settings_option['inr_to_canadian_conversion_rate']}}" readonly/>
            </div>

             <div class="col-4">
              <label class="control-label">{{ __('INR To Australia Conversion Rate') }}</label>
                <input type="text" class="form-control" name="inr_to_australia_conversion_rate" id="inr_to_australia_conversion_rate" step="any" value="{{$settings_option['inr_to_australia_conversion_rate']}}" readonly/>
            </div>

          </div>
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Shipping Cost UK') }}</label>
                <input type="number" min="0" class="form-control" name="shipping_cost_uk" id="shipping_cost_uk" step="any" value="{{$settings_option['shipping_cost_uk']}}" readonly/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Storage Cost UK(1 month)') }}</label>
                <input type="number" min="0" class="form-control" name="storage_cost_uk" id="storage_cost_uk" step="any" value="{{$settings_option['storage_cost_uk']}}" readonly/>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Shipping Cost US') }}</label>
                <input type="number" min="0" class="form-control" name="shipping_cost_us" id="shipping_cost_us" step="any" value="{{$settings_option['shipping_cost_us']}}" readonly/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Storage Cost US (1 month)') }}</label>
                <input type="number" min="0" class="form-control" name="storage_cost_us" id="storage_cost_us" step="any" value="{{$settings_option['storage_cost_us']}}" readonly/>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Shipping Cost EU') }}</label>
                <input type="number" min="0" class="form-control" name="shipping_cost_eu" id="shipping_cost_eu" step="any" value="{{$settings_option['shipping_cost_eu']}}" readonly/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Storage Cost EU (1 month)') }}</label>
                <input type="number" min="0" class="form-control" name="storage_cost_eu" id="storage_cost_eu" step="any" value="{{$settings_option['storage_cost_eu']}}" readonly/>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Shipping Cost Canada') }}</label>
                <input type="number" min="0" class="form-control" name="shipping_cost_canada" id="shipping_cost_canada" step="any" value="{{$settings_option['shipping_cost_canada']}}" readonly/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Storage Cost Canada (1 month)') }}</label>
                <input type="number" min="0" class="form-control" name="storage_cost_canada" id="storage_cost_canada" step="any" value="{{$settings_option['storage_cost_canada']}}" readonly/>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Shipping Cost California') }}</label>
                <input type="number" min="0" class="form-control" name="shipping_cost_california" id="shipping_cost_california" step="any" value="{{$settings_option['shipping_cost_california']}}" readonly/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Storage Cost California (1 month)') }}</label>
                <input type="number" min="0" class="form-control" name="storage_cost_california" id="storage_cost_california" step="any" value="{{$settings_option['storage_cost_california']}}" readonly/>
            </div>
          </div>

             <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Shipping Cost Australia') }}</label>
                <input type="number" min="0" class="form-control" name="shipping_cost_australia" id="shipping_cost_australia" step="any" value="{{$settings_option['shipping_cost_australia']}}" readonly/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Storage Cost Australia (1 month)') }}</label>
                <input type="number" min="0" class="form-control" name="storage_cost_australia" id="storage_cost_australia" step="any" value="{{$settings_option['storage_cost_australia']}}" readonly/>
            </div>
          </div>


          <div class="row mt-3">
            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalUploadPrice">Update Country Pricing Settings</button>
          </div>
        </div>
      </form>
    </div>

    @php
      $backmonthSettingsUsers = [
        'aaura1177@gmail.com',
        'info@globalvisioncompany.com',
        'finance@artisanfurniture.net',
      ];
      $canManageBackmonthSettings = in_array(strtolower(auth()->user()->email ?? ''), $backmonthSettingsUsers, true);
      $backmonthEditDayLimit = $setting->backmonth_edit_day_limit ?? 3;
      $backmonthFactoryCanCumulative = (int) ($setting->backmonth_factory_can_cumulative ?? 0) === 1;
      $backmonthFactoryCanIndividual = (int) ($setting->backmonth_factory_can_individual ?? 0) === 1;
    @endphp

    @if($canManageBackmonthSettings)
      <div class="mt-5">
        <hr />
        <h5>14. Backmonth Stock Settings</h5>

        <form method="POST" action="{{ url('/setting/updateBackmonthSettings') }}">
          @csrf
          <div class="form-group">
            <div class="row mt-3">
              <div class="col-4">
                <label class="control-label">{{ __('Editable Till Day Of Current Month') }}</label>
                <input type="number" min="1" max="31" class="form-control" name="backmonth_edit_day_limit" value="{{ $backmonthEditDayLimit }}" required />
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="backmonth_factory_can_cumulative" name="backmonth_factory_can_cumulative" {{ $backmonthFactoryCanCumulative ? 'checked' : '' }}>
                  <label class="form-check-label" for="backmonth_factory_can_cumulative">
                    Allow factory cumulative update
                  </label>
                </div>
              </div>
              <div class="col-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="backmonth_factory_can_individual" name="backmonth_factory_can_individual" {{ $backmonthFactoryCanIndividual ? 'checked' : '' }}>
                  <label class="form-check-label" for="backmonth_factory_can_individual">
                    Allow factory individual update
                  </label>
                </div>
              </div>
            </div>

            <div class="row mt-3">
              <button type="submit" class="btn btn-primary mt-3">Save Backmonth Settings</button>
            </div>
          </div>
        </form>
      </div>
    @endif
  </div>
   <!-- MODAL FOR DEFAULT PRICE -->
 <div class="modal fade" id="modalUploadPrice" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="modalUploadPriceForm" action="{{ url('/setting/update-country-conversion-rate') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-header">
        <h4 class="modal-title">Update Pricing Setting</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label for="select_setting" class="form-label">Setting</label>
            <select class="selectpicker form-control" data-live-search="true" data-width="100%" name="select_setting[]" id="select_setting" onchange="getPrice(this.value)" required>
              <option value="" selected disabled>Select Settings</option>
              <?php
              $defaultPriceNameArray = [
                  'profit_percentage' => 'Profit Percentage',
                  'admin_cost_percentage' => 'Admin Cost Percentage',
                  'india_profit_percentage' => 'India Profit Percentage',
                  'india_admin_cost_percentage' => 'India Admin Cost Percentage',
                  'inr_to_pound_conversion_rate' => 'INR To Pound Conversion Rate',
                  'inr_to_dollar_conversion_rate' => 'INR To Dollar Conversion Rate',
'inr_to_canadian_conversion_rate' => 'INR To Canadian Dollar Conversion Rate',
                  'inr_to_euro_conversion_rate' => 'INR To Euro Conversion Rate',
                    'inr_to_australia_conversion_rate' => 'INR To Australia Conversion Rate',
                  'shipping_cost_uk' => 'Shipping Cost UK',
                  'shipping_cost_us' => 'Shipping Cost US',
                  'shipping_cost_eu' => 'Shipping Cost EU',
                  'shipping_cost_canada' => 'Shipping Cost Canada',
                  'shipping_cost_california' => 'Shipping Cost California',
                    'shipping_cost_australia' => 'Shipping Cost Australia',
                  'storage_cost_uk' => 'Storage Cost UK (1 month)',
                  'storage_cost_us' => 'Storage Cost US (1 month)',
                  'storage_cost_eu' => 'Storage Cost EU (1 month)',
                  'storage_cost_canada' => 'Storage Cost Canada (1 month)',
                  'storage_cost_california' => 'Storage Cost California (1 month)',
                    'storage_cost_australia' => 'Storage Cost Australia (1 month)',
                    'wspackaging' => 'WholeSale Packaging Cost Setting',
                  'dspackaging' => 'Dropship Packaging Cost Setting',
                  'ishippingcost' => 'India Shipping Cost Setting',
              ];

              foreach ($defaultPriceNameArray as $key => $value) { ?>
                  <option value="<?=$key?>"><?=$value?></option>
              <?php } ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="price_value" class="form-label">Value</label>
            <input type="text" class="form-control" name="price_value" id="price_value" step="any" value="{{$setting->eu_product_1}}" placeholder="Enter value" required/>
        </div>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="update_all" name="update_all" value="1">
          <label for="update_all" class="form-check-label">Update All old Prices with New value</label>
        </div>
        {{-- TEMPORARY: to be removed once India columns are seeded everywhere. --}}
        <div class="form-check mt-2 d-none" id="indiaColumnsAllRowsWrap">
          <input type="checkbox" class="form-check-input" id="india_columns_all_rows" name="india_columns_all_rows" value="1">
          <label for="india_columns_all_rows" class="form-check-label">
            Update India Admin/Profit columns on all pricing rows (temporary)
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" onclick="return validate()">Update</button>
      </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('footer')

<!-- Script Start for Show PASSWORD -->
<script type="text/javascript">

  $(function () {
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

    $('#modalUploadPrice').on('shown.bs.modal', function () {
      var $sel = $('#select_setting');
      if ($sel.length && $.fn.selectpicker) {
        $sel.selectpicker('render');
        $sel.selectpicker('refresh');
      }
    });
  });

  function validatePasswordForm(btn) {
    var $form = $(btn).closest('form');
    var password = $form.find('input[name="newPass"]').val();
    var confirmPassword = $form.find('input[name="newPassConfirm"]').val();
    if (password != confirmPassword) {
      alert("Confirm Passwords do not match.");
      return false;
    }
    return true;
  }

  function getPrice(priceId){
    $('#price_value').val($('#'+priceId).val())

    // TEMPORARY: India-only percentage settings can also write their columns everywhere.
    var isIndiaPercentSetting = priceId === 'india_admin_cost_percentage' || priceId === 'india_profit_percentage';
    $('#indiaColumnsAllRowsWrap').toggleClass('d-none', !isIndiaPercentSetting);
    if (!isIndiaPercentSetting) {
      $('#india_columns_all_rows').prop('checked', false);
    }
  }

</script>
<!-- Script End -->

@endsection