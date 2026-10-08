<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use auth;
use App\product;
use App\buyer;
use App\contractor;
use App\invoice;
use App\purchaseOrder;
use App\allocation;
use App\supplier;
use App\rejectRepair;
use App\LoginSecurity;
use App\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function login(){
	    if(auth::check()){
		    $user=auth::user();
		   // echo $user->hasRole('packing');die;
		$twofa = LoginSecurity::where('user_id',Auth::user()->id)->where('google2fa_enable',1)->first();
		//$user->removeRole('packing');
	    //if(Auth::user()->role != 'Supplier'){
                if(isset($twofa->id)){
                    return redirect('/2fa_authenticate');
                }else{
                    return redirect(url('2fa'));
                }
            //}
        }
        else{
            return view('auth/login');
        }
    }

    public function index()
    {
	    $user=auth::user();

        if(!(\Request::session()->has('country'))){
            \Request::session()->put('country','india');
        }


        if($user->role == 'Stockadmin'){
            $user->assignRole('stockadmin');
        }
        if($user->hasRole('admin') || $user->hasRole('stockadmin') || $user->hasRole('auditor')){
            $productCount=product::count();
            $buyerCount=buyer::count();
            $contratorCount=contractor::count();
            $supplierCount=supplier::count();
            $rejectRepairCount=rejectRepair::count();
            $allocationCount = allocation::count();
            $notifications = Notification::where('user_id',$user->id)->where('is_read',0)->limit('20')->orderBy('id','desc')->get();
            return view('dashboard', ['productCount' => $productCount, 'allocationCount' => $allocationCount, 'buyerCount' => $buyerCount, 'contratorCount' => $contratorCount, 'supplierCount' => $supplierCount, 'rejectRepairCount' => $rejectRepairCount,'notifications'=>$notifications]);
        }

        
        if ($user->hasAnyRole(['factory', 'factory-pricing'])) {
            return redirect('stockout');
        }
        
        if($user->hasRole('office')){
            return redirect('invoice');
        }
		
		if($user->hasRole('packing')){
            return redirect('invoice/packing_list');
        }
		if($user->hasRole('quality')){
            return redirect('quality');
        }
		if($user->hasRole('supplier')){
            return redirect('supplier-dashboard');
        }
        if($user->hasRole('ukmanager')){
            \Request::session()->put('country','uk');
            return redirect('erp-manager');
        }

        if($user->hasRole('usmanager')){
            \Request::session()->put('country','us');
            return redirect('/erp-manager');
        }

        if($user->hasRole('eumanager')){
            \Request::session()->put('country','eu');
            return redirect('erp-manager');
        }

        if($user->hasRole('canadamanager')){
            \Request::session()->put('country','canada');
            return redirect('erp-manager');
        }

        if($user->hasRole('californiamanager')){
            \Request::session()->put('country','california');
            return redirect('erp-manager');
        }
        
        if($user->hasRole('Procurement Manager') || $user->hasRole('procurementmanager') || $user->hasRole('Inventory and Packing Manager') || $user->hasRole('inventoryAndPackingManager'))
        {
            return redirect()->route('allocation-management.index');
        }

        // if($user->role=='warehouseuser'){
        //     return redirect('warehouseuser-dashboard');
        // }

        // if($user->role=='accounts'){
        //     return redirect('accounts-dashboard');
        // }

    }

    public function data()
    {
        $product=product::where('quantity', '>=', 100)->get();
        return response()->json(['product'=>$product]);
    }
}
