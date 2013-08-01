<?php
/**
 * Frosted MySQL Library Class
 * - - - - - - - - - -
 * Supplies different functions for fast and easy mysql communication. Build your
 * queries inside this class or fire direct queries and scalars. Works completely
 * without zend framework but delivers similar functionality. Build with php5 and
 * oop techniques to get best possible usability.
 * - - - - - - - - - -
 * If you include "mysqlClass_Config" class in your scripts, before the
 * communication class, you don't have to set up the class every time you use it.
 * Take a look at the example configuration file to get an overview.
 * - - - - - - - - - -
 * Licensed under MIT license
 * - - - - - - - - - -
 * @Creator  Daniel 'Eisbehr' Kern
 * @Require  PHP5
 * @Version  3.0
 * @Date     01.08.2013
 * @Update   01.08.2013
 * - - - - - - - - - -
 */
class mysqlClass
{
	/**
	 * mysql hostname
	 * @var string
	 */
	private $hostname = "localhost";

	/**
	 * mysql port number
	 * @var string
	 */
	private $port = "3306";

	/**
	 * mysql username
	 * @var string
	 */
	private $username = "root";

	/**
	 * password to access the database
	 * @var string
	 */
	private $password = "";

	/**
	 * actually used database
	 * @var string
	 */
	private $database = "";

	/**
	 * prefix for {PRE} or {PREFIX} replacement
	 * @var string
	 */
	private $prefix = "";

	/**
	 * use a persistent mysql connection
	 * @var boolean
	 */
	private $persistent = true;

	/**
	 * use mysqli instead of default mysql
	 * @var boolean
	 */
	private $mysqli = false;

	/**
	 * selected connection type
	 * @var string
	 */
	private $type = self::CONNECTION_TYPE_WRITE;



	/*
	** internal data fields
	*/



	/**
	 * actually database connection identifier
	 * @var resource|mysqli
	 */
	private $identifier;

	/**
	 * last query result
	 * @var mixed
	 */
	private $result;

	/**
	 * connection types for database handling
	 * @var string array
	 */
	private $types = array();

	/**
	 * replace data inside of mysql queries
	 * @var string array
	 */
	private $replaces;

	/**
	 * verbose on error
	 * @var boolean
	 */
	private $verbose = false;

	/**
	 * verbose on error
	 * @var boolean
	 */
	private $format = false;



	/*
	** query classes 
	*/



	/**
	 * select query
	 * @var mysqlClass_Select
	 */
	private $querySelect = NULL;

	/**
	 * insert query
	 * @var mysqlClass_Insert
	 */
	private $queryInsert = NULL;

	/**
	 * replace query
	 * @var mysqlClass_Replace
	 */
	private $queryReplace = NULL;

	/**
	 * update query
	 * @var mysqlClass_Update
	 */
	private $queryUpdate = NULL;

	/**
	 * delete query
	 * @var mysqlClass_Delete
	 */
	private $queryDelete = NULL;

	/**
	 * truncate query
	 * @var mysqlClass_Truncate
	 */
	private $queryTruncate = NULL;



	/*
	** static & constants
	*/



	/**
	 * singleton instance holder
	 * @var mysqlClass
	 */
	private static $instance = NULL;

	/**
	 * connection types
	 * @var string
	 */
	const CONNECTION_TYPE_READ = "r";
	const CONNECTION_TYPE_WRITE = "w";

	/**
	 * result fetching types
	 * @var string
	 */
	const FETCH_ARRAY = "array";
	const FETCH_ASSOC = "assoc";
	const FETCH_ROW = "row";
	const FETCH_OBJ = "obj";
	const FETCH_OBJECT = "object";
	const FETCH_COLLECTION = "collection";

	/**
	 * join condition relations
	 * @var string
	 */
	const JOIN_OR = "OR";
	const JOIN_AND = "AND";
	
	/**
	 * where condition relations
	 * @var string
	 */
	const WHERE_OR = "OR";
	const WHERE_AND = "AND";

	/**
	 * having condition relations
	 * @var string
	 */
	const HAVING_OR = "OR";
	const HAVING_AND = "AND";

	/**
	 * group directions
	 * @var string
	 */
	const GROUP_ASC = "ASC";
	const GROUP_DESC = "DESC";

	/**
	 * order directions
	 * @var string
	 */
	const ORDER_ASC = "ASC";
	const ORDER_DESC = "DESC";

	/**
	 * exception messages
	 * @var string
	 */
	const MESSAGE_CONNECTION = "connection could not be established";
	const MESSAGE_DATABASE = "could not handle or get into the chosen database '%s'";
	const MESSAGE_QUERY = "could not process the given query";
	const MESSAGE_CREATE = "could not create the mysql query";
	const MESSAGE_PERMISSION = "the mysqlClass instance doesn't have the permission for this query, change the connection type to 'write' first";
	const MESSAGE_UNKNOWN = "function '%s(%s)' not found in class '%s'";



	/*
	** constuct & destruct
	*/



	/**
	 * create mysql class instance
	 * @param boolean $verbose
	 * @param string $type
	 * @return mysqlClass
	 */
	function __construct($verbose = false, $type = self::CONNECTION_TYPE_WRITE)
	{
		// reset class configuration
		$this->resetConfig();
		
		// create connection types
		$this->types["r"] = self::CONNECTION_TYPE_READ;
		$this->types["read"] = self::CONNECTION_TYPE_READ;
		$this->types["w"] = self::CONNECTION_TYPE_WRITE;
		$this->types["write"] = self::CONNECTION_TYPE_WRITE;

		// check if chosen type exists
		if( array_key_exists($type, $this->types) )
			$this->type = $this->types[$type];
		else
			$this->type = self::CONNECTION_TYPE_WRITE;

		// get verbose option
		if( is_bool($verbose) ) $this->verbose = $verbose;

		// try to find config class
		if( class_exists("mysqlClass_Config") )
		{
			$class = new ReflectionClass("mysqlClass_Config");
			
			// get possible configuration from config class
			if( $class->hasConstant("hostname") )   $this->hostname   = mysqlClass_Config::hostname;
			if( $class->hasConstant("port") )       $this->port       = mysqlClass_Config::port;
			if( $class->hasConstant("username") )   $this->username   = mysqlClass_Config::username;
			if( $class->hasConstant("password") )   $this->password   = mysqlClass_Config::password;
			if( $class->hasConstant("database") )   $this->database   = mysqlClass_Config::database;
			if( $class->hasConstant("prefix") )     $this->prefix     = mysqlClass_Config::prefix;
			if( $class->hasConstant("persistent") ) $this->persistent = mysqlClass_Config::persistent;
			if( $class->hasConstant("mysqli") )     $this->mysqli     = mysqlClass_Config::mysqli;
			if( $class->hasConstant("verbose") )    $this->verbose    = mysqlClass_Config::verbose;
			if( $class->hasConstant("format") )     $this->format     = mysqlClass_Config::format;
		}

		// create default data replacement
		$this->updateReplacement();

		// set singleton class instance
		self::$instance = $this;

		return $this;
	}

	/**
	 * destruct mysql class instance
	 * @return boolean
	 */
	function __destruct()
	{
		$this->close();
		
		self::$instance = NULL;
		unset($this->identifier);
		unset($this->result);
		unset($this);

		return true;
	}

	/**
	 * return class singleton or create new instance
	 * @param boolean $forceNew
	 * @return mysqlClass
	 */
	public static function getInstance($forceNew = false)
	{
		if( $forceNew ) return new self();
		if( !self::$instance ) self::$instance = new self();

		return self::$instance;
	}



	/*
	** getter & setter for internal data
	*/



	/**
	 * handle all get calls
	 * @param string $name
	 * @return mixed
	 */
	public function getData($name)
	{
		switch( $name )
		{
			case "hostname":
			case "port":
			case "username":
			case "password":
			case "database":
			case "prefix":
			case "type":
			case "connectiontype":
				return (string)$this->{$name};
			break;

			case "connection":
			case "identifier":
				return $this->identifier;
			break;

			case "verbose":
			case "format":
			case "persistent":
			case "mysqli":
				return (bool)$this->{$name};
			break;
		}

		$this->unknownFunction($this, $name);
		return NULL;
	}

	/**
	 * mysql hostname
	 * @return string
	 */
	public function getHostname()
	{
		return $this->getData("hostname");
	}

	/**
	 * set mysql port number
	 * @return string|integer
	 */
	public function getPort()
	{
		return $this->getData("port");
	}

	/**
	 * database username
	 * @return string
	 */
	public function getUsername()
	{
		return $this->getData("username");
	}

	/**
	 * database password
	 * @return string
	 */
	public function getPassword()
	{
		return $this->getData("password");
	}

	/**
	 * mysql database
	 * @return string
	 */
	public function getDatabase()
	{
		return $this->getData("database");
	}

	/**
	 * database table prefix
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->getData("prefix");
	}

	/**
	 * persistent connection enabled
	 * @return boolean
	 */
	public function getPersistent()
	{
		return $this->getData("persistent");
	}

	/**
	 * mysqli enabled
	 * @return boolean
	 */
	public function getMysqli()
	{
		return $this->getData("mysqli");
	}

	/**
	 * verbose on error enabled
	 * @return boolean
	 */
	public function getVerbose()
	{
		return $this->getData("verbose");
	}

	/**
	 * query string formation enabled
	 * @return boolean
	 */
	public function getFormat()
	{
		return $this->getData("format");
	}

	/**
	 * set mysql connection type
	 * @return boolean
	 */
	public function getType()
	{
		return $this->getData("type");
	}
	
	/**
	 * handle all set calls
	 * @param string $name
	 * @param mixed $value
	 * @return boolean
	 */
	public function setData($name, $value)
	{
		switch( $name )
		{
			case "hostname":
			case "username":
			case "password":
				if( is_string($value) )
				{
					$this->{$name} = $value;
					return true;
				}

				return false;
			break;

			case "database":
			case "prefix":
				if( is_string($value) )
				{
					$this->{$name} = $value;
					$this->updateReplacement();
					return true;
				}

				return false;
			break;

			case "port":
				if( is_string($value) || is_integer($value) )
				{
					$this->{$name} = $value;
					return true;
				}

				return false;
			break;

			case "verbose":
			case "format":
			case "persistent":
			case "mysqli":
				if( is_bool($value) )
				{
					$this->{$name} = $value;
					return true;
				}

				return false;
			break;

			case "type":
			case "connectiontype":
				if( array_key_exists($value, $this->types) )
				{
					$this->type = $this->types[$value];
					return true;
				}

				return false;
			break;
		}

		$this->unknownFunction($this, $name, array($value));
		return false;
	}

	/**
	 * set mysql hostname
	 * @param string $hostname
	 * @return boolean
	 */
	public function setHostname($hostname)
	{
		return $this->setData("hostname", $hostname);
	}

	/**
	 * set mysql port number
	 * @param string|integer $port
	 * @return boolean
	 */
	public function setPort($port)
	{
		return $this->setData("port", $port);
	}

	/**
	 * set database username
	 * @param string $username
	 * @return boolean
	 */
	public function setUsername($username)
	{
		return $this->setData("username", $username);
	}

	/**
	 * set database password
	 * @param string $password
	 * @return boolean
	 */
	public function setPassword($password)
	{
		return $this->setData("password", $password);
	}

	/**
	 * set mysql database
	 * @param string $database
	 * @return boolean
	 */
	public function setDatabase($database)
	{
		return $this->setData("database", $database);
	}

	/**
	 * set database table prefix
	 * @param string $prefix
	 * @return boolean
	 */
	public function setPrefix($prefix)
	{
		return $this->setData("prefix", $prefix);
	}

	/**
	 * enable persistent connection
	 * @param boolean $persistent
	 * @return boolean
	 */
	public function setPersistent($persistent)
	{
		return $this->setData("persistent", $persistent);
	}

	/**
	 * enable mysqli
	 * @param boolean $mysqli
	 * @return boolean
	 */
	public function setMysqli($mysqli)
	{
		return $this->setData("mysqli", $mysqli);
	}

	/**
	 * enable verbose on error
	 * @param boolean $verbose
	 * @return boolean
	 */
	public function setVerbose($verbose)
	{
		return $this->setData("verbose", $verbose);
	}

	/**
	 * enable query string formation
	 * @param boolean $format
	 * @return boolean
	 */
	public function setFormat($format)
	{
		return $this->setData("format", $format);
	}

	/**
	 * set mysql connection type
	 * @param string $type
	 * @return boolean
	 */
	public function setType($type)
	{
		return $this->setData("type", $type);
	}
	
	/**
	 * set mysql connection type
	 * @param string $connectiontype
	 * @return boolean
	 */
	public function setConnectionType($connectiontype)
	{
		return $this->setData("connectiontype", $connectiontype);
	}

	/**
	 * get class configuration as array
	 * @return array
	 */
	public function getConfigArray()
	{
		$config = array();

		$config["hostname"]   = $this->hostname;
		$config["port"]       = $this->port;
		$config["username"]   = $this->username;
		$config["password"]   = $this->password;
		$config["database"]   = $this->database;
		$config["prefix"]     = $this->prefix;
		$config["persistent"] = $this->persistent;
		$config["mysqli"]     = $this->mysqli;
		$config["verbose"]    = $this->verbose;
		$config["format"]     = $this->format;

		return $config;
	}

	/**
	 * set class configuration as array
	 * @param array $config
	 * @return mysqlClass
	 */
	public function setConfigArray($config)
	{
		if( isset($config["hostname"]) ) $this->hostname = $config["hostname"];
		if( isset($config["port"]) ) $this->port = $config["port"];
		if( isset($config["username"]) ) $this->username = $config["username"];
		if( isset($config["password"]) ) $this->password = $config["password"];
		if( isset($config["database"]) ) $this->database = $config["database"];
		if( isset($config["prefix"]) ) $this->prefix = $config["prefix"];
		if( isset($config["persistent"]) ) $this->persistent = $config["persistent"];
		if( isset($config["mysqli"]) ) $this->mysqli = $config["mysqli"];
		if( isset($config["verbose"]) ) $this->verbose = $config["verbose"];
		if( isset($config["format"]) ) $this->format = $config["format"];

		return $this;
	}

	/**
	 * reset whole class configuration
	 * @return void
	 */
	public function resetConfig()
	{
		$this->close();

		$this->hostname   = "localhost";
		$this->port       = "3306";
		$this->username   = "root";
		$this->password   = "";
		$this->database   = "";
		$this->prefix     = "";
		$this->persistent = true;
		$this->mysqli     = false;
		$this->verbose    = false;
		$this->format     = false;

		$this->updateReplacement();
		return;
	}

	/**
	 * internal getter for mysql hostname by current configuration
	 * @return string
	 */
	private function getConnectionHostname()
	{
		if( $this->mysqli )
		{
			$hostname  = ( $this->persistent ? "p:" : NULL) . $this->hostname;
			$hostname .= !empty($this->port) && (string)$this->port != "3306" ? ":" . $this->port : NULL;

			return $hostname;
		}

		$hostname  = $this->hostname;
		$hostname .= !empty($this->port) && (string)$this->port != "3306" ? ":" . $this->port : NULL;

		return $hostname;
	}



	/*
	** replacement functions
	*/



	/**
	 * replace all data inside mysql query
	 * @param string $query
	 * @return string
	 */
	public function replaceQuery($query)
	{
		if( !is_array($this->replaces) ) $this->replaces = array();

		foreach( $this->replaces as $replace => $value )
		{
			$query = str_replace($replace, $value, $query);
		}

		return $query;
	}

	/**
	 * reset replacements to default
	 * @return void
	 */
	private function updateReplacement()
	{
		if( !is_array($this->replaces) ) $this->replaces = array();

		$this->replaces["{DB}"]       = $this->database;
		$this->replaces["{DATABASE}"] = $this->database;
		$this->replaces["{PRE}"]      = $this->prefix;
		$this->replaces["{PREFIX}"]   = $this->prefix;

		return;
	}

	/**
	 * add a new replacement entry
	 * @param string $replace
	 * @param string $value
	 * @return boolean
	 */
	public function addReplacement($replace, $value)
	{
		if( !is_array($this->replaces) ) $this->updateReplacement();

		if( !empty($replace) && $replace != $value )
		{
			$this->replaces[$replace] = $value;
			return true;
		}

		return false;
	}

	/**
	 * remove a single replacement
	 * @param string $replace
	 * @return void
	 */
	public function removeReplacement($replace)
	{
		if( !is_array($this->replaces) ) $this->updateReplacement();

		if( isset($this->replaces[$replace]) )
		{
			unset($this->replaces[$replace]);
		}

		return;
	}



	/*
	** connection related functions
	*/



	/**
	 * open connection to database
	 * @return boolean
	 */
	public function connect()
	{
		// close possible open connection
		$this->close();

		// use mysqli
		if( $this->mysqli )
		{
			if( $this->persistent )
				$this->identifier = mysqli_connect($this->getConnectionHostname(), $this->username, $this->password);
			else
				$this->identifier = mysqli_connect($this->getConnectionHostname(), $this->username, $this->password);

			// on connection error
			if( mysqli_connect_error() ) $this->connectionError();

			// select database
			if( @mysqli_select_db($this->identifier, $this->database) )
				return true;
			else
				$this->databaseError();
		}

		// use default mysql
		else
		{
			if( $this->persistent )
				$this->identifier = @mysql_connect($this->getConnectionHostname(), $this->username, $this->password, true);
			else
				$this->identifier = @mysql_pconnect($this->getConnectionHostname(), $this->username, $this->password);

			if( !$this->identifier && mysql_error() ) $this->connectionError();

			// if connection was successfully select database
			if( $this->identifier )
			{
				if( @mysql_select_db($this->database, $this->identifier) )
					return true;
				else
					$this->databaseError();
			}
		}

		return false;
	}

	/**
	 * alias of connect()
	 * @return boolean
	 */
	public function reconnect()
	{
		return $this->connect();
	}

	/**
	 * check if connection is established
	 * @return boolean
	 */
	public function isConnected()
	{
		if( $this->identifier )
		{
			if( $this->mysqli )
			{
				if( $this->identifier instanceof mysqli && mysqli_ping($this->identifier) )
				{
					return true;
				}
			}
			else
			{
				if( is_resource($this->identifier) && mysql_ping($this->identifier) )
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * alias of isConnected()
	 * @return boolean
	 */
	public function ping()
	{
		return $this->isConnected();
	}

	/**
	 * close mysql connection
	 * @return boolean
	 */
	public function close()
	{
		if( isset($this->identifyer) )
		{
			if( $this->mysqli )
			{
				if( $this->identifier instanceof mysqli )
				{
					@mysqli_close($this->identifier);
					return true;
				}
			}
			else
			{
				if( is_resource($this->identifier) )
				{
					@mysql_close($this->identifyer);
					return true;
				}
			}
		}

		return false;
	}



	/*
	** query functions
	*/



	/**
	 * check if class has the permission for the query
	 * @param string $query
	 * @return boolean
	 */
	private function hasQueryPermission($query)
	{
		if( $this->type == self::CONNECTION_TYPE_WRITE )
			return true;

		if( preg_match("/(INSERT|UPDATE|DELETE) (.*)/i", $query) ||
			preg_match("/(?:CREATE|DROP|ALTER|CACHE) (.*)(?:FUNCTION|TABLE|VIEW|EVENT|TRIGGER|INDEX|SERVER|USER|DATABASE|TABLESPACE|PROCEDURE) /i", $query) )
			return false;

		return true;
	}

	/**
	 * run query string against database
	 * @param string $query
	 * @param boolean $fetch
	 * @param string $fetchType
	 * @return mixed
	 */
	public function query($query, $fetch = false, $fetchType = self::FETCH_ASSOC)
	{
		// if query is not an empty string
		if( !empty($query) )
		{
			// replace data inside query
			$queryString = $this->replaceQuery($query);

			// check if query is allowed by given connection type
			if( $this->hasQueryPermission($queryString) )
			{
				// run mysqli query
				if( $this->mysqli )
					$this->result = @mysqli_query($this->identifier, $queryString);
				
				// run default query
				else
					$this->result = @mysql_query($queryString, $this->identifier);
				
				// return error on failed query
				if( !$this->result )
					$this->queryError();

				// fetch or return result
				else
				{
					// return fetched result
					if( $fetch )
						return $this->fetch($this->result, $fetchType);

					// return result
					return $this->result;
				}
			}
			else
			{
				// print permission error
				$this->permissionError();
			}
		}

		return false;
	}

	/**
	 * alias of query()
	 * @param string $query
	 * @param boolean $fetch
	 * @param string $fetchType
	 * @return mixed
	 */
	public function qry($query, $fetch = false, $fetchType = self::FETCH_ASSOC)
	{
		return $this->query($query, $fetch, $fetchType);
	}

	/**
	 * affected rows by the last query
	 * @return integer
	 */
	public function getAffected()
	{
		if( $this->mysqli )
		{
			return mysqli_affected_rows($this->identifier);
		}

		return mysql_affected_rows($this->identifier);
	}

	/**
	 * count the rows in the result
	 * @return integer
	 */
	public function getNumRows()
	{
		if( $this->mysqli )
		{
			return mysqli_num_rows($this->result);
		}

		return mysql_num_rows($this->result);
	}

	/**
	 * get last insert id
	 * @return integer
	 */
	public function getLastId()
	{
		if( $this->mysqli )
		{
			return mysqli_insert_id($this->identifier);
		}

		if( ($id = @mysql_insert_id($this->identifier)) !== false )
		{
			return $id;
		}

		return 0;
	}

	/**
	 * alias of getLastId()
	 * @return integer | boolean
	 */
	public function getLastInsertId()
	{
		return $this->getLastId();
	}

	/**
	 * free result memory
	 * @return boolean
	 */
	public function free()
	{
		if( $this->mysqli )
		{
			mysqli_free_result($this->result);
			return true;
		}

		return @mysql_free_result($this->result);
	}

	/**
	 * fetch result to useable formats
	 * @param boolean|resource|mysqli_result $result
	 * @param string $type
	 * @return mixed
	 */
	public function fetch($result = false, $type = "assoc")
	{
		// call again to shift parameters
		if( $result === false || is_string($result) )
		{
			$type = is_string($result) ? $result : $type;
			$fetched = $this->fetch($this->result, $type);

			$this->free();
			return $fetched;
		}

		$fetched = array();

		// array
		if( $type == self::FETCH_ARRAY )
		{
			if( $this->mysqli )
				while( $row = mysqli_fetch_array($result) )
					$fetched[] = $row;
			else
				while( $row = mysql_fetch_array($result) )
					$fetched[] = $row;

			return $fetched;
		}

		// row
		if( $type == self::FETCH_ROW )
		{
			if( $this->mysqli )
				while( $row = mysqli_fetch_row($result) )
					$fetched[] = $row;
			else
				while( $row = mysql_fetch_row($result) )
					$fetched[] = $row;

			return $fetched;
		}

		// object
		if( $type == self::FETCH_OBJ ||  $type == self::FETCH_OBJECT ||  $type == self::FETCH_COLLECTION )
		{
			$fetched = new mysqlClass_Collection();

			if( $this->mysqli )
			{
				while( $row = mysqli_fetch_assoc($result) )
					$fetched->addItem($fetched->getNewItemWithData($row));
			}
			else
			{
				while( $row = mysql_fetch_assoc($result) )
					$fetched->addItem($fetched->getNewItemWithData($row));
			}
		}

		// default / assoc
		if( $this->mysqli )
			while( $row = mysqli_fetch_assoc($result) )
				$fetched[] = $row;
		else
			while( $row = mysql_fetch_assoc($result) )
				$fetched[] = $row;

		return $fetched;
	}

	/**
	 * fetch result to collection
	 * @param boolean|resource|mysqli_result $result
	 * @return mysqlClass_Collection
	 */
	public function getCollection($result = false)
	{
		return $this->fetch($result, self::FETCH_COLLECTION);
	}

	/**
	 * escape and quote value inside mysql query
	 * @param string $value
	 * @param boolean $nullable
	 * @return string
	 */
	public function escape($value, $nullable = true)
	{
		if( is_string($value) )
		{
			if( $this->mysqli )
			{
				if( $this->identifier instanceof mysqli )
					$value = mysqli_real_escape_string($this->identifier, $value);
			}
			else
			{
				$value = mysql_real_escape_string($value);
			}
		}
		
		if( is_null($value) && $nullable )
			$value = "NULL";
		elseif( is_numeric($value) );
			// nothing to do, numeral literals need no escape
		elseif( is_bool($value) )
			$value = (integer)$value;
		else
			$value = "'" . $value . "'";

		return $value;
	}

	/**
	 * alias of escape()
	 * @param string $value
	 * @param boolean $nullable
	 * @return string
	 */
	public function e($value, $nullable = true)
	{
		return $this->escape($value, $nullable);
	}

	/**
	 * alias of escape()
	 * @param string $value
	 * @param boolean $nullable
	 * @return string
	 */
	public function __($value, $nullable = true)
	{
		return $this->escape($value, $nullable);
	}



	/*
	** error verbose function
	*/


	
	/**
	 * print connection error or throw exception on verbose
	 * otherwise die with an message
	 * @throws mysqlClass_Connection_Exception
	 * @return void
	 */
	private function connectionError()
	{
		if( $this->verbose )
		{
			if( $this->mysqli )
				$reason = mysqli_connect_error();
			else
				$reason = mysql_error($this->identifier);

			throw new mysqlClass_Connection_Exception(self::MESSAGE_CONNECTION . ", reason: " . $reason);
		}

		$this->printError(self::MESSAGE_CONNECTION . "!");
	}

	/**
	 * print database error or throw exception on verbose
	 * @throws mysqlClass_Database_Exception
	 * @return void
	 */
	private function databaseError()
	{
		$message = sprintf(self::MESSAGE_DATABASE, $this->database);
		
		if( $this->verbose)
		{
			if( $this->mysqli )
				$reason = mysqli_error($this->identifier);
			else
				$reason = mysql_error($this->identifier);

			throw new mysqlClass_Database_Exception($message . ", reason: " . $reason);
		}

		$this->printError($message . " !");
	}

	/**
	 * print query error or throw exception on verbose
	 * @throws mysqlClass_Query_Exception
	 * @return void
	 */
	private function queryError()
	{
		if( $this->verbose)
		{
			if( $this->mysqli )
				$reason = mysqli_error($this->identifier);
			else
				$reason = mysql_error($this->identifier);

			throw new mysqlClass_Query_Exception(self::MESSAGE_QUERY . ", reason: " . $reason);
		}

		$this->printError(self::MESSAGE_QUERY . "!");
	}

	/**
	 * print create error or throw exception on verbose
	 * @param string $reason
	 * @throws mysqlClass_Create_Exception
	 */
	public function createError($reason = NULL)
	{
		if( $this->verbose)
		{
			if( !is_null($reason) )
				throw new mysqlClass_Create_Exception(self::MESSAGE_CREATE . ", reason: " . strtolower($reason));

			throw new mysqlClass_Create_Exception(self::MESSAGE_CREATE);
		}

		if( !is_null($reason) )
		{
			$this->printError(self::MESSAGE_CREATE . ", reason: " . strtolower($reason));
			return;
		}

		$this->printError(self::MESSAGE_CREATE . "!");
	}

	/**
	 * print permission error or throw exception on verbose
	 * dies or throws an mysqlClass_Permission_Exception
	 * @throws mysqlClass_Permission_Exception
	 */
	private function permissionError()
	{
		if( $this->verbose )
			throw new mysqlClass_Permission_Exception(self::MESSAGE_PERMISSION);

		$this->printError(self::MESSAGE_PERMISSION . "!");
	}

	/**
	 * print unknown function error or throw exception on verbose
	 * @param object $class
	 * @param string $name
	 * @param array $parameter
	 * @throws mysqlClass_Unknown_Function_Exception
	 */
	public function unknownFunction($class, $name, $parameter = array())
	{
		$params = "";

		for( $i = 1; $i <= count($parameter); $i++ )
		{
			$params .= "param" . $i;

			if( $i != count($parameter) )
			{
				$params .= ", ";
			}
		}

		$message = sprintf(self::MESSAGE_UNKNOWN, $name, $params, get_class($class));

		if( $this->verbose)
			throw new mysqlClass_Unknown_Function_Exception($message);
		
		$this->printError($message . " !");
	}

	/**
	 * print a non-verbose message
	 * @param string $message
	 */
	private function printError($message)
	{
		if( !$this->verbose )
		{
			trigger_error($message, E_USER_ERROR);
		}
	}



	/*
	** query initializer
	*/



	/**
	 * get select query instance
	 * @param string|array $columns
	 * @param boolean $newInstance
	 * @return mysqlClass_Select
	 */
	public function select($columns = "*", $newInstance = false)
	{
		// prevent php debug notification
		if( $columns && $newInstance );

		$args = array();

		// check if new instance have to be created
		if( func_num_args() > 0 )
		{
			$args = func_get_args();
			$last = array_splice($args, -1);

			if( is_bool($last[0]) )
			{
				if( $last[0] )
				{
					$instance = new mysqlClass_Select($this);

					if( !empty($args) )
						call_user_func_array(array($instance, "columns"), $args);

					return $instance;
				}
			}
			else
			{
				array_push($args, $last);
			}
		}

		// create instance or reset
		if( !$this->querySelect )
			$this->querySelect = new mysqlClass_Select($this);
		else
			$this->querySelect->resetQuery($this->format);

		// pass parameter
		if( count($args) > 0 )
			call_user_func_array(array($this->querySelect, "columns"), $args);

		return $this->querySelect;
	}

	/**
	 * get insert query instance
	 * @param string $table
	 * @param boolean $newInstance
	 * @return mysqlClass_Insert
	 */
	public function insert($table = NULL, $newInstance = false)
	{
		// prevent php debug notification
		if( $table && $newInstance );

		$args = array();

		// check parameters for new instance
		if( func_num_args() > 0 )
		{
			// extract last parameter
			$args = func_get_args();
			$last = array_splice($args, -1);

			// if last parameter is a boolean
			if( is_bool($last[0]) )
			{
				// if new instance has to be created
				if( $last[0] )
				{
					$instance = new mysqlClass_Insert($this);

					if( !empty($args) )
						call_user_func_array(array($instance, "table"), $args);

					return $instance;
				}
			}

			// otherwise push parameter back in list
			else
			{
				array_push($args, $last);
			}
		}

		// create instance or reset
		if( !$this->queryInsert)
			$this->queryInsert = new mysqlClass_Insert($this);
		else
			$this->queryInsert->resetQuery($this->format);

		// pass parameter
		if( count($args) > 0 )
			call_user_func_array(array($this->queryInsert, "table"), $args);

		return $this->queryInsert;
	}

	/**
	 * get insert query instance
	 * @param string $table
	 * @param boolean $newInstance
	 * @return mysqlClass_Insert
	 */
	public function insertInto($table = NULL, $newInstance = false)
	{
		// prevent php debug notification
		if( $table && $newInstance );

		return call_user_func_array(array($this, "insert"), func_get_args());
	}

	/**
	 * get replace query instance
	 * @param string $table
	 * @param boolean $newInstance
	 * @return mysqlClass_Replace
	 */
	public function replace($table = NULL, $newInstance = false)
	{
		// prevent php debug notification
		if( $table && $newInstance );

		$args = array();

		// check parameters for new instance
		if( func_num_args() > 0 )
		{
			// extract last parameter
			$args = func_get_args();
			$last = array_splice($args, -1);

			// if last parameter is a boolean
			if( is_bool($last[0]) )
			{
				// if new instance has to be created
				if( $last[0] )
				{
					$instance = new mysqlClass_Replace($this);

					if( !empty($args) )
						call_user_func_array(array($instance, "table"), $args);

					return $instance;
				}
			}

			// otherwise push parameter back in list
			else
			{
				array_push($args, $last);
			}
		}

		// create instance or reset
		if( !$this->queryReplace)
			$this->queryReplace = new mysqlClass_Replace($this);
		else
			$this->queryReplace->resetQuery($this->format);

		// pass parameter
		if( count($args) > 0 )
			call_user_func_array(array($this->queryReplace, "table"), $args);

		return $this->queryReplace;
	}

	/**
	 * get replace query instance
	 * @param string $table
	 * @param boolean $newInstance
	 * @return mysqlClass_Replace
	 */
	public function replaceInto($table = NULL, $newInstance = false)
	{
		// prevent php debug notification
		if( $table && $newInstance );

		return call_user_func_array(array($this, "replace"), func_get_args());
	}

	/**
	 * get update query instance
	 * @param string|array $table
	 * @param boolean $newInstance
	 * @return mysqlClass_Update
	 */
	public function update($table = NULL, $newInstance = false)
	{
		// prevent php debug notification
		if( $table && $newInstance );

		$args = array();

		// check parameters for new instance
		if( func_num_args() > 0 )
		{
			// extract last parameter
			$args = func_get_args();
			$last = array_splice($args, -1);

			// if last parameter is a boolean
			if( is_bool($last[0]) )
			{
				// if new instance has to be created
				if( $last[0] )
				{
					$instance = new mysqlClass_Update($this);

					if( !empty($args) )
						call_user_func_array(array($instance, "table"), $args);

					return $instance;
				}
			}

			// otherwise push parameter back in list
			else
			{
				array_push($args, $last);
			}
		}

		// create instance or reset
		if( !$this->queryUpdate)
			$this->queryUpdate = new mysqlClass_Update($this);
		else
			$this->queryUpdate->resetQuery($this->format);

		// pass parameter
		if( count($args) > 0 )
			call_user_func_array(array($this->queryUpdate, "table"), $args);

		return $this->queryUpdate;
	}

	/**
	 * get update query instance
	 * @param string|array $table
	 * @param boolean $newInstance
	 * @return mysqlClass_Delete
	 */
	public function delete($table = NULL, $newInstance = false)
	{
		// prevent php debug notification
		if( $table && $newInstance );

		$args = array();

		// check parameters for new instance
		if( func_num_args() > 0 )
		{
			// extract last parameter
			$args = func_get_args();
			$last = array_splice($args, -1);

			// if last parameter is a boolean
			if( is_bool($last[0]) )
			{
				// if new instance has to be created
				if( $last[0] )
				{
					$instance = new mysqlClass_Delete($this);

					if( !empty($args) )
						call_user_func_array(array($instance, "table"), $args);

					return $instance;
				}
			}

			// otherwise push parameter back in list
			else
			{
				array_push($args, $last);
			}
		}

		// create instance or reset
		if( !$this->queryDelete)
			$this->queryDelete = new mysqlClass_Delete($this);
		else
			$this->queryDelete->resetQuery($this->format);

		// pass parameter
		if( count($args) > 0 )
			call_user_func_array(array($this->queryDelete, "table"), $args);

		return $this->queryDelete;
	}

	/**
	 * get update query instance
	 * @param string|array $table
	 * @param boolean $newInstance
	 * @return mysqlClass_Delete
	 */
	public function deleteFrom($table = NULL, $newInstance = false)
	{
		// prevent php debug notification
		if( $table && $newInstance );

		return call_user_func_array(array($this, "delete"), func_get_args());
	}

	/**
	 * get truncate query instance
	 * @param string|array $table
	 * @param boolean $newInstance
	 * @return mysqlClass_Truncate
	 */
	public function truncate($table = NULL, $newInstance = false)
	{
		// prevent php debug notification
		if( $table && $newInstance );

		$args = array();

		// check parameters for new instance
		if( func_num_args() > 0 )
		{
			// extract last parameter
			$args = func_get_args();
			$last = array_splice($args, -1);

			// if last parameter is a boolean
			if( is_bool($last[0]) )
			{
				// if new instance has to be created
				if( $last[0] )
				{
					$instance = new mysqlClass_Truncate($this);

					if( !empty($args) )
						call_user_func_array(array($instance, "table"), array($table));

					return $instance;
				}
			}

			// otherwise push parameter back in list
			else
			{
				array_push($args, $last);
			}
		}

		// create instance or reset
		if( !$this->queryTruncate )
			$this->queryTruncate = new mysqlClass_Truncate($this);
		else
			$this->queryTruncate->resetQuery($this->format);

		// pass parameter
		if( count($args) > 0 )
			call_user_func_array(array($this->queryTruncate, "table"), array($table));

		return $this->queryTruncate;
	}

	/**
	 * get truncate query instance
	 * @param string|array $table
	 * @param boolean $newInstance
	 * @return mysqlClass_Truncate
	 */
	public function truncateTable($table = NULL, $newInstance = false)
	{
		// prevent php debug notification
		if( $table && $newInstance );

		return call_user_func_array(array($this, "truncate"), func_get_args());
	}
}
