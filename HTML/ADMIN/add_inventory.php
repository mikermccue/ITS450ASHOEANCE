<?php
// This file allows the administrator to add inventory.
// This script is created in Chapter 11.
// Require the configuration before any PHP code as configuration controls error reporting.
require ('../includes/config.inc.php');
// Set the page title and include the header:
$page_title = 'Add Inventory';
include ('./includes/header.html');
// The header file begins the session.
// Require the database connection:
require (MYSQL);
// Check for a form submission:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {	
	// Check for a added inventory:
	if (isset($_POST['add']) && is_array($_POST['add'])) {
		
		// Need the product functions:
		require ('../includes/product_functions.inc.php');
		
		// Define the two queries:
		$q1 = 'UPDATE specific_coffees SET stock=stock+? WHERE id=?';
		$q2 = 'UPDATE non_coffee_products SET stock=stock+? WHERE id=?';
		// Prepare the statements:
		$stmt1 = mysqli_prepare($dbc, $q1);
		$stmt2 = mysqli_prepare($dbc, $q2);
		
		// Bind the variables:
		mysqli_stmt_bind_param($stmt1, 'ii', $qty, $id);
		mysqli_stmt_bind_param($stmt2, 'ii', $qty, $id);
		
		// Count the number of affected rows:
		$affected = 0;
		
		// Loop through each submitted value:
		foreach ($_POST['add'] as $sku => $qty) {
			
			// Validate the added quantity:
			if (filter_var($qty, FILTER_VALIDATE_INT, array('min_range' => 1))) {
				// Parse the SKU:
				list($type, $id) = parse_sku($sku);
				
				// Determine which query to execute based upon the type:
				if ($type == 'coffee') {
					// Execute the query:
					mysqli_stmt_execute($stmt1);
					
					// Add to the affected rows:
					$affected += mysqli_stmt_affected_rows($stmt1);				
				} elseif ($type == 'other') {
					// Execute the query:
					mysqli_stmt_execute($stmt2);
					
					// Add to the affected rows:
					$affected += mysqli_stmt_affected_rows($stmt2);				
				}
				
			} // End of IF.
		} // End of FOREACH.
		
		// Print a message:
		echo "<h4>$affected Items(s) Were Updated!</h4>";
	} // End of $_POST['add'] IF.
} // End of the submission IF.
?>
<!-- box begin -->
<div class="box alt">
	<div class="left-top-corner">
	   	<div class="right-top-corner">
		      	<div class="border-top"></div>
		</div>
	</div>
	<div class="border-left">
		<div class="border-right">
			<div class="inner">


<h3>Add Inventory</h3>

<form action="add_inventory.php" method="post" accept-charset="utf-8">

	<fieldset><legend>Indicate how many additional quantity of each product should be added to the inventory.</legend>
	
		<table border="0" width="100%" cellspacing="4" cellpadding="4">
		<thead>
			<tr>
		    <th align="right">Item</th>
		    <th align="right">Normal Price</th>
		    <th align="right">Quantity in Stock</th>
		    <th align="center">Add</th>
		  </tr></thead>
		<tbody>		
		<?php
		
		// Fetch every product:
		$q = '(SELECT CONCAT("O", ncp.id) AS sku, ncc.category, ncp.name, ncp.price, ncp.stock FROM non_coffee_products AS ncp INNER JOIN non_coffee_categories AS ncc ON ncc.id=ncp.non_coffee_category_id ORDER BY category, name) UNION (SELECT CONCAT("C", sc.id), gc.category, CONCAT_WS(" - ", s.size, sc.caf_decaf, sc.ground_whole), sc.price, sc.stock FROM specific_coffees AS sc INNER JOIN sizes AS s ON s.id=sc.size_id INNER JOIN general_coffees AS gc ON gc.id=sc.general_coffee_id ORDER BY sc.general_coffee_id, sc.size_id, sc.caf_decaf, sc.ground_whole)';
		$r = mysqli_query ($dbc, $q);
		
		// Display form elements for each product:
		while ($row = mysqli_fetch_array ($r, MYSQLI_ASSOC)) {
			echo '<tr>
		    <td align="right">' . $row['category'] . '::' . $row['name'] . '</td>
		    <td align="center">' . $row['price'] .'</td>
		    <td align="center">' . $row['stock'] .'</td>
		    <td align="center"><input type="text" name="add[' . $row['sku'] . ']"  id="add[' . $row['sku'] . ']" size="5" class="small" /></td>
		  </tr>';
		}
		
?>

	</tbody></table>
	<div class="field"><input type="submit" value="Add The Inventory" class="button" /></div>	
	</fieldset>
</form>
</div>


		</div>
	</div>
	<div class="left-bot-corner">
		<div class="right-bot-corner">
			<div class="border-bot"></div>
		</div>
	</div>
</div>
<!-- box end -->

<?php
include ('./includes/footer.html');
?>
