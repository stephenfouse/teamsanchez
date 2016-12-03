<?php
session_start();

include 'functions.php';
$r = getDBConnection();


// define variables and set to empty values
$error = 0;
$aidErr = $emailErr = $full_nameErr = $genderErr = $dobErr = "";
$streetErr = $cityErr = $zipErr = $phoneNumberErr = $passwordErr = "";
$aid = $email = $full_name = $gender = $dob = $phoneNumber = $street = $city = $zip = $state = $password = "";

// Sign up
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['signup'])) {
	// Check aid is unique SELECT count(*) FROM Accounts WHERE aid = aid; = 0
	if (empty($_POST["aid"])) {
		$aidErr = "Username is required";
		$error = 1;
	} else {
		$aid = test_input($_POST["aid"]);
	}
    
	// Check email is unique SELECT count(*) FROM HasEmail WHERE email = email; = 0
	if (empty($_POST["email"])) {
		$emailErr = "Email is required";
		$error = 1;
	} else {
		$email = test_input($_POST["email"]);
	}
    
	if (empty($_POST["full_name"])) {
		$full_nameErr = "Full name is required";
		$error = 1;
	} else {
		$full_name = test_input($_POST["full_name"]);
	}
    
	if (empty($_POST["gender"])) {
		$genderErr = "Gender is required";
		$error = 1;
	} else {
		$gender = test_input($_POST["gender"]);
	}
    
	if (empty($_POST["dob"])) {
		$dobErr = "Date of birth is required";
		$error = 1;
	} else {
		$dob = test_input($_POST["dob"]);
	}
    
	if (empty($_POST["phoneNumber"])) {
		$phoneNumberErr = "Phone number is required";
		$error = 1;
	} else {
		$phoneNumber = test_input($_POST["phoneNumber"]);
	}
    
	if (empty($_POST["street"])) {
		$streetErr = "Street is required";
		$error = 1;
	} else {
		$street = test_input($_POST["street"]);
	}

	if (empty($_POST["city"])) {
		$cityErr = "City is required";
		$error = 1;
	} else {
		$city = test_input($_POST["city"]);
	}

	if (empty($_POST["zip"])) {
		$zipErr = "Zip is required";
		$error = 1;
	} else {
		$zip = test_input($_POST["zip"]);
	}
    
	$state = test_input($_POST["state"]);
    
	if (empty($_POST["password"]) || empty($_POST["password2"]) ||
        strcmp($_POST["password"], $_POST["password2"]) <> 0) {
		$passwordErr = "Password doesn't match";
		$error = 1;
	} else {
		$password = test_input($_POST["password"]);
	}

	if (empty($_POST["ccNum"])) {
		$ccNumErr = "Credit card number is required";
		$error = 1;
	} else {
		$ccNum = test_input($_POST["ccNum"]);
	}

	if (empty($_POST["ccExp"])) {
		$ccExpErr = "Credit card expiration is required";
		$error = 1;
	} else {
		$ccExp = test_input($_POST["ccExp"]);
	}

	if (empty($_POST["cc3"])) {
		$cc3Err = "Credit card 3 digit code is required";
		$error = 1;
	} else {
		$cc3 = test_input($_POST["cc3"]);
	}
    

	// If $error = 0, add to database and login
	//   Add session variable aid

	/*

	Check if address exists (SELECT COUNT(*)...)-> if not
	INSERT INTO Addresses (street, city, state, zip) VALUES (street, city, state, zip);
	$id = mysql_insert_id

	If address does exist
	$id = $row['address_id'];

	Either way for address
	INSERT INTO HasAddress (aid, address_id) VALUES (username, id);

	After each result:
	if (!$result) {
		$rollback = true;
	}


	Finally:
	if ($rollback) {
		ROLLBACK
	} else {
		COMMIT
	}

	*/

	if ($error == 0) {
		// Add to database
		beginTransaction();
		$rollback = 0;
		$commitMessage = array();
		
		$query = "INSERT INTO Accounts (aid, full_name, pass) VALUES (\"$aid\", \"$full_name\",
			 \"$password\");";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);

		$query = "INSERT INTO Emails (email, aid) VALUES (\"$email\", \"$aid\");";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);

		$query = "INSERT INTO Users (uid, gender, dob) VALUES (\"$aid\", \"$gender\", \"" .
			date("Y-m-d", strtotime($dob)) . "\");";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);

		$query = "INSERT INTO HasPhoneNumber (aid, phone_number) VALUES (\"$aid\", \"$phoneNumber\");";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);

		// Change to only add if NEW address
		$query = "INSERT INTO Addresses (street, city, state, zip) VALUES (\"$street\", \"$city\",
			 \"$state\", \"$zip\");";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);
		
		$addressID = mysql_insert_id();
		$query = "INSERT INTO HasAddress (aid, address_id) VALUES (\"$aid\", \"$addressID\");";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);


		$query = "INSERT INTO CreditCards (card_number, name_on_card, expiration, 
			three_digit_code, bills_to) VALUES (\"$ccNum\", \"$full_name\", \"" .
			date("Y-m-d", strtotime($ccExp)) . "\", \"$cc3\", \"$addressID\");";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);
			
		$query = "INSERT INTO OwnsCC (uid, card_number) VALUES (\"$aid\", \"$ccNum\");";
		$rs = mysql_query($query);
		$rollback = checkError($rs, $commitMessage);
			
		if ($rollback == 0) {
			commitTransaction();
			$_SESSION['aid'] = $aid;
			$_SESSION['name'] = $full_name;
		} else {
			rollbackTransaction();
		}
	}
}

// Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['login'])) {
	if (empty($_POST["username"]) || empty($_POST["password"])) {
		$loginErr = "Username and password is required";
	}

	$username = test_input($_POST["username"]);
	$password = test_input($_POST["password"]);

	$query = "SELECT * FROM Suppliers WHERE sid = \"$username\";";
	$rs = mysql_query($query);

	if ($row = mysql_fetch_assoc($rs)) {
		// Supplier
		$query = "SELECT * FROM Accounts A, Suppliers S WHERE S.sid = \"$username\" AND A.aid = S.sid;";
		$rs = mysql_query($query);
		$row = mysql_fetch_assoc($rs);

		if (empty($row) || strcmp($row['pass'], $password) <> 0) {
			$loginErr = "No match for username and password. Please try again.";
		}
	} else {
		$query = "SELECT * FROM Accounts A, Users U WHERE U.uid = \"$username\" AND A.aid = U.uid;";
		$rs = mysql_query($query);
		$row = mysql_fetch_assoc($rs);

		if (empty($row) || strcmp($row['pass'], $password) <> 0) {
			$loginErr = "No match for username and password. Please try again.";
		}
	}

	if (empty($loginErr)) {
		$_SESSION['aid'] = $_POST["username"];
		$_SESSION['name'] = $row['full_name'];
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
<a href="index.php"><img height="75" src="Pk7WXlrPofElIk0cA-XDTvkxe-b_tX0wCZUbj6x34tUhzOsDjoQ5zDS6mEE8TRWQchg3y-oXdIN3e4UMZ80W9VRf-J0WM0mUe8G4Jh5Dy2FkOjKIwx5ZXQPG7aDmLIUk7HNrw1S2Lco.png" width="75" /></a><span class="auto-style1">
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
	// Not logged in
	if (empty($_SESSION['aid'])) {
		echo "Please login below or create an account";
		echo '	<form action="myAccount.php" method="post">
			Full Name: <input type="text" name="full_name" value = "';
		echo $full_name . '"> <span class="error">*';
		echo $full_nameErr . '</span><br>
			E-mail: <input type="email" name="email" value = "';
		echo $email . '"> <span class="error">*';

		echo $emailErr . '</span><br>
			Username: <input type="text" name="aid" value = "';
		echo $aid . '">			<span class="error">*';
		
		echo $aidErr . '</span><br>
			Gender:
			<input type="radio" name="gender"';
		if (isset($gender) && $gender=="F") echo " checked ";
		echo ' value="F">Female
			<input type="radio" name="gender"';
		if (isset($gender) && $gender=="M") echo " checked ";
		echo ' value="M">Male
			<span class="error">*';

		echo $genderErr . '</span><br>
			Date of Birth: <input type="date" name="dob" value = "';
		echo $dob . '"> <span class="error">*';
		
		echo $dobErr . '</span><br>
			Phone Number: <input type="tel" name="phoneNumber" value = "';
		echo $phoneNumber . '"> <span class="error">*';
		
		echo $phoneNumberErr . '</span><br>
			Address - Street: <input type="text" name="street" value = "';
		echo $street . '"> <span class="error">*';
		
		echo $streetErr . '</span><br>
			Address - City: <input type="text" name="city" value = "';
		echo $city . '"> <span class="error">*';
		
		echo $cityErr . '</span><br>
			Address - Zip: <input type="text" name="zip" value = "';
		echo $zip . '">
    			<span class="error">*';
		
		echo $zipErr . '</span><br>
			Address - State: ';
		addStatesDropdown($state);
		echo '<br>
			Credit Card Number: <input type="text" name="ccNum" value = "';
		echo $ccNum . '">
    			<span class="error">*';
		
		echo $ccNumErr . '</span><br>
			Credit Card Expiration: <input type="date" name="ccExp" value = "';
		echo $ccExp . '">
    			<span class="error">*';
		
		echo $ccExpErr . '</span><br>
			Credit Card 3 Digit Code: <input type="text" name="cc3" value = "';
		echo $cc3 . '">
    			<span class="error">*';
		
		echo $cc3Err . '</span>';
		echo '<br>
			Password: <input type="password" name="password">
    			<span class="error">*';
		
		echo $passwordErr . '</span><br>
			Confirm Password: <input type="password" name="password2"><br>


			<input type="submit" name="signup" value="Sign up">

			</form><br><br>';

		echo "Login below";
		echo '	<form action="myAccount.php" method="post">
			Username: <input type="text" name="username" value = "';
		echo $username . '"><br>
			Password: <input type="password" name="password"><br>';
		echo '<span class="error">' . $loginErr . '</span><br>
			<input type="submit" name="login" value="Login">

			</form><br><br>';
		echo '<span class="error">';
		foreach ($commitMessage as $message)
		    echo "$message<br>";
		echo '</span>';

	} else {
		echo "Welcome back, " . $_SESSION['name'] . "!<br><br>";

		echo '<p class="auto-style4">Your Items:</p>
			<table style="width: 100%">
				<tr>
					<td class="auto-style6" width="200"><strong>Name</strong></td>
					<td class="auto-style6"><strong>Description</strong></td>
					<td class="auto-style6" width="100"><strong>List Price</strong></td>
					<td class="auto-style6" width="100"><strong>Auction Price</strong></td>
					<td class="auto-style6" width="100"><strong>Buy Now</strong></td>
					<td class="auto-style6" width="100"><strong>Bid</strong></td>
				</tr>';
		$query = "SELECT I.pid, D.name, D.description, I.list_price, I.auction_price FROM Items I, ItemDesc D, Owns O WHERE I.upc = D.upc AND O.owner_id = \"" . $_SESSION['aid'] . "\" AND I.pid = O.pid";

		$rs = mysql_query($query);

		while ($row = mysql_fetch_assoc($rs)) {
			echo "<tr>";
			echo "<td class=\"auto-style5\"><a href=" . getItemURL($row['pid']) . ">" .
				$row['name'] . "</td>" .
				"<td class=\"auto-style5\">" . $row['description'] . "</td><td>";
				
			if (is_null($row['list_price'])) {
				echo "Auction only";
			} else {
				echo "$" . $row['list_price'];
			}

			echo "</td><td class=\"auto-style5\">";

			if (is_null($row['auction_price'])) {
				echo "Buy only";
			} else {
				echo "$" . $row['auction_price'];
			}

			echo "</td><td class=\"auto-style5\">";

			if (!is_null($row['list_price'])) {
				echo "Buy";
			}

			echo "</td><td class=\"auto-style5\">";

			if (!is_null($row['auction_price'])) {
				echo "Bid";
			}

			echo "</td></tr>";

		}
		echo "</table><br><br>";

		echo '<p class="auto-style4">Your Transactions:</p>
			<table style="width: 100%">
				<tr>
					<td class="auto-style6" width="50"><strong>Item UPC</strong></td>
					<td class="auto-style6" width="50"><strong>Seller</strong></td>
					<td class="auto-style6" width="50"><strong>Tracking</strong></td>
					<td class="auto-style6" width="50"><strong>Sale Date</strong></td>
					<td class="auto-style6" width="50"><strong>Paid With</strong></td>
					<td class="auto-style6" width="150"><strong>Ships To</strong></td>
					<td class="auto-style6" width="150"><strong>Ships From</strong></td>
				</tr>';
		$query2 = "SELECT I.upc, T.seller, T.tracking_number, T.date_of_sale, T.paid_with, A1.street street_to, A1.city city_to, A1.state state_to, A1.zip zip_to, A2.street street_from, A2.city city_from, A2.state state_from, A2.zip zip_from FROM Transactions T, Addresses A1, Addresses A2, Items I WHERE T.buyer = \"" . $_SESSION['aid'] . "\"" . " AND T.ships_to = A1.address_id AND T.ships_from = A2.address_id AND I.included_in = T.tid";

		$rs2 = mysql_query($query2);

		while ($row2 = mysql_fetch_assoc($rs2)) {
			echo "<tr>";
			echo "<td class=\"auto-style5\">" . $row2['upc'] . "</td>" .
				"<td class=\"auto-style5\">" . $row2['seller'] . "</td>" .
				"<td class=\"auto-style5\">" . $row2['tracking_number'] . "</td>" .
				"<td class=\"auto-style5\">" . $row2['date_of_sale'] . "</td>" .
				"<td class=\"auto-style5\">" . $row2['paid_with'] . "</td>" .
				"<td class=\"auto-style5\">" . $row2['street_to'] . ", " . $row2['city_to'] . ", " . $row2['state_to'] . " " . $row2['zip_to'] . "</td>" .
				"<td class=\"auto-style5\">" . $row2['street_from'] . ", " . $row2['city_from'] . ", " . $row2['state_from'] . " " . $row2['zip_from'] . "</td>";
			echo "</tr>";
		}
		echo "</table><br><br>";
		
		echo '<a href="addProduct.php">Add a product</a>
			<br><br><a href="myInfo.php">Your Info</a>
			<br><br><a href="logout.php">Sign out</a>';
	}

?>

</body>

</html>

