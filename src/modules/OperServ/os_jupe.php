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
\\	Title: Jupe
//	
\\	Desc: Shuts down services properly
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class os_jupe {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_jupe";
	public $description = "Jupiter a server on the network";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

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
		$cmd = "JUPE";
		$help_string = "Jupiter a server on the network.";
		$syntax = "$cmd";
		$extended_help = 	"Tells Services to jupiter a server -- that is, to create\n".
							"a fake \"server\" connected to Services which prevents\n".
							"the real server of that name from connecting.  The jupe\n".
							"may be removed using a standard \002SQUIT\002. If a reason is\n".
							"given, it is placed in the server information field;\n".
							"otherwise, the server information field will contain the\n".
							"text \"Juped by <nick>\", showing the nickname of the\n".
							"person who jupitered the server.";

		if (!AddServCmd(
			'os_jupe', /* Module name */
			'OperServ', /* Client name */
			$cmd, /* Command */
			'os_jupe::cmd', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		return true;
	}
	
	function cmd($u)
	{
		$parv = explode(" ",$u['msg']);
		$os = $u['target'];
		$nick = $u['nick'];

		if (!ValidatePermissionsForPath("can_jupe", $nick))
		{
			$os->notice($nick->uid,"Permission denied!");
			return;
		}

		$parv[0] = NULL;
		$name = $parv[1];
		$parv[1] = NULL;
		$reason = (isset($parv[2])) ? glue($parv) : "Juped by $nick->nick";
		$sid = "9".rand(11,99);
		SVSLog("Server with name '$name' was juped by $nick->nick: $reason");
		new JupeServer($sid, $name, $reason);

	}
}

/* Jupe Server
 */
class JupeServer {

	static $list = [];

	static function find($term)
	{
		foreach(self::$list as $u)
			if ($u->sid == $term || $u->name = $term)
				return $u;
	}

	function __construct($sid = "2B2", $name, $description)
	{
		$this->sid = $sid;
		$this->name = $name;
		$this->description = $description;
		$this->connect();
		self::$list[] = $this;
	}
	function connect()
	{
		global $cf;
		S2S(":".$cf['sid']." SQUIT $this->name :$this->description");
		S2S(":".$cf['sid']." SID $this->name 2 $this->sid :$this->description");
		S2S(":$this->sid SINFO 99999999999 6000 dhiopqrstwxzBDGHIRSTWZ beI,fkL,lH,cdgimnprstzCDGKMNOPQRSTVWZ * DALEK");
		S2S(":$this->sid EOS");

		hook::run("SID", array(
			"server" => $this->name,
			"hops" => 1,
			"sid" => $this->sid,
			"desc" => $this->description,
			"intro_by" => $cf['sid'])
		);
	}
	function die()
	{
		for ($i = 0; isset(self::$list[$i]); $i++)
		{
			if (self::$list[$i] == $this)
				array_splice(self::$list[$i],$i);
		}
	}
	function __destruct()
	{
		S2S("SQUIT $this->name :$this->description");
		del_sid($this->sid);
	}
}
/*
[RECV] :736 SID irc.afnet.us 3 114 :AirFire Networks
[RECV] :114 SINFO 1659060860 6000 dhiopqrstwxzBDGHIRSTWZ beI,fkL,lH,cdgimnprstzCDGKMNOPQRSTVWZ * UnrealIRCd-6.0.4-git
[RECV] :114 MD client 114 link-security :2
[RECV] :114 SID irc.afwi.net 4 25B :AirFire Networks
[RECV] :25B EOS