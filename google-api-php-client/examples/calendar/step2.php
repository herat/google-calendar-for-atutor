<?php
require_once '../../src/apiClient.php';
require_once '../../src/contrib/apiCalendarService.php';
session_start();

$client = new apiClient();
$client->setApplicationName("Google Calendar PHP Starter Application");

// Visit https://code.google.com/apis/console?api=calendar to generate your
// client id, client secret, and to register your redirect uri.
// $client->setClientId('insert_your_oauth2_client_id');
// $client->setClientSecret('insert_your_oauth2_client_secret');
// $client->setRedirectUri('insert_your_oauth2_redirect_uri');
// $client->setDeveloperKey('insert_your_developer_key');
$cal = new apiCalendarService($client);

if( isset($_SESSION["token"]) && $client->getAccessToken())
{
	$calid = $_POST["calendarid"];
	$events = $cal->events->listEvents($calid);
	echo "<pre>".print_r( $events, true )."</pre>";
}

?>