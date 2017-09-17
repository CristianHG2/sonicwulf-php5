<?php

class Kernel
{
	static $execTime;
	static $dynClasses = array();

	static $loadedClasses = array();

	static $lastDb = '';
	static $lastDbClass = '';

	public function __construct()
	{
		self::$execTime = microtime(true);
	}

	static function errorHandler($errno, $errstr, $errfile, $errline)
	{
		$debug = debug_backtrace();
		$labl = null;

		$user = false;

		switch ( $errno )
		{
			case E_USER_ERROR:
				$labl = '['.time().'] FATAL ERROR';
				$user = true;
			break;
			case E_USER_WARNING:
				$labl = '['.time().'] WARNING';
				$user = true;
			break;
			case E_USER_NOTICE:
				$labl = '['.time().'] NOTICE';
				$user = true;
			break;
			default:
				$labl = '['.time().'] ERROR';
			break;
		}

		if ( DEBUG )
		{
			echo "<pre>";
				debug_print_backtrace();
			echo "</pre>";

			print(Text::Bind(GlobalConfig('sonicwulf')->get('styles/error'), array(
				"no"	=>	$errno,
				"str"	=>	$errstr,
				"fil"	=>	$errfile,
				"lin"	=>	$errline,
				"labl"	=>	$labl
			)));
		}

		return false;
	}

	static function exceptionHandler($exception)
	{
		$abs = null;

		if ( $exception->getCode() == 620 )
			$abs = 'Abs';

		$debug = debug_backtrace();

		if ( DEBUG )
		{
			echo "<pre>";
				debug_print_backtrace();
			echo "</pre>";

			print(Text::Bind(GlobalConfig('sonicwulf')->get('styles/error'.$abs), array(
				"no"	=>	$exception->getCode(),
				"str"	=>	$exception->getMessage(),
				"fil"	=>	$exception->getFile(),
				"lin"	=>	$exception->getLine(),
				"labl"	=>	'['.time().'] Uncaught Exception!'
			)));
		}

		exit;
	}

	static function tableExists($t)
	{
		$bridge = GlobalConfig('sql')->getBridge();

	    try {
	        $result = $bridge->query('SELECT 1 FROM '.$t.' LIMIT 1');
	    }
	    catch (Exception $e)
	    {
	        return FALSE;
	    }

	    return TRUE !== FALSE;
	}

	static function autoLoad($class)
	{
		if ( isset(self::$loadedClasses[$class]) )
		{
			self::$lastDb = strtolower($class);
			self::$lastDbClass = $class;

			self::$loadedClasses[$class] = $file;		
				
			return include(self::$loadedClasses[$class]);
		}

		$class = str_replace('\\', '/', $class);

		$file = MOD_DIR.$class.'.php';

		if ( file_exists($file) )
			include($file);
		else
		{
			$file = MOD_DIR.'dynamic/'.$class.'.php';

			if ( file_exists($file) )
			{
				self::$loadedClasses[$class] = $file;
				return include($file);
			}
			foreach ( GlobalConfig('sonicwulf')->get('incDirs') as $i )
			{
				$file = MOD_DIR.$i.$class.'.php';

				if ( file_exists($file) )
				{
					self::$lastDb = strtolower($class);
					self::$lastDbClass = $class;

					self::$loadedClasses[$class] = $file;
					return include($file);
				}
			}
		
			if ( self::tableExists($class) === true )
			{
				$i = self::initDynamic($class, 'SonicModel', true);

				if ( $i === true )
				{
					self::$lastDb = strtolower($class);
					self::$lastDbClass = $class;
					
					self::$loadedClasses[$class] = MOD_DIR.'dynamic/models/'.$class.'.php';
				}
				else
					throw new SonicException('Could not create dynamic class', 216);

				return include(MOD_DIR.'dynamic/models/'.$class.'.php');
			}
				

			throw new SonicException('Class file '.$class.'.php could not be found in '.MOD_DIR, 205);
		}
	}

	static function initDynamic($class, $extends = '', $isdb = true)
	{
		if ( !file_exists(MOD_DIR.'dynamic/'.$class.'.php') )
		{
			$string = '<?php '.PHP_EOL.PHP_EOL.'class '.$class.' extends '.$extends.' {} '.PHP_EOL.PHP_EOL;

			debug_print_backtrace();

			if ( file_put_contents(MOD_DIR.'dynamic/models/'.$class.'.php', $string) )
			{
				self::$dynClasses[] = $class;
				return true;
			}
			else
				throw new SonicException('Could not create dynamic class', 216);
		}
		else
		{
			self::$dynClasses[] = $class;
			return true;
		}
	}
	
	public function __get($property)
	{
		if ( $property == 'execTime' )
		{
			self::$execTime = time() - self::$execTime;
			return self::$execTime;
		}
	}

	static function shutdown()
	{
		global $page;

		self::$execTime = microtime(true) - self::$execTime;

		foreach ( glob(MOD_DIR.'dynamic/*') as $i )
		{
			if ( !is_dir($i) )
			{
				$exp = explode('/', $i);
				$exp = $exp[count($exp) - 1];

				$exp = explode('.', $exp);
				$exp = $exp[count($exp) - 2];

				if ( !file_exists(CONFIG_DIR.$exp.'.json') )
				{
					if ( count(file(MOD_DIR.'dynamic/'.$exp.'.php')) !== 4 )
						throw new SonicException('Could not delete dynamic class '.$exp.' as its line numbers have changed, please verify that the source configuration file exists', 530);
					else
						unlink(MOD_DIR.'dynamic/'.$exp.'.php');
				}
			}
		}

		if ( isset($page['id']) )
		{
			if ( !file_exists(VIEWS_DIR.$page['id'].'.php') )
				throw new SonicException('View "'.$page['id'].'" does not exist', 121);

			if ( ($list = GlobalConfig('gates')->getGates($page['id'])) !== false )
			{
				foreach ( $list as $i )
				{
					$r = GlobalConfig('gates')->runGate($i);

					if ( is_array($r) || count($r) === 2 )
					{
						switch ( $r[0] )
						{
							case 'redirect':
								header('Location: '.$r[1]);
							exit;
						}
					}

					$page['kernelResponse'][] = GlobalConfig('gates')->set('response', $r);
				}				
			}

			$lex = new Lexer;
			$lex->addToVals(GlobalConfig('views')->get('sonicvars'));
			$lex->addToVals(array("page_name" => $page['name']));

			$views = GlobalConfig('views')->getHeaders($page['id']);
			$views = array($views[0], $views[1], $page['id'].'.php', $views[2]);

			$lex = $lex->Compile($views);

			printf('%s', $lex);
		}
	}
}