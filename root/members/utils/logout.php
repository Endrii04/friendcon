<?php
session_start();
$userSession = $_SESSION['userSession'];

if (!isset($userSession)) {
    header("Location: /members/index.php");
} else if (isset($userSession) != "") {
    header("Location: /members/home.php");
}
include('dbconnect.php');

// Get parameters from the url
$logout = $MySQLi_CON->real_escape_string($_GET['logout']);
$dest = $MySQLi_CON->real_escape_string($_GET['dest']);

if (isset($logout)) {
    session_destroy();
    unset($userSession);

    if (isset($dest)) {
        // Go to a destination relative to friendcon.com
        header("Location: /{$dest}/index.php");
    } else {
        // Defaults to friendcon.com/index.php
        header("Location: /index.php");
    }
}
?>