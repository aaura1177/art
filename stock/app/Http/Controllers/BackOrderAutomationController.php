<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\allocated_backorder;
use App\poTable;
use App\product;
use App\PurchaseOrder;
use App\Mail\PoRaiseMail;
use Mail;

class BackOrderAutomationController extends Controller
{
    public function checkQtyInPoOrder($sku)
    {
        // Get all PoTable entries with matching SKU and remqty > 0
        $poItems = PoTable::with(['purchaseOrderTable', 'product'])
                            ->where('remqty', '>', 0)
                            ->whereHas('product', function ($query) use ($sku) {
                                $query->where('code', $sku);
                            })
                            ->get();
    
        if ($poItems->isEmpty()) {
           // return response()->json(['message' => 'Requested SKU not found in any purchase orders.']);
            return response()->json([
                'status' => "wc-backorder04",
                'msg' => 'Notified Jaipur Factory',
                'fulfilled_po_id' => "",
                'supplier_reference' => '',
                'del_date' => ''
            ]);
        }
    
        $groupedByPo = [];
    
        // Group and sum remqty by poid
        foreach ($poItems as $poItem) {
            $poid = $poItem->poid;
            if (!isset($groupedByPo[$poid])) {
                $groupedByPo[$poid] = [
                    'purchase_order_id' => $poid,
                    'total_remqty' => 0,
                    'supplier_reference' => optional($poItem->purchaseOrderTable)->ref_supplier
                ];
            }
            $groupedByPo[$poid]['total_remqty'] += $poItem->remqty;
        }
    
        // Filter only those with remqty > 0 (should already be, but just in case)
        $filteredPurchaseOrders = array_filter($groupedByPo, function ($item) {
            return $item['total_remqty'] > 0;
        });
    
      
        return array_values($filteredPurchaseOrders);
    }
    

    // public function checkBackorderStockStatus1(Request $request)
    // {
        
    //     $input = $request->all();
        
    //     // Validate the input data
    //     $validator = Validator::make($input, [
    //         'order_id' => 'required|numeric',
    //         'quantity' => 'required|integer|min:1',
    //         'sku' => 'required|string|max:10',
    //         'location' => 'required'
    //     ]);

    //     // Return validation errors if validation fails
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 400,
    //             'error' => $validator->errors()
    //         ]);
    //     }

    //     // Extract values from the input
    //     $code = $input['sku'] ?? '';
    //     $order_id = $input['order_id'] ?? '';
    //     $requested_quantity = (int) ($input['quantity'] ?? 0);
    //     $location = $input['location'];

    //     // Fetch current stock for the given SKU
    //     $currentStock = product::where(['code' => $code])
    //         ->select(['quantity', 'EAN', 'id'])
    //         ->first();

    //     // If no stock found for the SKU
    //     if (!$currentStock) {
    //         return response()->json([
    //             'status' => 404,
    //             'msg' => 'Product not found'
    //         ]);
    //     }

    //     // Check if the order already exists in allocated_backorder
    //     $orderExists = allocated_backorder::where(['sku' => $code, 'order_id' => $order_id])->first();
    //     // If the order doesn't exist, insert it
    //     if (!$orderExists) {
    //         allocated_backorder::insert([
    //             'sku' => $code,
    //             'order_id' => $order_id,
    //             'quantity' => $requested_quantity
    //         ]);
    //     }

    //     // Check for the EAN (European Article Number)
    //     $product_Ean = $currentStock['EAN'] ?? '';
    //     if (!$product_Ean) {
    //         return response()->json([
    //             'status' => 401,
    //             'msg' => 'EAN not found'
    //         ]);
    //     }
              
    //     // API call to get the remaining PO quantities
    //     $data = $this->checkQtyInPoOrder($code);
       
    //     // Initialize supplier references from the API response
    //     $supplierReferences = [];
    //     $Supplier = [];
    //     foreach ($data as $order) {
    //         if (strpos($order['supplier_reference'], $location) !== false) {
    //             $supplierReferences[$order['purchase_order_id']] = $order['total_remqty'];
    //             $Supplier[$order['purchase_order_id']] = $order['supplier_reference'];
    //         }
    //     }
        
    //     // Initialize remaining stock and fulfillment checks
    //     $remainingStock = $currentStock['quantity'] ?? 0;
    //     $quantityToFulfill = $requested_quantity;
    //     $fulfilledPoId = null;

    //     // First, try to fulfill the order from the existing stock
    //     if ($remainingStock >= $quantityToFulfill) 
    //     {
    //         // If we have enough stock, fulfill the order
    //         $remainingStock -= $quantityToFulfill;
    //         $orderExists = allocated_backorder::where(['sku' => $code, 'order_id' => $order_id])->first();
            
    //         // Order fulfilled directly from stock
    //         return response()->json([
    //             'status' => "wc-backorder24",
    //             'msg' => 'Getting Polished, QC\'d and Packed',
    //             'fulfilled_po_id' => $orderExists['po_number'] ?? '',
    //             'supplier_reference' => $orderExists['supplier_refrence'] ?? ''
    //         ]);

    //     } else {
    //         // If stock is insufficient, we need to fulfill the remaining quantity using the available POs
    //         $quantityToFulfill -= $remainingStock; // Reduce the remaining quantity by the stock available
    //         $remainingStock = 0; // Stock is now depleted

    //         // Now, try fulfilling the order using the available POs
    //         foreach ($supplierReferences as $po_id => $available_qty) {
    //             if ($quantityToFulfill <= 0) {
    //                 break; // Stop if we've already fulfilled the entire required quantity
    //             }

    //             // Deduct from this PO to fulfill the remaining order
    //             $qtyToDeduct = min($available_qty, $quantityToFulfill); // Deduct the minimum of what's available or needed
    //             $quantityToFulfill -= $qtyToDeduct; // Decrease the required quantity

    //             // If this PO is used to fulfill part of the order, store the PO ID
    //             if ($fulfilledPoId === null) {
    //                 $fulfilledPoId = $po_id;
    //             }

    //             // Update PO quantity (deduct from the available PO)
    //             $supplierReferences[$po_id] -= $qtyToDeduct;
    //         }

    //         // After trying all available POs, check if we fulfilled the order
    //         if ($quantityToFulfill > 0) 
    //         {
    //             $this->sendPOMailtoFactory();

    //             // If we still can't fulfill the order, notify Jaipur factory
    //             return response()->json([
    //                 'status' => "wc-backorder04",
    //                 'msg' => 'Notified Jaipur Factory'
    //             ]);

    //         } else {

    //             DB::table('allocated_backorder')
    //                 ->where('sku', $code)
    //                 ->where('order_id', $order_id)
    //                 ->update([
    //                     'po_number' => $fulfilledPoId,
    //                     'supplier_reference' => $Supplier[$fulfilledPoId] ?? '',
    //                     'updated_at' => now(),
    //                 ]);
    //                 $purchaseOrder = DB::table('purchase_order')->where('id', $fulfilledPoId)->first();
    
    //             // If part or all of the order was fulfilled using POs
    //             return response()->json([
    //                 'status' => "wc-backorder14",
    //                 'msg' => 'Under Production',
    //                 'fulfilled_po_id' => $fulfilledPoId,
    //                 'supplier_reference' => $Supplier[$fulfilledPoId] ?? '',
    //                 'del_date' => $purchaseOrder->del_date ?? ''
    //             ]);
    //         }
    //     }
    // }

    public function checkBackorderStockStatus(Request $request)
    {
        $input = $request->only(['order_id', 'quantity', 'sku', 'location']);
    
        $validator = Validator::make($input, [
            'order_id' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
            'sku' => 'required|string|max:10',
            'location' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'error' => $validator->errors()
            ]);
        }
    
        $sku = $input['sku'];
        $orderId = $input['order_id'];
        $quantity = (int) $input['quantity'];
        $location = $input['location'];
    
        $product = product::where('code', $sku)->select(['quantity', 'EAN', 'id'])->first();
        
        $stock = 0;
        if($product)
        {
            $stock = $product->quantity;
        }
       
        $requiredQty = $quantity;
        $fulfilledPoId = null;
        
        // Case 1: Enough stock to fulfill
        if ($stock >= $requiredQty) 
        {
            return response()->json([
                'status' => "wc-backorder24",
                'msg' => 'Getting Polished, QC\'d and Packed',
                'fulfilled_po_id' => "",
                'supplier_reference' => "",
                'del_date' => ''
            ]);
        }
        
        // Case 2: Use PO quantities
      
            $poData = $this->checkQtyInPoOrder($sku);
 
            if ($poData instanceof \Illuminate\Http\JsonResponse) {
                return $poData; // early return or handle the response as needed
            }
            $matchingPOs = collect($poData)->filter(function ($po) use ($location) {
                return strpos($po['supplier_reference'], $location) !== false;
            });
    
        
            $supplierReferences = $matchingPOs->pluck('total_remqty', 'purchase_order_id')->toArray();
            $supplierDetails = $matchingPOs->pluck('supplier_reference', 'purchase_order_id')->toArray();
        
            $requiredQty -= $stock;  // remaining qty  e.g 21-15 = 6 

            // check how many will be fullfiled from existing PO's and how many are left. 
            // e.g if 4 are in PO than 2 are left to be raised for new po
            foreach ($supplierReferences as $poId => $availableQty) {
                if ($requiredQty <= 0) break;
        
                $deductQty = min($availableQty, $requiredQty);
                $requiredQty -= $deductQty;
        
                if (!$fulfilledPoId) {
                    $fulfilledPoId = $poId;
                }
            }
            // item qty is not enough in po . e.g 2 are not present in PO 
            if ($requiredQty > 0) {
               // $this->sendPOMailtoFactory($requiredQty,$sku);
                return response()->json([
                    'status' => "wc-backorder04",
                    'msg' => 'Notified Jaipur Factory',
                    'fulfilled_po_id' => "",
                    'supplier_reference' => '',
                    'del_date' => ''
                ]);
            }
            
            // if item is enough in po  

            $po = DB::table('purchase_order')->find($fulfilledPoId);
            return response()->json([
                'status' => "wc-backorder14",
                'msg' => 'Under Production',
                'fulfilled_po_id' => $fulfilledPoId,
                'supplier_reference' => $supplierDetails[$fulfilledPoId] ?? '',
                'del_date' => $po->del_date ?? ''
            ]); 
    }

    private function updateStock($productId, $quantity)
    {
        product::where('id', $productId)->update(['quantity' => $quantity]);
    }

    // public function sendPOMailtoFactory()
    // {      
    //     $unfulfilledPreOrders = allocated_backorder::where(function ($query) {
    //                                 $query->where('quantity', '!=', DB::raw('fulfilled'))
    //                                     ->orWhereNull('fulfilled');
    //                             })->get();

    //     $skuQuantities = [];
    //     $skuCodes = [];
    //     $eanCodes = [];

    //     // Calculate remaining quantities for each SKU
    //     foreach ($unfulfilledPreOrders as $order) {
    //         $sku = $order['sku'];
    //         $quantity = $order['quantity'];
    //         $fulfilledQuantity = $order['fulfilled'];

    //         $skuQuantities[$sku] = ($skuQuantities[$sku] ?? 0) + ($quantity - $fulfilledQuantity);

    //         if (!in_array($sku, $skuCodes)) {
    //             $skuCodes[] = $sku;
    //         }
    //     }

    //     // Get EANs for the SKUs
    //     $eanQueryResults = product::whereIn('code', $skuCodes)->select(['EAN', 'code'])->get();

    //     foreach ($eanQueryResults as $ean) {
    //         $eanCodes[$ean['code']] = $ean['EAN'];
    //     }

    //     // Get already raised PO quantities and sum them by EAN
    //     $raisedPOQuantities = poTable::whereIn('EAN', $eanCodes)->select(['remqty', 'EAN'])->get();

    //     // Prepare a map to sum remaining quantities by EAN
    //     $remainingQuantityByEAN = [];
    //     foreach ($raisedPOQuantities as $po) {
    //         $remainingQuantityByEAN[$po->EAN] = ($remainingQuantityByEAN[$po->EAN] ?? 0) + $po->remqty;
    //     }

    //     // Prepare the response with SKUs and their quantities left to raise a new PO
    //     $details = [];
    //     foreach ($skuQuantities as $sku => $remainingQuantity) {
    //         // Get EAN for the SKU
    //         $ean = $eanCodes[$sku] ?? null;

    //         // Get total remaining quantity for the EAN
    //         $totalRemainingQuantity = $remainingQuantityByEAN[$ean] ?? 0;
    //         // Calculate the quantity needed for the new PO
    //         $quantityToOrder = max(0, $remainingQuantity - $totalRemainingQuantity); // Ensure it's not negative

    //         if ($quantityToOrder > 0) {
    //             $details[] = [
    //                 'SKU' => $sku,
    //                 'EAN' => $ean,
    //                 'quantity_to_order' => $quantityToOrder,
    //             ];
    //         }
    //     }

    //     // $x = mail::to('dilshadahmed7454@gmail.com')->send(new PoRaiseMail($details));
       
    //     return $details;
    // }

    public function sendPOMailtoFactory($requiredQty,$sku)
    {
        
        $skuQuantities[] = $requiredQty;
        $skuCodes[] = $sku;
        $eanCodes = [];

        
        // Get EANs for the SKUs
        $eanQueryResults = product::whereIn('code', $skuCodes)->select(['EAN', 'code'])->get();

        foreach ($eanQueryResults as $ean) {
            $eanCodes[$ean['code']] = $ean['EAN'];
        }

        // Get already raised PO quantities and sum them by EAN
        $raisedPOQuantities = poTable::whereIn('EAN', $eanCodes)->select(['remqty', 'EAN'])->get();

        // Prepare a map to sum remaining quantities by EAN
        $remainingQuantityByEAN = [];
        foreach ($raisedPOQuantities as $po) {
            $remainingQuantityByEAN[$po->EAN] = ($remainingQuantityByEAN[$po->EAN] ?? 0) + $po->remqty;
        }

        // Prepare the response with SKUs and their quantities left to raise a new PO
        $details = [];
        foreach ($skuQuantities as $sku => $remainingQuantity) {
            // Get EAN for the SKU
            $ean = $eanCodes[$sku] ?? null;

            // Get total remaining quantity for the EAN
            $totalRemainingQuantity = $remainingQuantityByEAN[$ean] ?? 0;
            // Calculate the quantity needed for the new PO
            $quantityToOrder = max(0, $remainingQuantity - $totalRemainingQuantity); // Ensure it's not negative

            if ($quantityToOrder > 0) {
                $details[] = [
                    'SKU' => $sku,
                    'EAN' => $ean,
                    'quantity_to_order' => $quantityToOrder,
                ];
            }
        }

        // dd($details);
        \Log::info('Details for PO Mail ');
        \Log::info(json_encode($details));

        $x = mail::to('dilshadahmed7454@gmail.com')->send(new PoRaiseMail($details));
        return $details;
    }
}