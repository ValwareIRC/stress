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
\\	Title:		Config parser
//				
\\	Desc:		Allows doing config things lol
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/
require_once("hook.php");
require_once("misc.php");
define("DALEK_VERSION", "Dalek-Services-0.1.1-git");
define("CONF_SYMBOL", "[CONFIG] ");
$error = [];
new Conf(DALEK_CONF_DIR . "/dalek.conf", $error);
if (!empty($error))
{
	echo "Configuration test failed. Dalek encountered the following error(s):\n";
	foreach($error as $err)
		echo $err."\n";
}
class Conf
{
	static $settings_short = [];
	static $settings_temp = [];
	static $settings = [];
	function __construct($filename, &$error)
	{
		if (!is_file($filename))
		{
			$error[] = "Not a valid configuration file: $filename";
		}
		else
		{
			$file = file_get_contents($filename);
			$file = preg_replace('/\/\*(.|\s)*?\*\//', '', $file);
			$file = preg_replace('/\/\/(.|\s)*?\n/', '', $file);
			$file = str_replace("\t"," ",$file);
			$file = str_replace("\n\n","\n",$file);
			$file = str_replace("\n", " ",$file);
			for(; strstr($file,"  ");)
				$file = str_replace("  "," ",$file);
			$config = $this->parse_config($file, $error);
		}
	}

	private function parse_config($string, &$error)
	{
		$tok = split($string);
		$n = 0;
		$blockstring = "";
		$full = "";
		foreach($tok as $str)
		{
			$str = trim($str);
			if (!strcmp($str,"{") && mb_substr($blockstring,-2,2) !== "::")
				strcat($blockstring,"::");
			
			elseif (!strcmp($str,"}"))
			{
				$p = $blockstring;
				$split = split($blockstring,"::");
				if (BadPtr($split[sizeof($split) - 1]))
					unset($split[sizeof($split) - 1]);
				unset($split[sizeof($split) - 1]);
				$blockstring = glue($split,"::");
				if (!BadPtr($blockstring))
				{
					strcat($blockstring,"::");
				}
			}
			// if we found a value and it's time to go to the next one
			elseif (!BadPtr($str) && $str[strlen($str) - 1] == ";")
			{
				if (substr_count($str,"\"") != 1)
					strcat($blockstring, "::".rtrim($str,";")); // finish off our item
				else strcat($blockstring, " ".rtrim($str,";"));
				strcat($full,str_replace(["::::", "\""],["::", ""],$blockstring)."\n"); // add the full line to our $full variable
				
				/* rejig the blockstring */
				$split = split($blockstring,"::");
				if (BadPtr($split[sizeof($split) - 1]))
					unset($split[sizeof($split) - 1]);
				unset($split[sizeof($split) - 1]);
				unset($split[sizeof($split) - 1]);
				$blockstring = glue($split,"::");
				if (!BadPtr($blockstring))
				{
					rtrim($blockstring,":");
					strcat($blockstring,"::");
				}
			}

			else
			{	if (!BadPtr($blockstring) && mb_substr($blockstring,-2,2) !== "::")
					strcat($blockstring," ");
				strcat($blockstring,$str);
			}
		}
		$temp_settings_file = fopen(DALEK_CONF_DIR."/.settings.temp","w");
		fwrite($temp_settings_file, $full);
		fclose($temp_settings_file);

		$full = split($full,"\n");
		$long = [];

		foreach($full as $config_item)
		{
			$arr = &$long;
			self::$settings_short[] = $config_item;
			$tok = split($config_item,"::");
			for ($i = 0; $i <= count($tok); $i++)
			{
				if (isset($tok[$i + 2]))
					$arr = &$arr[$tok[$i]];					
				
				elseif (isset($tok[$i + 1]) && isset($tok[$i - 1]))
					$arr[$tok[$i]] = $tok[$i + 1];

				elseif (isset($tok[$i + 1]))
					$arr[$tok[$i]][] = $tok[$i + 1];
			}
		}
		self::$settings_temp = $long;
		$cf = &self::$settings_temp;
		if (!isset($cf['info']))
			$error[] = "No info block was found.";

		if (!isset($cf['info']['SID']))
			$error[] = "'info::SID' not found.";

		if (!isset($cf['info']['network-name']))
			$error[] = "'info::network-name' not found.";

		if (!isset($cf['info']['services-name']))
			$error[] = "'info::services-name' not found.";

		if (!isset($cf['info']['admin-email']))
			$error[] = "'info::admin-email' not found.";

		if (!isset($cf['link']))
			$error[] = "No link block was found.";

		if (!isset($cf['link']['hostname']))
			$error[] = "'link::hostname' not found.";

		if (!isset($cf['link']['port']))
			$error[] = "'link::port' not found.";

		if (!isset($cf['link']['password']))
			$error[] = "'link::password' not found.";
			
		if (!isset($cf['log']))
			$error[] = "No log block was found.";

		if (!isset($cf['log']['debug']))
			$error[] = "'log::debug' not found.";

		if (!isset($cf['sql']))
			$error[] = "No sql block was found.";

		if (!isset($cf['sql']['hostname']) && !isset($cf['sql']['sockfile']))
			$error[] = "'sql::hostname' and 'sql::sockfile' not found. You must choose one.";
		
		if (isset($cf['sql']['hostname']) && isset($cf['sql']['sockfile']))
			$error[] = "'sql::hostname' and 'sql::sockfile' are both defined. You must choose one.";

		if (!isset($cf['sql']['port']) && isset($cf['sql']['hostname']))
			$error[] = "'sql::hostname' found but not 'sql::port'.";

		if (!isset($cf['wordpress']))
			$error[] = "No wordpress block was found.";
			
		if (!isset($cf['wordpress']['prefix']))
			$error[] = "'wordpress::prefix' not found";

		if (!empty($error))
		{
			self::$settings_temp = [];
			return false;
		}
		$arr = ['cfg' => $cf, 'err' => &$error];
		hook::run(HOOKTYPE_CONFIGTEST, $arr);
		if (!empty($error))
		{
			self::$settings_temp = [];
			return false;
		}
		self::$settings = self::$settings_temp;
		self::$settings_temp = [];
		
	}
}

/**
 * 2nd November 2022
 * Added to easily get/find a config item.
 * @param String item String of config block directory, example:
 * config_get_item("info::network-name")
 * will return your network name.
 * @return mixed Will return config item if not, or return DebugLog("") (which ultimately returns false)
 */
function config_get_item($item = NULL)
{
	$file_name = DALEK_CONF_DIR."/.settings.temp";
	if (BadPtr($item))
		return DebugLog("Did not specify an item");
	if (!file_exists($file_name))
		return DebugLog("Could not find settings.temp file. Try rehashing.");

	$file = split(file_get_contents($file_name),"\n");

	foreach($file as $line)
	{
		if (strstr($line,$item))
		{
			$linecount = substr_count($line,"::");
			$itemcount = substr_count($item,"::");
			if ($linecount !== $itemcount + 1)
				continue;
			
			$tok = split($line,"::");
			$value = $tok[count($tok) - 1];
			return $value;
		}
	}
	return DebugLog("Could not find config item '$item'");
}