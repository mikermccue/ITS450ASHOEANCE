<?php
// This file allows the administrator to view every order.
// This script is created in Chapter 11.
// Require the configuration before any PHP code as configuration controls error reporting.
require ('includes/config.inc.php');
// Set the page title and include the header:
$page_title = 'View All Shoe Customers';
include ('./includes/header.html');
// The header file begins the session.
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
    </tr></thead>
<tbody>';
// Make the query:
$q = 'SELECT o.id, total, c.id AS cid, CONCAT(last_name, ", ", first_name) AS name, CONCAT(address1, " ", address2) AS fulladdress, city, state, zip, COUNT(oc.id) AS items FROM orders AS o LEFT OUTER JOIN order_contents AS oc ON (oc.order_id=o.id AND oc.ship_date IS NULL) JOIN customers AS c ON (o.customer_id = c.id) JOIN transactions AS t ON (t.order_id=o.id AND t.response_code=1) GROUP BY o.id DESC';
$r = mysqli_query ($dbc, $q);
while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
	echo '<tr>
    <td align="center"><a href=view_customer.php?cid=' . $row['cid'] . '>' . $row['cid'] . '</a></td>    
    <td align="center">' . $row['name'] .'</td>
    <td align="center">' . $row['fulladdress'] . '</td>
    <td align="center">' . $row['city'] . '</td>
    <td align="center">' . $row['state'] .'</td>
    <td align="center">' . $row['zip'] .'</td>
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
