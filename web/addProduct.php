<?php
session_start();

include 'functions.php';
$r = getDBConnection();


// define variables and set to empty values
$error = 0;
$existingUPC = $newUPC = $name = $desc = $location = "";
$listPrice = $auctionPrice = $reservePrice = $bidStart = $bidEnd ="";
$existingUPCErr = $newProductErr = $locationErr = "";
$listPriceErr = $auctionPriceErr = $reservePriceErr = $bidStartErr = $bidEnd = "";

// Post Product
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['post'])) {

	if (strcmp($_POST["existingUPC"],"none") == 0 && empty($_POST["newUPC"])) {
		$existingUPCErr = "You must choose an existing product or create one";
		$error = 1;
	} else {
		$upc = $existingUPC = test_input($_POST["existingUPC"]);
	}

	if (strcmp($_POST["existingUPC"],"none") == 0 && (empty($_POST["newUPC"]) ||
		empty($_POST["name"]) || empty($_POST["desc"]))) {
		$newProductErr = "All new product fields are required";
		$error = 1;
	}

	$newUPC = test_input($_POST["newUPC"]);
	$name = test_input($_POST["name"]);
	$desc = test_input($_POST["desc"]);

	$location = test_input($_POST["location"]);

	if (empty($_POST["listPrice"]) && (empty($_POST["auctionPrice"]) || 
		empty($_POST["reservePrice"]) || empty($_POST["bidStart"]))) {
		$listPriceErr = "Item must be up for auction or sale";
		$error = 1;
	} else {
		$listPrice = test_input($_POST["listPrice"]);
	}

	if (empty($_POST["auctionPrice"]) || empty($_POST["reservePrice"]) ||
		 empty($_POST["bidStart"]) || empty($_POST["bidEnd"])) {

		if (!empty($_POST["listPrice"]) && empty($_POST["auctionPrice"]) && 
			empty($_POST["reservePrice"]) && empty($_POST["bidStart"]) && empty($_POST["bidEnd"])) {
		} else {
			$auctionPriceErr = "All auction fields are required";
			$error = 1;
		}
	}

	$auctionPrice = test_input($_POST["auctionPrice"]);
	$reservePrice = test_input($_POST["reservePrice"]);
	$bidStart = test_input($_POST["bidStart"]);
	$bidEnd = test_input($_POST["bidEnd"]);

	if (strcmp($auctionPrice,"") == 0) {
		$auctionPrice = "NULL";
		$reservePrice = "NULL";
		$bidStart = "NULL";
		$bidEnd = "NULL";
	}

	if (strcmp($listPrice,"") == 0) {
		$listPrice = "\N";
	}

	
	if ($error == 0) {
		// Add to database
		beginTransaction();
		$rollback = 0;
		$commitMessage = array();
		
		if (strcmp($auctionPrice,"NULL") <> 0) {
			$bidStart = "\"$bidStart\"";
			$bidEnd = "\"$bidEnd\"";
		}
		
		if (strcmp($existingUPC,"none") == 0) {

			// Add new upc
			$upc = $newUPC;
			$query = "INSERT INTO ItemDesc (upc, name, description) 
				VALUES (\"$upc\", \"$name\", \"$desc\");";
			$rs = mysql_query($query);
			$rollback = checkError($rs, $commitMessage);
		}
		
		$query = "INSERT INTO Items (location, upc, list_price, auction_price, reserve_price,
			bid_start, bid_end, included_in) VALUES ($location, \"$upc\", $listPrice, $auctionPrice,
			$reservePrice, $bidStart, $bidEnd, 1);";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);

		$query = "INSERT INTO Owns (pid, owner_id) 
			VALUES (" . mysql_insert_id() . ", \"" . $_SESSION['aid'] . "\");";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);
			
		if ($rollback == 0) {
			commitTransaction();
			$existingUPC = $newUPC = $name = $desc = $location = "";
			$listPrice = $auctionPrice = $reservePrice = $bidStart = $bidEnd ="";
			array_push($commitMessage, "Item added successfully!");
		} else {
			rollbackTransaction();

			if (strcmp($auctionPrice,"\N") == 0) {
				$auctionPrice = $reservePrice = $bidStart = $bidEnd = "";
			}

			if (strcmp($listPrice,"\N") == 0) {
				$listPrice = "";
			}
		}
	}

}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta content="en-us" http-equiv="Content-Language" />
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
<title>Lil' Bits - My Account</title>
<style type="text/css">
.auto-style1 {
	font-size: xx-large;
}
.auto-style2 {
	font-size: 40pt;
}
.auto-style3 {
	text-align: right;
}
.auto-style4 {
	font-size: x-large;
}
.auto-style5 {
	text-align: center;
}
.auto-style6 {
	text-align: center;
	text-decoration: underline;
}
.error {
	color: #FF0000;
}
</style>
</head>

<body bgcolor="#CCFFFF">
<p>
<meta charset="utf-8" />
<b id="docs-internal-guid-6a6da0ae-035a-24a6-c41b-9923ab67532f" style="font-weight: normal;">
<img height="75" src="Pk7WXlrPofElIk0cA-XDTvkxe-b_tX0wCZUbj6x34tUhzOsDjoQ5zDS6mEE8TRWQchg3y-oXdIN3e4UMZ80W9VRf-J0WM0mUe8G4Jh5Dy2FkOjKIwx5ZXQPG7aDmLIUk7HNrw1S2Lco.png" width="75" /><span class="auto-style1">
</span><span class="auto-style2">Lil' Bits Computer Hardware</span></b></p>
<p>&nbsp;</p>
<table style="width: 100%">
	<tr>
		<td style="width: 100px"><a href="index.php">Shop</a></td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td class="auto-style3" style="width: 150px"><a href="myAccount.php">My Account</a></td>
	</tr>
</table>
<p>&nbsp;</p>


<?php 
	echo "Please fill out product information<br><br>";
	echo '	<form action="addProduct.php" method="post">
		Either choose an existing product UPC:<br>
		Products: <select name="existingUPC">
		<option value="none">New Product</option>';

	$query = "SELECT * FROM ItemDesc;";
	$rs = mysql_query($query);
	while($row = mysql_fetch_assoc($rs)) {
		echo '<option value="' . $row['upc'] . '">' . $row['name'] . '</option>';
	}
	echo '</select><br><br>Or create a new product:<br>

		New Product - UPC: <input type="text" name="newUPC" value = "';
	echo $newUPC . '"> <span class="error">*</span><br>
		New Product - Name: <input type="text" name="name" value = "';
	echo $name . '"> <span class="error">*</span><br>
		New Product - Description: <input type="text" name="desc" value = "';
	echo $desc . '"> <span class="error">*<br>';
	echo "$newProductErr <br> $existingUPCErr" . '</span><br><br>

		Location: <select name="location">';

	$query = 'SELECT * FROM Addresses A, HasAddress H WHERE H.aid = "' . $_SESSION["aid"] . '" 
			AND A.address_id = H.address_id;';
	$rs = mysql_query($query);
	echo $rs;
	while($row = mysql_fetch_assoc($rs)) {
		echo '<option value="' . $row['address_id'] . '">' . $row['street'] .
			', ' . $row['city'] . ", " . $row['state'] . " " . $row['zip'] . '</option>';
	}
	echo '</select><br><br>

		If the item is up for direct buy, enter a list price. Otherwise leave it blank.<br>
		List Price: <input type="number" name="listPrice" min="1" step="0.01" value = "';
	echo $listPrice . '"> <span class="error">*';

	echo $listPriceErr . '</span><br><br>

		If the item is up for auction, enter both an auction and reserve price and bid start/end times.<br>
		Auction Price: <input type="number" name="auctionPrice" min="1" step="0.01" value = "';
	echo $auctionPrice . '"> <span class="error">*';

	echo $auctionPriceErr . '</span><br>
		Reserve Price: <input type="number" name="reservePrice" min="1" step="0.01" value = "';
	echo $reservePrice . '"> <span class="error">*';

	echo $reservePriceErr . '</span><br>

		Bid Start (yyyy-mm-dd hh:mm:ss): <input type="text" name="bidStart" value="';
	echo $bidStart . '"><span class="error">*';
	
	echo $bidStartErr . '</span><br>
		Bid End (yyyy-mm-dd hh:mm:ss): <input type="text" name="bidEnd" value="';
	echo $bidEnd . '"><span class="error">*';
	
	echo $bidEndErr . '</span><br>
	<input type="submit" name="post" value="Post">

	</form><br><br>';

	echo '<span class="error">';
	foreach ($commitMessage as $message)
	    echo "$message<br>";
	echo '</span>';

?>

</body>

</html>

