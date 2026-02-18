<?php
/**
 * Example: AndroidPublisher Batch Update with JWT Bearer Authentication
 *
 * This example demonstrates how to use the AndroidPublisher service
 * to perform batch updates of one-time products using JWT Bearer authentication.
 *
 * Usage:
 *   php examples/androidpublisher-batch-update.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Appning\Client as Appning_Client;
use Appning\Service\AndroidPublisher\AndroidPublisher;
use Appning\Exception as Appning_Exception;

// Check if serviceAccount.json exists
$serviceAccountFile = __DIR__ . '/../serviceAccount.json';
if (!file_exists($serviceAccountFile)) {
    echo "Error: serviceAccount.json not found.\n";
    echo "Please create a serviceAccount.json file with 'kid' and 'privateKeyPem'.\n";
    exit(1);
}

try {
    // 1. Load client from serviceAccount.json
    $client = new Appning_Client();

    $client->fromServiceAccountFile($serviceAccountFile);

    // 2. Create the AndroidPublisher service
    $service = new AndroidPublisher($client);

    // 3. Build the request body for batch update
    $packageName = "com.example.app";

    $batchRequestBody = [
        "oneTimeProduct" => [
            "packageName" => $packageName,
            "productId" => "coin_pack_etc_" . time(),
            "listings" => [
                [
                    "languageCode" => "pt-BR",
                    "title" => "300 Moedas",
                    "description" => "Receba 300 moedas instantaneamente"
                ],
                [
                    "languageCode" => "en-US",
                    "title" => "300 Coins",
                    "description" => "Receive 300 coins instantly"
                ]
            ],
            "purchaseOptions" => [
                [
                    "purchaseOptionId" => "default",
                    "buyOption" => [
                        "legacyCompatible" => true,
                        "multiQuantityEnabled" => false
                    ],
                    "regionalPricingAndAvailabilityConfigs" => [
                        [
                            "regionCode" => "US",
                            "price" => [
                                "currencyCode" => "USD",
                                "units" => "1",
                                "nanos" => 880000000
                            ],
                            "availability" => "AVAILABLE"
                        ]
                    ]
                ]
            ],
            "regionsVersion" => [
                "version" => "2025/03"
            ]
        ],
        "updateMask" => "listings,purchaseOptions",
        "allowMissing" => true,
        "latencyTolerance" => "PRODUCT_UPDATE_LATENCY_TOLERANCE_LATENCY_TOLERANT",
        "regionsVersion" => [
            "version" => "2025/03"
        ]
    ];

    // 4. Call batchUpdate
    echo "Calling batchUpdate for package: {$packageName}\n";
    $response = $service->monetization_onetimeproducts->batchUpdate(
        $packageName,
        ['requests'=>$batchRequestBody]
    );

    // Success: HTTP status code is in 2XX range
    echo "✅ Success\n";
} catch (Appning_Exception $e) {
    // Error: HTTP status code is not in 2XX range
    echo "❌ Error (HTTP {$e->getCode()}): {$e->getMessage()}\n";
    if ($e->getErrors()) {
        echo "Errors:\n";
        print_r($e->getErrors());
    }
} catch (Exception $e) {
    echo "❌ Unexpected error: {$e->getMessage()}\n";
    exit(1);
}
