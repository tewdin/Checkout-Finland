<?php
/*
PHP integration for Checkout Finland
@author: Timo Anttila, info@tuspe.com
*/

// Function for calculate HMAC
function calculateHmac($secret, $params, $body = '')
{
    // Keep only checkout- params, more relevant for response validation. Filter query
    // string parameters the same way - the signature includes only checkout- values.
    $includedKeys = array_filter(array_keys($params), function ($key) {
        return preg_match('/^checkout-/', $key);
    });

    // Keys must be sorted alphabetically
    sort($includedKeys, SORT_STRING);

    $hmacPayload =
        array_map(
            function ($key) use ($params) {
                return join(':', [ $key, $params[$key] ]);
            },
            $includedKeys
        );

    array_push($hmacPayload, $body);

    return hash_hmac('sha256', join("\n", $hmacPayload), $secret);
}

// Use test IDs if no merchant IDs are found
if($page->shop_merchant_id) $merchant = $page->shop_merchant_id; else $merchant .= "375917";
if($page->shop_merchant_secret) $password = $page->shop_merchant_secret; else $password .= "SAIPPUAKAUPPIAS";

// Explode the customer's name
$name = explode(" ", $cart_data["customer"]["name"]);
if($cart_data["customer"]["Company_id"]) $company_id = "FI". str_replace("-", "", $cart_data["customer"]["Company_id"]); else $company_id = "";

// Current time for stamp and reference
$time = time();

// Foreach products to array
$products = array();
foreach($cart_data["products"][0] as $item){
    $products[] = array(
        "unitPrice" => $item["price"],
        "units": $item["amount"],
        "vatPercentage": $item["vat"],
        "productCode": $item["sku"],
        "deliveryDate": $time,
        "description": $item["title"],
        "category": $item["category"],
        "merchant": $merchant,
        "stamp": $time,
        "reference": $sku
    );
}

// Generate array for JSON
$json = json_encode(array(
    "stamp" => $merchant . $time,
    "reference" => $time,
    "amount" => $cart_data["checkout_total"] * 100,
    "currency" => "EUR",
    "language" => $lang,
    "items" => $products,
    "customer" => array(
        "email" => $cart_data["customer"]["email"],
        "firstName" => $name[0],
        "lastName" => $name[1],
        "phone" => $cart_data["customer"]["phone"],
        "vatId" => $company_id
    ),
    "deliveryAddress" => array(
        "streetAddress" => $cart_data["customer"]["delivery_street"],
        "postalCode" => $cart_data["customer"]["delivery_postal"],
        "city" => $cart_data["customer"]["delivery_area"],
//      "county" => $cart_data["customer"]["delivery_street"],
        "country": "FI"
    ),
    "invoicingAddress" => array(
        "streetAddress" => $cart_data["customer"]["street"],
        "postalCode" => $cart_data["customer"]["postal"],
        "city" => $cart_data["customer"]["area"],
        "country": "FI"
    ),
    "redirectUrls" => array(
        "success": $page->url,
        "cancel": $page->url
    ),
    "callbackUrls" => array(
        "success": $page->url,
        "cancel": $page->url
    )
));


$signature = hash_hmac('sha1', $time, $password);

$headers = [
    "checkout-account" => $merchant,
    "checkout-algorithm" => "sha256",
    "checkout-method" => "POST",
    "checkout-nonce" => "564635208570151",
    "checkout-timestamp" => date("G"),
    "content-type" => "application/json; charset=utf-8"
];

$headers['signature'] = calculateHmac($password, $headers, $json);

$ch = curl_init("https://api.checkout.fi/payments");
curl_setopt_array($ch, array(
    CURLOPT_POST => TRUE,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => $json
));
$result=curl_exec ($ch);
curl_close ($ch);
$result = json_decode($result, true);
if($user->hasRole("superuser")){
    echo "<pre>";
    print_r($result);
    echo "</pre>";
}
