<?php

// This file allows the administrator to add a non-coffee product.
// This script is created in Chapter 11.

// Require the configuration before any PHP code as configuration controls error reporting.
require ('../includes/config.inc.php');

// Set the page title and include the header:
$page_title = 'Add a Goodie';
include ('./includes/header.html');
// The header file begins the session.

// Require the database connection:
require(MYSQL);

// For storing errors:
$add_product_errors = array();

// Check for a form submission:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {	

	// Check for a category:
	if (!isset($_POST['category']) || !filter_var($_POST['category'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$add_product_errors['category'] = 'Please select a category!';
	}

	// Check for a price:
	if (empty($_POST['price']) || !filter_var($_POST['price'], FILTER_VALIDATE_FLOAT) || ($_POST['price'] <= 0)) {
		$add_product_errors['price'] = 'Please enter a valid price!';
	}

	// Check for a stock:
	if (empty($_POST['stock']) || !filter_var($_POST['stock'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$add_product_errors['stock'] = 'Please enter the quantity in stock!';
	}

	// Check for a name:
	if (empty($_POST['name'])) {
		$add_product_errors['name'] = 'Please enter the name!';
	}
	
	// Check for a description:
	if (empty($_POST['description'])) {
		$add_product_errors['description'] = 'Please enter the description!';
	}

	// Check for an image:
	if (is_uploaded_file ($_FILES['image']['tmp_name']) && ($_FILES['image']['error'] == UPLOAD_ERR_OK)) {
		
		$file = $_FILES['image'];
		
		$size = ROUND($file['size']/1024);

		// Validate the file size:
		if ($size > 512) {
			$add_product_errors['image'] = 'The uploaded file was too large.';
		} 

		// Validate the file type:
		$allowed_mime = array ('image/gif', 'image/pjpeg', 'image/jpeg', 'image/JPG', 'image/X-PNG', 'image/PNG', 'image/png', 'image/x-png');
		$allowed_extensions = array ('.jpg', '.gif', '.png', 'jpeg');
		$image_info = getimagesize($file['tmp_name']);
		$ext = substr($file['name'], -4);
		if ( (!in_array($file['type'], $allowed_mime)) 
		||   (!in_array($image_info['mime'], $allowed_mime) ) 
		||   (!in_array($ext, $allowed_extensions) ) 
		) {
			$add_product_errors['image'] = 'The uploaded file was not of the proper type.';
		} 
		
		// Move the file over, if no problems:
		if (!array_key_exists('image', $add_product_errors)) {

			// Create a new name for the file:
			$new_name = (string) sha1($file['name'] . uniqid('',true));

			// Add the extension:
			$new_name .= ((substr($ext, 0, 1) != '.') ? ".{$ext}" : $ext);

			// Move the file to its proper folder but add _tmp, just in case:
			$dest =  "../products/$new_name";
			
			if (move_uploaded_file($file['tmp_name'], $dest)) {
				
				// Store the data in the session for later use:
				$_SESSION['image']['new_name'] = $new_name;
				$_SESSION['image']['file_name'] = $file['name'];
				
				// Print a message:
				echo '<h4>The file has been uploaded!</h4>';
				
			} else {
				trigger_error('The file could not be moved.');
				unlink ($file['tmp_name']);				
			}

		} // End of array_key_exists() IF.
		
	} elseif (!isset($_SESSION['image'])) { // No current or previous uploaded file.
		switch ($_FILES['image']['error']) {
			case 1:
			case 2:
				$add_product_errors['image'] = 'The uploaded file was too large.';
				break;
			case 3:
				$add_product_errors['image'] = 'The file was only partially uploaded.';
				break;
			case 6:
			case 7:
			case 8:
				$add_product_errors['image'] = 'The file could not be uploaded due to a system error.';
				break;
			case 4:
			default: 
				$add_product_errors['image'] = 'No file was uploaded.';
				break;
		} // End of SWITCH.

	} // End of $_FILES IF-ELSEIF-ELSE.
	
	if (empty($add_product_errors)) { // If everything's OK.

		// Add the product to the database:
		$q = 'INSERT INTO non_coffee_products (non_coffee_category_id, name, description, image, price, stock) VALUES (?, ?, ?, ?, ?, ?)';

		// Prepare the statement:
		$stmt = mysqli_prepare($dbc, $q);
		
		// For debugging purposes:
		// if (!$stmt) echo mysqli_stmt_error($stmt);

		// Bind the variables:
		mysqli_stmt_bind_param($stmt, 'isssdi', $_POST['category'], $name, $desc, $_SESSION['image']['new_name'], $_POST['price'], $_POST['stock']);
		
		// Make the extra variable associations:
		$name = strip_tags($_POST['name']);
		$desc = strip_tags($_POST['description']);

		// Execute the query:
		mysqli_stmt_execute($stmt);
		
		if (mysqli_stmt_affected_rows($stmt) == 1) { // If it ran OK.
			
			// Print a message:
			echo '<h4>The product has been added!</h4>';
		
			// Clear $_POST:
			$_POST = array();
			
			// Clear $_FILES:
			$_FILES = array();
			
			// Clear $file and $_SESSION['image']:
			unset($file, $_SESSION['image']);
					
		} else { // If it did not run OK.
			trigger_error('The product could not be added due to a system error. We apologize for any inconvenience.');
			unlink ($dest);
		}
				
	} // End of $errors IF.
	
} else { // Clear out the session on a GET request:
	unset($_SESSION['image']);	
} // End of the submission IF.

// Need the form functions script, which defines create_form_input():
require ('../includes/form_functions.inc.php');
?><h3>Add a Non-Coffee Product (a "Goodie")</h3>

<form enctype="multipart/form-data" action="add_other_products.php" method="post" accept-charset="utf-8">

	<input type="hidden" name="MAX_FILE_SIZE" value="524288" />
	
	<fieldset><legend>Fill out the form to add a non-coffee product to the catalog. All fields are required.</legend>
		<div class="field"><label for="category"><strong>Category</strong></label><br /><select name="category"<?php if (array_key_exists('category', $add_product_errors)) echo ' class="error"'; ?>>
			<option>Select One</option>
			<?php // Retrieve all the categories and add to the pull-down menu:
			$q = 'SELECT id, category FROM non_coffee_categories ORDER BY category ASC';		
			$r = mysqli_query ($dbc, $q);
				while ($row = mysqli_fetch_array ($r, MYSQLI_NUM)) {
					echo "<option value=\"$row[0]\"";
					// Check for stickyness:
					if (isset($_POST['category']) && ($_POST['category'] == $row[0]) ) echo ' selected="selected"';
					echo ">$row[1]</option>\n";
				}
			?>
			</select><?php if (array_key_exists('category', $add_product_errors)) echo ' <span class="error">' . $add_product_errors['category'] . '</span>'; ?></div>
		
			<div class="field"><label for="name"><strong>Name</strong></label><br /><?php create_form_input('name', 'text', $add_product_errors); ?></div>
		
			<div class="field"><label for="price"><strong>Price</strong></label><br /><?php create_form_input('price', 'text', $add_product_errors); ?> <small>Without the dollar sign.</small></div>
	
			<div class="field"><label for="stock"><strong>Initial Quantity in Stock</strong></label><br /><?php create_form_input('stock', 'text', $add_product_errors); ?></div>
			
			<div class="field"><label for="description"><strong>Description</strong></label><br /><?php create_form_input('description', 'textarea', $add_product_errors); ?></div>

			<div class="field"><label for="image"><strong>Image</strong></label><br /><?php
		
			// Check for an error:
			if (array_key_exists('image', $add_product_errors)) {
				
				echo '<span class="error">' . $add_product_errors['image'] . '</span><br /><input type="file" name="image" class="error" />';
				
			} else { // No error.

				echo '<input type="file" name="image" />';

				// If the file exists (from a previous form submission but there were other errors),
				// store the file info in a session and note its existence:		
				if (isset($_SESSION['image'])) {
					echo "<br />Currently '{$_SESSION['image']['file_name']}'";
				}

			} // end of errors IF-ELSE.
		 ?></div>
	
	<br clear="all" />
	
	<div class="field"><input type="submit" value="Add This Product" class="button" /></div>
	
	
	</fieldset>

</form> 

<?php // Include the HTML footer:
include ('./includes/footer.html');
?>
