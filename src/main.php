<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\							v3
//				
\\				
//				
\\	Title:		Server
//				
\\	Desc:		Parses raw server information and sends
//				it along to their hooks.
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* Loading the base code :P */

/*
 * Language files. Please uncomment yours!
 * If yours isn't available and you would like to contribute
 * one, please make a pull request via github.
 */
 
include "languages/en_GB";
//include "languages/tr_TR";

define("DALEK_CONF_DIR", getenv("DALEK_CONF_DIR") ?: __DIR__."/../conf");
define("DALEK_LOG_DIR", getenv("DALEK_LOG_DIR") ?: __DIR__."/../logs");

include DALEK_CONF_DIR . '/dalek.conf';
global $sql,$server,$port,$serv,$servertime;
include "misc.php";
include "hook.php";
include "conf.php";
include "numeric.php";
include "language.php";
include "rpc.php";
include "serv.php";
include "sql.php";
include "client.php";
include "user.php";
include "wordpress/wordpress.php";
include "channel.php";
include "cmd.php";
include "module.php";
include "filter.php";
include "servcmd.php";
include "events.php";
include "buffer.php";

// Server config
$server = config_get_item("link::hostname");
$port = config_get_item("link::port");
$mypass = config_get_item("link::password");


// SQL config
$arr = [];
$sql = new SQL();
hook::run("preconnect", $arr);
/* Okay, we've established all the information lmao, let's load the modules */


include DALEK_CONF_DIR . '/modules.conf';

start:
$serv = new Server($server,$port,$mypass);

if (!$socket || !$server)
	die("oops");

stream_set_blocking($socket, 0);

for ($input = Buffer::do_buf(stream_get_line($socket, 0, "\n"));;$input = Buffer::do_buf(stream_get_line($socket, 0, "\n")))
{

	/* Check for new events */
	if ($servertime != servertime())
	{
		Events::CheckForNew();
		$servertime = servertime();
	}
	
	/* Check for RPC Calls */
	rpc_check();
	if (!$socket)
		die();
	if (!$input)
		continue;

	log_to_disk($input);
	if (Conf::$settings['log']['debug'] == "yes")
		echo "[\e[0;30;47mRECV\e[0m] ".$input."\n";
	
	flush();
	$strippem = ircstrip(str_replace('\\','\\\\',$input));
	$splittem = explode(' ',$strippem);
	
	// If the server pings us
	if ($splittem[0] == 'PING')
	{
		/* hook into ping lol */
		$arr = ['token' => $splittem[1]];
		hook::run(HOOKTYPE_PING, $arr);
		S2S("PONG ".$splittem[1]); 	// Ping it back
	}
	elseif ($splittem[0] == 'ERROR')
	{
		
		if (strpos($input,'Throttled') !== false)
		{
			$serv->hear("Uh-oh, we've been throttled! Waiting 40 seconds and starting again.");
			sleep(40);
			$serv->shout("Reconnecting...");
			$serv = NULL;
			goto start;
		}
		elseif (strpos($input,'Timeout') !== false)
		{
			if (IsConnected())
			{
				$serv->hear("Connection issue. Trying again in 30 seconds");
				sleep(30);
				$serv = NULL;
				goto start;
			}
			else
			{
				die($serv->hear("Connection issue. Please check dalek.conf"));					
			}
			
		}
		else
		{
			$serv->hear("Unknown exit issue! Waiting 40 seconds and restarting");
			usleep(400000);
			$serv = NULL;
			goto start;
		}
	}
	else
	{
		
		$tagmsg = NULL;
		if ($splittem[0][0] == '@')
		{
			$tagmsg = $splittem[0];
			$strippem = ltrim(str_replace($tagmsg,"",$strippem)," ");
			$splittem = explode(" ",$strippem);
		}
		if ($splittem[0] == "PASS")
		{
			global $isconn;
			$isconn = NULL;
			
			$pass = mb_substr($splittem[1],1);
			
			if ($pass !== Conf::$settings['link']['password'])
				die("Passwords do not match.");
			
			$array = [];
			hook::run(HOOKTYPE_CONNECT, $array);
			$isconn = true;
		}
		$action = $splittem[1];

		/* well, we stopped supporting tags for the while for reasons */
		if ($action == "TAGMSG")
		{

			$nick = mb_substr($splittem[0],1);
			$dest = $splittem[2];
			$array = array(
				"nick" => $nick,
				"dest" => $dest,
				"mtags" => mtag_to_array($tagmsg));
			hook::run(HOOKTYPE_TAGMSG, $array);
			
		
		}
		else
		{
			$array = array('mtags' => $tagmsg, 'string' => $strippem);
			hook::run(HOOKTYPE_RAW, $array);
		}
		
	}
}

