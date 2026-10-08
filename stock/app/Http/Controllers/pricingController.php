<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\tempProduct;
use App\tempBuyer;
use App\buyer;
use App\product;
use App\pricingTable;
use App\setting;
use App\Exports\EachPricingExport;
use App\Exports\AllPricingExport;
use App\Imports\PricingImport;
//use Maatwebsite\Excel\Facades\Excel;
use App\courier;
use App\SettingsOption;
use App\hardwares;
use App\Helpers\Common;
use App\Helpers\InvoicePricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\PricingVolumetricWeight;
use App\Services\UsCourierRuleService;
use App\Exports\PricingBuyingCostExport;
use Maatwebsite\Excel\Facades\Excel; 
use App\PricingExcelSheet;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Illuminate\Validation\ValidationException;

class pricingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function index()
    {
        $pricingTables = pricingTable::orderBy('id')
            ->get()
            ->unique(fn ($row) => (int) $row->productType . ':' . (int) $row->product_id)
            ->values();
        $destinationBuyerIds = pricingTable::whereNotNull('buyer_id2')
            ->where('buyer_id2', '!=', '')
            ->distinct()
            ->pluck('buyer_id2');
        $destinationBuyers = tempBuyer::whereIn('id', $destinationBuyerIds)
            ->orderBy('c_name')
            ->get();

        return view('pricing/index', [
            'pricingTables' => $pricingTables,
            'destinationBuyers' => $destinationBuyers,
        ]);
    }

    public function exportEachCSV($id)
    {
        return (new EachPricingExport($id))->download('pricing.xlsx');
    }

    public function exportcsv(Request $request)
    {
        $request->validate([
            'buyer_id2' => 'nullable|exists:temp_buyer_table,id',
        ]);

		$ids = $request['ids'];
        $buyerId2 = $request->input('buyer_id2');

        return (new AllPricingExport($ids, $buyerId2))->download('pricingAll.xlsx');
    }

    public function exportBuyingCost(Request $request)
    {
        $ids = $request['ids'] ?? '';
        return (new PricingBuyingCostExport($ids))->download('pricingBuyingCost.xlsx');
    }

    public function importCSV(Request $request)
    {
		  $request->validate([
        'importCSV' => 'required|file|mimes:xlsx,xls,csv', 
    ]);
        $file = $request->file('importCSV');
        Excel::import(new PricingImport, $file);
        return redirect('/pricing')->with('success', 'Pricing Excel was updated successfully.');
    }

    public function downloadSample()
    {
        return (new DownloadPricingSample())->download('pricingSample.xlsx');
    }

    public function create()
    {
    	$tempProducts = tempProduct::get();
    	$tempBuyers = tempBuyer::get();
    	$products = product::get();
    	$settings = setting::get();
        $couriers = courier::all();
		$hardwares = hardwares::get();
		// $settingsOption = SettingsOption::get();
		$settingsOption = Common::getSettings();
    	return view('pricing/create', ['tempProducts' => $tempProducts, 'tempBuyers' => $tempBuyers, 'products' => $products, 'settings' => $settings, 'couriers'=>$couriers, 'hardwares'=>$hardwares, 'settings_option'=>$settingsOption]);
    }

	public function store(Request $request)
    {
		$this->validateBoxWeightRequest($request);
		$this->validateFulfilmentCostRequest($request);

		if($request['duplicatePage']){
			$productIdCount  = pricingTable::where('product_id',$request['product_id'])->count();
			if($productIdCount){
				return back()->with('danger', 'This Product is Already Present!');
			}
		} elseif (pricingTable::where('product_id', $request['product_id'])
			->where('productType', (int) ($request['productType'] ?? 1))
			->exists()) {
			return back()->withInput()->with('danger', 'This product already has pricing. Open the existing record to edit.');
		}
		$default_country = $request['default_country_0'];
		$volumetricWt = $this->volumetricWtForDestination($request, $default_country);
		pricingTable::create([
            'product_id' => $request['product_id'],
            'buyer_id' => $request['buyer_id'],
            'startDate' => $request['startDate'],
            'endDate' => $request['endDate'],
            'remarks' => $request['remarks'],
            'productheight' => $request['productheight'],
            'productwidth' => $request['productwidth'],
            'productdepth' => $request['productdepth'],
            'boxheight' => $request['boxheight'],
            'boxwidth' => $request['boxwidth'],
            'boxdepth' => $request['boxdepth'],
            'wholesalevolume' => $request['wholesalevolume'],
            'dropshipvolume' => $request['dropshipvolume'],
            'buyingCost' => $request['buyingCost'],
            'fabricCost' => $request['fabricCost'],
            'tapestryConsumed' => $request['tapestryConsumed'],
            'tapestryUnitCost' => $request['tapestryUnitCost'],
            'tapestryCost' => $request['tapestryCost'],
            'fillerCost' => $request['fillerCost'],
            'labourCost' => $request['labourCost'],
            'hardwareCost1' => $request['hardwareCost1'],
            'hardwareCost2' => $request['hardwareCost2'],
            'hardwareCost3' => $request['hardwareCost3'],
            'hardwareCost4' => $request['hardwareCost4'],
            'hardwareCost5' => $request['hardwareCost5'],
            'pUnitCost' => $request['pUnitCost'],
            'polishCost' => $request['polishCost'],
            'wSPackageCost' => $request['wSPackageCost'],
            'dSPackageCost' => $request['dSPackageCost'],
            'shippingCost' => $request['shippingCost'],
            'costPrice' => $request['costPrice'],
            'adminCostPercent' => $request['adminCostPercent'],
            'adminCost' => $request['adminCost'],
            'profitPercent' => $request['profitPercent'],
            'finalCost' => $request['finalCost'],
            'currency' => $request['currency'],
            'converRate' => $request['converRate'],
            'fobINCost' => $this->roundPricingAmount($request['fobINCost']),
            'tariff_percent' => $request['tariff_percent'] ?? 0,
            'final_fob_in_cost' => $this->roundPricingAmount($request['final_fob_in_cost'] ?? $request['fobINCost']),
            'boxWt' => $request['boxWt'],
            'volWt' => $volumetricWt,
            'shippingCost2' => $request['shippingCost2'],
            'StorageCost' => $request['StorageCost'],
            'adminCost2' => $request['adminCost2'],
            'qualityAssurance' => $request['qualityAssurance'],
            'landedCost' => $request['landedCost'],
            'adminProfit' => $request['adminProfit'],
            'adminPrice' => $request['adminPrice'],
            'finalPricePer' => $request['finalPricePer'],
            'finalPrice' => $request['finalPrice'],
            'courierType' => $request['courierType'],
            'courierCost' => $request['courierCost'],
            'deliveryCost' => $request['deliveryCost'],
            'adjustment' => $request['adjustment'],
            'newDelCost' => $request['newDelCost'],
            'productType' => $request['productType'],
            'deliveredCostStatus' => $request['deliveredCostStatus'],
            'buyer_id2' => $request['buyer_id2'],
            'destination' => $default_country,
            'hardware1' => $request['hardware1'],
            'hardware2' => $request['hardware2'],
            'hardware3' => $request['hardware3'],
            'hardware4' => $request['hardware4'],
            'hardware5' => $request['hardware5'],
            'hardware1_quantity' => $request['hardware1_quantity'],
            'hardware2_quantity' => $request['hardware2_quantity'],
            'hardware3_quantity' => $request['hardware3_quantity'],
            'hardware4_quantity' => $request['hardware4_quantity'],
            'hardware5_quantity' => $request['hardware5_quantity']
        ]);
				
		if(!empty($request['buyer_id2_multiple'])){
			
			foreach($request['buyer_id2_multiple'] as $key => $buyer_id_Clone){
				$buyer				= 	tempBuyer::where('id',$buyer_id_Clone)->first();
				$pricing  			=	pricingTable::where('product_id',$request['product_id'])
					->where('productType', (int) ($request['productType'] ?? 1))
					->where('buyer_id2',$buyer_id_Clone)->first();
				
				if(isset($buyer->final_price_percent)){
					$cloneDest = $request['destination_clone'][$key] ?? $request['default_country_clone'][$key] ?? '';
					$volumetricWt = $this->volumetricWtForDestination($request, $cloneDest);
					if(!isset($pricing->id)){
						
						pricingTable::create([
							'product_id' => $request['product_id'],
							'buyer_id' => $request['buyer_id'],
							'startDate' => $request['startDate'],
							'endDate' => $request['endDate'],
							'remarks' => $request['remarks'],
							'productheight' => $request['productheight'],
							'productwidth' => $request['productwidth'],
							'productdepth' => $request['productdepth'],
							'boxheight' => $request['boxheight'],
							'boxwidth' => $request['boxwidth'],
							'boxdepth' => $request['boxdepth'],
							'wholesalevolume' => $request['wholesalevolume'],
							'dropshipvolume' => $request['dropshipvolume'],
							'buyingCost' => $request['buyingCost'],
							'fabricCost' => $request['fabricCost'],
							'tapestryConsumed' => $request['tapestryConsumed'],
							'tapestryUnitCost' => $request['tapestryUnitCost'],
							'tapestryCost' => $request['tapestryCost'],
							'fillerCost' => $request['fillerCost'],
							'labourCost' => $request['labourCost'],
							'hardwareCost1' => $request['hardwareCost1'],
							'hardwareCost2' => $request['hardwareCost2'],
							'hardwareCost3' => $request['hardwareCost3'],
							'hardwareCost4' => $request['hardwareCost4'],
							'hardwareCost5' => $request['hardwareCost5'],
							'pUnitCost' => $request['pUnitCost'],
							'polishCost' => $request['polishCost'],
							'wSPackageCost' => $request['wSPackageCost'],
							'dSPackageCost' => $request['dSPackageCost'],
							'shippingCost' => $request['shippingCost'],
							'costPrice' => $request['costPrice'],
							'adminCostPercent' => $request['adminCostPercent'],
							'adminCost' => $request['adminCost'],
							'profitPercent' => $request['profitPercent'],
							'finalCost' => $request['finalCost'],
							'currency' => $request['currency_clone'][$key],
						    'converRate' => $request['converRate_clone'][$key],
						    'fobINCost' => $this->roundPricingAmount($request['fobINCost_clone'][$key] ?? null),
						    'tariff_percent' => $request['tariff_percent_clone'][$key] ?? 0,
						    'final_fob_in_cost' => $this->roundPricingAmount($request['final_fob_in_cost_clone'][$key] ?? $request['fobINCost_clone'][$key] ?? null),
						    'boxWt' => $request['boxWt'],
						    'volWt' => $volumetricWt,
							'shippingCost2' => $request['shippingCost2_clone'][$key],
							'StorageCost' => $request['StorageCost_clone'][$key],
							'adminCost2' => $request['adminCost2_clone'][$key],
							'qualityAssurance' => $request['qualityAssurance_clone'][$key],
							'landedCost' => $request['landedCost_clone'][$key],
							'adminProfit' => $request['adminProfit_clone'][$key],
							'adminPrice' => $request['adminPrice_clone'][$key],
							'finalPricePer' => $request['finalPricePer_clone'][$key],
							'finalPrice' => $request['finalPrice_clone'][$key],
							'courierType' => $request['courierType_clone'][$key],
							'courierCost' => $request['courierCost_clone'][$key],
							'deliveryCost' => $request['deliveryCost_clone'][$key],
							'adjustment' => $request['adjustment_clone'][$key],
							'newDelCost' => $request['newDelCost_clone'][$key],
							'productType' => $request['productType'],
							'deliveredCostStatus' => $request['deliveredCostStatus'],
							'buyer_id2' => $buyer_id_Clone,
							'destination' => $request['destination_clone'][$key],
							'hardware1' => $request['hardware1'],
							'hardware2' => $request['hardware2'],
							'hardware3' => $request['hardware3'],
							'hardware4' => $request['hardware4'],
							'hardware5' => $request['hardware5'],
							'hardware1_quantity' => $request['hardware1_quantity'],
							'hardware2_quantity' => $request['hardware2_quantity'],
							'hardware3_quantity' => $request['hardware3_quantity'],
							'hardware4_quantity' => $request['hardware4_quantity'],
							'hardware5_quantity' => $request['hardware5_quantity']
						]);
					}else{
						$pricing->product_id 	= $request['product_id'];
						$pricing->buyer_id 		= $request['buyer_id'];
						$pricing->startDate 	= $request['startDate'];
						$pricing->endDate 		= $request['endDate'];
						$pricing->remarks 		= $request['remarks'];
						$pricing->productheight = $request['productheight'];
						$pricing->productwidth 	= $request['productwidth'];
						$pricing->productdepth 	= $request['productdepth'];
						$pricing->boxheight 	= $request['boxheight'];
						$pricing->boxwidth 		= $request['boxwidth'];
						$pricing->boxdepth 		= $request['boxdepth'];
						$pricing->wholesalevolume 	= $request['wholesalevolume'];
						$pricing->dropshipvolume 	= $request['dropshipvolume'];
						$pricing->buyingCost 	= $request['buyingCost'];
						$pricing->fabricCost 	= $request['fabricCost'];
						$pricing->tapestryConsumed 	= $request['tapestryConsumed'];
						$pricing->tapestryUnitCost 	= $request['tapestryUnitCost'];
						$pricing->tapestryCost 	= $request['tapestryCost'];
						$pricing->fillerCost 	= $request['fillerCost'];
						$pricing->labourCost 	= $request['labourCost'];
						$pricing->hardwareCost1 = $request['hardwareCost1'];
						$pricing->hardwareCost2 = $request['hardwareCost2'];
						$pricing->hardwareCost3 = $request['hardwareCost3'];
						$pricing->hardwareCost4 = $request['hardwareCost4'];
						$pricing->hardwareCost5 = $request['hardwareCost5'];
						$pricing->pUnitCost 	= $request['pUnitCost'];
						$pricing->polishCost 	= $request['polishCost'];
						$pricing->wSPackageCost = $request['wSPackageCost'];
						$pricing->dSPackageCost = $request['dSPackageCost'];
						$pricing->shippingCost 	= $request['shippingCost'];
						$pricing->costPrice 	= $request['costPrice'];
						$pricing->adminCostPercent = $request['adminCostPercent'];
						$pricing->adminCost 	= $request['adminCost'];
						$pricing->profitPercent = $request['profitPercent'];
						$pricing->finalCost 	= $request['finalCost'];
						$pricing->currency = $request['currency_clone'][$key];
						$pricing->converRate = $request['converRate_clone'][$key];
						$pricing->fobINCost = $this->roundPricingAmount($request['fobINCost_clone'][$key] ?? null);
						$pricing->tariff_percent = $request['tariff_percent_clone'][$key] ?? 0;
						$pricing->final_fob_in_cost = $this->roundPricingAmount($request['final_fob_in_cost_clone'][$key] ?? $request['fobINCost_clone'][$key] ?? null);
						$pricing->boxWt = $request['boxWt'];
						$pricing->volWt = $volumetricWt;
						$pricing->shippingCost2 = $request['shippingCost2_clone'][$key];
						$pricing->StorageCost = $request['StorageCost_clone'][$key];
						$pricing->shippingCost2 = $request['shippingCost2'];
						$pricing->StorageCost 	= $request['StorageCost'];
						$pricing->adminCost2 	= $request['adminCost2_clone'][$key];
						$pricing->qualityAssurance = $request['qualityAssurance_clone'][$key];
						$pricing->landedCost 	= $request['landedCost_clone'][$key];
						$pricing->adminProfit 	= $request['adminProfit_clone'][$key];
						$pricing->adminPrice 	= $request['adminPrice_clone'][$key];
						$pricing->finalPricePer = $request['finalPricePer_clone'][$key];
						$pricing->finalPrice 	= $request['finalPrice_clone'][$key];
						$pricing->courierType 	= $request['courierType_clone'][$key];
						$pricing->courierCost 	= $request['courierCost_clone'][$key];
						$pricing->deliveryCost 	= $request['deliveryCost_clone'][$key];
						$pricing->adjustment 	= $request['adjustment_clone'][$key];
						$pricing->newDelCost 	= $request['newDelCost_clone'][$key];
						$pricing->productType 	= $request['productType'];
						$pricing->deliveredCostStatus = $request['deliveredCostStatus'];
						$pricing->buyer_id2 	= $buyer_id_Clone;
						$pricing->destination 	= $request['default_country_clone'][$key];
						$pricing->hardware1 	= $request['hardware1'];
						$pricing->hardware2 	= $request['hardware2'];
						$pricing->hardware3 	= $request['hardware3'];
						$pricing->hardware4 	= $request['hardware4'];
						$pricing->hardware5 	= $request['hardware5'];
						$pricing->hardware1_quantity = $request['hardware1_quantity'];
						$pricing->hardware2_quantity = $request['hardware2_quantity'];
						$pricing->hardware3_quantity = $request['hardware3_quantity'];
						$pricing->hardware4_quantity = $request['hardware4_quantity'];
						$pricing->hardware5_quantity = $request['hardware5_quantity'];
						$pricing->save();
					}
				}
			}
		}
        return redirect('/pricing')->with('success', 'Pricing was added successfully.');
	 }

   

    public function view($id)
    {
        $pricing = pricingTable::find($id);
        if (!$pricing) {
            return redirect('/pricing')->with('danger', 'Pricing was not found.');
        }
		$pageData = [
			'title' => 'Update Pricing',
			'dublicateEditRoute' => url('/pricing/view/'.$id),
			'duplicatePage' => false,
			'editMode' => true,
			'duplicateEditButtonName' => 'Update Price',
			];
			  $kg = 0;
        $lbs = 0;
        if($pricing->productType == 1){
            $theProduct = product::where('id', $pricing->product_id)->first();
			 $kg = $theProduct->gross_weight;
        $lbs = $kg * 2.20462;
         $lbs = round($lbs, 2);
        }
        if($pricing->productType == 0){
            $theProduct = tempProduct::where('id', $pricing->product_id)->first();
        }

        $savedBoxWt = (float) ($pricing->boxWt ?? 0);
        $grossWeightKg = (float) ($kg ?? 0);
        $displayBoxWt = $savedBoxWt > 0 ? $savedBoxWt : $grossWeightKg;

        $tempProducts = tempProduct::all();
        $tempBuyers = tempBuyer::all();
        $products = product::all();
        $settings = setting::all();
        $couriers = courier::where('country',$pricing->destination)->get();
		$hardwares = hardwares::get();
		// $SettingsOption = SettingsOption::get();
		$SettingsOption = Common::getSettings();
		
		$productCount = pricingTable::where('product_id', $pricing->product_id)
			->where('productType', $pricing->productType)
			->count();
		$productCount = $productCount - 1;
		$buyerCountry =tempBuyer::select('country')->where('id',$pricing->buyer_id2)->first();
		return view('pricing/view', ['pricing' => $pricing, 'tempProducts' => $tempProducts, 'tempBuyers' => $tempBuyers, 'products' => $products, 'theProduct' => $theProduct, 'settings' => $settings, 'couriers'=>$couriers, 'hardwares'=>$hardwares, 'settings_option'=>$SettingsOption, 'buyer_country'=>$buyerCountry,'productCount'=>$productCount,'page_data'=>$pageData,'kg'=>$kg,'lbs'=>$lbs,'displayBoxWt'=>$displayBoxWt]);

    }

	 public function update(Request $request, $id)
    {
		$this->validateBoxWeightRequest($request);
		$this->validateFulfilmentCostRequest($request);

        $pricingTable = pricingTable::where('id', $id)->first();
		$default_country = $request['default_country_0'];
		$volWt = $this->volumetricWtForDestination($request, $default_country);
		
        if ($pricingTable){
			pricingTable::where('product_id', $pricingTable->product_id)
				->where('productType', $pricingTable->productType)
				->delete();
			pricingTable::create([
				'product_id' => $request['product_id'],
				'buyer_id' => $request['buyer_id'],
				'startDate' => $request['startDate'],
				'endDate' => $request['endDate'],
				'remarks' => $request['remarks'],
				'productheight' => $request['productheight'],
				'productwidth' => $request['productwidth'],
				'productdepth' => $request['productdepth'],
				'boxheight' => $request['boxheight'],
				'boxwidth' => $request['boxwidth'],
				'boxdepth' => $request['boxdepth'],
				'wholesalevolume' => $request['wholesalevolume'],
				'dropshipvolume' => $request['dropshipvolume'],
				'buyingCost' => $request['buyingCost'],
				'fabricCost' => $request['fabricCost'],
				'tapestryConsumed' => $request['tapestryConsumed'],
				'tapestryUnitCost' => $request['tapestryUnitCost'],
				'tapestryCost' => $request['tapestryCost'],
				'fillerCost' => $request['fillerCost'],
				'labourCost' => $request['labourCost'],
				'hardwareCost1' => $request['hardwareCost1'],
				'hardwareCost2' => $request['hardwareCost2'],
				'hardwareCost3' => $request['hardwareCost3'],
				'hardwareCost4' => $request['hardwareCost4'],
				'hardwareCost5' => $request['hardwareCost5'],
				'pUnitCost' => $request['pUnitCost'],
				'polishCost' => $request['polishCost'],
				'wSPackageCost' => $request['wSPackageCost'],
				'dSPackageCost' => $request['dSPackageCost'],
				'shippingCost' => $request['shippingCost'],
				'costPrice' => $request['costPrice'],
				'adminCostPercent' => $request['adminCostPercent'],
				'adminCost' => $request['adminCost'],
				'profitPercent' => $request['profitPercent'],
				'finalCost' => $request['finalCost'],
				'currency' => $request['currency'],
				'converRate' => $request['converRate'],
				'fobINCost' => $this->roundPricingAmount($request['fobINCost']),
				'tariff_percent' => $request['tariff_percent'] ?? 0,
				'final_fob_in_cost' => $this->roundPricingAmount($request['final_fob_in_cost'] ?? $request['fobINCost']),
				'boxWt' => $request['boxWt'],
				'volWt' => $volWt,
				'shippingCost2' => $request['shippingCost2'],
				'StorageCost' => $request['StorageCost'],
				'adminCost2' => $request['adminCost2'],
				'qualityAssurance' => $request['qualityAssurance'],
				'landedCost' => $request['landedCost'],
				'adminProfit' => $request['adminProfit'],
				'adminPrice' => $request['adminPrice'],
				'finalPricePer' => $request['finalPricePer'],
				'finalPrice' => $request['finalPrice'],
				'courierType' => $request['courierType'],
				'courierCost' => $request['courierCost'],
				'deliveryCost' => $request['deliveryCost'],
				'adjustment' => $request['adjustment'],
				'newDelCost' => $request['newDelCost'],
				'productType' => $request['productType'],
				'deliveredCostStatus' => $request['deliveredCostStatus'],
				'buyer_id2' => $request['buyer_id2'],
				'destination' => $default_country,
				'hardware1' => $request['hardware1'],
				'hardware2' => $request['hardware2'],
				'hardware3' => $request['hardware3'],
				'hardware4' => $request['hardware4'],
				'hardware5' => $request['hardware5'],
				'hardware1_quantity' => $request['hardware1_quantity'],
				'hardware2_quantity' => $request['hardware2_quantity'],
				'hardware3_quantity' => $request['hardware3_quantity'],
				'hardware4_quantity' => $request['hardware4_quantity'],
				'hardware5_quantity' => $request['hardware5_quantity']
			]);
			if(!empty($request['buyer_id2_multiple'])){
				foreach($request['buyer_id2_multiple'] as $key => $buyer_id_Clone){
					$buyer				= 	tempBuyer::where('id',$buyer_id_Clone)->first();
					$pricing  			=	pricingTable::where('product_id',$request['product_id'])
						->where('productType', (int) ($request['productType'] ?? $pricingTable->productType))
						->where('buyer_id2',$buyer_id_Clone)->first();
					if(isset($buyer->final_price_percent)){
						$cloneDest = $request['destination_clone'][$key] ?? $request['default_country_clone'][$key] ?? '';
						$volWt = $this->volumetricWtForDestination($request, $cloneDest);
						if(!isset($pricing->id)){
							
							pricingTable::create([
								'product_id' => $request['product_id'],
								'buyer_id' => $request['buyer_id'],
								'startDate' => $request['startDate'],
								'endDate' => $request['endDate'],
								'remarks' => $request['remarks'],
								'productheight' => $request['productheight'],
								'productwidth' => $request['productwidth'],
								'productdepth' => $request['productdepth'],
								'boxheight' => $request['boxheight'],
								'boxwidth' => $request['boxwidth'],
								'boxdepth' => $request['boxdepth'],
								'wholesalevolume' => $request['wholesalevolume'],
								'dropshipvolume' => $request['dropshipvolume'],
								'buyingCost' => $request['buyingCost'],
								'fabricCost' => $request['fabricCost'],
								'tapestryConsumed' => $request['tapestryConsumed'],
								'tapestryUnitCost' => $request['tapestryUnitCost'],
								'tapestryCost' => $request['tapestryCost'],
								'fillerCost' => $request['fillerCost'],
								'labourCost' => $request['labourCost'],
								'hardwareCost1' => $request['hardwareCost1'],
								'hardwareCost2' => $request['hardwareCost2'],
								'hardwareCost3' => $request['hardwareCost3'],
								'hardwareCost4' => $request['hardwareCost4'],
								'hardwareCost5' => $request['hardwareCost5'],
								'pUnitCost' => $request['pUnitCost'],
								'polishCost' => $request['polishCost'],
								'wSPackageCost' => $request['wSPackageCost'],
								'dSPackageCost' => $request['dSPackageCost'],
								'shippingCost' => $request['shippingCost'],
								'costPrice' => $request['costPrice'],
								'adminCostPercent' => $request['adminCostPercent'],
								'adminCost' => $request['adminCost'],
								'profitPercent' => $request['profitPercent'],
								'finalCost' => $request['finalCost'],
								'currency' => $request['currency_clone'][$key],
								'converRate' => $request['converRate_clone'][$key],
								'fobINCost' => $this->roundPricingAmount($request['fobINCost_clone'][$key] ?? null),
								'tariff_percent' => $request['tariff_percent_clone'][$key] ?? 0,
								'final_fob_in_cost' => $this->roundPricingAmount($request['final_fob_in_cost_clone'][$key] ?? $request['fobINCost_clone'][$key] ?? null),
								'boxWt' => $request['boxWt'],
								'volWt' => $volWt,
								'shippingCost2' => $request['shippingCost2_clone'][$key],
								'StorageCost' => $request['StorageCost_clone'][$key],
								'adminCost2' => $request['adminCost2_clone'][$key],
								'qualityAssurance' => $request['qualityAssurance_clone'][$key],
								'landedCost' => $request['landedCost_clone'][$key],
								'adminProfit' => $request['adminProfit_clone'][$key],
								'adminPrice' => $request['adminPrice_clone'][$key],
								'finalPricePer' => $request['finalPricePer_clone'][$key],
								'finalPrice' => $request['finalPrice_clone'][$key],
								'courierType' => $request['courierType_clone'][$key],
								'courierCost' => $request['courierCost_clone'][$key],
								'deliveryCost' => $request['deliveryCost_clone'][$key],
								'adjustment' => $request['adjustment_clone'][$key],
								'newDelCost' => $request['newDelCost_clone'][$key],
								'productType' => $request['productType'],
								'deliveredCostStatus' => $request['deliveredCostStatus'],
								'buyer_id2' => $buyer_id_Clone,
								'destination' => $request['destination_clone'][$key],
								'hardware1' => $request['hardware1'],
								'hardware2' => $request['hardware2'],
								'hardware3' => $request['hardware3'],
								'hardware4' => $request['hardware4'],
								'hardware5' => $request['hardware5'],
								'hardware1_quantity' => $request['hardware1_quantity'],
								'hardware2_quantity' => $request['hardware2_quantity'],
								'hardware3_quantity' => $request['hardware3_quantity'],
								'hardware4_quantity' => $request['hardware4_quantity'],
								'hardware5_quantity' => $request['hardware5_quantity']
							]);
						}else{
							$pricing->product_id 	= $request['product_id'];
							$pricing->buyer_id 		= $request['buyer_id'];
							$pricing->startDate 	= $request['startDate'];
							$pricing->endDate 		= $request['endDate'];
							$pricing->remarks 		= $request['remarks'];
							$pricing->productheight = $request['productheight'];
							$pricing->productwidth 	= $request['productwidth'];
							$pricing->productdepth 	= $request['productdepth'];
							$pricing->boxheight 	= $request['boxheight'];
							$pricing->boxwidth 		= $request['boxwidth'];
							$pricing->boxdepth 		= $request['boxdepth'];
							$pricing->wholesalevolume 	= $request['wholesalevolume'];
							$pricing->dropshipvolume 	= $request['dropshipvolume'];
							$pricing->buyingCost 	= $request['buyingCost'];
							$pricing->fabricCost 	= $request['fabricCost'];
							$pricing->tapestryConsumed 	= $request['tapestryConsumed'];
							$pricing->tapestryUnitCost 	= $request['tapestryUnitCost'];
							$pricing->tapestryCost 	= $request['tapestryCost'];
							$pricing->fillerCost 	= $request['fillerCost'];
							$pricing->labourCost 	= $request['labourCost'];
							$pricing->hardwareCost1 = $request['hardwareCost1'];
							$pricing->hardwareCost2 = $request['hardwareCost2'];
							$pricing->hardwareCost3 = $request['hardwareCost3'];
							$pricing->hardwareCost4 = $request['hardwareCost4'];
							$pricing->hardwareCost5 = $request['hardwareCost5'];
							$pricing->pUnitCost 	= $request['pUnitCost'];
							$pricing->polishCost 	= $request['polishCost'];
							$pricing->wSPackageCost = $request['wSPackageCost'];
							$pricing->dSPackageCost = $request['dSPackageCost'];
							$pricing->shippingCost 	= $request['shippingCost'];
							$pricing->costPrice 	= $request['costPrice'];
							$pricing->adminCostPercent = $request['adminCostPercent'];
							$pricing->adminCost 	= $request['adminCost'];
							$pricing->profitPercent = $request['profitPercent'];
							$pricing->finalCost 	= $request['finalCost'];
							$pricing->currency = $request['currency_clone'][$key];
							$pricing->converRate = $request['converRate_clone'][$key];
							$pricing->fobINCost = $this->roundPricingAmount($request['fobINCost_clone'][$key] ?? null);
							$pricing->tariff_percent = $request['tariff_percent_clone'][$key] ?? 0;
							$pricing->final_fob_in_cost = $this->roundPricingAmount($request['final_fob_in_cost_clone'][$key] ?? $request['fobINCost_clone'][$key] ?? null);
							$pricing->boxWt = $request['boxWt'];
							$pricing->volWt = $volWt;
							$pricing->shippingCost2 = $request['shippingCost2_clone'][$key];
							$pricing->StorageCost = $request['StorageCost_clone'][$key];
							$pricing->shippingCost2 = $request['shippingCost2'];
							$pricing->StorageCost 	= $request['StorageCost'];
							$pricing->adminCost2 	= $request['adminCost2_clone'][$key];
							$pricing->qualityAssurance = $request['qualityAssurance_clone'][$key];
							$pricing->landedCost 	= $request['landedCost_clone'][$key];
							$pricing->adminProfit 	= $request['adminProfit_clone'][$key];
							$pricing->adminPrice 	= $request['adminPrice_clone'][$key];
							$pricing->finalPricePer = $request['finalPricePer_clone'][$key];
							$pricing->finalPrice 	= $request['finalPrice_clone'][$key];
							$pricing->courierType 	= $request['courierType_clone'][$key];
							$pricing->courierCost 	= $request['courierCost_clone'][$key];
							$pricing->deliveryCost 	= $request['deliveryCost_clone'][$key];
							$pricing->adjustment 	= $request['adjustment_clone'][$key];
							$pricing->newDelCost 	= $request['newDelCost_clone'][$key];
							$pricing->productType 	= $request['productType'];
							$pricing->deliveredCostStatus = $request['deliveredCostStatus'];
							$pricing->buyer_id2 	= $buyer_id_Clone;
							$pricing->destination 	= $request['default_country_clone'][$key];
							$pricing->hardware1 	= $request['hardware1'];
							$pricing->hardware2 	= $request['hardware2'];
							$pricing->hardware3 	= $request['hardware3'];
							$pricing->hardware4 	= $request['hardware4'];
							$pricing->hardware5 	= $request['hardware5'];
							$pricing->hardware1_quantity = $request['hardware1_quantity'];
							$pricing->hardware2_quantity = $request['hardware2_quantity'];
							$pricing->hardware3_quantity = $request['hardware3_quantity'];
							$pricing->hardware4_quantity = $request['hardware4_quantity'];
							$pricing->hardware5_quantity = $request['hardware5_quantity'];
							$pricing->save();
						}
					}
				}
			}
            if ($pricingTable->save()){
                return redirect('/pricing')->with('success', 'Pricing was updated successfully.');
             } else{
                return back()->with('danger', 'Error occurred while saving pricing.');
            }
        } else {
            return redirect('/pricing')->with('danger', 'Pricing was not found.');
        }
    }

    public function delete($id)
    {
        $pricingTable = pricingTable::where('id', $id)->first();
        if($pricingTable->delete()){
            return redirect('/pricing')->with('success', 'Pricing deleted successfully.');
        }
        else{
            return redirect('/pricing')->with('danger', 'Pricing was not found.');
        }
    }

    public function data()
    {
        $product=product::all();
        $tempProduct=tempProduct::all();
        $hardwares=hardwares::all();
        $pricedProductKeys = pricingTable::select('product_id', 'productType')
            ->get()
            ->map(function ($row) {
                return (int) $row->productType . ':' . (int) $row->product_id;
            })
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'product' => $product,
            'tempProduct' => $tempProduct,
            'hardwares' => $hardwares,
            'pricedProductKeys' => $pricedProductKeys,
        ]);
    }

    public function checkProductPricing(Request $request)
    {
        $productId = (int) $request->input('product_id', 0);
        $productType = (int) $request->input('product_type', 1);
        if ($productId <= 0) {
            return response()->json(['exists' => false]);
        }

        $exists = pricingTable::where('product_id', $productId)
            ->where('productType', $productType)
            ->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists
                ? 'This product already has pricing. Edit the existing record.'
                : '',
        ]);
    }

    public function apcalculate()
    {
        $tempBuyers = tempBuyer::get();
        return view('apCalculator/create', ['tempBuyers' => $tempBuyers]);
    }

    public function apData($id, $startDate, $endDate)
    {
        $pricingTable=pricingTable::where('buyer_id', $id)->whereBetween('startDate', [$startDate, $endDate])->select('profitPercent')->get();
        $avg=round($pricingTable->avg('profitPercent'),2);
        return response()->json(['avg'=>$avg]);
    }

    public function duplicate($id)
    {
        $pricing = pricingTable::find($id);
		$pageData = [
		'title' => 'Add Pricing(Duplicate Pricing)',
		'dublicateEditRoute' => url('/pricing/create'),
		'duplicatePage' => true,
		'editMode' => false,
		'duplicateEditButtonName' => 'Duplicate Price',
		];
        if($pricing->productType == 1){
            $theProduct = product::where('id', $pricing->product_id)->first();
        }
        if($pricing->productType == 0){
            $theProduct = tempProduct::where('id', $pricing->product_id)->first();
        }

        $tempProducts = tempProduct::all();
        $tempBuyers = tempBuyer::all();
        $products = product::all();
        $settings = setting::all();
        $couriers = courier::where('country',$pricing->destination)->get();
		$hardwares = hardwares::get();
		$SettingsOption = Common::getSettings();
		
        if ($pricing){
			$productCount = pricingTable::where('product_id', $pricing->product_id)
				->where('productType', $pricing->productType)
				->count();
			$productCount = $productCount - 1;
			$buyerCountry =tempBuyer::select('country')->where('id',$pricing->buyer_id2)->first();
			return view('pricing/view', ['pricing' => $pricing, 'tempProducts' => $tempProducts, 'tempBuyers' => $tempBuyers, 'products' => $products, 'theProduct' => $theProduct, 'settings' => $settings, 'couriers'=>$couriers, 'hardwares'=>$hardwares, 'settings_option'=>$SettingsOption, 'buyer_country'=>$buyerCountry,'productCount'=>$productCount,'page_data'=>$pageData]);
        }
        else{
            return redirect('/pricing')->with('danger', 'Pricing was not found. ');
        }
	}

    public function duplicateStore(Request $request)
    {
        $this->validateBoxWeightRequest($request);
        $this->validateFulfilmentCostRequest($request);

        $dest = $request['destination'] ?? $request['default_country_0'] ?? '';
        $volWt = $this->volumetricWtForDestination($request, $dest);

        pricingTable::create([
            'product_id' => $request['product_id'],
            'buyer_id' => $request['buyer_id'],
            'startDate' => $request['startDate'],
            'endDate' => $request['endDate'],
            'remarks' => $request['remarks'],
            'buyingCost' => $request['buyingCost'],
            'fabricCost' => $request['fabricCost'],
            'tapestryConsumed' => $request['tapestryConsumed'],
            'tapestryUnitCost' => $request['tapestryUnitCost'],
            'tapestryCost' => $request['tapestryCost'],
            'fillerCost' => $request['fillerCost'],
            'labourCost' => $request['labourCost'],
            'hardwareCost1' => $request['hardwareCost1'],
            'hardwareCost2' => $request['hardwareCost2'],
            'hardwareCost3' => $request['hardwareCost3'],
            'hardwareCost4' => $request['hardwareCost4'],
            'hardwareCost5' => $request['hardwareCost5'],
            'pUnitCost' => $request['pUnitCost'],
            'polishCost' => $request['polishCost'],
            'wSPackageCost' => $request['wSPackageCost'],
            'dSPackageCost' => $request['dSPackageCost'],
            'shippingCost' => $request['shippingCost'],
            'costPrice' => $request['costPrice'],
            'adminCostPercent' => $request['adminCostPercent'],
            'adminCost' => $request['adminCost'],
            'profitPercent' => $request['profitPercent'],
            'finalCost' => $request['finalCost'],
            'currency' => $request['currency'],
            'converRate' => $request['converRate'],
            'fobINCost' => $request['fobINCost'],
            'tariff_percent' => $request['tariff_percent'] ?? 0,
            'final_fob_in_cost' => $this->roundPricingAmount($request['final_fob_in_cost'] ?? $request['fobINCost']),
            'boxWt' => $request['boxWt'],
            'volWt' => $volWt,
            'shippingCost2' => $request['shippingCost2'],
            'StorageCost' => $request['StorageCost'],
            'adminCost2' => $request['adminCost2'],
            'qualityAssurance' => $request['qualityAssurance'],
            'landedCost' => $request['landedCost'],
            'adminProfit' => $request['adminProfit'],
            'adminPrice' => $request['adminPrice'],
            'finalPricePer' => $request['finalPricePer'],
            'finalPrice' => $request['finalPrice'],
            'courierType' => $request['courierType'],
            'courierCost' => $request['courierCost'],
            'deliveryCost' => $request['deliveryCost'],
            'adjustment' => $request['adjustment'],
            'newDelCost' => $request['newDelCost'],
            'productType' => $request['productType'],
            'deliveredCostStatus' => $request['deliveredCostStatus'],
            'buyer_id2' => $request['buyer_id2'],
            'destination' => $request['destination'],
			'hardware1' => $request['hardware1'],
            'hardware2' => $request['hardware2'],
            'hardware3' => $request['hardware3'],
            'hardware4' => $request['hardware4'],
            'hardware5' => $request['hardware5'],
            'hardware1_quantity' => $request['hardware1_quantity'],
            'hardware2_quantity' => $request['hardware2_quantity'],
            'hardware3_quantity' => $request['hardware3_quantity'],
            'hardware4_quantity' => $request['hardware4_quantity'],
            'hardware5_quantity' => $request['hardware5_quantity']
        ]);
        return redirect('/pricing')->with('success', 'Pricing was added successfully.');
    }

    public function related_product_calculator(){
    	//echo 'Cal work in progress..';die;
    	$products = product::get();
        return view('pricing/related_product_calculator', ['products' => $products]);
    }

	public function getnewdelcost(Request $request)
    {	
		
		$pricing = pricingTable::where('product_id',$request['id'])->first();
		// echo "<pre>";
		// print_r($pricing->newDelCost);die;
		$newDelCost = $pricing->newDelCost;		
        return response()->json(['newDelCost'=>$newDelCost]);
    }

	public function check_courier_country(Request $request)
    {
        $buyer = tempBuyer::where('id', $request['selectedId'])->first();
		$adminProfitPercentage = $buyer->admin_profit ?? 0;
		$finalPricePercentag = $buyer->final_price_percent ?? 0;
		$tariffSolidWoodPercent = $buyer->tariff_solid_wood_percent ?? 0;
		$tariffUpholsteredPercent = $buyer->tariff_upholstered_percent ?? 0;
		$costAdjustmentsPercentage = $buyer->cost_adjustments ?? 0;
		// Temp buyer courierType: null/''/0 = No Courier (do not auto-apply country default).
		$buyerCourierType = $buyer->courierType ?? null;
		$buyerHasCourier = !($buyerCourierType === null || $buyerCourierType === '' || (int) $buyerCourierType === 0);
        if ($buyer && !empty($buyer->country)) {
            $pricingCountry = $buyer->country;
            $courierName = courier::where(['country'=> $pricingCountry])->get();
          } else {
            $pricingCountry = false;
            $courierName = false;
        }
        return response()->json([
            'courierName' => $courierName,
            'pricingCountry' => $pricingCountry,
            'adminProfitPercentage' => $adminProfitPercentage,
            'finalPricePercentag' => $finalPricePercentag,
            'tariffSolidWoodPercent' => $tariffSolidWoodPercent,
            'tariffUpholsteredPercent' => $tariffUpholsteredPercent,
            'costAdjustmentsPercentage' => $costAdjustmentsPercentage,
            'buyerCourierType' => $buyerCourierType,
            'buyerHasCourier' => $buyerHasCourier,
        ]);
    }

	public function getVolumetricWtByCountry($request ,$default_country){

		$country = strtolower(trim((string) $default_country));
		$volumetricWt = PricingVolumetricWeight::isLbsDestination($country)
			? $request['volWt_lbs']
			: $request['volWt'];
		return ['weightUnit' => $volumetricWt];
	}

	protected function volumetricWtForDestination(Request $request, ?string $destination): float
	{
		$setting = setting::first();
		$settingData = $setting ? $setting->toArray() : [];
		$pricingData = [
			'boxwidth' => $request->input('boxwidth'),
			'boxheight' => $request->input('boxheight'),
			'boxdepth' => $request->input('boxdepth'),
		];

		return PricingVolumetricWeight::forDestination($pricingData, $settingData, (string) ($destination ?? ''));
	}

	public function getBuyerFormOnEdit(Request $request)
	{
		$productId = $request->input('productId');
		$buyerId = $request->input('buyerId');
		$pricingId = $request->input('pricingId');

		if ($productId === null || $productId === '' || $buyerId === null || $buyerId === '' || $pricingId === null || $pricingId === '') {
			return view('pricing/edit_pricing_ajax', ['buyer_data' => collect(), 'couriers' => collect()]);
		}

		$mainRow = pricingTable::find($pricingId);
		if (! $mainRow) {
			return view('pricing/edit_pricing_ajax', ['buyer_data' => collect(), 'couriers' => collect()]);
		}

		$buyerData = pricingTable::query()
			->select('temp_buyer_table.c_name', 'pricingTable.*')
			->join('temp_buyer_table', 'temp_buyer_table.id', '=', 'pricingTable.buyer_id2')
			->where('pricingTable.product_id', $productId)
			->where('pricingTable.productType', $mainRow->productType)
			->where('pricingTable.buyer_id', $buyerId)
			->where('pricingTable.id', '!=', $pricingId)
			->whereNotNull('pricingTable.buyer_id2')
			->where('pricingTable.buyer_id2', '!=', '')
			->orderBy('pricingTable.id')
			->get();

		$couriers = courier::all();

		return view('pricing/edit_pricing_ajax', [
			'buyer_data' => $buyerData,
			'couriers' => $couriers,
		]);
	}

	// function getFobValue(Request $request){
	// 	$fobCost = 0;
	// 	$buyerId   = $request['buyerId'];
	// 	$productId = $request['productId'];
	// 	$buyerCode = buyer::find($buyerId);
	// 	$tempBuyerCode = tempBuyer::where('id',$buyerCode->code)->first();
	// 	if(!empty($tempBuyerCode->code)){
	// 		$buyerData = PricingTable::select('fobINCost')->where(['product_id'=>$productId,'buyer_id'=>$buyerId,'buyer_id2'=>$tempBuyerCode->id])->first();
	// 		if(!empty($buyerData->fobINCost)){
	// 			$fobCost = $buyerData->fobINCost;
	// 		}
	// 	}
    //     return $fobCost;
    // }

	public function getFobValue(Request $request)
{
    $buyerId   = $request['buyerId'];
    $productId = $request['productId'];

    $fobCost = InvoicePricing::getFobInCostForInvoiceBuyer((int) $productId, $buyerId);

    if ($fobCost !== null) {
        return $fobCost;
    }

    return response()->noContent();
}
	public function getCustomValue(Request $request)
	{
		$courierType = $request->input('courierType');
		$country = strtoupper((string) $request->input('country', ''));

		$courierData = null;
		if ($courierType) {
			$q = courier::query()->where('name', $courierType);
			if ($country !== '') {
				$q->where(function ($q2) use ($country) {
					$q2->where('country', $country)->orWhere('country', strtolower($country));
				});
			}
			$courierData = $q->first();
			if (! $courierData) {
				$courierData = courier::where('name', $courierType)->first();
			}
		}

		if ($courierData && UsCourierRuleService::tablesExist()) {
			$blockCountry = $country !== '' ? strtoupper($country) : strtoupper((string) ($courierData->country ?? ''));
			if (UsCourierRuleService::supportsBlockEngine($blockCountry)) {
				$payload = UsCourierRuleService::buildPayload((int) $courierData->id, $blockCountry);
				if ($payload !== null) {
					return response()->json($payload);
				}
			}
		}

		$customCondition = 0;
		if ($courierData && ! empty($courierData->custom_condition)) {
			$customCondition = $courierData->custom_condition;
		}

		return response($customCondition);
	}
	public function excelSheet()
	{
		return view('pricing.sheet');
	}
public function excelSheetstore(Request $request)
{
    $request->validate([
        'sheet' => 'required|file|mimes:xlsx,xls,csv',
    ]);

    $file = $request->file('sheet');
    $data = ExcelFacade::toArray([], $file);

    if (count($data) && count($data[0])) {
        foreach ($data[0] as $row) {
            if (!isset($row[0]) || strtolower(trim($row[0])) === 'product_sku') continue;

            $pricingExcel = PricingExcelSheet::create([
                'product_sku' => trim($row[0]),
                'buying_cost' => is_numeric($row[1]) ? floatval($row[1]) : null,
            ]);

            $productsku = $pricingExcel->product_sku;
            $pricingBuyingCost = $pricingExcel->buying_cost;

            $product = product::where('code', $productsku)->first();

            if ($product) {
                $pricingTables = pricingTable::where('product_id', $product->id)->get();

                foreach ($pricingTables as $pricingTab) {
                    $pricingTab->buyingCost = $pricingBuyingCost;
                    $pricingTab->save();
                }
            }
        }
    }

    return back()->with('success', 'Excel data imported and pricing table updated successfully!');
}

  public function excelSheetFormat()
    {
        $fileName = 'pricingBuyingCost.xlsx';
        return (new PricingBuyingCostExport())->download($fileName);
    }

    private function roundPricingAmount($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value);
    }

    /**
     * Box Wt. (kg) in Storage Cost must be a positive number.
     */
    private function validateBoxWeightRequest(Request $request): void
    {
        $request->validate([
            'boxWt' => 'required|numeric|gt:0',
        ], [
            'boxWt.required' => 'Box Wt. (kg) is required.',
            'boxWt.gt' => 'Box Wt. (kg) must be greater than 0.',
        ]);
    }

    /**
     * Server-side checks for Buyer 2 (destination) and fulfilment cost fields.
     * Skipped when delivered cost section is disabled (deliveredCostStatus != 1).
     */
    private function validateFulfilmentCostRequest(Request $request): void
    {
        if ((int) $request->input('deliveredCostStatus', 1) !== 1) {
            return;
        }

        $rules = [
            'buyer_id2' => 'required|exists:temp_buyer_table,id',
            'default_country_0' => 'required|string',
            'converRate' => 'required|numeric|min:0',
            'adminCost2' => 'required|numeric|min:0',
            'qualityAssurance' => 'required|numeric|min:0',
            'adminProfit' => 'required|numeric|min:0',
            'finalPricePer' => 'required|numeric|min:0',
            'adjustment' => 'required|numeric|min:0',
            'deliveryCost' => 'required|numeric|min:0',
        ];

        $messages = [
            'buyer_id2.required' => 'Please select Buyer 2 (destination buyer).',
            'buyer_id2.exists' => 'Selected destination buyer is invalid.',
            'default_country_0.required' => 'Country of destination is required. Select a destination buyer with a valid country.',
            'converRate.required' => 'Conversion rate is required for fulfilment cost.',
            'adminCost2.required' => 'Outbound charges are required for fulfilment cost.',
            'qualityAssurance.required' => 'Quality assurance is required for fulfilment cost.',
            'adminProfit.required' => 'Admin profit (%) is required for fulfilment cost.',
            'finalPricePer.required' => 'Final price (%) is required for fulfilment cost.',
            'adjustment.required' => 'Cost adjustment (%) is required for fulfilment cost.',
            'deliveryCost.required' => 'Delivery cost is required for fulfilment cost.',
        ];

        $cloneIds = $request->input('buyer_id2_multiple', []);
        if (!empty($cloneIds)) {
            $rules['buyer_id2_multiple'] = 'required|array|min:1';
            $rules['buyer_id2_multiple.*'] = 'required|exists:temp_buyer_table,id';
            $rules['converRate_clone'] = 'required|array';
            $rules['converRate_clone.*'] = 'required|numeric|min:0';
            $rules['adminCost2_clone'] = 'required|array';
            $rules['adminCost2_clone.*'] = 'required|numeric|min:0';
            $rules['qualityAssurance_clone'] = 'required|array';
            $rules['qualityAssurance_clone.*'] = 'required|numeric|min:0';
            $rules['adminProfit_clone'] = 'required|array';
            $rules['adminProfit_clone.*'] = 'required|numeric|min:0';
            $rules['finalPricePer_clone'] = 'required|array';
            $rules['finalPricePer_clone.*'] = 'required|numeric|min:0';
            $rules['adjustment_clone'] = 'required|array';
            $rules['adjustment_clone.*'] = 'required|numeric|min:0';
        }

        $request->validate($rules, $messages);

        $buyer = tempBuyer::find($request->input('buyer_id2'));
        if (!$buyer || trim((string) $buyer->country) === '') {
            throw ValidationException::withMessages([
                'buyer_id2' => 'Selected destination buyer must have a country configured.',
            ]);
        }

        foreach ($cloneIds as $index => $cloneId) {
            $cloneBuyer = tempBuyer::find($cloneId);
            if (!$cloneBuyer || trim((string) $cloneBuyer->country) === '') {
                throw ValidationException::withMessages([
                    "buyer_id2_multiple.{$index}" => 'Each additional destination buyer must have a country configured.',
                ]);
            }

            $destination = $request->input("destination_clone.{$index}")
                ?? $request->input("default_country_clone.{$index}");
            if (trim((string) $destination) === '') {
                throw ValidationException::withMessages([
                    "destination_clone.{$index}" => 'Country of destination is required for each additional buyer.',
                ]);
            }
        }

        $this->validateUniqueDestinationBuyers($request);
    }

    /**
     * One destination buyer (buyer_id2) per product on create/edit submit.
     */
    private function validateUniqueDestinationBuyers(Request $request): void
    {
        if ((int) $request->input('deliveredCostStatus', 1) !== 1) {
            return;
        }

        $main = trim((string) $request->input('buyer_id2'));
        $clones = array_map('strval', (array) $request->input('buyer_id2_multiple', []));
        $all = [];

        if ($main !== '') {
            $all[] = $main;
        }
        foreach ($clones as $cloneId) {
            if ($cloneId !== '') {
                $all[] = $cloneId;
            }
        }

        $counts = array_count_values($all);
        $duplicateIds = array_keys(array_filter($counts, static fn ($count) => $count > 1));

        if ($duplicateIds === []) {
            return;
        }

        $names = tempBuyer::whereIn('id', $duplicateIds)->pluck('c_name', 'id');
        $labels = array_map(
            static fn ($id) => (string) ($names->get($id) ?: "ID {$id}"),
            $duplicateIds
        );

        throw ValidationException::withMessages([
            'buyer_id2' => 'Each destination buyer can only be selected once for this product. Duplicate: '
                . implode(', ', $labels) . '.',
        ]);
    }

}
