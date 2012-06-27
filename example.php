<?php

// Get the eventarc library. Note this is VERSION 3 of the lib.
require_once __DIR__.'/api/Eventarc.php';

// Helper functions
// 

function output($label, $data)
{
	echo '<hr><h2>'.$label.'</h2>';
	echo '<pre>'.print_r($data,TRUE).'</pre>';
	echo '<br><br>';
}


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
	// Create an event with eWay
	// 
	// First, create the eway driver. You only need to do this once for each version
	// of the driver.
	$eway_customer_code = 'abcdefg12345';
	$pd_data = $eventarc->payment_createewaydriver($eway_customer_code);
	output('Eway payment driver data',$pd_data);

	// Now we have a driver we need to make a payment config using this driver. You 
	// can make as many payment configs as you want, referencing the same payment
	// driver.
	// 
	// 'pa_bookingstatic' float The static booking fee (as a currency value) for 
	// each ticket sold eg. 1 Would mean $1 booking fee
	// 'pa_bookingpercent' float The percentage booking fee for each ticket sold. 
	// eg. a value of 5 would mean that for a $100 ticket, a booking fee of $5 would
	// be charged. Note that you can have both a booking static and booking 
	// percentage fee.
	// 'pa_bookingcap' float The maximum booking fee charge per ticket.
	// 'pd_id' int The pd_id of the payment driver you want to use for this config
	// 'pa_default' int (1|0) Do you want to make this config the default for all 
	// events.
	// 'pa_currency' string The currency to use ie. 'AUD'
	// 
	$pa_insert = array(
		'pa_bookingstatic' => 0,
		'pa_bookingpercent' => 5,
		'pa_bookingcap' => 15,
		'pd_id' => $pd_data['pd_id'],
		'pa_default' => 1,
		'pa_currency' => 'AUD');

	$pa_data = $eventarc->payment_createconfig($pa_insert);
	output('Payment config', $pa_data);

	// Now that we have a driver and a config, create an event to use it. Note the
	// new fields e_showcomments, e_showsocialmedia, e_attachticket
	$next_week = time() + (7 * 24 * 60 * 60);
	$e_data = array(
		'e_name' => 'Eway test event',
		'e_description' => 'This is my event',
		'e_start' => date('Y-m-d G:i:s', $next_week),
		'e_stop' => date('Y-m-d G:i:s', $next_week + 60),
		'e_deadline' => date('Y-m-d G:i:s', $next_week - 60), 
		'e_status' => 'active', // Either active, draft or deleted
		'e_timezone' => 'Australia/Melbourne', // Defaults to your users timezone
		'e_thanksmessage' => '<p>Thanks!</p>',
		'e_showcomments' => FALSE,
		'e_showsocialmedia' => TRUE,
		'e_attachticket' => FALSE,
		'g_id' => $g_id, // The group (folder) you want the event to sit in
		'u_id' => $u_id); // Your user id

	// Note we have the ability to show a map (via lat / lon)
	$a_data = array(
		'a_type' => 'venue', // Always leave this as venue
		'a_add1' => '534 Church Street', // Address line 1
		'a_city' => 'Richmond', // City
		'a_state' => 'Victoria', // State
		'a_post' => '3121', // Postcode / zip
		'a_lat' =>  -37.8285938, 
		'a_lon' => 144.997282,
		'a_showmap' => TRUE,
		'a_country' => 'Australia'); // Country

	$ticket = array(
		't_name' => 'Paid ticket',
		't_description' => 'You have to pay for this one',
		't_total' => '200',
		't_price' => '10',
		't_earlybird' => '0', // Earlybird is off
		't_order' => '2',
		't_type' => 'normal',
		't_defaultquantity' => 10);

	// Create the event
	$e_data = $eventarc
		->add_event($e_data) // Add the event data
		->add_address($a_data) // Add the address data
		->add_ticket_limit(1000) // Set a ticket limit of 2000 (THIS IS REQUIRED)
		->add_ticket($ticket) // Add a ticket
		->event_create(); // Create it

	output('Create event',$e_data);
	echo '<a href="'.$e_data['url'].'" target="_blank">View event</a>';

	// Assign the config to the event
	$eventarc->event_set_paymentconfig($e_data['e_id'], $pa_data['pa_id']);
}
catch(Eventarc_Exception $e)
{
	echo 'BAD times';
	var_dump($e);
}
