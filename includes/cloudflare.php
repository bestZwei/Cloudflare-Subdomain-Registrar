<?php
require_once __DIR__ . '/../config/config.php';

function createNSRecord($zoneId, $subdomain, $NSServer) {
    $url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records";
    $data = [
        'type' => 'NS',
        'name' => $subdomain,
        'content' => $NSServer,
        'proxied' => false
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . CLOUDFLARE_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        error_log("Cloudflare API Error: " . $error);
        return ['success' => false, 'message' => 'API connection failed: ' . $error];
    }

    $result = json_decode($response, true);
    
    if (!$result['success']) {
        $errorMessage = isset($result['errors'][0]['message']) 
            ? $result['errors'][0]['message'] 
            : 'Unknown error occurred';
        error_log("Cloudflare API Error: " . $errorMessage);
        return ['success' => false, 'message' => $errorMessage];
    }

    return ['success' => true, 'data' => $result['result']];
}

function getARecord($zoneId, $subdomain) {
    // Ensure the subdomain is fully qualified
    $fullSubdomain = $subdomain . "." . DOMAIN_NAME;
    error_log("Checking DNS record for: " . $fullSubdomain);

    $url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records?type=NS&name=$fullSubdomain";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . CLOUDFLARE_API_KEY
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Cloudflare API Error: " . $error);
        return false;
    }

    $result = json_decode($response, true);
    error_log("Cloudflare API Response: " . print_r($result, true));

    if (!$result['success']) {
        $errorMessage = isset($result['errors'][0]['message']) 
            ? $result['errors'][0]['message'] 
            : 'Unknown error occurred';
        error_log("Cloudflare API Error: " . $errorMessage);
        return false;
    }

    // Return the total count of records found
    return $result['result_info']['total_count'];
}

function deleteARecord($zoneId, $subdomain) {
    // Ensure the subdomain is fully qualified
    $fullSubdomain = $subdomain . "." . DOMAIN_NAME;

    // Fetch the DNS record ID
    $url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records?type=NS&name=$fullSubdomain";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . CLOUDFLARE_API_KEY
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!$result['success'] || empty($result['result'])) {
        return ['success' => false, 'message' => 'Failed to find DNS record'];
    }

    $recordId = $result['result'][0]['id'];

    // Delete the DNS record
    $url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records/$recordId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . CLOUDFLARE_API_KEY
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result;
} 