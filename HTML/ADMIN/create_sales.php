<?php

// This file allows the administrator to add sales.
// This script is created in Chapter 11.

// Require the configuration before any PHP code as configuration controls error reporting.
require_once ('../includes/config.inc.php');

// Set the page title and include the header:
$page_title = 'Create Sales';
include ('./includes/header.html');
// The header file begins the session.

// Require the database connection:
require_once(MYSQL);

// Check for a form submission:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {	
	
	// Make sure these variables are set:
	if (isset($_POST['sale_price'], $_POST['start_date'], $_POST['end_date'])) {
		
		// Need the product functions:
		require ('../includes/product_functions.inc.php');
		
		// Prepare the query to be run:
		$q = 'INSERT INTO sales (product_type, product_id, price, start_date, end_date) VALUES (?, ?, ?, ?, ?)';
		$stmt = mysqli_prepare($dbc, $q);
		mysqli_stmt_bind_param($stmt, 'sidss', $type, $id, $price, $start_date, $end_date);

		// Count the number of affected rows:
		$affected = 0;
		
		// Loop through each provided value:
		foreach ($_POST['sale_price'] as $sku => $price) {
			
			// Validate the price and start date:
			if (filter_var($price, FILTER_VALIDATE_FLOAT) 
			&& ($price > 0)
			&& (!empty($_POST['start_date'][$sku]))
			){
				
				// Parse the SKU:
				list($type, $id) = parse_sku($sku);
				
				// Get the dates:
				$start_date = $_POST['start_date'][$sku];
				$end_date = (empty($_POST['end_date'][$sku])) ? NULL : $_POST['end_date'][$sku];
				
				// Execute the query:
				mysqli_stmt_execute($stmt);
				$affected += mysqli_stmt_affected_rows($stmt);
				
			} // End of price/date validation IF.
						
		} // End of FOREACH loop.
		
		// Indicate the results:
		echo "<h4>$affected Sales Were Created!</h4>";
		
	} // $_POST variables aren't set.

} // End of the submission IF.
?>

<h3>Create Sales</h3>
<p>To mark an item as being on sale, indicate the sale price, the date the sale starts, and the date the sale ends. You may leave the end date blank, thereby creating an open-ended sale. Only the currently stocked products are listed below!</p>
<form action="create_sales.php" method="post" accept-charset="utf-8">

	<fieldset>

<table border="0" width="100%" cellspacing="4 cellpadding="6">
	<thead>
	<tr>
		<th align="center">Item</th>
		<th align="center">Normal Price</th>
		<th align="center">Quantity in Stock</th>
		<th align="center">Sale Price</th>
		<th align="center">Start Date</th>
		<th align="center">End Date</th>
	</tr>
	</thead>
	<tbody>

<?php // Retrieve every in stock product:
$q = '(SELECT CONCAT("O", ncp.id) AS sku, ncc.category, ncp.name, ncp.price, ncp.stock FROM non_coffee_products AS ncp INNER JOIN non_coffee_categories AS ncc ON ncc.id=ncp.non_coffee_category_id WHERE ncp.stock > 0 ORDER BY category, name) UNION (SELECT CONCAT("C", sc.id), gc.category, CONCAT_WS(" - ", s.size, sc.caf_decaf, sc.ground_whole), sc.price, sc.stock FROM specific_coffees AS sc INNER JOIN sizes AS s ON s.id=sc.size_id INNER JOIN general_coffees AS gc ON gc.id=sc.general_coffee_id WHERE sc.stock > 0 ORDER BY sc.general_coffee_id, sc.size_id, sc.caf_decaf, sc.ground_whole)';
$r = mysqli_query ($dbc, $q);
while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
	echo '<tr>
    <td align="right">' . $row['category'] . '::' . $row['name'] . '</td>
    <td align="center">' . $row['price'] .'</td>
    <td align="center">' . $row['stock'] .'</td>
    <td align="center"><input type="text" name="sale_price[' . $row['sku'] . ']" class="small" /></td>
    <td align="center"><input type="text" name="start_date[' . $row['sku'] . ']" class="calendar" /></td>
    <td align="center"><input type="text" name="end_date[' . $row['sku'] . ']" class="calendar" /></td>
  </tr>';
}
?>

	</tbody></table>
	<div class="field"><input type="submit" value="Add These Sales" class="button" /></div>
	</fieldset>
</form>

<link href="/css/ui-lightness/jquery-ui-1.8.4.custom.css" rel="stylesheet" type="text/css" />
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.5/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
	$(function() {
		$(".calendar").datepicker({dateFormat: "yy-mm-dd", minDate:0});
	});
	</script>

<?php
include ('./includes/footer.html');
?>
