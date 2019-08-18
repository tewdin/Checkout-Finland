<?php
/*
PHP integration for Checkout Finland
@author: Timo Anttila, info@tuspe.com
*/
header('Content-Type: text/html; charset=utf-8');

$cart_data = [
    "checkout_total" => 90.00,
    "products" => [
        "items" => [
            [
                "title" => "Test 1",
                "sku" => "1234",
                "price" => 15.00,
                "amount" => 2,
                "total" => 37.20,
                "vat" => 24
            ],
            [
                "title" => "Test 2",
                "sku" => "2468",
                "price" => 20.00,
                "amount" => 3,
                "total" => 66.00,
                "vat" => 10
            ]
        ],
        "test" => [
            1234,
            2468
        ]
    ],
    "customer" => [
        "name" => "Timo Anttila",
        "phone" => "+35841234567",
        "email" => "info@example.com",
        "street" => "Testikuja 8",
        "postal" => "21600",
        "area" => "Rauma",
        "delivery_street" => "Testikuja 8",
        "delivery_postal" => "21600",
        "delivery_area" => "Rauma"
    ]
];
$lang = "fi";

// Use test IDs if no merchant IDs are found (ProcessWire)
if($page->shop_merchant_id) $merchant = $page->shop_merchant_id; else $merchant = "375917";
if($page->shop_merchant_secret) $password = $page->shop_merchant_secret; else $password = "SAIPPUAKAUPPIAS";

// Explode the customer's name and fix for the company ID if needed
$name = explode(" ", $cart_data["customer"]["name"]);
if($cart_data["customer"]["Company_id"]) $company_id = "FI". str_replace("-", "", $cart_data["customer"]["Company_id"]); else $company_id = "";

// Current time
$time = time();

// Foreach products to array
$products = array();
foreach($cart_data["products"]["items"] as $item){
    $products[] = array(
        "unitPrice" => $item["price"],
        "units" => $item["amount"],
        "vatPercentage" => $item["vat"],
        "productCode" => $item["sku"],
        "description" => $item["title"],
//      "category" => $item["category"],
        "deliveryDate" => date("Y-m-d", strtotime("+2 Weeks")),
        "merchant" => $merchant,
        "stamp" => "$time",
        "reference" => $item["sku"]
    );
}

// Generate array for JSON
$json = json_encode([
    "stamp" => $merchant . $time,
    "reference" => "$time",
    "amount" => $cart_data["checkout_total"],
    "currency" => "EUR",
    "language" => strtoupper($lang),
    "items" => $products,
    "customer" => [
        "email" => $cart_data["customer"]["email"],
        "firstName" => $name[0],
        "lastName" => $name[1],
        "phone" => $cart_data["customer"]["phone"],
        "vatId" => $company_id
    ],
    "deliveryAddress" => [
        "streetAddress" => $cart_data["customer"]["delivery_street"],
        "postalCode" => $cart_data["customer"]["delivery_postal"],
        "city" => $cart_data["customer"]["delivery_area"],
//      "county" => $cart_data["customer"]["delivery_street"],
        "country" => "FI"
    ],
    "invoicingAddress" => [
        "streetAddress" => $cart_data["customer"]["street"],
        "postalCode" => $cart_data["customer"]["postal"],
        "city" => $cart_data["customer"]["area"],
        "country" => "FI"
    ],
    "redirectUrls" => [
        "success" => "https://example.com/checkout",
        "cancel" => "https://example.com/checkout"
    ],
    "callbackUrls" => [
        "success" => "https://example.com/checkout",
        "cancel" => "https://example.com/checkout"
    ]
], JSON_UNESCAPED_SLASHES);

$headers = [
    "checkout-account:$merchant",
    "checkout-algorithm:sha256",
    "checkout-method:POST",
    "checkout-nonce:". $merchant . $time,
    "checkout-timestamp:".  date("Y-m-d\TH:i:s.000\Z", time())
];

// JSON body to $check
$check = array_push($headers, $json);

// Content type and signature to the headers
$headers[] = "content-type:application/json;charset=utf-8";
$headers[] = "signature:". hash_hmac('sha256', join("\n", $check), $password);

// Send
$opt = array (
    CURLOPT_URL => "https://api.checkout.fi/payments",
    CURLOPT_PROXY => $proxy,
    CURLOPT_POST => true,
    CURLOPT_VERBOSE => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $json
);

$ch = curl_init();
curl_setopt_array($ch, $opt);
$result = json_decode(curl_exec($ch),true);
curl_close($ch);

/*
header("Location: ". $result["href"]);
die();
*/
echo "<pre>";
print_r($result);
echo "</pre>";
