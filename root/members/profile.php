<?php
session_start();
$userSession = $_SESSION['userSession'];

if(!isset($userSession) || $userSession == ""){
	// If not logged in, go to registration index
	header("Location: /members/index.php");
	exit;
}
include_once('../utils/dbconnect.php');

// Get the user data
$query = $MySQLi_CON->query("SELECT u.email, u.emergencyCn, u.emergencyCNP, u.favoriteAnimal, u.favoriteBooze, u.favoriteNerdism, u.name, u.phone, u.uid, u.upoints, uh.housename AS housename 
							FROM users u 
							JOIN house uh ON uh.houseid = u.houseid 
							WHERE uid={$userSession}");
$userRow = $query->fetch_array();
$MySQLi_CON->close();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome <?php echo $userRow['email']; ?></title>
<link href="../lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen"> 
<link href="../lib/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet" media="screen"> 
<link rel="stylesheet" href="style.css" type="text/css" />
</head>

<body>
<?php include_once('header.php'); ?>

<div class="container content">
	
	<div class="container content-card">
		<h3 class="form-signin-heading center">My Profile</h3>
		<hr />
		<h4 class="form-signin-heading center">Your Phone Number</h4>
		<div class="form-group">
			<input type="tel" pattern='[\(]\d{3}[\)]\d{3}[\-]\d{4}' class="form-control phone" placeholder="Phone Number ex. (123)867-5309" name="phone" required  />
		</div>
		<hr />
		<h4 class="form-signin-heading center">Emergency Contact Information</h4>
		
		<div class="form-group">
			<input type="text" class="form-control emergencyName" placeholder="Emergency Contact Name" name="emergencyCN" required="required"  />
			<span id="check-e"></span>
		</div>
		
		<div class="form-group">
			<input type="tel" pattern='[\(]\d{3}[\)]\d{3}[\-]\d{4}' class="form-control emergencyNumber" placeholder="Emergency Contact Phone Number ex. (123)867-5309" name="emergencyCNP" required  />
			<span id="check-e"></span>
		</div>
		
		<h4 class="form-signin-heading center">Preferences</h4>
		<div class="form-group">
			<span>Favorite Animal:</span>
			<input type="text" class="form-control animal" placeholder="Favorite Animal" name="favoriteAnimal" required="required"/>
			<span id="check-e"></span>
		</div>
		<div class="form-group">
			<span>Favorite Food/Beverage:</span>
			<input type="text" class="form-control food" placeholder="Favorite Food/Beverage" name="favoriteBooze" required="required"/>
			<span id="check-e"></span>
		</div>
		<div class="form-group">
			<span>Favorite Nerdism:</span>
			<input type="text" class="form-control nerdism" placeholder="Favorite Nerdism" name="favoriteNerdism" required="required"/>
			<span id="check-e"></span>
		</div>
		
		<div class="form-group">
			<button class="btn btn-default" id="submit" name="btn-update" style=" display:block; margin-left: auto; margin-right: auto;">Update Settings</button>
		</div>
		
		<div class="alert alert-success" style="display:none">
			<span class="glyphicon glyphicon-info-sign"></span>
			<span class="message"></span>
		</div>
	</div>
</div>

<!-- JavaScript -->
<script type="text/javascript" src="/js/jquery-1.11.1.min.js"></script>
<script src="/lib/bootstrap/js/bootstrap.min.js"></script>
<script src="/js/utils/formatter.js"></script>
<script type="text/javascript">
(function(){
	// When the phone number input loses focus, format the phone number, if possible
	formatPhoneNumberOnBlur($('.form-control[name=phone], .form-control[name=emergencyCNP]'));
	
	// Set the starting values (Note: 'blur' makes it reformat the phone number)
	var startingPhoneNumber = "<?php echo $userRow['phone']; ?>";
	var startingEmergencyName = "<?php echo $userRow['emergencyCn']; ?>";
	var startingEmergencyPhone = "<?php echo $userRow['emergencyCNP']; ?>";
	var startingFavoriteAnimal = "<?php echo $userRow['favoriteAnimal']; ?>";
	var startingFavoriteBooze = "<?php echo $userRow['favoriteBooze']; ?>";
	var startingFavoriteNerdism = "<?php echo $userRow['favoriteNerdism']; ?>";
	$('.form-control[name=phone]').val(startingPhoneNumber).blur();
	$('.form-control[name=emergencyCN]').val(startingEmergencyName);
	$('.form-control[name=emergencyCNP]').val(startingEmergencyPhone).blur();
	$('.form-control[name=favoriteAnimal]').val(startingFavoriteAnimal);
	$('.form-control[name=favoriteBooze]').val(startingFavoriteBooze);
	$('.form-control[name=favoriteNerdism]').val(startingFavoriteNerdism);
	
	function clearAlert() {
		var $alert = $('.alert');
		if (!$alert.is(':hidden')) {
			$alert.hide();
			$('.alert .message').text("");
		}
	}
	
	function buildProfileParams() {
		var arr = [
			"phone=" + $('.phone').val().trim(),
			"emergencyCN=" + $('.emergencyName').val().trim(),
			"emergencyCNP=" + $('.emergencyNumber').val().trim(),
			"favoriteAnimal=" + $('.animal').val().trim(),
			"favoriteBooze=" + $('.food').val().trim(),
			"favoriteNerdism=" + $('.nerdism').val().trim()
		];
		return arr.join('&');
	}
	
	// When any input gains focus, is clicked, or has a keydown call clearAlert()
	$('input').focus(clearAlert).click(clearAlert).keydown(clearAlert);
	
	// Click handler for the submit button
	$('#submit').click(function() {
		$.ajax({
			type: 'POST',
			url: '/utils/update_profile.php',
			data: buildProfileParams()
		})
		.done(function(resp) {
			//TODO: detect error and add 'alert-danger' class to .alert
			$('.alert .message').text(resp);
			$('.alert').show();
		});
	});
})();
</script>
</body>
</html>