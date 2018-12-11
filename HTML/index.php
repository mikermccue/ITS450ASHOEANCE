<?php

// This file is the home page. 
// This script is begun in Chapter 8.

// Require the configuration before any PHP code:
require ('./includes/config.inc.php');

// Include the header file:
$page_title = 'Coffee - Wouldn\'t You Love a Cup Right Now?';
include ('./includes/header.html');

// Require the database connection:
require (MYSQL);

// Invoke the stored procedure:
$r = mysqli_query ($dbc, "CALL select_sale_items(false)");

// Include the view:
include('./views/home.html');

// Include the footer file:
include ('./includes/footer.html');
?>