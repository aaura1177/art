<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\ContainersAllocation;
use App\product;

class ContainerAllocation extends Command
{
    protected $signature = 'allocation:get-container-allocation';
    protected $description = 'Get Container Allocation From ER Software';

    public function handle()
    {
        try {
            $response = Http::timeout(10)->get("https://artisanerp.net/public_html/admin/imsadmin/getplannedcontainers");

            if (!$response->successful()) {
                $message = "API request failed with status: " . $response->status();
                $this->error($message);
                \Log::info($message);
                return;
            }

            $apiData = $response->json();
            $records = $apiData['data'] ?? [];

            if (empty($records)) {
                $message = "No records found in API response.";
                $this->info($message);
                \Log::info($message);
                return;
            }

            foreach ($records as $item) {
                $skuRecords = $item['skus'] ?? [];
                $itemsData = [];

                foreach ($skuRecords as $skuRecord) {
                    $sku = $skuRecord['sku'];
                    $qty = $skuRecord['qty'];

                    //\Log::info("Product Code: {$sku}");

                    $product = product::where('code', $sku)->first();

                    $itemsData[] = [
                        'sku' => $sku,
                        'product_id' => $product?->id,
                        'qty' => $qty,
                        'drop_ship_volume' => $product ? $qty * $product->dropshipvolume : 0,
                        'physical_volume' => $product ? $qty * $product->dropshipvolume : 0,
                    ];
                }

                if (empty($item['planned_date'])) {
                    continue;
                }

                $plannedDate = Carbon::parse($item['planned_date'])->format('Y-m-d');
                $containerNumber = $item['container_number'];
                $tenant = $item['tenant'];

                $data = [
                    'container_number' => $containerNumber,
                    'tenant' => $tenant,
                    'buyer_order_number' => "{$tenant}#{$containerNumber}",
                    'planned_date' => $plannedDate,
                    'status' => 'pending'
                ];

                $containerAllocation = ContainersAllocation::updateOrCreate(
                    ['container_number' => $containerNumber, 'planned_date' => $plannedDate],
                    $data
                );

                foreach ($itemsData as $itemData) {
                    $existingItem = $containerAllocation->containerAllocationItems()
                        ->where('sku', $itemData['sku'])
                        ->first();

                    if ($existingItem) {
                        if ($itemData['qty'] > $existingItem->qty) {
                            $existingItem->update($itemData);
                        } else {
                            \Log::info("Skipped updating SKU '{$itemData['sku']}' as new qty is not greater than older quantity.");
                        }
                    } else {
                        $containerAllocation->containerAllocationItems()->create($itemData);
                    }
                }
            }

            \Log::info("Container allocation records updated successfully.");
        } catch (\Exception $e) {
            $message = "Exception occurred: " . $e->getMessage();
            $this->error($message);
            \Log::error($message);
        }
    }

}

