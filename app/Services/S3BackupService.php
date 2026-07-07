<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class S3BackupService
{
    /**
     * Upload a backup file to Amazon S3 or S3-compatible service using pure PHP & AWS SigV4.
     */
    public static function uploadFile(string $filePath, array $s3Settings): bool
    {
        $bucket = $s3Settings['bucket'] ?? '';
        $key = $s3Settings['key'] ?? '';
        $secret = $s3Settings['secret'] ?? '';
        $region = $s3Settings['region'] ?? 'us-east-1';
        $endpoint = $s3Settings['endpoint'] ?? '';

        if (empty($bucket) || empty($key) || empty($secret)) {
            Log::warning("S3BackupService: S3 bucket, key, or secret is not configured. Skipping S3 upload.");
            return false;
        }

        if (!file_exists($filePath)) {
            Log::error("S3BackupService: Backup file to upload does not exist: {$filePath}");
            return false;
        }

        $filename = basename($filePath);
        $s3Key = "backups/" . $filename;

        // Content details
        $content = file_get_contents($filePath);
        $contentHash = hash('sha256', $content);
        $contentType = 'application/x-sqlite3';

        // Endpoint and Host
        if (!empty($endpoint)) {
            // Parse custom endpoint
            $urlParts = parse_url($endpoint);
            $host = $urlParts['host'] ?? '';
            // Path style access for custom compatible S3 hosts
            $url = rtrim($endpoint, '/') . '/' . $bucket . '/' . $s3Key;
            $requestUri = '/' . $bucket . '/' . $s3Key;
        } else {
            $host = "{$bucket}.s3.{$region}.amazonaws.com";
            $url = "https://{$host}/{$s3Key}";
            $requestUri = '/' . $s3Key;
        }

        $amzDate = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        // Headers to sign
        $headers = [
            'host' => $host,
            'x-amz-content-sha256' => $contentHash,
            'x-amz-date' => $amzDate,
            'content-type' => $contentType,
        ];
        ksort($headers);

        // Canonical Request
        $canonicalHeaders = "";
        $signedHeaders = "";
        foreach ($headers as $k => $v) {
            $canonicalHeaders .= $k . ':' . trim($v) . "\n";
            $signedHeaders .= $k . ';';
        }
        $signedHeaders = rtrim($signedHeaders, ';');

        $canonicalRequest = "PUT\n"
            . $requestUri . "\n"
            . "" . "\n" // Query String
            . $canonicalHeaders . "\n"
            . $signedHeaders . "\n"
            . $contentHash;

        // String to Sign
        $credentialScope = "{$date}/{$region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n"
            . $amzDate . "\n"
            . $credentialScope . "\n"
            . hash('sha256', $canonicalRequest);

        // Calculate Signature
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $secret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = "AWS4-HMAC-SHA256 Credential={$key}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        // Exec PUT request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $httpHeaders = [
            "Authorization: {$authorization}",
            "x-amz-date: {$amzDate}",
            "x-amz-content-sha256: {$contentHash}",
            "Content-Type: {$contentType}",
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            Log::info("S3BackupService: Successfully uploaded backup {$filename} to S3.");
            return true;
        } else {
            Log::error("S3BackupService: S3 upload failed with HTTP code {$httpCode}. Response: {$response}. Curl Error: {$curlError}");
            return false;
        }
    }
}
