<?php

namespace App\Exports;

use App\product;
use App\hardwares;
use App\pricingTable;
use App\WfConsumable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllProductsExport implements FromArray, WithHeadings
{
	use Exportable;

	protected $exportData = [];

	public function __construct()
	{
		$this->prepareExportData();
	}

	private function prepareExportData()
	{
		// $products = product::where('id', 495)->get();
		$products = product::all();


		foreach ($products as $product) {
			$row = $this->mapProductRow($product);
			$this->exportData[] = $row;

			$consumables = WfConsumable::where('product_id', $product->id)->with('consumable')->get();

			if ($consumables->count() > 0) {
				$this->exportData[] = ['---- Consumables ----'];
				$this->exportData[] = [
					'',
					'Name',
					'Qty',
					'Rate',
					'Unit'
				];

				foreach ($consumables as $key => $consumable) {
					$this->exportData[] = [

						$key + 1,
						optional($consumable->consumable)->name ?? 'N/A',
						$consumable->qty !== null ? $consumable->qty : 0,
						$consumable->consumable->rate !== null ? $consumable->consumable->rate : 0,
						$consumable->unit_type_name ?? 'N/A',
					];
				}
			}
		}
	}



	public function array(): array
	{
		return $this->exportData;
	}

	public function headings(): array
	{
		return [
			"Product",
			"Product Code",
			"Product Name",
			"EAN",
			"HSN",
			"Category Name",
			"Sub-Category Name",
			"Finishing",
			"Finishing Price",
			"GSTSlab",
			"Width",
			"Height",
			"Depth",
			"Box Width",
			"Box Height",
			"Box Depth",
			"WholeSale Volume",
			"DropShip Volume",
			"Volume",
			"Quantity",
			"Hardware 1",
			"Hardware 1 Quantity",
			"Hardware Cost1",
			"Hardware 2",
			"Hardware 2 Quantity",
			"Hardware Cost2",
			"Hardware 3",
			"Hardware 3 Quantity",
			"Hardware Cost3",
			"Hardware 4",
			"Hardware 4 Quantity",
			"Hardware Cost4",
			"Hardware 5",
			"Hardware 5 Quantity",
			"Hardware Cost5",
			"Upholstry",
			"Corner",
			"L",
			"Addons",
			"Remarks",
			"Location"
		];
	}

	private function mapProductRow($product): array
	{
		$pricing = pricingTable::where('product_id', $product->id)->where('buyer_id2', 3)->first();

		$hd_data = [];
		for ($i = 1; $i <= 5; $i++) {
			$hd = null;
			$hd_id = $product->{'hardware' . $i};
			$hd_qty = $product->{'hardware' . $i . '_quantity'};
			$hd_name = '';
			$hd_cost = '';

			if ($hd_id) {
				$hd = hardwares::find($hd_id);
				if ($hd) {
					$hd_name = $hd->name;
					if ($pricing && $pricing->{'hardwareCost' . $i} != "") {
						$hd_cost = $pricing->{'hardwareCost' . $i};
					} else {
						$hd_cost = $hd_qty * $hd->rate;
					}
				}
			}
			$hd_data[] = [$hd_name, $hd_qty, $hd_cost];
		}

		$location = '';
		if ($product->productLocations) {
			foreach ($product->productLocations as $productLocation) {
				$location .= $productLocation->location . ' - ' . $productLocation->quantity . "\n";
			}
		}

		return [
			$product->code . ' - ' . $product->name,
			$product->code,
			$product->name,
			$product->EAN,
			$product->HSN,
			optional($product->category)->name,
			optional($product->subcategory)->name,
			$product->finishing,
			$product->finishing_price,
			$product->gstslab,
			$product->width,
			$product->height,
			$product->depth,
			$product->boxwidth,
			$product->boxheight,
			$product->boxdepth,
			$product->wholesalevolume,
			$product->dropshipvolume,
			$product->volume,
			$product->quantity,
			$hd_data[0][0],
			$hd_data[0][1],
			$hd_data[0][2],
			$hd_data[1][0],
			$hd_data[1][1],
			$hd_data[1][2],
			$hd_data[2][0],
			$hd_data[2][1],
			$hd_data[2][2],
			$hd_data[3][0],
			$hd_data[3][1],
			$hd_data[3][2],
			$hd_data[4][0],
			$hd_data[4][1],
			$hd_data[4][2],
			$product->upholstry,
			$product->corner,
			$product->lhardware,
			$product->addons,
			$product->remarks,
			$location
		];
	}
}
