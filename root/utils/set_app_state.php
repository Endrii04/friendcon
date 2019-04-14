<?php
// Usage: 
// 1. Connects to the database
// 2. Checks for admin
// 3. Accepts any combination of these parameters:
//		- conDay (0 < Number < 32)
//		- conMonth (0 < Number < 13)
//		- conYear (Number > 1000)
//		- enableRegistration|disableRegistration (no value)
//		- enablePoints|disablePoints (no value)
//		- premiumDueDateDisplay (String)
//		- premiumLastMonth (0 < Number < 13)
//		- premiumLastDay (0 < Number < 32)
//		- teeDueDateDisplay (String)
//		- teeLastMonth (0 < Number < 13)
//		- teeLastDay (0 < Number < 32)
//		- teePrice (0 < Number < 1000)

session_start();
$userSession = $_SESSION['userSession'];

if (!isset($userSession) || $userSession == "") {
	// If not logged in, go to main homepage
	header("Location: /");
	exit;
}
include_once './dbconnect.php';
include_once './checkadmin.php';
include_once './check_app_state.php';

if (!$isAdmin) {
	die("Must be admin to set app state");
}

// Get parameters from the url
if (isset($_POST['conDay']) && $_POST['conDay'] > 0 && $_POST['conDay'] < 32) {
	$conDay = $_POST['conDay'];
}
if (isset($_POST['conMonth']) && $_POST['conMonth'] > 0 && $_POST['conMonth'] < 13) {
	$conMonth = $_POST['conMonth'];
}
if (isset($_POST['conYear']) && $_POST['conYear'] > 1000) {
	$conYear = $_POST['conYear'];
}
if (isset($_POST['enableRegistration'])) {
	$isRegistrationEnabled = 1;
} else if (isset($_POST['disableRegistration'])) {
	$isRegistrationEnabled = 0;
}
if (isset($_POST['enablePoints'])) {
	$isPointsEnabled = 1;
} else if (isset($_POST['disablePoints'])) {
	$isPointsEnabled = 0;
}

// Count the rows with the provided year
$numRows = 0;
$checkResult = $MySQLi_CON->query("SELECT s.conYear FROM app_state s WHERE s.conYear = {$conYear}");
while($checkResult->fetch_array()){
	$numRows++;
}
$checkResult->free_result();

if (!$checkResult || $numRows == 0) {
	// Insert a new row
	$insertQuery =
		"INSERT INTO `app_state`(`conMonth`, `conDay`, `conYear`, `registrationEnabled`, `pointsEnabled`)
		VALUES ({$conMonth}, {$conDay}, {$conYear}, {$isRegistrationEnabled}, {$isPointsEnabled})";
	$MySQLi_CON->query($insertQuery);
	die("Added entry for {$conYear}!");
} else {
	// Update an existing row
	$updateQuery = 
		"UPDATE app_state s
		 SET s.conDay = {$conDay},
			s.conMonth = {$conMonth},
			s.conYear = {$conYear},
			s.registrationEnabled = {$isRegistrationEnabled},
			s.pointsEnabled = {$isPointsEnabled}
		 WHERE s.conYear = {$conYear}";

	$updateResult = $MySQLi_CON->query($updateQuery);
	if (!$updateResult) {
		die("App state change failed [DB-2]");
	} else {
		die("Updated entry for {$conYear}!");
	}
}

?>