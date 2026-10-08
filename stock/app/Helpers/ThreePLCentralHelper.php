<?php 
namespace App\Helpers;

class ThreePLCentralHelper
{
    public static function get3PLAccessToken(){
        $tokenUrl =  "https://secure-wms.com/AuthServer/api/Token";
        $postData = [
            "grant_type" => "client_credentials",
            "user_login" => "info@globalvisiondirect.co.uk",
        ];
        $headers = ['Authorization: Basic Mzk0ZDRlNmQtYTk5Zi00ZmY1LTkyZTEtMDkyZTU2ZDA4Y2Y2Ok1FckUrMHlOblJrRnhtcFRjOTJrOW1qa2tZaytiOTB1'];
        $response = Common::callCurlRequest($tokenUrl, 'POST', $postData, $headers);
        return $response;
    }

    public static function getInventory($token){
        $pageSize = 1000;
        $pageNumber = 1;
        $data = [];
        $headers = ["Content-Type: application/json", "Authorization: Bearer $token", "Accept: application/hal+json"];
        $baseUrl = "https://secure-wms.com";
        $nextLink = "$baseUrl/inventory?pgsiz=$pageSize&pgnum=$pageNumber";

        while($nextLink){
            $inventoryResponse  = Common::callCurlRequest($nextLink, 'GET', [], $headers);
            $inventoryResponse = json_decode($inventoryResponse);
            $nextLink = isset($inventoryResponse->_links->next->href) ? $inventoryResponse->_links->next->href : null;
            if($nextLink){
                $pageNumber++;
                $nextLink = $baseUrl."".$nextLink;
            }

            foreach($inventoryResponse->_embedded->item as $product) {
                $data[] = $product;
            }
        }
        return $data;

    }
}

?>