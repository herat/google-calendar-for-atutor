<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Demos
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * PHP sample code for the Google Calendar data API.  Utilizes the
 * Zend Framework Gdata components to communicate with the Google API.
 *
 * Requires the Zend Framework Gdata components and PHP >= 5.1.4
 *
 * You can run this sample both from the command line (CLI) and also
 * from a web browser.  When running through a web browser, only
 * AuthSub and outputting a list of calendars is demonstrated.  When
 * running via CLI, all functionality except AuthSub is available and dependent
 * upon the command line options passed.  Run this script without any
 * command line options to see usage, eg:
 *     /usr/local/bin/php -f Calendar.php
 *
 * More information on the Command Line Interface is available at:
 *     http://www.php.net/features.commandline
 *
 * NOTE: You must ensure that the Zend Framework is in your PHP include
 * path.  You can do this via php.ini settings, or by modifying the
 * argument to set_include_path in the code below.
 *
 * NOTE: As this is sample code, not all of the functions do full error
 * handling.  Please see getEvent for an example of how errors could
 * be handled and the online code samples for additional information.
 */

/**
 * @see Zend_Loader
 */
require_once 'Zend/Loader.php';

/**
 * @see Zend_Gdata
 */
Zend_Loader::loadClass('Zend_Gdata');

/**
 * @see Zend_Gdata_AuthSub
 */
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

/**
 * @see Zend_Gdata_ClientLogin
 */
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

/**
 * @see Zend_Gdata_HttpClient
 */
Zend_Loader::loadClass('Zend_Gdata_HttpClient');

/**
 * @see Zend_Gdata_Calendar
 */
Zend_Loader::loadClass('Zend_Gdata_Calendar');

/**
 * @var string Location of AuthSub key file.  include_path is used to find this
 */
$_authSubKeyFile = null; // Example value for secure use: 'mykey.pem'

/**
 * @var string Passphrase for AuthSub key file.
 */
$_authSubKeyFilePassphrase = null;

/**
 * Returns the full URL of the current page, based upon env variables
 *
 * Env variables used:
 * $_SERVER['HTTPS'] = (on|off|)
 * $_SERVER['HTTP_HOST'] = value of the Host: header
 * $_SERVER['SERVER_PORT'] = port number (only used if not http/80,https/443)
 * $_SERVER['REQUEST_URI'] = the URI after the method of the HTTP request
 *
 * @return string Current URL
 */
function getCurrentUrl()
{
    global $_SERVER;

    /**
     * Filter php_self to avoid a security vulnerability.
     */
    $php_request_uri = htmlentities(substr($_SERVER['REQUEST_URI'], 0, strcspn($_SERVER['REQUEST_URI'], "\n\r")), ENT_QUOTES);

    if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    $host = $_SERVER['HTTP_HOST'];
    if ($_SERVER['SERVER_PORT'] != '' &&
        (($protocol == 'http://' && $_SERVER['SERVER_PORT'] != '80') ||
            ($protocol == 'https://' && $_SERVER['SERVER_PORT'] != '443'))) {
        $port = ':' . $_SERVER['SERVER_PORT'];
    } else {
        $port = '';
    }
    return $protocol . $host . $port . $php_request_uri;
}

/**
 * Returns the AuthSub URL which the user must visit to authenticate requests
 * from this application.
 *
 * Uses getCurrentUrl() to get the next URL which the user will be redirected
 * to after successfully authenticating with the Google service.
 *
 * @return string AuthSub URL
 */
function getAuthSubUrl()
{
    global $_authSubKeyFile;
    $next = getCurrentUrl();
    $scope = 'http://www.google.com/calendar/feeds/';
    $session = true;
    if ($_authSubKeyFile != null) {
        $secure = true;
    } else {
        $secure = false;
    }
    return Zend_Gdata_AuthSub::getAuthSubTokenUri($next, $scope, $secure,
        $session);
}

/**
 * Outputs a request to the user to login to their Google account, including
 * a link to the AuthSub URL.
 *
 * Uses getAuthSubUrl() to get the URL which the user must visit to authenticate
 *
 * @return void
 */
function requestUserLogin($linkText)
{
    $authSubUrl = getAuthSubUrl();
    echo "<a href='javascript:void(0)' onclick=\"window.open('{$authSubUrl}','Authentication','height=500,width=600');\">{$linkText}</a>";
}

/**
 * Returns a HTTP client object with the appropriate headers for communicating
 * with Google using AuthSub authentication.
 *
 * Uses the $_SESSION['sessionToken'] to store the AuthSub session token after
 * it is obtained.  The single use token supplied in the URL when redirected
 * after the user succesfully authenticated to Google is retrieved from the
 * $_GET['token'] variable.
 *
 * @return Zend_Http_Client
 */
function getAuthSubHttpClient()
{
    global $_SESSION, $_GET, $_authSubKeyFile, $_authSubKeyFilePassphrase;
    $client = new Zend_Gdata_HttpClient();
    if ($_authSubKeyFile != null) {
        // set the AuthSub key
        $client->setAuthSubPrivateKeyFile($_authSubKeyFile, $_authSubKeyFilePassphrase, true);
    }
    if (!isset($_SESSION['sessionToken']) && isset($_GET['token'])) {
        $_SESSION['sessionToken'] =
            Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token'], $client);
    }
    $client->setAuthSubToken($_SESSION['sessionToken']);
    return $client;
}
function isvalidtoken( $tokent )
{
    try
    {
        $client = getAuthSubHttpClient();
        outputCalendarListCheck($client);
        return true;
    }
    catch( Zend_Gdata_App_HttpException $e )
    {
        $db = mysql_connect('localhost','root','root');
        mysql_select_db('test',$db);
        $qry = "SELECT * FROM test_session";
        $res = mysql_query($qry,$db);
        if( mysql_num_rows($res) > 0 )
        {
          $qry = "DELETE FROM test_session";
          mysql_query($qry,$db);          
        }
        logout();
    }
}
/**
 * Processes loading of this sample code through a web browser.  Uses AuthSub
 * authentication and outputs a list of a user's calendars if succesfully
 * authenticated.
 *
 * @return void
 */
function processPageLoad()
{
    global $_SESSION, $_GET;
    session_start();
    if( isset($_GET['logout']) )
    {
        $db = mysql_connect('localhost','root','root');
        mysql_select_db('test',$db);
        $qry = "SELECT * FROM test_session";
        $res = mysql_query($qry,$db);
        if( mysql_num_rows($res) > 0 )
        {
          $qry = "DELETE FROM test_session";
          mysql_query($qry,$db);          
        }
        logout();
    }
    else
    {
        $db = mysql_connect('localhost','root','root');
        mysql_select_db('test',$db);
        $qry = "SELECT * FROM test_session";
        $res = mysql_query($qry,$db);
        if( mysql_num_rows($res) > 0 )
        {
          $row = mysql_fetch_assoc($res);
          $_SESSION['sessionToken'] = $row['sessionkey'];
        }

        if (!isset($_SESSION['sessionToken']) && !isset($_GET['token'])) {
            requestUserLogin('Please login to your Google Account.');
        } else {
            if( isset($_GET['token']) || ( isset($_SESSION['sessionToken']) && isvalidtoken($_SESSION['sessionToken']) ) )
            {
                if( isset($_POST['calid']) )
                {
                    $client = getAuthSubHttpClient();
                    outputCalendarByDateRange($client,'2007-06-01','2015-08-31');
                }
                else
                {
                    $client = getAuthSubHttpClient();
                    if( mysql_num_rows($res) <= 0 )
                   {
                       $db = mysql_connect('localhost','root','root');
                       mysql_select_db('test',$db);
                       $qry = "INSERT INTO test_session values ('".$_SESSION['sessionToken']."')";
                       $res = mysql_query($qry,$db);
                   }
                    echo "<script>window.opener.location.reload(false);window.close();</script>";
                    outputCalendarList($client);            
                }
            }
        }
    }
}

/**
 * Returns a HTTP client object with the appropriate headers for communicating
 * with Google using the ClientLogin credentials supplied.
 *
 * @param  string $user The username, in e-mail address format, to authenticate
 * @param  string $pass The password for the user specified
 * @return Zend_Http_Client
 */
function getClientLoginHttpClient($user, $pass)
{
    $service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;

    $client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
    return $client;
}

function outputCalendarListCheck($client)
{
    $gdataCal = new Zend_Gdata_Calendar($client);
    $calFeed = $gdataCal->getCalendarListFeed();
}
/**
 * Outputs an HTML unordered list (ul), with each list item representing a
 * calendar in the authenticated user's calendar list.
 *
 * @param  Zend_Http_Client $client The authenticated client object
 * @return void
 */
function outputCalendarList($client)
{
    $gdataCal = new Zend_Gdata_Calendar($client);
    $calFeed = $gdataCal->getCalendarListFeed();
    echo "<a href='http://localhost/tmpp/google-calendar-for-atutor/ui.php?logout=true' >Logout</a><br/>
    <form method='post' action='' onsubmit='window.opener.location.reload(false);window.close();'>";
    echo "<h1>" . $calFeed->title->text . "</h1>\n";
    echo "<ul>\n";
    foreach ($calFeed as $calendar) {
        //echo "\t<li>" . $calendar->title->text . "</li>\n";
        echo "\t<input type='radio' name ='calid' value='".$calendar->id->text."'/>".$calendar->title->text."<br/>";
    }
    echo "</ul>\n";
    echo "<input type='submit' value='Submit' />";
    echo "</form>";
}

/**
 * Outputs an HTML unordered list (ul), with each list item representing an
 * event on the authenticated user's calendar.  Includes the start time and
 * event ID in the output.  Events are ordered by starttime and include only
 * events occurring in the future.
 *
 * @param  Zend_Http_Client $client The authenticated client object
 * @return void
 */
function outputCalendar($client)
{
    //echo "<br/>".substr($_POST['calid'],strrpos($_POST['calid'],"/")+1)."<br/>";
    $gdataCal = new Zend_Gdata_Calendar($client);
    $query = $gdataCal->newEventQuery();
    $query->setUser(substr($_POST['calid'],strrpos($_POST['calid'],"/")+1));
    $query->setVisibility('private');
    $query->setProjection('full');
    $query->setOrderby('starttime');
    $query->setFutureevents(false);
    $eventFeed = $gdataCal->getCalendarEventFeed($query);
    // option 2
    // $eventFeed = $gdataCal->getCalendarEventFeed($query->getQueryUrl());
    echo "<a href='http://localhost/tmpp/google-calendar-for-atutor/ui.php?logout=true' >Logout</a><br/><ul>\n";
    foreach ($eventFeed as $event) {
        echo "\t<li>" . $event->title->text .  " (" . $event->id->text . ")\n";
        // Zend_Gdata_App_Extensions_Title->__toString() is defined, so the
        // following will also work on PHP >= 5.2.0
        //echo "\t<li>" . $event->title .  " (" . $event->id . ")\n";
        echo "\t\t<ul>\n";
        foreach ($event->when as $when) {
            echo "\t\t\t<li>Starts: " . $when->startTime . "</li>\n";
        }
        echo "\t\t</ul>\n";
        echo "\t</li>\n";
    }
    echo "</ul>\n";
}

/**
 * Outputs an HTML unordered list (ul), with each list item representing an
 * event on the authenticated user's calendar which occurs during the
 * specified date range.
 *
 * To query for all events occurring on 2006-12-24, you would query for
 * a startDate of '2006-12-24' and an endDate of '2006-12-25' as the upper
 * bound for date queries is exclusive.  See the 'query parameters reference':
 * http://code.google.com/apis/gdata/calendar.html#Parameters
 *
 * @param  Zend_Http_Client $client    The authenticated client object
 * @param  string           $startDate The start date in YYYY-MM-DD format
 * @param  string           $endDate   The end date in YYYY-MM-DD format
 * @return void
 */
function outputCalendarByDateRange($client, $startDate='2007-05-01',
                                   $endDate='2007-08-01')
{
    echo $_SESSION['sessionToken'];
    $gdataCal = new Zend_Gdata_Calendar($client);
    $query = $gdataCal->newEventQuery();
    $query->setUser(substr($_POST['calid'],strrpos($_POST['calid'],"/")+1));
    $query->setVisibility('private');
    $query->setProjection('full');
    $query->setOrderby('starttime');
    $query->setStartMin($startDate);
    $query->setStartMax($endDate);
    $eventFeed = $gdataCal->getCalendarEventFeed($query);
    echo "<a href='http://localhost/tmpp/google-calendar-for-atutor/ui.php?logout=true' >Logout</a><br/><ul>\n";
    foreach ($eventFeed as $event) {

        $eventID = "";
        for($i=0;$i<7;$i++)
        {
            $eventID .= $event->id->text[rand(0,strlen($event->id->text)-1)];
        }

        echo "\t<li>" . $event->title->text .  " (" . $eventID . ")\n";
        echo "\t\t<ul>\n";
        foreach ($event->when as $when) {
            $startD = substr($when->startTime,0,19);
            $startD = str_replace("T"," ",$startD);

            $endD = substr($when->endTime,0,19);
            $endD = str_replace("T"," ",$endD);

            /*
             * If both start time and end time are different and their time parts differ then allDay is false
             */
            if( ($startD != $endD) && substr($startD,0,10) == substr($endD,0,10) )
            {
                $allDay = 'false';
            }
            else
            {
                $allDay = 'true';
            }
            echo "\t\t\t<li>Starts: " . $startD . "</li>\n";
            echo "\t\t\t<li>Ends:". $endD ."</li>\n";
            echo "\t\t\t<li>allDay:". $allDay ."</li>\n";
        }
        echo "\t\t</ul>\n";
        echo "\t</li>\n";
    }
    echo "</ul>\n";
}

function logout()
{
    // Carefully construct this value to avoid application security problems.
    $php_self = htmlentities(substr($_SERVER['PHP_SELF'],
                             0,
                             strcspn($_SERVER['PHP_SELF'], "\n\r")),
                             ENT_QUOTES);
     
    Zend_Gdata_AuthSub::AuthSubRevokeToken($_SESSION['sessionToken']);
    unset($_SESSION['sessionToken']);
    header('Location: ' . $php_self);
    exit();
}

processPageLoad();
?>