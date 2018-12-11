<?php
// This file is the second step in the checkout process.
// It takes and validates the billing information.
// This script is begun in Chapter 10.
// Require the configuration before any PHP code:
require ('./includes/config.inc.php');
// Start the session:
session_start();
// The session ID is the user's cart ID:
$uid = session_id();
// Check that this is valid:
if (!isset($_SESSION['customer_id'])) { // Redirect the user.
	$location = 'https://' . BASE_URL . 'checkout.php';
	header("Location: $location");
	exit();
}
// Require the database connection:
require (MYSQL);
// Validate the billing form...
// For storing errors:
$billing_errors = array();
// Check for a form submission:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (get_magic_quotes_gpc()) {
		$_POST['cc_first_name'] = stripslashes($_POST['cc_first_name']);
		// Repeat for other variables that could be affected.
	}
	// Check for a first name:
	if (preg_match ('/^[A-Z \'.-]{2,20}$/i', $_POST['cc_first_name'])) {
		$cc_first_name = mysqli_real_escape_string($dbc, $_POST['cc_first_name']);
		$cc_first_name = trim($cc_first_name);
		$cc_first_name = stripslashes($cc_first_name);
	} else {
		$billing_errors['cc_first_name'] = 'Please enter your first name!';
	}
	
	// Check for a last name:
	if (preg_match ('/^[A-Z \'.-]{2,40}$/i', $_POST['cc_last_name'])) {
		$cc_last_name  = mysqli_real_escape_string($dbc, $_POST['cc_last_name']);
		$cc_last_name  = trim($cc_last_name);
		$cc_last_name  = stripslashes($cc_last_name);
		
	} else {
		$billing_errors['cc_last_name'] = 'Please enter your last name!';
	}
	
	// Check for a valid credit card number...
	// Strip out spaces or hyphens:
	$cc_number = mysqli_real_escape_string($dbc, $_POST['cc_number']);
	$cc_number = str_replace(array(' ', '-'), '', $cc_number);
	$cc_number  = stripslashes($cc_number);
	
	// Validate the card number against allowed types:
	if (!preg_match ('/^4[0-9]{12}(?:[0-9]{3})?$/', $cc_number) // Visa
	&& !preg_match ('/^5[1-5][0-9]{14}$/', $cc_number) // MasterCard
	&& !preg_match ('/^3[47][0-9]{13}$/', $cc_number) // American Express
	&& !preg_match ('/^6(?:011|5[0-9]{2})[0-9]{12}$/', $cc_number) // Discover
	) {
		$billing_errors['cc_number'] = 'Please enter your credit card number!';
	}
	
	// Check for an expiration date:
	if ( ($_POST['cc_exp_month'] < 1 || $_POST['cc_exp_month'] > 12)) {
		$billing_errors['cc_exp_month'] = 'Please enter your expiration month!';		
	}
	
	if ($_POST['cc_exp_year'] < date('Y')) {
		$billing_errors['cc_exp_year'] = 'Please enter your expiration year!';
	}
	
	// Check for a CVV:
	if (preg_match ('/^[0-9]{3,4}$/', $_POST['cc_cvv'])) {
		$cc_cvv = $_POST['cc_cvv'];
	} else {
		$billing_errors['cc_cvv'] = 'Please enter your CVV!';
	}
	
	// Check for a street address:
	if (preg_match ('/^[A-Z0-9 \',.#-]{2,160}$/i', $_POST['cc_address'])) {
		$cc_address =  mysqli_real_escape_string($dbc, $_POST['cc_address']);
		$cc_address  = trim($cc_address);
		$cc_address  = stripslashes($cc_address);
	} else {
		$billing_errors['cc_address'] = 'Please enter your street address!';
	}
		
	// Check for a city:
	if (preg_match ('/^[A-Z \'.-]{2,60}$/i', $_POST['cc_city'])) {
		$cc_city = mysqli_real_escape_string($dbc, $_POST['cc_city']);
		$cc_city = trim($cc_city);
		$cc_city = stripslashes($cc_city);
	} else {
		$billing_errors['cc_city'] = 'Please enter your city!';
	}
	
	// Check for a state:
	if (preg_match ('/^[A-Z]{2}$/', $_POST['cc_state'])) {
		$cc_state = $_POST['cc_state'];
	} else {
		$billing_errors['cc_state'] = 'Please enter your state!';
	}
	
	// Check for a zip code:
	if (preg_match ('/^(\d{5}$)|(^\d{5}-\d{4})$/', $_POST['cc_zip'])) {
		$cc_zip = $_POST['cc_zip'];
	} else {
		$billing_errors['cc_zip'] = 'Please enter your zip code!';
	}
	
	if (empty($billing_errors)) { // If everything's OK...
		// Convert the expiration date to the right format:
		$cc_exp = sprintf('%02d%d', $_POST['cc_exp_month'], $_POST['cc_exp_year']);
		
		// Check for an existing order ID:
		if (isset($_SESSION['order_id'])) { // Use existing order info:
			$order_id = $_SESSION['order_id'];
			$order_total = $_SESSION['order_total'];
		} else { // Create a new order record:
			
			// Get the last four digits of the credit card number:
			$cc_last_four = substr($cc_number, -4);
			// Call the stored procedure:
			$r = mysqli_query($dbc, "CALL add_order({$_SESSION['customer_id']}, '$uid', {$_SESSION['shipping']}, $cc_last_four, @total, @oid)");
			// Confirm that it worked:
			if ($r) {
				// Retrieve the order ID and total:
				$r = mysqli_query($dbc, 'SELECT @total, @oid');
				if (mysqli_num_rows($r) == 1) {
					list($order_total, $order_id) = mysqli_fetch_array($r);
					
					// Store the information in the session:
					$_SESSION['order_total'] = $order_total;
					$_SESSION['order_id'] = $order_id;
					
				} else { // Could not retrieve the order ID and total.
					unset($cc_number, $cc_cvv);
					trigger_error('Your order could not be processed due to a system error. We apologize for the inconvenience.');
				}
			} else { // The add_order() procedure failed.
				unset($cc_number, $cc_cvv);
				trigger_error('Your order could not be processed due to a system error. We apologize for the inconvenience.');
			}
			
		} // End of isset($_SESSION['order_id']) IF-ELSE.
		
		// ------------------------
		// Process the payment!
		if (isset($order_id, $order_total)) { 
				
				// Need the customer ID:
				$customer_id = $_SESSION['customer_id'];
				// Make the request to the payment gateway:
				require_once('../private/gateway_setup.php');
				require_once('../private/gateway_process.php');
				
				// Add slashes to two text values:
				$reason = addslashes($response_array[3]);
				$response = addslashes($response);
				// Record the transaction:
				$r = mysqli_query($dbc, "CALL add_transaction($order_id, '{$data['x_type']}', $response_array[9], $response_array[0], '$reason', $response_array[6], '$response')");				
			
				// Upon success, redirect:
				if ($response_array[0] == 1) {
					
					// Add the transaction info to the session:
					$_SESSION['response_code'] = $response_array[0];
					
					// Redirect to the next page:
					$location = 'https:/' . '/www.ashoeance.com/' . 'final.php';
					header("Location: $location");
					exit();
				} else { // Do different things based upon the response:
					
					if ($response_array[0] == 2) { // Declined
						$message = $response_array[3] . ' Please fix the error or try another card.';			
					} elseif ($response_array[0] == 3) { // Error
						$message = $response_array[3] . '  Please fix the error or try another card.';			
					} elseif ($response_array[0] == 4) { // Held for review
						$message = "The transaction is being held for review. You will be contacted ASAP about your order. We apologize for any inconvenience.";			
					}
					
				} // End of $response_array[0] IF-ELSE.
		} // End of isset($order_id, $order_total) IF.
		// Above code added as part of payment processing.
		// ------------------------
	} // Errors occurred IF.
} // End of REQUEST_METHOD IF.
							
// Include the header file:
$page_title = 'Not Amazon - Checkout - Your Billing Information';
include ('./includes/checkout_header.html');
// Get the cart contents:
$r = mysqli_query($dbc, "CALL get_shopping_cart_contents('$uid')");
if (mysqli_num_rows($r) > 0) { // Products to show!
	if (isset($_SESSION['shipping_for_billing']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {
		$values = 'SESSION';
	} else {
		$values = 'POST';
	}
	include ('./views/billing.html');
} else { // Empty cart!
	include ('./views/emptycart.html');
}
// Finish the page:
include ('./includes/footer.html');
?>
