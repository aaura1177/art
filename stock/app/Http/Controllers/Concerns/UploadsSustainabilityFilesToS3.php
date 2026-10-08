<?php

namespace App\Http\Controllers\Concerns;

use App\supplier;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Http\Request;

trait UploadsSustainabilityFilesToS3
{
    /**
     * Upload optional Stage 1 transport chalan and set $data['chalan_url'].
     *
     * @return string|null Error message on failure, null on success or no file.
     */
    protected function attachStage1ChalanUrl(Request $request, array &$data): ?string
    {
        if (!$request->hasFile('chalan_url')) {
            return null;
        }

        $supplier = supplier::select('id', 'c_name', 'name')->find($request->supplier_id);
        if (!$supplier) {
            return 'Supplier not found.';
        }

        $folderName = 'sustainability/supplier/stage1-transport-chalan/'
            . str_replace(' ', '_', $supplier->c_name) . '-' . $supplier->id . '/';
        $file = $request->file('chalan_url');
        $fileName = str_replace(' ', '_', $supplier->c_name) . '-' . $supplier->id . '-'
            . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $folderName . $fileName;

        $return_data = $this->uploadToS3($filePath, $file->get());

        if (isset($return_data['error'])) {
            return $return_data['details'] ?? $return_data['error'];
        }

        $data['chalan_url'] = $return_data['path'];

        return null;
    }

    /**
     * Upload optional Stage 2 transport chalan and set $data['chalan_url'].
     *
     * @return string|null Error message on failure, null on success or no file.
     */
    protected function attachStage2TransportChalanUrl(Request $request, array &$data): ?string
    {
        if (!$request->hasFile('chalan_url')) {
            return null;
        }

        $supplier = supplier::select('id', 'c_name', 'name')->find($request->supplier_id);
        if (!$supplier) {
            return 'Supplier not found.';
        }

        $folderName = 'sustainability/supplier/stage2-transport-chalan/'
            . str_replace(' ', '_', $supplier->c_name) . '-' . $supplier->id . '/';
        $file = $request->file('chalan_url');
        $fileName = str_replace(' ', '_', $supplier->c_name) . '-' . $supplier->id . '-'
            . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $folderName . $fileName;

        $return_data = $this->uploadToS3($filePath, $file->get());

        if (isset($return_data['error'])) {
            return $return_data['details'] ?? $return_data['error'];
        }

        $data['chalan_url'] = $return_data['path'];

        return null;
    }

    public function uploadToS3($filePath, $fileContent)
    {
        $bucketName = env('AWS_BUCKET');
        $region = env('AWS_DEFAULT_REGION');
        $accessKey = env('AWS_ACCESS_KEY_ID');
        $secretKey = env('AWS_SECRET_ACCESS_KEY');

        try {
            $s3 = new S3Client([
                'region' => $region,
                'version' => 'latest',
                'credentials' => [
                    'key' => $accessKey,
                    'secret' => $secretKey,
                ],
                'http' => [
                    'verify' => false,
                ],
            ]);

            $result = $s3->putObject([
                'Bucket' => $bucketName,
                'Key' => $filePath,
                'Body' => $fileContent,
            ]);

            return [
                'message' => 'File uploaded successfully.',
                'path' => $filePath,
                'url' => $result['ObjectURL'],
            ];
        } catch (AwsException $e) {
            return [
                'error' => 'Upload failed.',
                'details' => $e->getMessage(),
            ];
        }
    }
}
