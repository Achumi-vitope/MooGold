<?php
/*
 * Template Name: Create Order Moogold
 */

get_header();
$autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
require_once $autoloadPath;

use Medboubazine\Moogold\Moogold;
use Medboubazine\Moogold\Api\Orders;
use Medboubazine\Moogold\Auth\Credentials;

// Initialize variables
$user_id = defined('MOOGOLD_USER_ID') ? MOOGOLD_USER_ID : '';
$partner_id = defined('MOOGOLD_PARTNER_ID') ? MOOGOLD_PARTNER_ID : '';
$secret_key = defined('MOOGOLD_SECRET_KEY') ? MOOGOLD_SECRET_KEY : '';

// Credentials
$credentials = new Credentials($user_id, $partner_id, $secret_key);
$moogold = new Moogold($credentials);
$orders = $moogold->orders(); //Make order

$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
$quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$server_id = isset($_POST['server_id']) ? $_POST['server_id'] : '';

echo "<h3>Order Details:</h3>";
echo "<p><strong>Product ID:</strong> " . htmlspecialchars($product_id) . "</p>";
echo "<p><strong>Quantity:</strong> " . htmlspecialchars($quantity) . "</p>";
echo "<p><strong>User ID:</strong> " . htmlspecialchars($user_id) . "</p>";
echo "<p><strong>Server ID:</strong> " . htmlspecialchars($server_id) . "</p>";

$timestamp = time();

function fetch_header($signature){
    global $partner_id;
    global $secret_key;
    global $timestamp;
	
    return [
        'Accept' => 'application/json',
        'timestamp' => $timestamp,
        'Authorization' => "Basic " . base64_encode("{$partner_id}:{$secret_key}"),
        'auth' => $signature,
    ];
}

function _ProductDetail($product_id) {
	global $timestamp;
	global $secret_key;
	
	$path = 'product/product_detail';
	$url = "https://moogold.com/wp-json/v1/api/product/product_detail?product_id={$product_id}"; 
	
	$signature = hash_hmac('SHA256', "{$timestamp}{$path}", $secret_key); 
	$header = fetch_header($signature);
	
	$args = [
		'headers' => $header,
	];
	
	$response = wp_remote_get($url, $args); // Changed to GET request
	return $response;
}

$response = _ProductDetail($product_id);

if (is_wp_error($response)) {
    echo 'Error: ' . $response->get_error_message();
} else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!empty($data)) {
        // Prepare Create Order API request
        $orderData = [
            'path' => 'order/create_order',
            'data' => [
                'category' => '1',
                'product-id' => (string) $product_id,
                'quantity' => (string) $quantity,
                'User ID' => $user_id,
                'Server ID' => $server_id,
            ],
//             'partnerOrderId' => "MG-12345", // This can be dynamically generated as needed
        ];
        
        // Make the Create Order API call (add this part)
        $createOrderResponse = create_order($orderData);
        
        // Handle the Create Order API response
        if (is_wp_error($createOrderResponse)) {
            echo 'Order Error: ' . $createOrderResponse->get_error_message();
        } else {
            echo '<div class="order-details">';
			echo '<h2>Order Details</h2>';
			echo '<div class="order-content">';
			echo '<pre>' . htmlspecialchars(wp_remote_retrieve_body($createOrderResponse), ENT_QUOTES) . '</pre>';
			echo '</div>';
			echo '</div>';
        }
        
    } else {
        echo 'No product data found.';
    }
}

get_footer();

function create_order($data) {
    global $timestamp, $partner_id, $secret_key;
    
    $path = $data['path'];
    $url = "https://moogold.com/wp-json/v1/api/order/create_order"; // Adjust the URL if necessary

    $payload = json_encode($data);
    $signature = hash_hmac('SHA256', $payload . $timestamp . $path, $secret_key);
    $header = fetch_header($signature);
    
    $args = [
        'headers' => $header,
        'body' => $payload,
    ];
    
    return wp_remote_post($url, $args);
}

// Add this CSS in your header or stylesheet
?>
<style>
.order-details {
    border: 2px solid #4CAF50; /* Green border */
    border-radius: 8px; /* Rounded corners */
    padding: 20px; /* Space inside the box */
    background-color: #f9f9f9; /* Light gray background */
    margin: 20px 0; /* Space around the box */
}

.order-details h2 {
    color: #333; /* Darker text color for the heading */
    text-align: center; /* Center the heading */
}

.order-content pre {
    white-space: pre-wrap; /* Wrap long lines */
    background: #eaeaea; /* Light background for the preformatted text */
    padding: 10px; /* Padding inside the pre block */
    border-radius: 5px; /* Slightly rounded corners */
    overflow: auto; /* Enable scrolling if needed */
}
</style>
