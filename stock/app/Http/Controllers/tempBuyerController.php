<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \auth;
use App\buyer;
use App\tempBuyer;
use App\courier;
use App\Helpers\Common;

class tempBuyerController extends Controller
{
    public function __construct()
    {
    	$this->middleware(['auth','2fa']);
    }

    public function create()
    {
        $countriesArray = Common::getCountries();
		$couriers = courier::all();
        $invoiceBuyers = buyer::orderBy('c_name')->get(['id', 'code', 'c_name', 'temp_buyer_id']);
        $tempBuyerNames = $this->tempBuyerDisplayNames();
    	return view('/temporaryBuyer/create', [
            'couriers' => $couriers,
            'countriesArray' => $countriesArray,
            'invoiceBuyers' => $invoiceBuyers,
            'assignedBuyerIds' => [],
            'tempBuyerNames' => $tempBuyerNames,
        ]);
    }

    public function store(Request $request)
    {       
        $request->validate([
        'code' => 'unique:temp_buyer_table',
        ]);

        if (!isset($errors))
        {
            $tempBuyer = tempBuyer::create
          ([
            'code'=>strtoupper($request['code']),
            'c_name'=>$request['c_name'],  
            'name'=>$request['name'],
            'address1'=>$request['address1'],
            'address2'=>$request['address2'],
            'city'=>$request['city'],
            'state'=>$request['state'],
            'country'=>$request['country'],
            'postcode'=>$request['postcode'],
            'profit'=>$request['profit'],
            'final_price_percent'=>$request['final_price_percent'],
            'cost_adjustments'=>$request['cost_adjustments'],
            'courierType'=>$request['courierType'],
            'admin_profit'=>$request['adminProfit'],
            'tariff_solid_wood_percent'=>$request['tariff_solid_wood_percent'] ?? 0,
            'tariff_upholstered_percent'=>$request['tariff_upholstered_percent'] ?? 0,
          ]);

            $this->syncInvoiceBuyers($tempBuyer->id, $request->input('invoice_buyer_ids', []));
        };

      return redirect('/temporaryBuyer')->with('success', 'Temporary Buyer was added successfully.');
    }

    public function index()
    {
        $tempBuyers=tempBuyer::get();
        return view('temporaryBuyer/index',['tempBuyers'=>$tempBuyers]);
    }

    public function view($id)
    {
        $countriesArray = Common::getCountries();
        $tempBuyer = tempBuyer::find($id);
		$couriers = courier::all();
        if ($tempBuyer) {
            $invoiceBuyers = buyer::orderBy('c_name')->get(['id', 'code', 'c_name', 'temp_buyer_id']);
            $assignedBuyerIds = buyer::where('temp_buyer_id', $tempBuyer->id)->pluck('id')->map(function ($id) {
                return (int) $id;
            })->all();
            $tempBuyerNames = $this->tempBuyerDisplayNames();

            return view('temporaryBuyer/view', [
                'tempBuyer' => $tempBuyer,
                'couriers' => $couriers,
                'countriesArray' => $countriesArray,
                'invoiceBuyers' => $invoiceBuyers,
                'assignedBuyerIds' => $assignedBuyerIds,
                'tempBuyerNames' => $tempBuyerNames,
            ]);
        } else {
            return redirect('/temporaryBuyer')->with('danger', 'Temporary Buyer was not found.');
        }
    }

    public function update(Request $request, $id)
    {   
        $tempBuyer = tempBuyer::find($id);

        if ($tempBuyer) {
            $tempBuyer->c_name=$request['c_name'];  
            $tempBuyer->name=$request['name'];
            $tempBuyer->address1=$request['address1'];
            $tempBuyer->address2=$request['address2'];
            $tempBuyer->city=$request['city'];
            $tempBuyer->state=$request['state'];
            $tempBuyer->country=$request['country'];
            $tempBuyer->postcode=$request['postcode'];
            $tempBuyer->profit=$request['profit'];
            $tempBuyer->final_price_percent=$request['final_price_percent'];
            $tempBuyer->cost_adjustments=$request['cost_adjustments'];
            $tempBuyer->courierType=$request['courierType'];
            $tempBuyer->admin_profit=$request['adminProfit'];
            $tempBuyer->tariff_solid_wood_percent=$request['tariff_solid_wood_percent'] ?? 0;
            $tempBuyer->tariff_upholstered_percent=$request['tariff_upholstered_percent'] ?? 0;

            if ($tempBuyer->save()) {
                $this->syncInvoiceBuyers($tempBuyer->id, $request->input('invoice_buyer_ids', []));
                return back()->with('success', 'Temporary Buyer was updated successfully.');
            } else {
                return redirect('temporaryBuyer')->with('danger', 'Error occurred while saving Temporary Buyer');
            }
        } else {
            return redirect('/temporaryBuyer')->with('danger', 'Temporary Buyer was not found.');
        }
    }

    public function delete($id)
    {
        $tempBuyer = tempBuyer::find($id);
        if ($tempBuyer) {
            buyer::where('temp_buyer_id', $tempBuyer->id)->update(['temp_buyer_id' => null]);

            if ($tempBuyer->delete()) {
                return redirect('/temporaryBuyer')->with('success', 'Temporary Buyer deleted successfully.');
            } else {
                return redirect('/temporaryBuyer')->with('danger', 'Temporary Buyer was not found.');
            }
        }
    }

    /**
     * Temp buyer display names keyed by id, e.g. "Artisan Furniture UK (UK-18)".
     */
    private function tempBuyerDisplayNames(): array
    {
        return tempBuyer::get(['id', 'code', 'c_name'])->mapWithKeys(function ($tempBuyer) {
            $name = trim((string) $tempBuyer->c_name);
            return [$tempBuyer->id => $name !== '' ? $name.' ('.$tempBuyer->code.')' : $tempBuyer->code];
        })->all();
    }

    /**
     * Assign invoice buyers to this temp buyer (pricing destination).
     */
    private function syncInvoiceBuyers(int $tempBuyerId, $buyerIds): void
    {
        $buyerIds = array_values(array_unique(array_filter(array_map('intval', (array) $buyerIds))));

        buyer::where('temp_buyer_id', $tempBuyerId)
            ->when(!empty($buyerIds), function ($query) use ($buyerIds) {
                $query->whereNotIn('id', $buyerIds);
            })
            ->update(['temp_buyer_id' => null]);

        if (!empty($buyerIds)) {
            buyer::whereIn('id', $buyerIds)->update(['temp_buyer_id' => $tempBuyerId]);
        }
    }
}
