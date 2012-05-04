<?php

class CLI
{
	
	protected $args = array(
		'flags'			=> array(),
		'arguments' 	=> array(),
		'options' 		=> array()
	);
	
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
	
	public function __construct($namespace = null, $arguments = null, $flags = null, $options = null, $callStructure = null)
	{
		self::$_instance = $this;
		
		if (empty($namespace) AND empty($this->_nameSpace))
		{
			$namespace = get_class($this);
		}
		
		$this->_nameSpace = $namespace;
		
		if (is_array($arguments) AND is_array($flags) AND is_array($options))
		{
			$this->args['arguments'] 	= $arguments;
			$this->args['flags'] 		= $flags;
			$this->args['options'] 		= $options;
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
		return in_array($flag, $this->args['flags']);
	}
	
	public function getFlags()
	{
		return $this->args['flags'];
	}
	
	public function hasOption($option)
	{
		return isset($this->args['options'][$option]);
	}
	
	public function getOption($option)
	{
		return $this->hasOption($option) ? $this->args['options'][$option] : false;
	}
	
	public function getOptions()
	{
		return $this->args['options'];
	}
	
	public function hasArgument($argument)
	{
		return in_array($argument, $this->arguments);
	}
	
	public function getArguments()
	{
		return $this->args['arguments'];
	}
	
	public function getArgumentAt($index)
	{
		return isset($this->args['arguments'][$index]) ? $this->args['arguments'][$index] : false;
	}
	
	public static function printInfo($message, $newLine = true)
	{
		print_r($message);
		
		if ($newLine)
		{
			echo "\n";
		}
	}
	
	public static function printDebug($message, $newLine = true)
	{
		print_r($message);
		
		if ($newLine)
		{
			echo "\n";
		}
	}
	
	public static function getInput($prompt = "")
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
	
	public static function colorText($text, $color = self::NORMAL)
	{
		if (empty($color))
		{
			$color = self::NORMAL;
		}
		
		return chr(27) . $color . $text . chr(27) . self::NORMAL;
	}
	
	protected function _run()
	{
		if ($command = $this->getArgumentAt(0))
		{
			$class 	= $this->_nameSpace . '_' . ucfirst(strtolower($command));
			$method = 'run' . ucfirst(strtolower($command));
		}
		
		if ($command AND class_exists($class))
		{
			$arguments = $this->getArguments();
			array_shift($arguments);
			
			$callStructure 		= $this->_callStructure;
			$callStructure[] 	= $this;
			
			new $class($class, $arguments, $this->getFlags(), $this->getOptions(), $callStructure);
		}
		else if ($command AND method_exists($this, $method))
		{
			call_user_func(array($this, $method));
		}
		else
		{
			foreach ($this->args AS $type => $values)
			{
				foreach ($values AS $k => $v)
				{
					$v = is_numeric($k) ? $v : $k;
					$method = substr($type,0,-1) . ucfirst(strtolower($v));
					
					if (method_exists($this, $method))
					{
						call_user_func(array($this, $method));
					}
				}
			}
			
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
			if ( ! $this->parseFlag($argv[$c]) === false)
			{
				continue;
			}
			
			if ( ! $this->parseOption($argv[$c]) === false)
			{
				continue;
			}
			
			$this->args['arguments'][] = $argv[$c];
		}
	}
	
	protected function parseFlag($argument)
	{
		if ( ! preg_match('/^--?([a-z-]*?)$/i', $argument, $matches))
		{
			return false;
		}
		
		list($full, $flag) = $matches;
		$flag = strtolower($flag);
		
		return $this->args['flags'][] = $flag;
	}
	
	protected function parseOption($argument)
	{
		if ( ! preg_match('/^--?([a-z-]*?)=(.+)$/i', $argument, $matches))
		{
			return false;
		}
		
		list($full, $flag, $arg) = $matches;
		$flag = strtolower($flag);
		
		if ( ! empty($arg))
		{
			$arg = preg_replace('/^(?:"|\'|)(.*?)(?:"|\'|)$/', '$1', $arg);
		}
		
		return $this->args['options'][$flag] = $arg;
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
		echo "\n" . self::colorText('ERROR: ', self::RED) . $error . "\n";
		die();
	}
	
}