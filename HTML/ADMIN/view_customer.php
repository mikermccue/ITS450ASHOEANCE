<?php
// This file allows the administrator to view every order.
// This script is created in Chapter 11.
// Require the configuration before any PHP code as configuration controls error reporting.
require ('includes/config.inc.php');
// Set the page title and include the header:
$page_title = 'View All Customers';
include ('./includes/header.html');
// The header file begins the session.
// Validate the customer ID:
$customer_id = false;
if (isset($_GET['cid']) && (filter_var($_GET['cid'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) { // First access
	$customer_id = $_GET['cid'];
	$_SESSION['customer_id'] = $customer_id;
} elseif (isset($_SESSION['customer_id']) && (filter_var($_SESSION['customer_id'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) {
	$order_id = $_SESSION['customer_id'];
}
// Stop here if there's no $order_id:
if (!$customer_id) {
	echo '<h3>Error!</h3><p>This page has been accessed in error.</p>';
	include ('./includes/footer.html');
	exit();
}
// Require the database connection:
require(MYSQL);
echo '<!-- box begin -->
<div class="box alt">
	<div class="left-top-corner">
	   	<div class="right-top-corner">
		      	<div class="border-top"></div>
		</div>
	</div>
	<div class="border-left">
		<div class="border-right">
			<div class="inner"><h3>View Customer</h3><table border="0" width="100%" cellspacing="4" cellpadding="4">
<thead>
	<tr>
    <th align="center">Customer ID Number</th>
    <th align="center">Customer Name</th>
    <th align="center">Address</th>
    <th align="center">City</th>
    <th align="center">State</th>
    <th align="center">Zip</th>
    <th align="center">Email</th>
    <th align="center">Phone</th>
    </tr></thead>
<tbody>';
// Make the query:
$q = 'SELECT id as cid, email, CONCAT(first_name, " ", last_name) as name, CONCAT(address1, " ", address2) as fulladdress, city, state, zip, phone FROM customers WHERE id = ' . $customer_id;
$r = mysqli_query ($dbc, $q);
while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
	echo '<tr>
    <td align="center">' . $row['cid'] .'</td>    
    <td align="center">' . $row['name'] .'</td>
    <td align="center">' . $row['fulladdress'] . '</td>
    <td align="center">' . $row['city'] . '</td>
    <td align="center">' . $row['state'] .'</td>
    <td align="center">' . $row['zip'] .'</td>
    <td align="center"><a href="mailto:' . $row['email'] .'">' . $row['email'] . '</a></td>
    <td align="center">' . $row['phone'] .'</td>
  </tr>';
}
echo '</tbody></table>		        </div>
		</div>
	</div>
	<div class="left-bot-corner">
		<div class="right-bot-corner">
			<div class="border-bot"></div>
		</div>
	</div>
</div>
<!-- box end -->';
// Include the footer file to complete the template.
include ('./includes/footer.html');
?>
