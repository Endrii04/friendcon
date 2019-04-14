<?php
session_start();
$userSession = $_SESSION['userSession'];

if (!isset($userSession) || $userSession == "") {
	// If not logged in, go to registration index
	header("Location: /members/index.php");
	exit;
}
include_once('../utils/dbconnect.php');
include_once('../utils/checkadmin.php');
include_once('../utils/check_app_state.php');

if (!$isAdmin) {
	die("You are not an admin! GTFO.");
}

// Get the user data
$query = $MySQLi_CON->query("SELECT * FROM users WHERE uid={$userSession}");
$userRow = $query->fetch_array();

// User Information
$name = $userRow['name'];
$emailAddress = $userRow['email'];

$MySQLi_CON->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Team Sorting the Friends of Cons</title>
<link href="../lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
<link href="../lib/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet" media="screen">
<link href="../css/datatables.min.css" rel="stylesheet" media="screen">
<link rel="stylesheet" href="style.css" type="text/css" />
</head>

<body class="admin-check-in admin-team-sort">
<?php include_once('header.php'); ?>
<br />
<br />
<br />
<br />
<div class="container content-card wide">
	<span>Admin Navigation:</span>
	<div class="btn-group" role="group">
		<a class="btn btn-default" href="/members/adminCheckIn.php">Check-In</a>
		<a class="btn btn-default" href="/members/adminTeamSort.php" disabled>Team Sorting</a>
		<a class="btn btn-default" href="/members/adminEmailList.php">Email List</a>
	</div>
	<?php if ($isSuperAdmin) { ?>
		<div class="btn-group" role="group">
			<a class="btn btn-default" href="/members/superAdmin.php">SUPERadmin</a>
		</div>
	<?php } ?>
	<?php if ($isPointsEnabled) { ?>
		<div class="btn-group" role="group">
			<a class="btn btn-default" href="/members/points.php">Points</a>
		</div>
	<?php } ?>
</div>
<div class="container content-card wide">
	<div class="btn-group pull-right" role="group">
		<a class="btn btn-default" id="sort-the-unsorted">Sort the Unsorted</a>
	</div>
	<h4>Sort into Teams (For Checked-In Friends)</h4>
	<table id="user-table"></table>
</div>

<!-- JavaScript -->
<script type="text/javascript" src="/js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="/lib/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/js/datatables.min.js"></script>
<script type="text/javascript" src="/js/dataTables.dataSourcePlugins.js"></script>
<script type="text/javascript" src="/js/underscore.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {

var dataTableForUserTable;
setupUserTable();

function setupUserTable(){
	var $userTable = $('#user-table');
	$userTable.off().empty();
	$userTable.on('draw.dt', setupActionButtonClickHandlers);
	$userTable.on('order.dt', renumberRows);
	
	return $.get('/utils/getusers.php?forTeamSort')
		.done(function(resp){
			if(!(resp instanceof Array)){
				$userTable.text("Error loading users");
				return;
			}
			
			// Build up the data
			var dataArr = [];
			$.each(resp, function(i, user){
				var dataRow = {
					uid: user.uid,
					name: user.name,
					email: user.email,
					housename: user.housename,
					isPaid: user.isPaid
				};
				dataArr.push(dataRow);
			});
			dataArr.sort(function(a, b){
				return a.housename.localeCompare(b);
			});
			
			function renderBooleanHumanReadable(bool){
				return bool ? "YES" : "NO";
			}
			
			function renderToggleButton(value, className, uid){
				var text = (value ? "YES" : "NO");
				return '<button class="'+className+'" uid="'+uid+'">'+text+'</button>';
			}
			
			function renderTeamDropdown(value, uid){
				//TODO: pull teams from the database
				var options = ['Unsorted', 'Baratheon', 'Lannister', 'Martell', 'Stark', 'Maesters'];
				var optionHtml = _.reduce(options, function(html, team, i){
					html += '<option value="'+team+'">'+team+'</option>';
					return html;
				}, "");
				return '<select class="team-dropdown" uid="'+uid+'" value="'+value+'">'+optionHtml+'</select>';
			}
			
			// Use DataTables for a fancy table
			dataTableForUserTable = $userTable.DataTable({
				// Don't do any fancy auto resizing of columns
				autoWidth: false,
				// Column definitions
				columns: [
					//placeholder cell for row number
					{title: "#", data: null, orderable: false, className: "row-num"},
					{title: "Name", data: "name"},
					{title: "Email", data: "email"},
					{title: "House", data: "housename", className: "house-cell", orderDataType: "team-select",
						render: function(house, type, row, meta){
							return renderTeamDropdown(house, row.uid);
						}
					},
					{title: "Paid?", data: "isPaid",
						render: function(isPaid, type, row, meta){
							return renderToggleButton(isPaid, 'paid-toggle-btn', row.uid);
						}
					}
				],
				// Default order
				order: [[3, "asc"], [1, "asc"]],
				// Data for the table
				data: dataArr,
				// Entries per page menu
				lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
				// Default to showing all
				displayLength: -1,
				// HTML DOM
				dom: '<"top"<"row"lf><"row"ip>>rt<"bottom"<"row"ip>>'
			});
			
			// Fix the starting state for the house dropdown
			_.each($('.house-cell select'), function(select){
				var $dropdown = $(select);
				$dropdown.val($dropdown.attr('value'));
			});
		});
}

function renumberRows(){
	// Go through each row and re-number them
	$.each($('td.row-num'), function(i, row){
		$(row).text(i+1);
	});
}

var processingPaid = [];
function setupActionButtonClickHandlers(){
	renumberRows();
	
	var YES = 1;
	var NO = 0;
	
	// Click handler for the paid toggle button
	$('.paid-toggle-btn').off().on('click', function(e){
		var $btn = $(this);
		var $row = $btn.closest('tr');
		var uid = $btn.attr('uid') || "";
		
		// Do nothing if it's already processing for this row
		if(isTogglingPaidLocked(uid)){
			alert("Toggling this won't work until the last request finishes processing.");
			return;
		}
		lockTogglingIsPaid(uid);
		
		// Update the value in the table
		var row = dataTableForUserTable.row($row[0]);
		var data = row.data();
		data.isPaid = (data.isPaid ? NO : YES);
		row.invalidate();
		setupActionButtonClickHandlers();
		
		// Make the ajax call
		$.ajax({
			url: "/utils/modifyregistration.php",
			type: 'POST',
			data: "togglePaid=true&uid="+uid
		}).done(function(resp){
			if(typeof resp == 'object'){
				// Make the change
				data.isRegistered = resp.isRegistered;
				data.isPaid = resp.isPaid;
				data.isPresent = resp.isPresent;
			}else{
				// Print the error message and revert the change
				alert(resp);
				data.isPaid = (data.isPaid ? NO : YES);
			}
			row.invalidate();
			setupActionButtonClickHandlers();
		}).always(function(){
			unlockTogglingIsPaid(uid);
		});
	});
	
	$('#sort-the-unsorted').on('click', function(){
		$.get('/utils/getusers.php?forTeamSort')
			.done(function(users){
				_.each(users, function(user){
					if(user.houseid === "0"){
						queueTeamSort(user.uid);
					}
				});
			});
	});
	
	$('.team-dropdown').on('change', function(e){
		var $dropdown = $(this);
		var uid = $dropdown.attr('uid');
		var housename = $dropdown.val();
		$.ajax({
			url: "/utils/sortuser.php?uid="+uid+"&housename="+housename,
			type: "GET"
		});
	});
	//TODO: allow multi-edit and/or drag-and-drop
}

var uidQueue = [],
	sortPromise = null;
function queueTeamSort(uid){
	if(_.isNull(sortPromise)){
		sortPromise = $.get("/utils/sortuser.php?uid="+uid)
			.always(nextTeamSort);
	}else{
		uidQueue = _.union(uidQueue, [uid]);
	}
}
function nextTeamSort(){
	if(_.isEmpty(uidQueue)){
		window.location.reload(true);
		return;
	}
	
	// Get the next uid from the queue
	var uid = uidQueue[0];
	uidQueue = _.without(uidQueue, uid);
	
	// Make an ajax call
	sortPromise = $.get("/utils/sortuser.php?uid="+uid)
			.always(nextTeamSort);
}

// Helper functions for locking/unlocking isPaid toggling
function isTogglingPaidLocked(uid){
	return _.contains(processingPaid, uid);
}
function lockTogglingIsPaid(uid){
	processingPaid = _.union(processingPaid, [uid]);
}
function unlockTogglingIsPaid(uid){
	processingPaid = _.without(processingPaid, uid);
}

});
</script>
</body>
</html>