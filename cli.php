<?php

/**
 * Command Line Interface class
 */
abstract class CLI
{
	
	/**
	 * @var array	Argument types
	 */
	protected $args = array(
		'flags'			=> array(), 	// eg. -f, --flag
		'options' 		=> array(),		// eg. -f=foo, --foo=bar, --foo="bar"
		'arguments' 	=> array()		// everything else
	);
	
	/**
	 * @var string	The default help to show for the currently executed command
	 */
	protected $_help 		= 'Invalid input';
	
	/**
	 * @var string	The namespace that we are executing commands in
	 */
	protected $_nameSpace 	= null;
	
	/**
	 * @var array	Hierarchy of class instances that lead up to this one
	 */
	protected $_callStructure = array();
	
	/**
	 * @var bool	If set to true defaults to showing help if the command is executed without any arguments
	 */
	protected $_requireArgs = false;
	
	/**
	 * @var object|null	Instance of current class, for use in static methods
	 */
	protected static $_instance = null;
	
	/**
	 * Color Constants, for use with self::colorText()
	 */
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
	
	/** Main class constructors *******************************************************************/
	
	/**
	 * Class Constructor
	 * 
	 * @param	null|string			$namespace		
	 * @param	null|array			$arguments		
	 * @param	null|array			$flags			
	 * @param	null|array			$options		
	 * @param	null|array			$callStructure	
	 * 
	 * @return	void
	 */
	public function __construct($namespace = null, $arguments = null, $flags = null, $options = null, $callStructure = null)
	{
		self::$_instance = $this;
		
		// If no namespace is given use the current class name as the namespace
		if (empty($namespace) AND empty($this->_nameSpace))
		{
			$namespace = get_class($this);
		}
		
		// Save namespace
		$this->_nameSpace = $namespace;
		
		// Inherit arguments
		if (is_array($arguments) AND is_array($flags) AND is_array($options))
		{
			$this->args['arguments'] 	= $arguments;
			$this->args['flags'] 		= $flags;
			$this->args['options'] 		= $options;
		}
		else
		{
			// Parse arguments from user input
			$this->parseArguments();
		}
		
		// Inherit call structure
		if ( ! empty($callStructure))
		{
			$this->_callStructure = $callStructure;
		}
		
		// Allow child class to run it's initialization before executing the command
		$this->initialize();
		
		// Run the command
		$this->_run();
	}
	
	/**
	 * Get class instance
	 * 
	 * @return	Object							
	 */
	public static function getInstance()
	{
		return self::$_instance;
	}
	
	/** Abstract methods meant to be overridden ***************************************************/
	
	/**
	 * Initializer for child class, child class should override this
	 * 
	 * @return	void							
	 */
	public function initialize()
	{}
	
	/**
	 * Run the command, child class should override this
	 * 
	 * @return	void							
	 */
	public function run()
	{
		$this->showHelp();
	}
	
	/** Argument Helpers **************************************************************************/
	
	/**
	 * Check if given flag was used
	 * 
	 * @param	string			$flag
	 * 
	 * @return	bool							
	 */
	public function hasFlag($flag)
	{
		return in_array($flag, $this->args['flags']);
	}
	
	/**
	 * Retrieve all flags used
	 * 
	 * @return	array
	 */
	public function getFlags()
	{
		return $this->args['flags'];
	}
	
	/**
	 * Check if given option was used
	 * 
	 * @param	string			$option
	 * 
	 * @return	bool							
	 */
	public function hasOption($option)
	{
		return isset($this->args['options'][$option]);
	}
	
	/**
	 * Get value of specific option (if used)
	 * 
	 * @param	string			$option
	 * 
	 * @return	string|bool
	 */
	public function getOption($option)
	{
		return $this->hasOption($option) ? $this->args['options'][$option] : false;
	}
	
	/**
	 * Retrieve all options used
	 * 
	 * @return	array
	 */
	public function getOptions()
	{
		return $this->args['options'];
	}
	
	/**
	 * Check if given argument was used
	 * 
	 * @param	string			$argument
	 * 
	 * @return	bool							
	 */
	public function hasArgument($argument)
	{
		return in_array($argument, $this->arguments);
	}
	
	/**
	 * Retrieve all arguments used
	 * 
	 * @return	array							
	 */
	public function getArguments()
	{
		return $this->args['arguments'];
	}
	
	/**
	 * Get argument at specific index
	 * 
	 * @param	int			$index
	 * 
	 * @return	string|bool							
	 */
	public function getArgumentAt($index)
	{
		return isset($this->args['arguments'][$index]) ? $this->args['arguments'][$index] : false;
	}
	
	/** Output Helpers ****************************************************************************/
	
	/**
	 * Print info message
	 * 
	 * @param	string			$message		
	 * @param	bool			$newLine
	 * 
	 * @return	void							
	 */
	public function printInfo($message, $newLine = true)
	{
		print_r($message);
		
		if ($newLine)
		{
			echo "\n";
		}
	}
	
	/**
	 * Print debug message
	 * 
	 * @param	string			$message		
	 * @param	bool			$newLine
	 * 
	 * @return	void							
	 */
	public function printDebug($message, $newLine = true)
	{
		print_r($message);
		
		if ($newLine)
		{
			echo "\n";
		}
	}
	
	/**
	 * Get input from user
	 * 
	 * @param	string			$prompt
	 * 
	 * @return	string							
	 */
	public function getInput($prompt = "")
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
	
	/**
	 * Get colored text
	 * 
	 * @param	string			$text			
	 * @param	string			$color
	 * 
	 * @return	string							
	 */
	public function colorText($text, $color = self::NORMAL)
	{
		if (empty($color))
		{
			$color = self::NORMAL;
		}
		
		return chr(27) . $color . $text . chr(27) . self::NORMAL;
	}
	
	/**
	 * Output help text
	 * 
	 * @param	bool			$die
	 * 
	 * @return	void							
	 */
	public function showHelp($die = false)
	{
		echo trim(self::getInstance()->_help);
		if ($die)
		{
			die();
		}
	}
	
	/**
	 * Output error message and kill script
	 * 
	 * @param	string			$error
	 * 
	 * @return	void							
	 */
	public function bail($error)
	{
		echo "\n" . self::colorText('ERROR: ', self::RED) . $error . "\n";
		die();
	}
	
	/**********************************************************************************************/
	/** PROTECTED METHODS *************************************************************************/
	/**********************************************************************************************/
	
	/** Execution *********************************************************************************/
	
	/**
	 * Run the command, this method will check whether to recurse further into the namespace classes
	 * or run the command on the current class
	 * 
	 * @return	void							
	 */
	protected function _run()
	{
		// Retrieve command that is to be executed
		if ($command = $this->getArgumentAt(0))
		{
			$class 	= $this->_nameSpace . '_' . ucfirst(strtolower($command));
			$method = 'run' . ucfirst(strtolower($command));
		}
		
		// Check if the command maps to a class and if so, use that class to execute the command
		if ($command AND class_exists($class))
		{
			$arguments = $this->getArguments();
			array_shift($arguments);
			
			$callStructure 		= $this->_callStructure;
			$callStructure[] 	= $this;
			
			new $class($class, $arguments, $this->getFlags(), $this->getOptions(), $callStructure);
		}
		
		// Check if the command has it's own dedicated method and execute it
		else if ($command AND method_exists($this, $method))
		{
			$this->runArgumentMethods();
			call_user_func(array($this, $method));
		}
		
		// If all else fails just execute it on the local run() method
		else
		{
			$this->runArgumentMethods();
			$this->run();
		}
	}
	
	/**
	 * Run methods related to the arguments that were passed
	 *
	 * eg. passing --foo=bar will execute optionFoo, if that method exists
	 * 
	 * @return	void							
	 */
	protected function runArgumentMethods()
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
	}
	
	/** Parsing of Request ************************************************************************/
	
	/**
	 * Parse arguments from user input
	 * 
	 * @return	void							
	 */
	protected function parseArguments()
	{
		global $argc, $argv;
		
		// xf itself is an argument, so if there's only one argument no command was given and we
		// shuld show the help
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
		
		// Parse each argument 
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
	
	/**
	 * Try to parse argument as a flag
	 * 
	 * @param	string			$argument
	 * 
	 * @return	string|bool
	 */
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
	
	/**
	 * Try to parse argument as an option
	 * 
	 * @param	string			$argument
	 * 
	 * @return	string|bool
	 */
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

	/** Assert Methods ************************************************************************/

	/**
	 * Checks if their are at least $num arguments, if not show help and die
	 * 
	 * @param  int $num 
	 * @return void      
	 */
	protected function assertNumArguments($num)
	{
		if ( ! $this->getArgumentAt($num - 1))
		{
			$this->showHelp(true);
		}
	}

	/**
	 * Checks if a certain argument exists, if not shows help and dies
	 * 
	 * @param  string $argument 
	 * @return void           
	 */
	protected function assertHasArgument($argument)
	{
		if ( ! $this->hasArgument($argument))
		{
			$this->showHelp(true);
		}
	}

	/**
	 * Checks if a certain flag exists, if not shows help and dies
	 * @param  string $flag 
	 * @return void       
	 */
	protected function assertHasFlag($flag)
	{
		if ( ! $this->hasFlag($flag))
		{
			$this->showHelp(true);
		}
	}
}