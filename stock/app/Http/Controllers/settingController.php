<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\setting;
use App\Notification;
use App\settinguk;
use App\settingus;
use App\settingeu;
use App\certificate;
use App\settingcanada;
use App\settingcalifornia;
use App\user;
use App\LoginSecurity;
use App\supplier;
use App\SettingsOption;
use App\pricingTable;
use App\Http\Controllers\UpdatePriceController;
use \auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class settingController extends Controller
{
    private const BACKMONTH_SETTINGS_ALLOWED_EMAILS = [
        'aaura1177@gmail.com',
        'info@globalvisioncompany.com',
        'finance@artisanfurniture.net',
    ];

    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function index()
    {
        $userid = \auth::user()->id;
        $user = user::find($userid);
        $setting = setting::get()->first();
        $certificate = certificate::get()->first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
         $settingsOption = SettingsOption::get()->toArray();
        $settingsOptionArray = [];
        foreach ($settingsOption as $item) {
            $settingsOptionArray[$item['setting_key']] = $item['setting_value'];
        }
        return view('setting/index', ['user'=>$user, 'setting'=>$setting, 'certificate'=> $certificate, 'settings_option'=> $settingsOptionArray, 'fileMap' => $fileMap]);

    }

    public function index_uk()
    {
        $userid = \auth::user()->id;
        $user = user::find($userid);
        $setting = settinguk::get()->first();
        $certificate = certificate::where('id',2)->first();
        return view('setting/index_uk', ['user'=>$user, 'setting'=>$setting, 'certificate'=> $certificate]);

    }
    public function index_us()
    {
        $userid = \auth::user()->id;
        $user = user::find($userid);
        $setting = settingus::get()->first();
        $certificate = certificate::where('id',2)->first();
        return view('setting/index_us', ['user'=>$user, 'setting'=>$setting, 'certificate'=> $certificate]);

    }

    public function index_eu()
    {
        $userid = \auth::user()->id;
        $user = user::find($userid);
        $setting = settingeu::get()->first();
        $certificate = certificate::where('id',3)->first();
        return view('setting/index_eu', ['user'=>$user, 'setting'=>$setting, 'certificate'=> $certificate]);

    }

    public function index_canada()
    {
        $userid = \auth::user()->id;
        $user = user::find($userid);
        $setting = settingcanada::get()->first();
        $certificate = certificate::where('id',4)->first();
        return view('setting/index_canada', ['user'=>$user, 'setting'=>$setting, 'certificate'=> $certificate]);
    }
    public function index_california()
    {
        $userid = \auth::user()->id;
        $user = user::find($userid);
        $setting = settingcalifornia::get()->first();
        $certificate = certificate::where('id',5)->first();
        return view('setting/index_california', ['user'=>$user, 'setting'=>$setting, 'certificate'=> $certificate]);

    }

    public function viewuser()
    {
        $user = user::where('id', '!=', 1)->orderByDesc('id')->get();
        $roles = Role::orderBy('name')->pluck('name')->toArray();
        return view('setting/viewuser', ['user'=>$user, 'roles' => $roles]);

    }

    public function create()
    {
        $roles = Role::pluck('name','name')->all();
                $supplier = supplier::all();
        return view('setting/createuser',['supplier'=>$supplier,'roles'=>$roles]);
    }

    public function storeuser(Request $request)
    {
        $userData = [
            'firstname' => $request['firstname'],
            'lastname'  => $request['lastname'],
            'email'     => $request['email'],
            'password'  => Hash::make($request['password']),
            'role' => implode(",",$request->input('roles'))
        ];

        // Add supplier_id only if the role is supplier
        if (isset($request['supplier_id']))
        {
            $userData['supplier_id'] = $request['supplier_id'];
        }

        $user = User::create($userData);
        $user->assignRole($request->input('roles'));

        Notification::create([
            'user_id' => Auth::user()->id,
            'notification' => 'New User with name- '. $request['firstname'] ." ". $request['lastname'] .' has been created by the admin '. Auth::user()->firstname." ".Auth::user()->lastname,
            'is_read' => 0
        ]);
      return redirect('/setting/viewuser')->with('success', 'User was added successfully.');
    }

    public function updateDetails(Request $request)
    {
        $setting = setting::get()->first();

         if ($setting) {
            $setting->c_name=$request['c_name'];
            $setting->pan=strtoupper($request['pan']);
            $setting->gstin=strtoupper($request['gstin']);
            $setting->address1=$request['address1'];
            $setting->address2=$request['address2'];
            $setting->city=$request['city'];
            $setting->state=$request['state'];
            $setting->country=$request['country'];
            $setting->postcode=$request['postcode'];
            $setting->iec=strtoupper($request['iec']);
            $setting->rbi=strtoupper($request['rbi']);
            $setting->gsp=strtoupper($request['gsp']);
            $setting->lut=strtoupper($request['lut']);
            $setting->website=$request['website'];
            $setting->email=$request['email'];
            $setting->phone1=$request['phone1'];
            $setting->phone2=$request['phone2'];
            $setting->factory_address=$request['factory_address'];
            $setting->invoice_declaration_export = $request->input('invoice_declaration_export');
            $setting->invoice_declaration_local = $request->input('invoice_declaration_local');
            $setting->electricity_factor=$request['electricity_factor'];
            $setting->distance_factor=$request['distance_factor'];
            $setting->petrol_price=$request['petrol_price'];
            $setting->diesel_price=$request['diesel_price'];
              $setting->contractor_finishing_new_rate_from = $request->filled('contractor_finishing_new_rate_from')
                ? $request->input('contractor_finishing_new_rate_from')
                : null;



            if ($setting->save()) {
                Notification::create([
                    'user_id' => Auth::user()->id,
                    'notification' => 'Details updated successfully has been created by the admin '. Auth::user()->firstname." ".Auth::user()->lastname,
                    'is_read' => 0
                ]);
                return redirect('setting')->with('success', 'Details updated successfully.');
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating details.');
        }
    }

    public function updateDetailsUk(Request $request)
    {
        $setting = settinguk::get()->first();

         if ($setting) {
            $setting->vat=$request['vat'];
            $setting->address1=$request['address1'];
            $setting->address2=$request['address2'];
            $setting->city=$request['city'];
            $setting->state=$request['state'];
            $setting->country=$request['country'];
            $setting->postcode=$request['postcode'];
            $setting->phone1=$request['phone1'];
            $setting->bank_details=$request['bank_details'];
            $setting->bank_details_euro=$request['bank_details_euro'];
            $setting->bank_details_dollar=$request['bank_details_dollar'];

            if ($setting->save()) {
                return redirect('settings_uk')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_uk')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_uk')->with('danger', 'Error occurred while updating details.');
        }
    }

    public function updateDetailsUs(Request $request)
    {
        $setting = settingus::get()->first();

         if ($setting) {
            $setting->vat=$request['vat'];
            $setting->address1=$request['address1'];
            $setting->address2=$request['address2'];
            $setting->city=$request['city'];
            $setting->state=$request['state'];
            $setting->country=$request['country'];
            $setting->postcode=$request['postcode'];
            $setting->phone1=$request['phone1'];
            $setting->bank_details=$request['bank_details'];
            $setting->bank_details_euro=$request['bank_details_euro'];
            $setting->bank_details_dollar=$request['bank_details_dollar'];

            if ($setting->save()) {
                return redirect('settings_us')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_us')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_us')->with('danger', 'Error occurred while updating details.');
        }
    }

    public function updateDetailsEu(Request $request)
    {
        $setting = settingeu::get()->first();

         if ($setting) {
            $setting->vat=$request['vat'];
            $setting->address1=$request['address1'];
            $setting->address2=$request['address2'];
            $setting->city=$request['city'];
            $setting->state=$request['state'];
            $setting->country=$request['country'];
            $setting->postcode=$request['postcode'];
            $setting->phone1=$request['phone1'];
            $setting->bank_details=$request['bank_details'];
            $setting->bank_details_euro=$request['bank_details_euro'];
            $setting->bank_details_dollar=$request['bank_details_dollar'];

            if ($setting->save()) {
                return redirect('settings_eu')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_eu')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_eu')->with('danger', 'Error occurred while updating details.');
        }
    }


    public function updateDetailsCanada(Request $request)
    {
        $setting = settingcanada::get()->first();

         if ($setting) {
            $setting->vat=$request['vat'];
            $setting->address1=$request['address1'];
            $setting->address2=$request['address2'];
            $setting->city=$request['city'];
            $setting->state=$request['state'];
            $setting->country=$request['country'];
            $setting->postcode=$request['postcode'];
            $setting->phone1=$request['phone1'];
            $setting->bank_details=$request['bank_details'];
            $setting->bank_details_euro=$request['bank_details_euro'];
            $setting->bank_details_dollar=$request['bank_details_dollar'];

            if ($setting->save()) {
                return redirect('settings_canada')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_canada')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_canada')->with('danger', 'Error occurred while updating details.');
        }
    }


    public function updateDetailsCalifornia(Request $request)
    {
        $setting = settingcalifornia::get()->first();

         if ($setting) {
            $setting->vat=$request['vat'];
            $setting->address1=$request['address1'];
            $setting->address2=$request['address2'];
            $setting->city=$request['city'];
            $setting->state=$request['state'];
            $setting->country=$request['country'];
            $setting->postcode=$request['postcode'];
            $setting->phone1=$request['phone1'];
            $setting->bank_details=$request['bank_details'];
            $setting->bank_details_euro=$request['bank_details_euro'];
            $setting->bank_details_dollar=$request['bank_details_dollar'];

            if ($setting->save()) {
                return redirect('settings_california')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_california')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_california')->with('danger', 'Error occurred while updating details.');
        }
    }



    public function updateEmail(Request $request)
    {
        $userid = \auth::user()->id;
        $user = user::find($userid);

         if ($user) {
             $user->email=$request['email'];
            if ($user->save()) {
                Notification::create([
                    'user_id' => Auth::user()->id,
                    'notification' => 'Login E-Mail was updated successfully by the admin '. Auth::user()->firstname." ".Auth::user()->lastname,
                    'is_read' => 0
                ]);
                return redirect('setting')->with('success', 'Login E-Mail was updated successfully.');
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating the e-mail.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating the e-mail.');
        }
    }

    public function updateLogo(Request $request)
    {
        $setting = setting::first();

        $request->validate([
            'logoUrl' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('logoUrl')) {
            // Get the uploaded file
            $file = $request->file('logoUrl');

            $existingImage = $setting->logoUrl;

            $oldFilePath = 'stock/' . $existingImage;
            if (Storage::disk('s3')->exists($oldFilePath)) {
                Storage::disk('s3')->delete($oldFilePath);
            }

            if ($file) {
                $filePath = Storage::disk('s3')->put('stock', $file);

                $imageFileName = basename($filePath);
            } else {
                $imageFileName = $existingImage;
            }
        } else {
            $imageFileName = $setting->logoUrl ?? 'default.jpg';
        }

        $setting->logoUrl = $imageFileName;

        if ($setting->save()) {
            Notification::create([
                'user_id' => Auth::user()->id,
                'notification' => 'Logo updated successfully by the admin ' . Auth::user()->firstname . " " . Auth::user()->lastname,
                'is_read' => 0
            ]);

            return redirect('setting')->with('success', 'Logo updated successfully.');
        } else {
            return redirect('setting')->with('danger', 'Error occurred while updating the logo.');
        }
    }

    public function updatePassword(Request $request)
    {
        $userid = \auth::user()->id;
        $user = user::find($userid)->first();
        if (Hash::check($request['oldPass'], $user->password)) {
            $user->password = Hash::make($request['newPass']);

            if ($user->save()) {
                Notification::create([
                    'user_id' => Auth::user()->id,
                    'notification' => 'Password updated successfully by the admin '. Auth::user()->firstname." ".Auth::user()->lastname,
                    'is_read' => 0
                ]);
            return redirect('setting')->with('success', 'Password updated successfully.');
            }
        } else{
            return redirect('setting')->with('danger', 'Old Password does not match.');
        }
    }

     public function updateCertificateDetails(Request $request)
    {
        $certificate = certificate::get()->first();

         if ($certificate) {
            $certificate->name=$request['name'];
            $certificate->description=$request['description'];

            if ($certificate->save()) {
                Notification::create([
                    'user_id' => Auth::user()->id,
                    'notification' => 'Certifcation details  successfully updated by the admin '. Auth::user()->firstname." ".Auth::user()->lastname,
                    'is_read' => 0
                ]);
                return redirect('setting')->with('success', 'Details updated successfully.');
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating details.');
        }
    }

    public function updateCertificateDetailsUk(Request $request)
    {
        $certificate = certificate::where('id',2)->first();

         if ($certificate) {
            $certificate->name=$request['name'];
            $certificate->description=$request['description'];

            if ($certificate->save()) {
                return redirect('settings_uk')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_uk')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_uk')->with('danger', 'Error occurred while updating details.');
        }
    }

    public function updateCertificateDetailsUs(Request $request)
    {
        $certificate = certificate::where('id',2)->first();

         if ($certificate) {
            $certificate->name=$request['name'];
            $certificate->description=$request['description'];

            if ($certificate->save()) {
                return redirect('settings_us')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_us')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_us')->with('danger', 'Error occurred while updating details.');
        }
    }

    public function updateCertificateDetailsEu(Request $request)
    {
        $certificate = certificate::where('id',3)->first();

         if ($certificate) {
            $certificate->name=$request['name'];
            $certificate->description=$request['description'];

            if ($certificate->save()) {
                return redirect('settings_eu')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_eu')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_eu')->with('danger', 'Error occurred while updating details.');
        }
    }

    public function updateCertificateDetailsCanada(Request $request)
    {
        $certificate = certificate::where('id',4)->first();

         if ($certificate) {
            $certificate->name=$request['name'];
            $certificate->description=$request['description'];

            if ($certificate->save()) {
                return redirect('settings_canada')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_canada')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_canada')->with('danger', 'Error occurred while updating details.');
        }
    }

    public function updateCertificateDetailsCalifornia(Request $request)
    {
        $certificate = certificate::where('id',5)->first();

         if ($certificate) {
            $certificate->name=$request['name'];
            $certificate->description=$request['description'];

            if ($certificate->save()) {
                return redirect('settings_california')->with('success', 'Details updated successfully.');
            } else {
                return redirect('settings_california')->with('danger', 'Error occurred while updating details.');
            }
        } else {
            return redirect('/settings_california')->with('danger', 'Error occurred while updating details.');
        }

    }


    public function delete($id)
    {
        $user = user::find($id);
        if ($user) {
            if ($user->delete()) {
                return redirect('/setting/viewuser')->with('success', 'User deleted successfully.');
            } else {
                return redirect('/setting/viewuser')->with('danger', 'User was not found.');
            }
        }
    }

    public function updateUserPassword(Request $request, $id)
    {
        $user = user::find($id);
        if (Hash::check($request['oldPass'], $user->password)) {
            $user->password = Hash::make($request['newPass']);

            if ($user->save()) {
                Notification::create([
                    'user_id' => Auth::user()->id,
                    'notification' => 'Password successfully updated  by the admin '. Auth::user()->firstname." ".Auth::user()->lastname ."of user ". $user->firstname." ".$user->lastname,
                    'is_read' => 0
                ]);
            return redirect('setting/viewuser')->with('success', 'Password updated successfully.');
            }
        } else{
            return redirect('setting/viewuser')->with('danger', 'Old Password does not match.');
        }


    }

    public function updatePricingSettings(Request $request)
    {
        $setting = setting::get()->first();
        if ($setting) {
            $oldIndiaVolumetric = (float) ($setting->india_volumetric_weight_kg ?? 5000);

            // Update pricing settings (Section 6)
            if ($request->has('corner_rate')) {
                $setting->corner_rate = $request['corner_rate'];
            }
            if ($request->has('packaging_per_sqinch_rate')) {
                $setting->packaging_per_sqinch_rate = $request['packaging_per_sqinch_rate'];
            }
            if ($request->has('wspackaging')) {
                $setting->wspackaging = $request['wspackaging'];
            }
            if ($request->has('dspackaging')) {
                $setting->dspackaging = $request['dspackaging'];
            }
            if ($request->has('ishippingcost')) {
                $setting->ishippingcost = $request['ishippingcost'];
            }

            // Update country-based volumetric weight settings (Section 7)
            if ($request->has('uk_volumetric_weight_kg')) {
                $setting->uk_volumetric_weight_kg = $request['uk_volumetric_weight_kg'] ?? null;
            }
            if ($request->has('us_volumetric_weight_lbs')) {
                $setting->us_volumetric_weight_lbs = $request['us_volumetric_weight_lbs'] ?? null;
            }
            if ($request->has('eu_volumetric_weight_kg')) {
                $setting->eu_volumetric_weight_kg = $request['eu_volumetric_weight_kg'] ?? null;
            }
            if ($request->has('canada_volumetric_weight_lbs')) {
                $setting->canada_volumetric_weight_lbs = $request['canada_volumetric_weight_lbs'] ?? null;
            }
            if ($request->has('california_volumetric_weight_kg')) {
                $setting->california_volumetric_weight_kg = $request['california_volumetric_weight_kg'] ?? null;
            }
            if ($request->has('australia_volumetric_weight_kg')) {
                $setting->australia_volumetric_weight_kg = $request['australia_volumetric_weight_kg'] ?? null;
            }
            if ($request->has('india_volumetric_weight_kg')) {
                $setting->india_volumetric_weight_kg = $request['india_volumetric_weight_kg'] ?? 5000;
            }

            if ($setting->save()) {
                // Update existing pricing records if checkbox is checked (only for Section 6)
                if ($request->has('update_all_pricing') && $request['update_all_pricing'] == 1) {
                    $updatePriceController = new UpdatePriceController();
                    $updatePriceController->updateAllPricingRecords($setting);
                }

                $indiaVolumetricChanged = $request->has('india_volumetric_weight_kg')
                    && (float) $setting->india_volumetric_weight_kg !== $oldIndiaVolumetric;
                if ($indiaVolumetricChanged) {
                    $updatePriceController = $updatePriceController ?? new UpdatePriceController();
                    $updatePriceController->updateAllPricingRecords($setting, 'india');
                }

                $message = $request->has('uk_volumetric_weight_kg')
                    || $request->has('us_volumetric_weight_lbs')
                    || $request->has('india_volumetric_weight_kg')
                    ? 'Volumetric Weight Settings updated successfully.'
                    : 'Pricing Settings updated successfully.';

                Notification::create([
                    'user_id' => Auth::user()->id,
                    'notification' => $message . ' by the admin ' . Auth::user()->firstname . " " . Auth::user()->lastname,
                    'is_read' => 0
                ]);
                return redirect('setting')->with('success', $message);
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating Settings.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating Settings.');
        }
    }

    public function updateVolumetricWeightSettings(Request $request)
    {
        $setting = setting::get()->first();
        if ($setting) {
            // Countries with kg only
            $setting->uk_volumetric_weight_kg = $request['uk_volumetric_weight_kg'] ?? null;
            $setting->eu_volumetric_weight_kg = $request['eu_volumetric_weight_kg'] ?? null;
            $setting->california_volumetric_weight_kg = $request['california_volumetric_weight_kg'] ?? null;
            $setting->australia_volumetric_weight_kg = $request['australia_volumetric_weight_kg'] ?? null;

            // US and Canada have only LBS (no kg)
            $setting->us_volumetric_weight_lbs = $request['us_volumetric_weight_lbs'] ?? null;
            $setting->canada_volumetric_weight_lbs = $request['canada_volumetric_weight_lbs'] ?? null;

            if ($setting->save()) {
                Notification::create([
                    'user_id' => Auth::user()->id,
                    'notification' => 'Volumetric weight settings updated successfully by the admin ' . Auth::user()->firstname . " " . Auth::user()->lastname,
                    'is_read' => 0
                ]);
                return redirect('setting')->with('success', 'Volumetric Weight Settings updated successfully.');
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating Volumetric Weight Settings.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating Volumetric Weight Settings.');
        }
    }

    public function updateUsaRates(Request $request)
    {
        $setting = setting::get()->first();
           if ($setting) {
            $setting->in2047=$request['in2047'];
            $setting->in2108=$request['in2108'];
            $setting->us_product_1=$request['us_product_1'];
            $setting->us_product_2=$request['us_product_2'];
            $setting->usa_remaining_stock=$request['usa_remaining_stock'];


            if ($setting->save()) {

                return redirect('setting')->with('success', 'USA stock updated successfully.');
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating Pricing Settings.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating Pricing Settings.');
        }
    }

    public function updateEuRates(Request $request)
    {
        $setting = setting::get()->first();

         if ($setting) {
            $setting->eu_product_1=$request['eu_product_1'];
            $setting->eu_product_2=$request['eu_product_2'];
            $setting->eu_product_1_stock=$request['eu_product_1_stock'];
            $setting->eu_product_2_stock=$request['eu_product_2_stock'];
            $setting->eu_remaining_stock=$request['eu_remaining_stock'];


            if ($setting->save()) {
                return redirect('setting')->with('success', 'EU stock updated successfully.');
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating Pricing Settings.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating Pricing Settings.');
        }
    }


    public function updateCanadaRates(Request $request)
    {
        $setting = setting::get()->first();

         if ($setting) {
            $setting->canada_product_1=$request['canada_product_1'];
            $setting->canada_product_2=$request['canada_product_2'];
            $setting->canada_product_1_stock=$request['canada_product_1_stock'];
            $setting->canada_product_2_stock=$request['canada_product_2_stock'];
            $setting->canada_remaining_stock=$request['canada_remaining_stock'];


            if ($setting->save()) {
                return redirect('setting')->with('success', 'Canada stock updated successfully.');
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating Pricing Settings.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating Pricing Settings.');
        }
    }


    public function updateCaliforniaRates(Request $request)
    {
        $setting = setting::get()->first();

         if ($setting) {
            $setting->california_product_1=$request['california_product_1'];
            $setting->california_product_2=$request['california_product_2'];
            $setting->california_product_1_stock=$request['california_product_1_stock'];
            $setting->california_product_2_stock=$request['california_product_2_stock'];
            $setting->california_remaining_stock=$request['california_remaining_stock'];


            if ($setting->save()) {
                return redirect('setting')->with('success', 'California stock updated successfully.');
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating Pricing Settings.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating Pricing Settings.');
        }
    }



        public function getDbBackup(){
                $filename       =       "backup-" . date("d-m-Y") . ".sql.gz";
                $mime           =       "application/x-gzip";
                $DBUSER         =       env('DB_USERNAME', 'forge');
                $DBPASSWD       =       env('DB_PASSWORD', '');
                $DATABASE       =       env('DB_DATABASE', 'forge');
                header( "Content-Type: " . $mime );
                header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

                $cmd            = "mysqldump -u $DBUSER --password=$DBPASSWD $DATABASE | gzip --best";

                passthru( $cmd );

                die;
        }

    public function setCountry(Request $request, $country)
    {
        \Request::session()->put('country', $country);

        return redirect('/dashboard');
    }

    public function updatePasswordByAdmin(Request $request, $id)
    {
        $authUser = Auth::user();
        $allowedAdminEmails = ['aaura1177@gmail.com'];
        $canChangePasswordAsAdmin = $authUser
            && (
                (int) $authUser->id === 4
                || in_array(strtolower((string) ($authUser->email ?? '')), $allowedAdminEmails, true)
            );

        if (! $canChangePasswordAsAdmin) {
            return redirect()->back()->with('error', 'You are not authorized to change user passwords.');
        }

        $user = User::find($id);

        if (!$user) {
            return redirect()->back()->with('error', 'User not found');
        }

        $validatedData = $request->validate([
            'newPass' => 'required|min:5',
            'newPassConfirm' => 'required|same:newPass',
        ]);

        $user->password = Hash::make($request->newPass);

        if ($user->save()) {
            Notification::create([
                'user_id' => Auth::user()->id,
                'notification' => 'Password updated successfully by the admin ' . Auth::user()->firstname . " " . Auth::user()->lastname,
                'is_read' => 0,
            ]);

            // Redirect to theusers page with success message
            return redirect('/setting/viewuser')->with('success', 'Password updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Failed to update password.');
        }
    }



    public function updateLoginSecurity(Request $request, $id)
{
    $LoginSecurity = LoginSecurity::where('user_id', $id)->first();

    if ($LoginSecurity) {
        $LoginSecurity->google2fa_enable = $request->has('google2fa_enable') ? 1 : 0;
        $LoginSecurity->save();

        return back()->with('success', 'Login security updated successfully.');
    }

    return back()->with('error', 'Login security settings not found.');
}



    public function updateSign(Request $request)
    {
        $request->validate([
            'sign_url' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $setting = setting::get()->first();

        $existing = $setting->sign_url ?: null;

        if ($request->hasFile('sign_url')) {
            $disk = Storage::disk('s3');
            $file = $request->file('sign_url');
            $filename = $file->hashName();

            $ok = $disk->putFileAs('stock', $file, $filename);
            if (!$ok) {
                return back()->with('danger', 'Upload failed (S3 write returned false). Check S3 config/permissions/region.');
            }

            if (!empty($existing) && $existing !== 'default.jpg' && $disk->exists("stock/{$existing}")) {
                $disk->delete("stock/{$existing}");
            }

            $setting->sign_url = $filename;
        } else {
            $setting->sign_url = $existing ?: 'default.jpg';
        }

        $setting->save();

        return back()->with('success', 'Sign updated successfully.');
    }

  public function roleupdate(Request $request,$id){
              $user = user::find($id);
              $user->role = $request->user_role;
              $user->save();
                    return redirect('setting/viewuser')->with('success', 'Role updated successfully.');
    }

public function updateAustraliaRates(Request $request)
    {
        $setting = setting::get()->first();

         if ($setting) {
            $setting->australia_product_1=$request['australia_product_1'];
            $setting->australia_product_2=$request['australia_product_2'];
            $setting->australia_product_1_stock=$request['australia_product_1_stock'];
            $setting->australia_product_2_stock=$request['australia_product_2_stock'];
            $setting->australia_remaining_stock=$request['australia_remaining_stock'];

            if ($setting->save()) {
                return redirect('setting')->with('success', 'Australia stock updated successfully.');
            } else {
                return redirect('setting')->with('danger', 'Error occurred while updating Pricing Settings.');
            }
        } else {
            return redirect('/setting')->with('danger', 'Error occurred while updating Pricing Settings.');
        }
    }

    public function updateBackmonthSettings(Request $request)
    {
        $currentEmail = strtolower((string) (auth()->user()->email ?? ''));
        if (! in_array($currentEmail, self::BACKMONTH_SETTINGS_ALLOWED_EMAILS, true)) {
            return back()->with('danger', 'You are not authorized to update backmonth settings.');
        }

        $validated = $request->validate([
            'backmonth_edit_day_limit' => 'required|integer|min:1|max:31',
            'backmonth_factory_can_cumulative' => 'nullable|in:1',
            'backmonth_factory_can_individual' => 'nullable|in:1',
        ]);

        $setting = setting::first();
        if (! $setting) {
            return back()->with('danger', 'Settings record not found.');
        }

        $setting->backmonth_edit_day_limit = (int) $validated['backmonth_edit_day_limit'];
        $setting->backmonth_factory_can_cumulative = $request->has('backmonth_factory_can_cumulative') ? 1 : 0;
        $setting->backmonth_factory_can_individual = $request->has('backmonth_factory_can_individual') ? 1 : 0;
        $setting->save();

        return back()->with('success', 'Backmonth stock settings updated successfully.');
    }

}
