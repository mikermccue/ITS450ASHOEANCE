<?php

// This file allows the administrator to view a specific order.
// The administrator can also mark order items as shipped.
// This script is created in Chapter 11.

// Require the configuration before any PHP code as configuration controls error reporting.
require ('../includes/config.inc.php');

// Set the page title and include the header:
$page_title = 'View An Order';
include ('./includes/header.html');
// The header file begins the session.

// Validate the order ID:
$order_id = false;
if (isset($_GET['oid']) && (filter_var($_GET['oid'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) { // First access
	$order_id = $_GET['oid'];
	$_SESSION['order_id'] = $order_id;
} elseif (isset($_SESSION['order_id']) && (filter_var($_SESSION['order_id'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) {
	$order_id = $_SESSION['order_id'];
}

// Stop here if there's no $order_id:
if (!$order_id) {
	echo '<h3>Error!</h3><p>This page has been accessed in error.</p>';
	include ('./includes/footer.html');
	exit();
}

// Require the database connection:
require(MYSQL);

// ------------------------
// Process the payment!

// Check for a form submission:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {	
	
	// Need to process payment, record the transaction, update the order_contents table, and update the inventory.
	
	// Get the order information:
	$q = "SELECT customer_id, total, transaction_id FROM orders AS o JOIN transactions AS t ON (o.id=t.order_id AND t.type='AUTH_ONLY' AND t.response_code=1) WHERE o.id=$order_id";
	$r = mysqli_query($dbc, $q);
	
	if (mysqli_num_rows($r) == 1) {
		
		// Get the returned values:
		list($customer_id, $order_total, $trans_id) = mysqli_fetch_array($r, MYSQLI_NUM);
		
		// Check for a positive order total:
		if ($order_total > 0) {
			
			// Make the request to the payment gateway:
			require_once(BASE_URI . 'private/gateway_setup_admin.php');
			require_once(BASE_URI . 'private/gateway_process.php');
			
			// Add slashes to two text values:
			$reason = addslashes($response_array[3]);
			$response = addslashes($response);

			// Record the transaction:
			$r = mysqli_query($dbc, "CALL add_transaction($order_id, '{$data['x_type']}', $response_array[9], $response_array[0], '$reason', $response_array[6], '$response')");				
			
			// Upon success, update the order and the inventory:
			if ($response_array[0] == 1) {
				
				$message = 'The payment has been made. You may now ship the order.';
					
				// Update order_contents:
				$q = "UPDATE order_contents SET ship_date=NOW() WHERE order_id=$order_id";
				$r = mysqli_query($dbc, $q);
	
				// Update the inventory...
				$q = 'UPDATE specific_coffees AS sc, order_contents AS oc SET sc.stock=sc.stock-oc.quantity WHERE sc.id=oc.product_id AND oc.product_type="coffee" AND oc.order_id=' . $order_id;
				$r = mysqli_query($dbc, $q);
				$q = 'UPDATE non_coffee_products AS ncp, order_contents AS oc SET ncp.stock=ncp.stock-oc.quantity WHERE ncp.id=oc.product_id AND oc.product_type="other" AND oc.order_id=' . $order_id;
				$r = mysqli_query($dbc, $q);
								
			} else { // Do different things based upon the response:
				
				$error = "The payment could not be processed because: $response_array[3]";
			
			} // End of payment response IF-ELSE.

		} else { // Invalid order total!

				$error = "The order total (\$$order_total) is invalid.";

		} // End of $order_total IF-ELSE.

	} else { // No matching order!
		
		$error = 'No matching order could be found.';
		
	} // End of transaction ID IF-ELSE.
	
	// Report any messages or errors:
	echo '<h3>Order Shipping Results</h3>';
	if (isset($message)) echo "<p>$message</p>";
	if (isset($error)) echo "<p class=\"error\">$error</p>";

} // End of the submission IF.

// Above code added as part of payment processing.
// ------------------------

// Define the query:
$q = 'SELECT total, shipping, credit_card_number, DATE_FORMAT(order_date, "%a %b %e, %Y at %h:%i%p") AS od, email, CONCAT(last_name, ", ", first_name) AS name, CONCAT_WS(" ", address1, address2, city, state, zip) AS address, phone, customer_id, CONCAT_WS(" - ", ncc.category, ncp.name) AS item, ncp.stock, quantity, price_per, DATE_FORMAT(ship_date, "%b %e, %Y") AS sd FROM orders AS o INNER JOIN customers AS c ON (o.customer_id = c.id) INNER JOIN order_contents AS oc ON (oc.order_id = o.id) INNER JOIN non_coffee_products AS ncp ON (oc.product_id = ncp.id AND oc.product_type="other") INNER JOIN non_coffee_categories AS ncc ON (ncc.id = ncp.non_coffee_category_id) WHERE o.id=' . $order_id . '
UNION 
SELECT total, shipping, credit_card_number, DATE_FORMAT(order_date, "%a %b %e, %Y at %l:%i%p"), email, CONCAT(last_name, ", ", first_name), CONCAT_WS(" ", address1, address2, city, state, zip), phone, customer_id, CONCAT_WS(" - ", gc.category, s.size, sc.caf_decaf, sc.ground_whole) AS item, sc.stock, quantity, price_per, DATE_FORMAT(ship_date, "%b %e, %Y") FROM orders AS o INNER JOIN customers AS c ON (o.customer_id = c.id) INNER JOIN order_contents AS oc ON (oc.order_id = o.id) INNER JOIN specific_coffees AS sc ON (oc.product_id = sc.id AND oc.product_type="coffee") INNER JOIN sizes AS s ON (s.id=sc.size_id) INNER JOIN general_coffees AS gc ON (gc.id=sc.general_coffee_id) WHERE o.id=' . $order_id;

// Execute the query:
$r = mysqli_query($dbc, $q);
if (mysqli_num_rows($r) > 0) { // Display the order info:

	echo '<h3>View an Order</h3>
	<form action="view_order.php" method="post" accept-charset="utf-8">
		<fieldset>';
		
	// Get the first row:
	$row = mysqli_fetch_array($r, MYSQLI_ASSOC);
	
	// Display the order and customer information:
	echo "<p><strong>Order ID</strong>: $order_id<br /><strong>Total</strong>: \${$row['total']}<br /><strong>Shipping</strong>: \${$row['shipping']}<br /><strong>Order Date</strong>: {$row['od']}<br /><strong>Customer Name</strong>: {$row['name']}<br /><strong>Customer Address</strong>: {$row['address']} <br /><strong>Customer Email</strong>: {$row['email']}<br /><strong>Customer Phone</strong>: {$row['phone']}<br /><strong>Credit Card Number Used</strong>: *{$row['credit_card_number']}</p>";
	
	// Create the table:
	echo '<table border="0" width="100%" cellspacing="8" cellpadding="6">
	<thead>
		<tr>
	    <th align="center">Item</th>
	    <th align="right">Price Paid</th>
	    <th align="center">Quantity in Stock</th>
	    <th align="center">Quantity Ordered</th>
	    <th align="center">Shipped?</th>
	  </tr>
	</thead>
	<tbody>';
	
	// For confirming that the order has shipped:
	$shipped = true;
	
	// Print each item:
	do {
		
		// Create a row:
		echo '<tr>
		    <th align="left">' . $row['item'] . '</th>
		    <th align="right">' . $row['price_per'] . '</th>
		    <th align="center">' . $row['stock'] . '</th>
		    <th align="center">' . $row['quantity'] . '</th>
		    <th align="center">' . $row['sd'] . '</td>
		</tr>';
		
		if (!$row['sd']) $shipped = false;
						
	} while ($row = mysqli_fetch_array($r));
	
	// Complete the table and the form:
	echo '</tbody></table>';
	
	// Only show the submit button if the order hasn't already shipped:
	if (!$shipped) {
		echo '<div class="field"><p class="error">Note that actual payments will be collected once you click this button!</p><input type="submit" value="Ship This Order" class="button" /></div>';	
	}
		
	// Complete the form:
	echo '</fieldset>
	</form>';

} else { // No records returned!
	echo '<h3>Error!</h3><p>This page has been accessed in error.</p>';
	include ('./includes/footer.html');
	exit();	
}

include ('./includes/footer.html');
?>
