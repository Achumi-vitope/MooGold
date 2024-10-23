<?php
/**
 * Template Name: Moogold Checkout 
 */
get_header();

$autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
require_once $autoloadPath;

use Medboubazine\Moogold\Moogold;
use Medboubazine\Moogold\Api\Orders;
use Medboubazine\Moogold\Auth\Credentials;
use Medboubazine\Moogold\Api\User;

// Initialize variables
$user_id = defined('MOOGOLD_USER_ID') ? MOOGOLD_USER_ID : '';
$partner_id = defined('MOOGOLD_PARTNER_ID') ? MOOGOLD_PARTNER_ID : '';
$secret_key = defined('MOOGOLD_SECRET_KEY') ? MOOGOLD_SECRET_KEY : '';

// Credentials
$credentials = new Credentials($user_id, $partner_id, $secret_key);
$moogold = new Moogold($credentials);
$user = $moogold->user(); //check wallet balance
$orders = $moogold->orders(); //Make order

$product_name = isset($_POST['product_name']) ? $_POST['product_name'] : '';
$price = isset($_POST['price']) ? $_POST['price'] : '';
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
$quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$server_id = isset($_POST['server_id']) ? $_POST['server_id'] : '';
$image_url = isset($_POST['image_url']) ? $_POST['image_url'] : '';

// Calculate total price based on price and quantity
$price = (float)($price);
$quantity = (int)($quantity);
$total_price = (float) ($price * $quantity);

echo "<div class='checkout-ml-container'>";
echo "<h5>Checkout</h5>";
echo "<form id='checkout-form' method='post' action='/order_status'>"; // Submit to the same page
echo "<div class='checkout-ml-container-child'>";
echo "<div class='checkout-ml-product-image'>";
echo "<img class='prod-img' src='" . htmlspecialchars($image_url) . "' alt='Product Image' />";
echo "</div>";
echo "<div class='checkout-ml-product-items'>";

//hidden form -> It's data will be sent
echo "<input type='hidden' name='product_id' value='" . htmlspecialchars($product_id) . "'>";
echo "<input type='hidden' name='quantity' value='" . htmlspecialchars($quantity) . "'>";
echo "<input type='hidden' name='user_id' value='" . htmlspecialchars($user_id) . "'>";
echo "<input type='hidden' name='server_id' value='" . htmlspecialchars($server_id) . "'>";

// Displaying the product information
echo "<p><strong>Product:</strong> Mobile Legends - " . htmlspecialchars($product_name) . "</p>";
echo "<p><strong>Price:</strong> ₹ " . htmlspecialchars($total_price) . "</p>";
echo "<p><strong>Quantity:</strong> " . htmlspecialchars($quantity) . "</p>";
echo "<p><strong>User ID:</strong> " . htmlspecialchars($user_id) . "</p>";
echo "<p><strong>Server ID:</strong> " . htmlspecialchars($server_id) . "</p>";

// Adding a submit button
echo "<button type='submit' class='submit-button'>Proceed to payment</button>";
echo "</div>";
echo "</div>";
echo "</form>";
echo "</div>";

$timestamp = time();

function fetch_header($signature, $timestamp){
    global $partner_id;
    global $secret_key;
    return [
        'Accept' => 'application/json',
        'timestamp' => $timestamp,
        'Authorization' => "Basic " . base64_encode("{$partner_id}:{$secret_key}"),
        'auth' => $signature,
    ];
}

function check_wallet_balance(){
    global $user;
    global $timestamp;
    global $secret_key;

    $path = 'user/balance'; // for reload_bal endpoint
    $url = 'https://moogold.com/wp-json/v1/api/user/balance';
    $body = [
        "path" => $path,
    ];

    $payload = json_encode($body);

    $signature = hash_hmac('SHA256', $payload . $timestamp . $path, $secret_key);

    $headers = fetch_header($signature, $timestamp);

    $args = [
        'headers' => $headers,
        'body'    => $payload, // Encode body as JSON
        'timeout' => 10, // Optional timeout
    ];

    return $response = wp_remote_post($url, $args);
}

$get_check_wallet_balance = check_wallet_balance();

$proceed_to_place_order = false;

if (is_wp_error($get_check_wallet_balance)) {
    echo "<h4 style='color:red'>Something happened and we're looking into it</h4>";
} else {
    // Get the response body
    $get_check_wallet_balance = wp_remote_retrieve_body($get_check_wallet_balance);
    $data = json_decode($get_check_wallet_balance, true);
    $current_wallet_balance = (float)($data['balance']);

    if ($total_price > $current_wallet_balance) {
        wp_redirect('/error'); // Custom error page
        exit();
    } else {
        $proceed_to_place_order = true;
    }
}


get_footer();
