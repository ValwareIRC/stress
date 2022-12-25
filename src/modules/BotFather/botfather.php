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
\\	Title: BotFather
//	
\\	Desc: BotFather for botfather stuff lmao
//	
\\	
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
include("botfather.conf");
class botfather {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "botfather";
	public $description = "BotFather PseudoClient";
	public $author = "Valware";
	public $version = "1.0";

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{

	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		global $bf;
		$tbf = Client::find($bf['nick']);
		if ($tbf)
			$tbf->quit();
	}


	function __init()
	{

		hook::func(HOOKTYPE_CONNECT, 'botfather::spawn_client');
			
		if (IsConnected())
			if (!botfather::spawn_client())
				return true;

		return true;
	}

	static function spawn_client()
	{
		global $bf;
		SVSLog("Trying to spawn client...");
		$tbf = new Client($bf['nick'],$bf['ident'],$bf['hostmask'],NULL,$bf['gecos'],'botfather');
		if (!$tbf)
			return false;
		$tbf->join("#services");
		$tbf->join(config_get_item("botfather::channel"));
		return true;
	}


	/* hooking system you can copy and paste to yours with no edits needed */
	private static $actions = array();
	public static function run($hook, $args = array())
	{
		if (!empty(self::$actions[$hook]))
			foreach (self::$actions[$hook] as $f)
				$f($args);
	}

	public static function func($hook, $function)
	{
		self::$actions[$hook][] = $function;
	}
	public static function del($hook, $function)
	{
		for ($i = 0; isset(self::$actions[$hook][$i]); $i++)
			if (self::$actions[$hook][$i] == $function)
				array_splice(self::$actions[$hook],$i);
	}
}