<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Eventarc API</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/timepicker.css" rel="stylesheet">
	<link href="css/bootstrap-wysihtml5.css" rel="stylesheet">
	
	<
	<style>
	/* Put space at the top of each header and section to make room for the nav bar */
	header {
        padding-top: 60px;
    }
	section {
        padding-top: 30px;
    }
    
    /* Set Error Label to Red */
    label.error { 
    	color: red; 
    }
    </style>
	
    <link href="css/bootstrap-responsive.css" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/<script type="text/javascript" src="jquery-1.3.2.js"></script>
	<script type="text/javascript" src="jquery.validate.min.js"></script><script type="text/javascript" src="jquery-1.3.2.js"></script>
	<script type="text/javascript" src="jquery.validate.min.js"></script>svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- event_success variable declared as false - This is for javascript at bottom of page that shows the success window -->
    <script>var event_success=false;</script>

    <?php

	// Get the eventarc library. Note this is VERSION 3 of the lib.
	require_once __DIR__.'/api/Eventarc.php';



	// User specific variables
	$user_name = 'bob';
	$apikey = '91c16e01615dde8a964b';
	$g_id = 48; // Group for this user
	$u_id = 123; // User id for this user
	$eventarc = new Eventarc($apikey, $user_name);

	// Reset to local dev version
	$eventarc->server = 'http://myeventarc.earcdev.com/api/v3/';

	try
	{
		if ($_POST)
		{
			// Now that we have a driver and a config, create an event to use it. Note the
			// new fields e_showcomments, e_showsocialmedia, e_attachticket
			$e_data = array(
				'e_name' => $_POST['e_name'],
				'e_description' => $_POST['e_description'],
				'e_start' => convert_date_time($_POST['e_start_date'],$_POST['e_start_time']),
				'e_stop' => convert_date_time($_POST['e_stop_date'],$_POST['e_stop_time']),					// convert_date_time function is around line 70
				'e_deadline' => convert_date_time($_POST['e_deadline_date'],$_POST['e_deadline_time']),
				'e_status' => 'active', // Either active, draft or deleted
				'e_timezone' => 'Australia/Melbourne', // Defaults to your users timezone
				'e_thanksmessage' => '<p>Thanks!</p>',
				'e_showcomments' => FALSE,
				'e_showsocialmedia' => TRUE,
				'e_attachticket' => FALSE,
				'g_id' => $g_id, // The group (folder) you want the event to sit in
				'u_id' => $u_id); // Your user id

			$ticket = array(
				't_name' => $_POST['t_name'],
				't_description' => '',
				't_total' => $_POST['t_total'],
				't_price' => '0',
				't_earlybird' => '0', // Earlybird is off
				't_order' => '2',
				't_type' => 'normal',
				't_defaultquantity' => 10);

			// Create the event
			$e_data = $eventarc
				->add_event($e_data) // Add the event data
				->add_ticket_limit(1000000) // Set a ticket limit of 2000 (THIS IS REQUIRED)
				->add_ticket($ticket) // Add a ticket
				->event_create(); // Create it

			//die('Create event'.print_r($e_data,TRUE));
			//echo '<a href="'.$e_data['url'].'" target="_blank">View event</a>';

			// Set event_success to true apone succesful creation - this will activate the success window at bottom of page
			echo "<script>event_success=true;</script>";
		}
	}
	catch(Eventarcapi_Exception $e)
	{
		echo 'BAD times';
		var_dump($e);
	}

	try
	{
		// download event list for the table
		$event_list = $eventarc->event_listsummary();
	}
	catch(Eventarcapi_Exception $e)
	{
		echo 'BAD times';
		var_dump($e);
	}

	// function used above to turn the raw time and date data into the server format
	function convert_date_time($date, $time){
		list($d,$m,$y) = explode("-", $date);
		return sprintf('%4d-%02d-%02d',$y,$m,$d) . " " . DATE("H:i:s", STRTOTIME($time));
	}

	// define variable with the date of 7 days in advance from today - This is for code further down the page
	$nextweek_date=date('d-m-Y', date('U') + (7 * 24 * 60 * 60));
	?>
  </head>

  <body>

  	<!-- Top Nav Bar -->
    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="#">Eventarc API</a>
          <div class="nav-collapse">
            <ul class="nav">
				<li class="divider-vertical"></li>
				<li class><a href="#">Home</a></li>
				<li class><a href="#list">List Events</a></li>
				<li class><a href="#create">Create Event</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>
	
	<!-- The Error Modal - This is set of by the generate error button -->	
	<div class="modal hide" id="errorModal">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal">×</button>
			<h3>Error</h3>
		</div>
		<div class="modal-body">
			<p>An Error Occured!</p>
		</div>
		<div class="modal-footer">
			<a href="javascript:hide_error_modal()" class="btn" data-dismiss="modal">Close</a>
		</div>
	</div>

	<!-- The Success Modal - This is activated in code below and is used to show that the event has be succesfully created --> 
	<div class="modal hide" id="successModal">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal">×</button>
			<h3>Success</h3>
		</div>
		<div class="modal-body">
			<p>You have successfully created a new event!</p>
		</div>
		<div class="modal-footer">
			<a target='_blank' <?PHP echo 'href="' . $e_data['url'] . '"'; ?> class="btn btn-primary" >View Event</a>
			<a href="javascript:hide_sucess_modal()" class="btn" data-dismiss="modal">Close</a>
		</div>
	</div>
    
    <!-- Start the page contents -->
	<div class="container">

		<!-- Sub Header at top of page -->
		<header class="jumbotron subhead" id="overview">
		<h1>Eventarc API</h1>
		<p class="lead">Stuff</p>
		</header>
		
		<!-- Table List Section of the Page -->
		<section id="list">
			<div class="page-header">
				<h2>List Events</h2>
			</div>
			<table class="table table-bordered">
				<thead>
				<tr>
					<th>Event Name</th>
					<th>URL</th>
					<th>Event Date</th>
					<th>Money Raised</th>
					<th colspan="2">Availability</th>
				</tr>
			</thead>
				<tbody>
					<!-- Table Structure
						<tr>
							<th>Event Name - e_name</th>
							<th>URL - e_url</th>
							<th>Event Date - e_start</th>
							<th>Money Raised - p_sum e_currency</th>
							<th>Availablity - tp_count/to_total</th>
							<th><span data-colour="green" data-diameter="30" class="piechart">Availablity - tp_count/to_total</span></th>
						</tr>
					-->

					<!-- PHP that loops through the events list and adds an item to the table with structure above -->
					<?PHP
						for($event_no=0; $event_no<count($event_list); $event_no++)
						{
							echo "<tr>";
								echo "<th>" . $event_list[$event_no]["e_name"] . "</th>";
								echo "<th><a target='_blank' href='" . $event_list[$event_no]["e_url"] . "'>" . $event_list[$event_no]["e_url"] . "</a></th>";
								echo "<th>" . $event_list[$event_no]["e_start"] . "</th>";
								echo "<th>" . $event_list[$event_no]["p_sum"] . " " . $event_list[$event_no]["e_currency"] . "</th>";
								echo "<th>" . $event_list[$event_no]["tp_count"] . "/" . $event_list[$event_no]["to_total"] . "</th>";
								echo '<th><span data-colour="green" data-diameter="30" class="piechart">' . $event_list[$event_no]["tp_count"] . "/" . $event_list[$event_no]["to_total"] . "</span></th>";
							echo "</tr>";
							// Change the Pie Chart apperance above - data-diameter sets the diameter - data-colour sets the colour
						}
					?>
				</tbody>
			</table>
		</section>
		
		<!-- Create new event section -->
		<section id="create">
			<div class="page-header">
				<h2>Create Event</h2>
			</div>
			<form class="form-horizontal" method="POST" action="james.php" id="create_form">
				<fieldset>

					<!-- Event Name -->
					<div class="control-group"> 
						<label class="control-label" for="name">Event Name</label>
						<div class="controls">
							<input type="text" class="input-xlarge span10 required" id="name" name="e_name">
						</div>
					</div>

					<!-- Event Start Time -->
					<div class="control-group"> 
						<label class="control-label" for="start">Event Start Time</label>
						<div class="controls">
							<input type="text" class="timepicker span5" data-provide="timepicker" name="e_start_time">
							<div class="input-append date" id="start" data-date="<?PHP echo $nextweek_date; ?>" data-date-format="dd-mm-yyyy">
								<input class="span5" size="16" type="text" value="<?PHP echo $nextweek_date; ?>" readonly name="e_start_date">
								<span class="add-on"><i class="icon-th"></i></span>
							</div>
						</div>
					</div>

					<!-- Event End Time -->
					<div class="control-group">	
						<label class="control-label" for="end">Event End Time</label>
						<div class="controls">
							<input type="text" class="timepicker span5" data-provide="timepicker" name="e_stop_time">
							<div class="input-append date" id="end" data-date="<?PHP echo $nextweek_date; ?>" data-date-format="dd-mm-yyyy">
								<input class="span5" size="16" type="text" value="<?PHP echo $nextweek_date; ?>" readonly name="e_stop_date">
								<span class="add-on"><i class="icon-th"></i></span>
							</div>
						</div>
					</div>

					<!-- Registration Deadline -->
					<div class="control-group"> 	
						<label class="control-label" for="registration">Registration Deadline</label>
						<div class="controls">
							<input type="text" class="timepicker span5" data-provide="timepicker" name="e_deadline_time">
							<div class="input-append date" id="registration" data-date="<?PHP echo $nextweek_date; ?>" data-date-format="dd-mm-yyyy">
								<input class="span5" size="16" type="text" value="<?PHP echo $nextweek_date; ?>" readonly name="e_deadline_date">
								<span class="add-on"><i class="icon-th"></i></span>
							</div>
						</div>
					</div>

					<!-- Event Describtion -->	
					<div class="control-group"> 
						<label class="control-label" for="description">Event Description</label>
						<div class="controls">
							<textarea class="richtexteditor span10 required" name="e_description"></textarea>
						</div>
					</div>

					<!-- Ticket Name -->
					<div class="control-group"> 	
						<label class="control-label" for="ticketname">Ticket Name</label>
						<div class="controls">
							<input type="text" class="input-xlarge span10 required" id="ticketname" name="t_name">
						</div>
					</div>

					<!-- Number of Tickets Available -->	
					<div class="control-group"> 
						<label class="control-label" for="ticketno">Number of Tickets Available</label>
						<div class="controls">
							<input type="number" class="input-xlarge span10 digits required" id="ticketno" name="t_total">
						</div>
					</div>

					<!-- Submit Button -->
					<div class="form-actions span10"> 
						<a href="javascript:form_validate()" class="btn btn-large btn-primary">Submit</a>
						<a data-toggle="modal" href="#errorModal" class="btn btn-large btn-primary">Generate Error</a>
					</div>
				</fieldset>
			</form>
		</section>

    </div> <!-- /container -->

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap-transition.js"></script>
    <script src="js/bootstrap-alert.js"></script>
    <script src="js/bootstrap-modal.js"></script>
    <script src="js/bootstrap-dropdown.js"></script>
    <script src="js/bootstrap-scrollspy.js"></script>
    <script src="js/bootstrap-tab.js"></script>
    <script src="js/bootstrap-tooltip.js"></script>
    <script src="js/bootstrap-popover.js"></script>
    <script src="js/bootstrap-button.js"></script>
    <script src="js/bootstrap-collapse.js"></script>
    <script src="js/bootstrap-carousel.js"></script>
    <script src="js/bootstrap-typeahead.js"></script>

    <script type="text/javascript" src="js/jquery.validate.min.js"></script>

	<!-- Date Picker -->
	<script src="js/bootstrap-datepicker.js"></script>
	<script src="js/bootstrap-timepicker.js"></script>
	<script>
		$(document).ready(function () {
			$('#start').datepicker();
			$('#end').datepicker();
			$('#registration').datepicker();
			$('.timepicker').timepicker({
                defaultTime: 'current',
                minuteStep: 15,
                disableFocus: true,
                template: 'modal'
            });
		});
	</script>
	
	<!-- Pie Chart -->
	<script src="js/jquery.peity.min.js"></script>
	<script>
		// Function gets the data-colour and data-diameter properties and changes the pie graph apperance
		$(function(){
			$(".piechart").peity("pie", {
				colours: function() {
					return ["#dddddd", this.getAttribute("data-colour")]
				},
				diameter: function() {
					return this.getAttribute("data-diameter")
				}
			});
		});
	</script>
	
	<!-- Rich-text Editor -->
	<link rel="stylesheet" href="css/jquery.wysiwyg.css" type="text/css"/>
	<script type="text/javascript" src="js/wysiwyg/jquery.wysiwyg.js"></script>
	<script type="text/javascript" src="js/wysiwyg/wysiwyg.image.js"></script>
	<script type="text/javascript" src="js/wysiwyg/wysiwyg.link.js"></script>
	<script type="text/javascript" src="js/wysiwyg/wysiwyg.table.js"></script>
	<script>
		// Function creates the rich text editor on page ready
		$(document).ready( function () {
			$('.richtexteditor').wysiwyg();
		});
	</script>
	
	<!-- Modals -->
	<script>
		// Creates the error modal on page ready
		$(document).ready( function () {
			$('#errorModal').modal({
				show: false
			});
		});

		// creates the success modal but only shows if event_success is true - This is set above
		$(document).ready( function () {
			$('#successModal').modal({
				show: event_success
			});
		});

		// Functions that hide the modals 
		function hide_error_modal(){
			$('#errorModal').modal('hide');
		}
		function hide_sucess_modal(){
			$('#successModal').modal('hide');
		}
	</script>

	<!-- Form Validation -->
	<script>
		// Function that is run when submit button is clicked. It first finds if the form is valid, if it is it submits the form with jquery, if it isn't the form validation is shown.
		function form_validate(){
			if($("#create_form").valid()){
				$("#create_form").submit();
			}
		}
	</script>
  </body>
</html>