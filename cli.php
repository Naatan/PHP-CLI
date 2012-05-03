<?php

class CLI
{
	
	protected $flags 		= array();
	protected $arguments 	= array();
	
	protected $_help 		= 'Invalid input';
	protected $_nameSpace 	= null;
	protected $_callStructure = array();
	protected $_requireArgs = false;
	
	protected static $_instance = null;
	
	const LIGHT_RED		= "[1;31m";
	const LIGHT_GREEN	= "[1;32m";
	const YELLOW		= "[1;33m";
	const LIGHT_BLUE	= "[1;34m";
	const MAGENTA		= "[1;35m";
	const LIGHT_CYAN	= "[1;36m";
	const WHITE			= "[1;37m";
	const NORMAL		= "[0m";
	const BLACK			= "[0;30m";
	const RED			= "[0;31m";
	const GREEN			= "[0;32m";
	const BROWN			= "[0;33m";
	const BLUE			= "[0;34m";
	const CYAN			= "[0;36m";
	const BOLD			= "[1m";
	const UNDERSCORE	= "[4m";
	const REVERSE		= "[7m";
	
	public function __construct($namespace = null, $arguments = null, $flags = null, $callStructure = null)
	{
		self::$_instance = $this;
		
		if (empty($namespace) AND empty($this->_nameSpace))
		{
			$namespace = get_class($this);
		}
		
		$this->_nameSpace = $namespace;
		
		if (is_array($arguments) AND is_array($flags))
		{
			$this->arguments 	= $arguments;
			$this->flags 		= $flags;
		}
		else
		{
			$this->parseArguments();
		}
		
		if ( ! empty($callStructure))
		{
			$this->_callStructure = $callStructure;
		}
		
		$this->initialize();
		
		$this->_run();
	}
	
	public static function getInstance()
	{
		return self::$_instance;
	}
	
	public function initialize()
	{}
	
	public function run()
	{
		$this->showHelp();
	}
	
	public function hasFlag($flag)
	{
		return isset($this->flags[$flag]);
	}
	
	public function getFlag($flag)
	{
		return $this->hasFlag($flag) ? $this->flags[$flag] : false;
	}
	
	public function getFlags()
	{
		return array_keys($this->flags);
	}
	
	public function hasArgument($argument)
	{
		return in_array($argument, $this->arguments);
	}
	
	public function getArguments()
	{
		return $this->arguments;
	}
	
	public function getArgumentAt($index)
	{
		return isset($this->arguments[$index]) ? $this->arguments[$index] : false;
	}
	
	static public function getInput($prompt = "")
	{
		if ( ! empty($prompt))
		{
			echo trim($prompt) . ' ';
		}
		
		$handle = fopen ("php://stdin","r");
		$line 	= fgets($handle);
		$result = trim($line);
		
		return $result;
	}
	
	static public function colorText($text, $color = self::NORMAL)
	{
		if (empty($color))
		{
			$color = self::NORMAL;
		}
		
		return chr(27) . $color . $text . chr(27) . self::NORMAL;
	}
	
	protected function _run()
	{
		if ( ! $command = $this->getArgumentAt(0))
		{
			return $this->run();
		}
		
		$class 	= $this->_nameSpace . '_' . ucfirst(strtolower($command));
		$method = 'run' . ucfirst(strtolower($command));
		
		if (class_exists($class))
		{
			$arguments = $this->getArguments();
			array_shift($arguments);
			
			$callStructure 		= $this->_callStructure;
			$callStructure[] 	= $this;
			
			new $class($class, $arguments, $this->flags, $callStructure);
		}
		else if (method_exists($this, $method))
		{
			call_user_func(array($this, $method));
		}
		else
		{
			$this->run();
		}
	}
	
	protected function parseArguments()
	{
		global $argc, $argv;
		
		if ($argc == 1)
		{
			if ($this->_requireArgs)
			{
				return $this->showHelp();
			}
			else
			{
				return false;
			}
		}
		
		array_shift($argv);
		
		for ($c=0;$c<count($argv); $c++)
		{
			if ( $this->parseFlag($argv, $c) === false)
			{
				$this->arguments[] = $argv[$c];
			}
		}
	}
	
	protected function parseFlag($argv, &$c)
	{
		if ( ! preg_match('/^--?([a-z]*?)(?:$|=)(.*)/i', $argv[$c], $matches))
		{
			return false;
		}
		
		list($full, $flag, $arg) = $matches;
		$flag = strtolower($flag);
		
		if (empty($arg))
		{
			if ( isset($argv[$c+1]) AND substr($argv[$c+1],0,1) != '-')
			{
				$arg = $argv[$c+1];
				$c++;
			}
			else
			{
				$arg = null;
			}
		}
		
		if ( ! empty($arg))
		{
			$arg = preg_replace('/^(?:"|\')(.*?)(?:"|\')$/', '$1', $arg);
		}
		
		return $this->flags[$flag] = $arg;
	}
	
	protected static function showHelp($die = false)
	{
		echo trim(self::getInstance()->_help);
		if ($die)
		{
			die();
		}
	}
	
	public static function bail($error)
	{
		echo self::colorText('ERROR: ', self::RED) . $error;
		die();
	}
	
}