<?php
	
require_once 'Zend/Loader.php';

Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_HttpClient');
Zend_Loader::loadClass('Zend_Gdata_Calendar');

$_authSubKeyFile = null; // Example value for secure use: 'mykey.pem'
$_authSubKeyFilePassphrase = null;

function getCurrentUrl()
{
    global $_SERVER;
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

function requestUserLogin($linkText)
{
    $authSubUrl = getAuthSubUrl();
    echo "<a href='javascript:void(0)' onclick=\"window.open('{$authSubUrl}','Authentication','height=500,width=600');\">{$linkText}</a>";
}

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
                //if( isset($_POST['calid']) )
                //{
                    $client = getAuthSubHttpClient();
                    outputCalendarByDateRange($client,'2007-06-01','2015-08-31');
                //}
                /*else
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
                }*/
            }
        }
    }
}

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

function outputCalendar($client)
{
    $gdataCal = new Zend_Gdata_Calendar($client);
    $query = $gdataCal->newEventQuery();
    $query->setUser(substr($_POST['calid'],strrpos($_POST['calid'],"/")+1));
    $query->setVisibility('private');
    $query->setProjection('full');
    $query->setOrderby('starttime');
    $query->setFutureevents(false);
    $eventFeed = $gdataCal->getCalendarEventFeed($query);
    
    echo "<a href='http://localhost/tmpp/google-calendar-for-atutor/ui.php?logout=true' >Logout</a><br/><ul>\n";
    foreach ($eventFeed as $event) {
        echo "\t<li>" . $event->title->text .  " (" . $event->id->text . ")\n";
        echo "\t\t<ul>\n";
        foreach ($event->when as $when) {
            echo "\t\t\t<li>Starts: " . $when->startTime . "</li>\n";
        }
        echo "\t\t</ul>\n";
        echo "\t</li>\n";
    }
    echo "</ul>\n";
}

function outputCalendarByDateRange($client, $startDate='2007-05-01',
                                   $endDate='2007-08-01')
{
    $gdataCal = new Zend_Gdata_Calendar($client);
    $query = $gdataCal->newEventQuery();
    $query->setUser(substr('http://www.google.com/calendar/feeds/default/07bit012%40nirmauni.ac.in',strrpos('http://www.google.com/calendar/feeds/default/07bit012%40nirmauni.ac.in',"/")+1));
    $query->setVisibility('private');
    $query->setProjection('full');
    $query->setOrderby('starttime');
    $query->setStartMin($startDate);
    $query->setStartMax($endDate);
    $eventFeed = $gdataCal->getCalendarEventFeed($query);
    
    $rows = array();
    $row;
    
    foreach ($eventFeed as $event) {

        $eventID = "";
        for($i=0;$i<7;$i++)
        {
            $eventID .= $event->id->text[rand(0,strlen($event->id->text)-1)];
        }

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
            
            $row = array();
            $row["title"] = $event->title->text;
            $row["id"] = $eventID;
            $row["start"] = $startD;
            $row["end"] = $endD;
            $row["allDay"] = $allDay;
            
            array_push( $rows, $row );            
        }        
    }
    //Encode in JSON format.
    $str =  json_encode( $rows );
    
    //Replace "true","false" with true,false for javascript.
    $str = str_replace('"true"','true',$str);
    $str = str_replace('"false"','false',$str);
    
    //Return the events in the JSON format.
    echo $str;    
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