<?php

namespace App\Console\Commands\Concerns;

trait FetchesStage6WpOrderQuantities
{
    /**
     * Order count from the WP ERP route for a single order status (one parcel per order).
     *
     * @return float|null null on transport / HTTP / JSON failure
     */
    protected function fetchStage6OrderCountForStatus(string $shopBaseUrl, string $startOfMonth, string $endOfMonth, string $postStatus): ?float
    {
        $base = rtrim($shopBaseUrl, '/');
        $url = $base.'/wp-json/erp-route/all-orders-quantity?from='.$startOfMonth.'&to='.$endOfMonth.'&post_status='.$postStatus.'&timestamp='.time();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            \Log::info("Stage6 cURL Error ({$postStatus}): {$error_msg}\n");

            return null;
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            \Log::info("Stage6 HTTP Error ({$postStatus}): Status Code {$httpCode}\n");
            \Log::info("Stage6 response: {$response}\n");

            return null;
        }

        $records = json_decode($response, true);
        if (! is_array($records)) {
            \Log::info("Stage6 invalid JSON for post_status={$postStatus}\n");

            return null;
        }

        return $this->countStage6Orders($records);
    }

    /**
     * One parcel per order row returned by the WP ERP route.
     */
    protected function countStage6Orders(array $records): float
    {
        $count = 0;
        foreach ($records as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (array_key_exists('orderId', $row) || array_key_exists('total_qty', $row)) {
                $count++;
            }
        }

        return (float) $count;
    }

    /**
     * Completed order count (one parcel per order).
     *
     * @return float|null null if the completed request fails
     */
    protected function fetchCompletedParcelTotal(string $shopBaseUrl, string $startOfMonth, string $endOfMonth): ?float
    {
        return $this->fetchStage6OrderCountForStatus($shopBaseUrl, $startOfMonth, $endOfMonth, 'completed');
    }
}
