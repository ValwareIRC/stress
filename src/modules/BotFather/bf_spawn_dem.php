<?php

/*				
//	(C) 2021 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: Dictionarahhhh
//	
\\	Desc: Give a dictionary lookup thingamajig
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class bf_spawn_dem {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "bf_spawn_dem";
	public $description = "Spawn tons of clients lmao";
	public $author = "Valware";
	public $version = "1.0";

	/* Command references */
	const MSG_SPAWN = "SPAWN";
	const MSG_JOIN = "JOIN";
	const MSG_PART = "PART";
	const MSG_QUIT = "QUIT";

	static $conf_err = 0; // keep check of errors in the config
	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}


	function __init()
	{
		/* Global variable based on ya botfather config */
		global $bf;

		/* Find the client by the nick specified in the config =] */
		$tbf = Client::find($bf['nick']);

		/* Check botfather {} config in dalek.conf */
		hook::func(HOOKTYPE_CONFIGTEST, 'bf_spawn_dem::configtest');

		/* Config test reported an error */
		if (self::$conf_err)
			return false; // do not load module if configtest failed

		/* Some variables for loading the command SPAWN */
		$help_string = "Let's get spawny! Spawns the specified amount of clients with the nickmask";
		$syntax = "SPAWN <nickmask> <number>";
		$extended_help = 	"This will spawn the heck out of shit. There is no limit cap on the amount you \n".
							"can spawn, so go wild ;D just beware that remote SQL connections may take\n".
							"considerably longer.";

		/* If there was a crazy happening when adding the command, run away like a little girl flailing your arms */
		if (!AddServCmd(
			'bf_spawn_dem', /* Module name */
			$bf['nick'], /* Client name */
			self::MSG_SPAWN, /* Command */
			'bf_spawn_dem::spawn', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false; /* aaaaaaaaaaaaaaaaa */

		/* Some variables for loading the command DO */
		$help_string = "Does a raw IRC command from every spawn";
		$syntax = "JOIN #Channel";
		$extended_help = 	"This will let you make each spawned client join the channel that you specify.\n".
							"For example:\n".
							"JOIN #PossumsOnly";

		/* If there was a crazy happening when adding the command, run away like a little girl flailing your arms */
		if (!AddServCmd(
			'bf_spawn_dem', /* Module name */
			$bf['nick'], /* Client name */
			self::MSG_JOIN, /* Command */
			'bf_spawn_dem::join', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false; /* aaaaaaaaaaaaaaaaa */

		return true; /* Everything went well! Phew! */
	}
	
	/**
	 * SPAWN command 
	 * @param $parv[0] = SPAWN - The command
	 * @param $parv[1] = User - The nick prefix.
	 * @param $parv[2] = Number - The amount of clients to spawn
	 */
	public static function spawn($u)
	{
		$nick = $u['nick']; // Their User object
		/* Split dem parv's like a... banana...split? yeah. */
		$parv = split($u['msg']);

		/* Our BotFather who art in IRC */
		$bf = $u['target'];

		/* MessageTags account check */
		$mtags = $u['mtags'];
		if (!$mtags['account']) // no account
		{
			$bf->notice($nick->uid,"Permission denied.");
			return;
		}

		/** Permissions checking */
		/* get the owners list and put them into an array[] */
		$ownerslist = split(config_get_item("botfather::owner"),",");

		/* convert that array to lowercase for checkins */
		foreach($ownerslist as $key => &$val)
			$val = trim(strtolower($val),"\s"); // trim spaces also

		if (!in_array(strtolower($mtags['account']),$ownerslist))
		{
			$bf->notice($nick->uid,"Permission denied.");
			return;
		}
		
		/* some basic self-explanatory error checkin' */
		if (!isset($parv[2]) || !strlen($parv[1]) || !is_numeric($parv[2]))
		{
			$bf->notice($nick->uid,"Incorrect parameters. See /msg $bf->nick HELP ".self::MSG_SPAWN);
			return;
		}

		/* We need a number bigger than one :o */
		if ($parv[2] < 1) // uh oh
		{
			$bf->notice($nick->uid,"Number cannot be smaller than 1 ;o");
			return;
		}

		/* Run this the specified number of times */
		for ($i = 0; $i <= $parv[2]; $i++)
		{
			$botting = new Client(
				$parv[1].$i, /* Their name plus their iteration */
				"robot", /* ident */
				"not.a.real.person", /* host */
				NULL, /* UID (deprecated) */
				"Doctor Robotnik uwu", /* Gecos */
				'bf_spawn_dem' /* module info */
			);
		}
		$bf->notice($nick->uid, "Spawned ".$parv[2]." clients");
	}
	public static function join($u)
	{
		$nick = $u['nick']; // Their User object
		/* Split dem parv's like a... banana...split? yeah. */
		$parv = split($u['msg']);

		/* Our BotFather who art in IRC */
		$bf = $u['target'];

		/* MessageTags account check */
		$mtags = $u['mtags'];
		if (!$mtags['account']) // no account
		{
			$bf->notice($nick->uid,"Permission denied.");
			return;
		}

		/** Permissions checking */
		/* get the owners list and put them into an array[] */
		$ownerslist = split(config_get_item("botfather::owner"),",");

		/* convert that array to lowercase for checkins */
		foreach($ownerslist as $key => &$val)
			$val = trim(strtolower($val),"\s"); // trim spaces also

		if (!in_array(strtolower($mtags['account']),$ownerslist))
		{
			$bf->notice($nick->uid,"Permission denied.");
			return;
		}
		
		/* some basic self-explanatory error checkin' */
		if (!isset($parv[1]) || strcmp($parv[1][0],"#"))
		{
			$bf->notice($nick->uid,"Incorrect parameters. See /msg $bf->nick HELP ".self::MSG_JOIN);
			return;
		}

		foreach(Client::$list as $c)
		{
			if ($c->modinfo == "bf_spawn_dem" && $c->nick !== $bf->nick)
				$c->join($parv[1]);
		}
		$bf->notice($nick->uid,"Joined all clients to ".$parv[1]);
	}

	static function configtest($err)
	{
		$owner = NULL;
		$chan = NULL;
		if (!($owner = config_get_item("botfather::owner")))
		{
			$err['err'][] = "You did not specify a bot owner.";
			self::$conf_err++;
		}

		if (!($chan = config_get_item("botfather::channel")))
		{
			$err['err'][] = "You did not specify a bot channel";
			self::$conf_err++;
		}

		if (strcmp($chan[0],"#"))
		{
			$err['err'][] = "botfather::channel must start with a #";
			self::$conf_err++;
		}
	}
}