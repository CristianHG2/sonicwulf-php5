<?php

/* ERROR SETTINGS FOR DEVELOPMENT */

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting('~E_ALL');


/* EXCEPTION CLASS AND SHORTHAND FUNCTIONS */

class Kesh
{
	static function fieldIs()
	{
		$func = func_get_args();

		if ( isset($_POST[$func[0]]) )
		{
			$funcArgs = $func;
			unset($funcArgs[0]);

			return in_array($_POST[$func[0]], $funcArgs);
		}
		else
			return false;
	}

	static function sfu($pattern)
	{
		$files = glob($pattern);

		usort($files, function($x, $y) {
		    return filemtime($x) < filemtime($y);
		});

		return $files;
	}

	static function Warn($string)
	{
		trigger_error($string, E_USER_WARNING);
	}
}

class SonicException extends Exception
{
	public function __construct($message, $code = 0, Exception $previous = null, $line = null, $file = null)
	{
		parent::__construct($message, $code, $previous);

		if ( !is_null($line) && is_numeric($line) )
			$this->line = $line;

		if ( !is_null($file) )
			$this->file = $file;
	}

	public function __toString()
	{
		return 'SonicException'.(isset($this->mod) > 0 ? '['.$this->mod.']' : "").' '.$this->code.' : "'.$this->message.'"';
	}
}

/* MAIN CONSTANTS */

define('PATH', str_replace('\\', '/', __DIR__) );
define('CONFIG_DIR', PATH.'/conf.d/');
define('MOD_DIR', PATH.'/inc/');
define('CONFIG_SECURE', false);
define('MAINCONFIG', CONFIG_DIR.'conf.json');
define('SONICWULF_STRICT', true);
define('VIEWS_DIR', PATH.'/views/');
define('LEXER_REGEX', false);
define('LOG_DIR', PATH.'/logs/');
define('DUMP_DIR', LOG_DIR.'dumps/');
define('GATE_DIR', PATH.'/gates/');
define('DEBUG', false);

if ( strpos(php_sapi_name(), 'cli') )
	define('IS_CLI', true);
else
	define('IS_CLI', false);

/* GLOBALIZE */

$GlobalConfig = array();
global $GlobalConfig;

/* CONFIGURATION CHECKS */

if ( !file_exists(MOD_DIR.'dynamic/') && !mkdir(MOD_DIR.'dynamic/') )
	throw new SonicException('The dynamic class directory could not be created', 217);

if ( CONFIG_SECURE )
{
	$perms = intval(substr(decoct(fileperms(MAINCONFIG)), -3));

	if (DIRECTORY_SEPARATOR == '\\')
		throw new SonicException('SonicWulf does not run correctly under Windows', 111);

	if ( !file_exists(CONFIG_DIR) )
			throw new SonicException('The configuration directory '.(IS_CLI ? '' : '<b>').CONFIG_DIR.(IS_CLI ? '' : '</b>').' does not exist. Cannot initiate SonicWulf 4', 101);

	if ( !file_exists(MAINCONFIG) )
		throw new SonicException('The main configuration file conf.json does not seem to exist', 102);
	else
	{
		if ( fileowner(MAINCONFIG) !== exec('whoami') )
			throw new SonicException('The main configuration file conf.json is not owned by '.exec('whoami'), 103);
		else if ( $perms > 0660 )
			throw new SonicException('Invalid permissions on conf.json, please make sure that the access level doesn\'t go beyond 0660', 104);

		if ( $perms > 0600 )
			trigger_error('SonicException: Possibly insecure permissions on '.MAINCONFIG.', please switch your permission level to 0600', E_USER_WARNING);
	}
}

/* KERNEL INTEGRATION */

require MOD_DIR.'Kernel.php';
new Kernel;

/* ERROR HANDLERS AND AUTOLOAD */

spl_autoload_register('Kernel::autoLoad');
register_shutdown_function('Kernel::shutdown');

set_error_handler('Kernel::errorHandler');
set_exception_handler('Kernel::exceptionHandler');

/* RUN CONFIGURATION INCLUDES */

$confFiles = glob(CONFIG_DIR.'*.json');

$GlobalConfig = array();

foreach ( $confFiles as $file )
{
	if ( !is_dir($file) )
	{
		$data = json_decode(file_get_contents($file), true);

		if ( !is_null($data) )
		{
			$name = preg_match_all('/([A-z0-9]*).json/', $file, $matches);
			Kernel::initDynamic($matches[1][0], 'Config');

			$GlobalConfig[$matches[1][0]] = new $matches[1][0];
			$GlobalConfig[$matches[1][0]]->set('file', $file);

			$GlobalConfig[$matches[1][0]]->init();
		}
		else
			throw new SonicException('The configuration file '.$file.' is not a valid JSON file or cannot be accessed', 105);
	}
}

function GlobalConfig($name)
{
	global $GlobalConfig;

	if ( !isset($GlobalConfig[$name]) )
		throw new SonicException('Dynamic configuration object not found for '.$name, 218);

	return $GlobalConfig[$name];
}

/* SESSION START AND DATE SETUP */

date_default_timezone_set('America/New_York');
session_start();
