<?php
/**
 * Template Name: MooGold API
 */

get_header();

$autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
require_once $autoloadPath; 

use Medboubazine\Moogold\Moogold;
use Medboubazine\Moogold\Api\Products;
use Medboubazine\Moogold\Auth\Credentials;

$user_id = defined('MOOGOLD_USER_ID') ? MOOGOLD_USER_ID : '';
$partner_id = defined('MOOGOLD_PARTNER_ID') ? MOOGOLD_PARTNER_ID : '';
$secret_key = defined('MOOGOLD_SECRET_KEY') ? MOOGOLD_SECRET_KEY : '';

// Credentials
$credentials = new Credentials($user_id, $partner_id, $secret_key);
$moogold = new Moogold($credentials);
$products = $moogold->products();


// Set a unique cache key for the product list
$categgory_id = 50;
$cache_key = 'moogold_product_list_category_' . $categgory_id;
$fetch_product_list = get_transient($cache_key);

// Check if the product list is not cached
if (false === $fetch_product_list) {
    // Fetch Product List from API
    $fetch_product_list = $products->list($categgory_id);
    
    // Store it in the cache for 1 hour (3600 seconds)
    set_transient($cache_key, $fetch_product_list, 3600);
}

$product_id_to_find = 15145;
$product_details = null;

// Set a unique cache key for the specific product details
$product_cache_key = 'moogold_product_details_' . $product_id_to_find;
$product_details = get_transient($product_cache_key);

// Check if the product details are not cached
if (false === $product_details) {
    // Iterate through the list to find the product with the specified ID
    foreach ($fetch_product_list as $category) {
        if (method_exists($category, 'toArray')) {
            $attributes = $category->toArray();
            
            // Check if the current product's ID matches the one we're looking for
            if ($attributes['id'] == $product_id_to_find) {
                // Fetch the product details from the API
                $product_details = $products->details($product_id_to_find);
                
                // Cache the fetched product details for 1 hour (3600 seconds)
                set_transient($product_cache_key, $product_details, 3600);
                break; // Exit the loop once the product is found
            }
        }
    }
}

// Display the product details if found
if ($product_details) {
    $attributes = $product_details->toArray();

    // Start the container div
    echo "<div class='content-wrapper'>";
    echo "<div class='product-container' style='display: flex; justify-content: center; align-items: flex-start; flex-wrap: wrap; gap: 20px;'>";

    // Left Column: Product Image Section
    echo "<div style='flex: 1; min-width: 200px; max-width: 300px;'>";
    echo "<img src='" . htmlspecialchars($attributes['image_url']) . "' alt='Product Image' style='width: 100%; height: auto;'/>";
    echo "</div>";

    // Right Column: Variations Section
    echo "<div style='flex: 2; min-width: 300px; max-width: 600px;'>";
    echo "<p style='color:red;'>Kindly provide the correct User ID and Server ID to avoid unnecessary delay in top-up.</p>";
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tbody>";

    // Display variations in a two-column layout
    $counter = 0;
    echo "<tr>"; // Start the first row
    foreach ($attributes['offers'] as $index => $offer) {
        $offer_attributes = $offer->toArray();

        // Clean offer name
        $cleanedName = preg_replace('/Mobile Legends\s*-\s*/i', '', $offer_attributes['name']);
        $cleanedName = preg_replace('/\s*\(#\d+\)$/', '', $cleanedName);

        // Output the current offer in a table cell with data attributes for easy access
        // Output the current offer in a table cell with data attributes for easy access
echo "<td class='offer-cell' data-index='$index' data-price='" . htmlspecialchars($offer_attributes['price']) . "' data-variation-id='" . htmlspecialchars($offer_attributes['id']) . "' style='padding: 8px; text-align: center; cursor: pointer;'>" . htmlspecialchars($cleanedName) . "</td>";
        $counter++;
        // If two items have been added, close the row and start a new one
        if ($counter % 2 == 0) {
            echo "</tr><tr>"; // Close current row and start a new row
        }
    }

    // If the last row has only one cell, add an empty cell
    if ($counter % 2 != 0) {
        echo "<td style='padding: 8px;'></td>";
    }
	
    echo "</tr>"; // Close the last row
    echo "</tbody>";
    echo "</table>";
    echo "</div>";

    // Close the container div
    echo "</div>";
	//product pricing and quantity container 
	echo "<div class='form-container' style='margin-top: 20px;'>";

	// Form for submission
	echo "<form id='topup-form' method='post' action='/checkout-ml'>";
	// Include hidden fields to pass the required values
	echo "<input type='hidden' name='product_name' id='product_name' value=''/>";
	echo "<input type='hidden' name='diamond_amount' id='diamond_amount' value=''/>";
	echo "<input type='hidden' name='bonus' id='bonus' value=''/>";
	echo "<input type='hidden' name='price' id='price' value=''/>";
	echo "<input type='hidden' name='product_id' id='product_id' value=''/>";
	echo "<input type='hidden' name='image_url' id='image_url' value='" . htmlspecialchars($attributes['image_url']) . "'/>";

	// Other inputs
	echo "<label for='variation-price'>Price:</label>";
	echo "<input type='text' id='variation-price' name='variation_price' readonly/>";
	echo "<label for='user_id'>User ID:</label>";
	echo "<input type='text' id='user_id' name='user_id' required placeholder='User ID' />";
	echo "<label for='server_id'>Server ID:</label>";
	echo "<input type='text' id='server_id' name='server_id' required placeholder='Server ID'/>";
	echo "<label for='quantity'>Quantity:</label>";
	echo "<input type='number' id='quantity' name='quantity' value='1' min='1'/>";
	echo "<br>";
	echo "<input class='submit' type='submit' value='Submit'/>";
	echo "</form>";
    echo "</div>";
} else {
    echo "<h2>Product not found.</h2>";
}


get_footer();
