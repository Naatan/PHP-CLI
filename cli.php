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
	protected $_help 		= false;
	
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
	 * @var bool	whether to use exceptions instead of die()
	 */
	public static $_useExceptions = false;
	
	/**
	 * @var string	Exception class to use
	 */
	public static $_exceptionClass = 'Exception';
	
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
	 * @param 	null|bool			$initialize
	 * 
	 * @return	void
	 */
	public function __construct($initialize = true)
	{
		if ($initialize instanceof CLI)
		{
			$arguments = $initialize->getArguments();
			array_shift($arguments);
			
			$callStructure 		= $initialize->getCallStructure();
			$callStructure[] 	= $initialize;
			
			$this->setFlags($initialize->getFlags());
			$this->setOptions($initialize->getOptions());
			$this->setArguments($arguments);
			$this->setCallStructure($callStructure);
		}
		else if ($initialize == true)
		{
			// Parse arguments from user input
			$this->parseArguments();
		}
		
		if ($initialize)
		{
			self::$_instance = $this;
			
			// Run the command
			$this->_run();
		}
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
	
	/** Call Structure Methods ********************************************************************/
	
	/**
	 * Call a custom CLI command
	 * 
	 * @param		array|string	$arguments
	 * @param 		bool 			$merge
	 * @param		array|bool		$flags			
	 * @param		array|bool		$options
	 * 
	 * @return		void						
	 */
	public function manualRun($arguments, $merge = true, $flags = true, $options = true)
	{
		$forwardClass = new CLI_Xf(false);
		
		// Parse arguments
		if ( ! is_array($arguments))
		{
			$arguments = explode(' ', $arguments);
		}
		
		// Merge current arguments with given
		if ($merge)
		{
			$arguments = array_merge($arguments, $this->getArguments());
		}
		
		// Set arguments
		$forwardClass->setArguments($arguments);
		
		// Set flags
		if ($flags === true)
		{
			$forwardClass->setFlags($this->getFlags());
		}
		else if (is_array($flags))
		{
			$forwardClass->setFlags($flags);
		}
		
		// Set options
		if ($options === true)
		{
			$forwardClass->setOptions($this->getOptions());
		}
		else if (is_array($options))
		{
			$forwardClass->setOptions($options);
		}
		
		// Set callstructure
		$callStructure 		= $this->_callStructure;
		$callStructure[] 	= $this;
		
		$forwardClass->setCallStructure($callStructure);
		
		// Run
		$forwardClass->_run();
	}
	
	/**
	 * Get parent class in the hierarchy
	 *
	 * eg. when called from within CLI_Command_Subcommand it will return the instance of CLI_Command
	 *
	 * @param 	null|string 		$name
	 * 
	 * @return	Object|bool							
	 */
	public function getParent($name = null)
	{
		$structure 	= $this->_callStructure;
		$index 		= count($structure) - 1;
		
		if (isset($structure[$index]))
		{
			if ($name != null AND get_class($structure[$index]) != $name)
			{
				return $structure[$index]->getParent($name);
			}
			
			return $structure[$index];
		}
		
		$this->bail('Could not locate parent');
	}
	
	/**
	 * Get call structure
	 * 
	 * @return		array				
	 */
	public function getCallStructure()
	{
		return $this->_callStructure;
	}
	
	/**
	 * Set call structure
	 * 
	 * @param		array		$structure
	 * 
	 * @return		array
	 */
	public function setCallStructure($structure)
	{
		return $this->_callStructure = $structure;
	}
	
	/** Abstract methods meant to be overridden ***************************************************/
	
	/**
	 * Initializer for child class, child class should override this
	 * 
	 * @return	void							
	 */
	public function initialize()
	{}
	
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
	 * Set custom flags
	 * 
	 * @param		array		$flags
	 * 
	 * @return		array					
	 */
	public function setFlags(array $flags)
	{
		return $this->args['flags'] = $flags;
	}
	
	/**
	 * Manually define a flag
	 * 
	 * @param	string			$flag
	 * 
	 * @return	void			
	 */
	public function setFlag($flag)
	{
		if ( ! $this->hasFlag($flag))
		{
			$this->args['flags'][] = $flag;
		}
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
	 * Manually define an option
	 * 
	 * @param	string			$option			
	 * @param	string			$value
	 * 
	 * @return	void				
	 */
	public function setOption($option, $value)
	{
		$this->args['options'][$option] = $value;
	}
	
	/**
	 * Set options, overrides current value
	 * 
	 * @param		array		$options
	 * 
	 * @return		array						
	 */
	public function setOptions(array $options)
	{
		return $this->args['options'] = $options;
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
	
	/**
	 * Set arguments, overrides current value
	 * 
	 * @param		array		$arguments
	 * 
	 * @return		array						
	 */
	public function setArguments(array $arguments)
	{
		return $this->args['arguments'] = $arguments;
	}
	
	/** Output Helpers ****************************************************************************/
	
	public function printMessage($message, $newLine = true)
	{
		print_r($message);
		
		if ($newLine)
		{
			echo "\n";
		}
	}
	
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
		$this->printMessage($message, $newLine);
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
		$this->printMessage($message, $newLine);
	}
	
	/**
	 * Print a key / value list
	 * 
	 * @param		array		$list		
	 * @param		string		$prefix
	 * 
	 * @return		void					
	 */
	public function printKeyList($list, $prefix = ' * ')
	{
		$longest = 0;
		
		foreach ($list AS $k => $v)
		{
			if (strlen($k) > $longest)
			{
				$longest = strlen($k);
			}
		}
		
		$longest += 5;
		
		foreach ($list AS $k => $v)
		{
			$n 		= $longest - strlen($k);
			$append	= '';
			
			for ($c=0;$c<$n; $c++)
			{
				$append .= ' ';
			}
			
			echo $prefix . $k . ': ' . $append . $v;
			echo PHP_EOL;
		}
	}
	
	/**
	 * Print array as table
	 * 
	 * @param		array		$array
	 * @param 		string 		$prefix
	 * @param 		bool 		$headers
	 * 
	 * @return		void					
	 */
	public function printTable($array, $prefix = '', $headers = true)
	{
		$longest = array();
		
		// Calculate column length
		foreach ($array AS $entry)
		{
			foreach ($entry AS $k => $v)
			{
				$v = preg_replace('/\[[0-9;]{1,4}m/','', $v);
				if ( ! isset($longest[$k]) OR $longest[$k] < (strlen($v) + 5))
				{
					$longest[$k] = strlen($v) + 5;
				}
				
				if ( $headers AND ( ! isset($longest[$k]) OR $longest[$k] < (strlen($k) + 5)))
				{
					$longest[$k] = strlen($k) + 5;
				}
			}
		}
		
		// Print headers
		if ($headers)
		{
			foreach (current($array) AS $k => $v)
			{
				$append = '';
				$n 		= $longest[$k] - strlen($k);
				for ($c=0;$c<$n; $c++) $append .= ' ';
				
				echo $this->colorText($k, self::BOLD) . $append;
			}
			
			echo PHP_EOL;
			$append = '';
			foreach ($longest AS $length)
			{
				for ($c=0;$c<$length; $c++) $append .= '-';
			}
			
			echo substr($append,0,-5);
		}
		
		// Print entries
		foreach ($array AS $entry)
		{
			echo PHP_EOL;
			
			foreach ($entry AS $k => $v)
			{
				$append = '';
				$n 		= $longest[$k] - strlen($v);
				for ($c=0;$c<$n; $c++) $append .= ' ';
				
				echo $v . $append;
			}
		}
	}
	
	/**
	 * Print empty lines
	 * 
	 * @param		int		$lines
	 * 
	 * @return		void			
	 */
	public function printEmptyLine($lines=1)
	{
		for ($c=0;$c<$lines; $c++)
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
	 * User Confirmation
	 * 
	 * @param	string			$prompt
	 * 
	 * @return	string							
	 */
	public function confirm($prompt = "")
	{
		if ( ! empty($prompt))
		{
			echo trim($prompt) . " ";
		}
		
		$handle = fopen ("php://stdin","r");
		$line 	= fgets($handle);
		$result = trim($line);
		
		$true = array('1', 'yes','y','ok');
		
		if (in_array($result, $true))
		{
			return true;
		}
		
		return false;
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
		if ($this->hasFlag('no-colors'))
		{
			return $text;
		}
		
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
		$help = self::getInstance()->_help;
		
		preg_match('/^\n*(\s*)/', $help, $whitespace);
		$help = preg_replace('/^'.$whitespace[1].'/m', '', $help);
		
		if ( ! $help)
		{
			throw new Exception('Invalid input');
		}
		else
		{
			echo trim($help);
		}
		
		if ($die)
		{
			die();
		}
	}
	
	/**
	 * Output error message and kill script
	 * 
	 * @param	string			$error
	 * @param 	string 			$type
	 * 
	 * @return	void							
	 */
	public function bail($error, $type = 'ERROR')
	{
		if (self::$_useExceptions)
		{
			throw new self::$_exceptionClass($error);
		}
		else
		{
			echo "\n" . $this->colorText($type . ': ', self::RED) . $error . "\n";
			die();
		}
	}
	
	/** Execution *********************************************************************************/
	
	/**
	 * Run the command, this method will check whether to recurse further into the namespace classes
	 * or run the command on the current class
	 * 
	 * @return	void							
	 */
	public function _run()
	{
		// Allow child class to run it's initialization before executing the command
		$this->initialize();
			
		// Retrieve command that is to be executed
		if ($command = $this->getArgumentAt(0))
		{
			$class 	= get_class($this) . '_' . ucfirst(strtolower($command));
			$method = 'run' . ucfirst(strtolower($command));
		}
		
		// Run methods related to flags, options and arguments
		$this->runArgumentMethods();
		
		// Check if the command maps to a class and if so, use that class to execute the command
		if ($command AND class_exists($class))
		{
			new $class($this);
		}
		else 
		{
			
			// Check if the command has it's own dedicated method and execute it
			if ( $command AND method_exists($this, $method))
			{
				$arguments = $this->getArguments();
				array_shift($arguments);
				$this->setArguments($arguments);
			}
			else
			{
				$method = 'run';
			}
			
			if ( ! method_exists($this, $method) OR $this->hasFlag('help'))
			{
				$this->showHelp(true);
			}
			
			call_user_func_array(array($this, $method), $this->getArguments());
			
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
	public function parseArguments()
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

	/** Assert Methods ****************************************************************************/

	/**
	 * Checks if their are at least $num arguments, if not show help and die
	 * 
	 * @param  int $num
	 * 
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
	 * 
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
	 * 
	 * @param  string $flag
	 * 
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