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



/*
** query classes
*/



/**
 * Frosted MySQL Library Query Class Interface
 * - - - - - - - - - -
 * Interface for all query classes related to Frosted MySQL Library.
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
interface mysqlClass_Queries
{
	/**
	 * execute mysql query
	 * @param boolean $returnRaw
	 * @return mysqlClass|mixed
	 */
	public function run($returnRaw = false);

	/**
	 * execute mysql query
	 * @param boolean $returnRaw
	 * @return mysqlClass|mixed
	 */
	public function execute($returnRaw = false);
	
	/**
	 * build query string
	 * @param integer $formatOffset
	 * @return string
	 */
	public function build($formatOffset = 0);
	
	/**
	 * reset query instance
	 * @param boolean $format
	 * @return mysqlClass_Queries
	 */
	public function resetQuery($format);

	/**
	 * print query to browser
	 * @return mysqlClass_Queries
	 */
	public function showQuery();

	/**
	 * build and return query
	 * @return string
	 */
	public function getQuery();
}

/**
 * Frosted MySQL Library Query Abstract Class
 * - - - - - - - - - -
 * Abstraction for all query classes related to Frosted MySQL Library.
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
class mysqlClass_Abstract implements mysqlClass_Queries
{
	/**
	 * parent class
	 * @var mysqlClass
	 */
	protected $parent = NULL;

	/**
	 * format the query output
	 * @var boolean
	 */
	protected $format = true;

	/**
	 * format offset
	 * @var integer
	 */
	protected $formatOffset = 0;

	/**
	 * build query
	 * @var array
	 */
	protected $query = array();



	/*
	** public
	*/



	/**
	 * create select class
	 * @param mysqlClass $parent
	 */
	public function __construct($parent)
	{
		$this->parent = $parent;
		$this->resetQuery($this->parent->getFormat());
	}

	/**
	 * reset query instance
	 * @param boolean $format
	 * @return mysqlClass_Queries
	 */
	public function resetQuery($format)
	{
		$this->format = $format;
		$this->query = array();

		return $this;
	}

	/**
	 * build query string
	 * @param integer $formatOffset
	 * @return string
	 */
	public function build($formatOffset = 0)
	{
		return NULL;
	}

	/**
	 * print query string
	 * @return mysqlClass_Select
	 */
	public function showQuery()
	{
		$query = $this->build();
		$query = $this->parent->replaceQuery($query);

		echo $query . "\n\n";

		return $this;
	}

	/**
	 * build and get query string
	 * @return string
	 */
	public function getQuery()
	{
		$query = $this->build();
		$query = $this->parent->replaceQuery($query);
		
		return $query;
	}

	/**
	 * run mysql query against database
	 * @param boolean $returnRaw
	 * @return mysqlClass|mysqli_result|resource
	 */
	public function run($returnRaw = false)
	{
		$query = $this->build();
		$result = $this->parent->query($query);

		if( $returnRaw )
			return $result;

		return $this->parent;
	}

	/**
	 * alias of run()
	 * @param boolean $returnRaw
	 * @return mixed|mysqlClass|resource
	 */
	public function execute($returnRaw = false)
	{
		return $this->run($returnRaw);
	}
}

/**
 * Frosted MySQL Library Delete Query Class
 * - - - - - - - - - -
 * Add "DELETE" functionality to Frosted MySQL Library and will not work without them.
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
class mysqlClass_Delete extends mysqlClass_Abstract implements mysqlClass_Queries
{
	/**
	 * delete error messages
	 * @var string
	 */
	const MESSAGE_ORDER = "you cannot use 'order' if you delete from more than one table";
	const MESSAGE_LIMIT = "you cannot use 'limit' if you delete from more than one table";



	/*
	** public 
	*/



	/**
	 * reset delete class
	 * @param boolean $format
	 * @return mysqlClass_Delete
	 */
	public function resetQuery($format)
	{
		parent::resetQuery($format);

		$this->query["tables"] = array();
		$this->query["using"] = array();
		$this->query["low"]    = false;
		$this->query["quick"]  = false;
		$this->query["ignore"] = false;
		$this->query["where"]  = array();
		$this->query["order"]  = array();
		$this->query["limit"]  = NULL;

		return $this;
	}



	/*
	** query related
	*/



	/**
	 * add tables to query
	 * @param string|array $table
	 * @return mysqlClass_Delete
	 */
	public function table($table)
	{
		// only one string is set
		if( func_num_args() == 1 && is_string($table) )
		{
			$this->query["tables"][] = $table;
			return $this;
		}

		// add all tables to query
		foreach( func_get_args() as $param )
		{
			if( !is_array($param) )
				$this->query["tables"][] = $param;
			else
				foreach( $param as $database => $name )
				{
					if( !is_numeric($database) )
						$this->query["tables"][] = $database;
					else
						$this->query["tables"][] = $name;
				}
		}

		// add table to using
		call_user_func_array(array($this, "using"), func_get_args());

		return $this;
	}

	/**
	 * alias of 'table'
	 * @param string|array $table
	 * @return mysqlClass_Delete
	 */
	public function from($table)
	{
		// pervent php debug notification
		if( $table );
		
		return call_user_func_array(array($this, "table"), func_get_args());
	}

	/**
	 * add using tables to query
	 * @param string|array $table
	 * @return mysqlClass_Delete
	 */
	public function using($table)
	{
		// only one string is set
		if( func_num_args() == 1 && is_string($table) )
		{
			if( !in_array($table, $this->query["using"]) )
				$this->query["using"][] = $table;
			
			return $this;
		}

		// add all tables to query
		foreach( func_get_args() as $param )
		{
			if( !is_array($param) )
			{
				if( !in_array($table, $this->query["using"]) )
					$this->query["using"][] = $param;
			}
			else
				foreach( $param as $database => $name )
					if( !is_numeric($database) )
						$this->query["using"][] = $database . " AS " . $name;
					else if( !in_array($name, $this->query["using"]) )
							$this->query["using"][] = $name;
		}

		return $this;
	}
	
	/**
	 * add 'low priority' to query
	 * @param boolean $low
	 * @return mysqlClass_Delete
	 */
	public function lowPriority($low = true)
	{
		$this->query["low"] = (bool)$low;
		return $this;
	}

	/**
	 * add 'quick' to query
	 * @param boolean $quick
	 * @return mysqlClass_Delete
	 */
	public function quick($quick = true)
	{
		$this->query["quick"] = (bool)$quick;
		return $this;
	}
	
	/**
	 * add 'ignore' to query
	 * @param boolean $ignore
	 * @return mysqlClass_Delete
	 */
	public function ignore($ignore = true)
	{
		$this->query["ignore"] = (bool)$ignore;
		return $this;
	}
	
	/**
	 * add 'where' to query
	 * @param string $condition
	 * @param string $replace
	 * @param string $nextRelation
	 * @return mysqlClass_Delete
	 */
	public function where($condition, $replace = NULL, $nextRelation = mysqlClass::WHERE_AND)
	{
		// add condition
		if( !is_null($replace) )
		{
			if( is_array($replace) )
			{
				// escape all values
				foreach( $replace as &$value ) $value = $this->parent->escape($value);

				// format sub-query
				if( $this->format )
				{
					$condition = str_replace("ANY(?)", "\nANY\n(\n    ?\n)", $condition);
					$condition = str_replace("IN(?)", "\nIN\n(\n    ?\n)", $condition);
					$condition = str_replace("SOME(?)", "\nSOME\n(\n    ?\n)", $condition);
				}

				$glue = $this->format ? ",\n    " : ",";
				$this->query["where"][] = str_replace("?", join($glue, $replace), $condition);
			}
			else if( $replace instanceof mysqlClass_Select )
				$this->query["where"][] = array($condition, $replace);
			else
				$this->query["where"][] = str_replace("?", $this->parent->escape($replace), $condition);
		}
		else
			$this->query["where"][] = $condition;

		// add relation
		if( strtoupper($nextRelation) == mysqlClass::WHERE_OR )
			$this->query["where"][] = mysqlClass::WHERE_OR;
		else
			$this->query["where"][] = mysqlClass::WHERE_AND;

		return $this;
	}

	/**
	 * add 'or' related 'where' to query
	 * @param string $condition
	 * @param string $replace
	 * @param string $nextRelation
	 * @return mysqlClass_Delete
	 */
	public function orWhere($condition, $replace = NULL, $nextRelation = mysqlClass::WHERE_AND)
	{
		if( !empty($this->query["where"]) )
			$this->query["where"][(count($this->query["where"]) - 1)] = mysqlClass::WHERE_OR;

		return $this->where($condition, $replace, $nextRelation);
	}

	/**
	 * add 'order' to query
	 * @param string $field
	 * @param string $order
	 * @return mysqlClass_Delete
	 */
	public function orderBy($field, $order = mysqlClass::ORDER_ASC)
	{
		if( count($this->query["tables"]) >= 2 )
		{
			$this->parent->createError(self::MESSAGE_ORDER);
			return $this;
		}

		if( strtoupper($order) == mysqlClass::ORDER_DESC )
			$this->query["order"][] = $field . " " . mysqlClass::ORDER_DESC;
		else
			$this->query["order"][] = $field . " " . mysqlClass::ORDER_ASC;

		return $this;
	}

	/**
	 * alias of 'orderBy'
	 * @param string $field
	 * @param string $order
	 * @return mysqlClass_Delete
	 */
	public function order($field, $order = mysqlClass::ORDER_ASC)
	{
		return $this->orderBy($field, $order);
	}

	/**
	 * add 'limit' to query
	 * @param integer $limit
	 * @return mysqlClass_Delete
	 */
	public function limit($limit)
	{
		if( count($this->query["tables"]) >= 2 )
		{
			$this->parent->createError(self::MESSAGE_LIMIT);
			return $this;
		}

		$this->query["limit"] = $limit;

		return $this;
	}



	/*
	** build
	*/



	/**
	 * build mysql delete query string
	 * @param integer $formatOffset
	 * @return string
	 */
	public function build($formatOffset = 0)
	{
		$this->formatOffset += $formatOffset;
		$offset = str_pad("", $this->formatOffset, " ");

		// end if no table is set
		if( empty($this->query["tables"]) ) return NULL;

		$query = $this->format ? $offset . "DELETE " : "DELETE ";

		// low priority
		if( $this->query["low"] ) $query .= $this->format ? "\n" . $offset . "    LOW_PRIORITY " : "LOW_PRIORITY ";

		// quick
		if( $this->query["quick"] ) $query .= $this->format ? "\n" . $offset . "    QUICK " : "QUICK ";

		// ignore
		if( $this->query["ignore"] ) $query .= $this->format ? "\n" . $offset . "    IGNORE " : "IGNORE ";

		// format line break
		$query .= $this->format && ($this->query["low"] || $this->query["quick"] || $this->query["ignore"]) ? "\n" : NULL;

		// tables
		if( count($this->query["tables"]) == 1 )
		{
			$query .= $this->format ? $offset . "FROM\n" . $offset . "    " . $this->query["tables"][0] . "\n" : "FROM " . $this->query["tables"][0] . " ";
		}
		else
		{
			if( $this->format )
			{
				$query .= "FROM\n";

				for( $i = 0; $i < count($this->query["tables"]); $i++ )
				{
					$query .= $offset . "    " . $this->query["tables"][$i];
					$query .= $i < count($this->query["tables"]) - 1 ? "," : NULL;
					$query .= "\n";
				}

				$query .= $offset . "USING\n";

				for( $i = 0; $i < count($this->query["using"]); $i++ )
				{
					$query .= $offset . "    " . $this->query["using"][$i];
					$query .= $i < count($this->query["using"]) - 1 ? "," : NULL;
					$query .= "\n";
				}
			}
			else
			{
				$query .= "FROM " . join(",", $this->query["tables"]) . " ";
				$query .= "USING " . join(",", $this->query["using"]) . " ";
			}
		}

		// where
		if( !empty($this->query["where"]) )
		{
			if( $this->format )
			{
				$query .= $offset . "WHERE \n";

				for( $i = 0; $i < count($this->query["where"]); $i = $i + 2 )
				{
					if( is_array($this->query["where"][$i]) )
					{
						$select = $this->query["where"][$i][1];

						if( $select instanceof mysqlClass_Select )
						{
							$select = $select->build($this->formatOffset + 4);
							$select = trim($select);
						}

						$query .= $offset . "    " . str_replace("?", "\n" . $offset . "    (" . $select . ")", $this->query["where"][$i][0]);
						$query .= $i < count($this->query["where"]) - 2 ? " \n" . $offset . $this->query["where"][$i + 1] . " " : NULL;
						$query .= " \n";
					}
					else
					{
						$query .= $offset . "    " . $this->query["where"][$i];
						$query .= $i < count($this->query["where"]) - 2 ? " \n" . $offset . $this->query["where"][$i + 1] . " " : NULL;
						$query .= " \n";
					}
				}
			}
			else
			{
				for( $i = 0; $i < count($this->query["where"]); $i = $i + 2 )
				{
					if( is_array($this->query["where"][$i]) )
					{
						$select = $this->query["where"][$i][1];

						if( $select instanceof mysqlClass_Select )
							$select = $select->build();

						$this->query["where"][$i]  = str_replace("?", "(" . $select . ")", $this->query["where"][$i][0]);
					}
				}

				$where  = array_slice($this->query["where"], 0, -1);
				$query .= "WHERE " . join(" ", $where) . " ";
			}
		}

		// order
		if( !empty($this->query["order"]) )
		{
			if( $this->format )
			{
				$query .= $offset . "ORDER BY \n";

				for( $i = 0; $i < count($this->query["order"]); $i++ )
				{
					$query .= $offset . "    " . $this->query["order"][$i];
					$query .= $i < count($this->query["order"]) - 1 ? "," : NULL;
					$query .= " \n";
				}
			}
			else
			{
				$query .= "ORDER BY " . join(",", $this->query["order"]) . " ";
			}
		}

		// limit
		if( !empty($this->query["limit"]) )
		{
			$query .= $this->format ? $offset . "LIMIT \n" . $offset  . "    " : "LIMIT ";
			$query .= $this->query["limit"];
		}

		return $query;
	}
}

/**
 * Frosted MySQL Library Insert Query Class
 * - - - - - - - - - -
 * Add "INSERT" functionality to Frosted MySQL Library and will not work without them.
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
class mysqlClass_Insert extends mysqlClass_Abstract implements mysqlClass_Queries
{
	/**
	 * insert error messages
	 * @var string
	 */
	const MESSAGE_AFTER_FIELDS = "you cannot add columns after using 'set' or 'select'";
	const MESSAGE_AFTER_SET = "you cannot use 'set' after adding columns or a select";
	const MESSAGE_AFTER_SELECT = "you cannot use 'select' after adding values or 'set'";
	const MESSAGE_BEFORE_VALUES = "you have to specify a column list before adding values";
	const MESSAGE_VALUES_COUNT = "value count doesn't match columns";
	const MESSAGE_VALUES_MISSING = "columns not found in values";



	/*
	** public 
	*/



	/**
	 * reset insert class
	 * @param boolean $format
	 * @return mysqlClass_Select
	 */
	public function resetQuery($format)
	{
		parent::resetQuery($format);

		$this->query["table"]     = NULL;
		$this->query["low"]       = false;
		$this->query["delayed"]   = false;
		$this->query["high"]      = false;
		$this->query["ignore"]    = false;
		$this->query["columns"]   = array();
		$this->query["values"]    = array();
		$this->query["set"]       = array();
		$this->query["select"]    = NULL;
		$this->query["duplicate"] = array();

		return $this;
	}



	/*
	** query related
	*/


	
	/**
	 * add table to insert query
	 * @param string $table
	 * @return mysqlClass_Insert
	 */
	public function table($table)
	{
		if( is_string($table) )
			$this->query["table"] = $table;
		else if( is_array($table) )
			foreach( $table as $_table => $_name )
				if( !is_numeric($_table) )
					$this->query["table"] = $_table;
				else
					$this->query["table"] = $_name;
		
		return $this;
	}

	/**
	 * alias of 'table'
	 * @param string $table
	 * @return mysqlClass_Insert
	 */
	public function into($table)
	{
		return $this->table($table);
	}

	/**
	 * add 'low priority' to query
	 * @param boolean $low
	 * @return mysqlClass_Insert
	 */
	public function lowPriority($low = true)
	{
		if( (bool)$low )
		{
			$this->query["delayed"] = false;
			$this->query["high"] = false;
		}
		
		$this->query["low"] = (bool)$low;
		return $this;
	}
	
	
	/**
	 * add 'delayed' to insert query
	 * @param boolean $delayed
	 * @return mysqlClass_Insert
	 */
	public function delayed($delayed = true)
	{
		if( (bool)$delayed )
		{
			$this->query["low"] = false;
			$this->query["high"] = false;
		}
		
		$this->query["delayed"] = (bool)$delayed;
		return $this;
	}

	/**
	 * add 'high priority' to query
	 * @param boolean $high
	 * @return mysqlClass_Insert
	 */
	public function highPriority($high = true)
	{
		if( (bool)$high )
		{
			$this->query["low"] = false;
			$this->query["delayed"] = false;
		}
		
		$this->query["high"] = (bool)$high;
		return $this;
	}

	/**
	 * add 'ignore' to insert query
	 * @param boolean $ignore
	 * @return mysqlClass_Insert
	 */
	public function ignore($ignore = true)
	{
		$this->query["ignore"] = (bool)$ignore;
		return $this;
	}

	/**
	 * add columns to insert query
	 * @param string|array $columns
	 * @return mysqlClass_Insert
	 */
	public function columns($columns)
	{
		if( count($this->query["set"]) > 0 || !is_null($this->query["select"]) )
		{
			$this->parent->createError(self::MESSAGE_AFTER_FIELDS);
			return $this;
		}

		if( func_num_args() == 1 && is_string($columns) && !in_array($columns, $this->query["columns"]) )
		{
			$this->query["columns"][] = $columns;
			return $this;
		}

		foreach( func_get_args() as $column )
		{
			if( !is_array($column) && !in_array($column, $this->query["columns"]) )
			{
				$this->query["columns"][] = $column;
			}
			else
			{
				foreach( $column as $_column )
					if( !in_array($_column, $this->query["columns"]) )
						$this->query["columns"][] = $_column;
			}
		}

		return $this;
	}

	/**
	 * alias of "columns"
	 * @param string|array $fields
	 * @return mysqlClass_Insert
	 */
	public function fields($fields)
	{
		if( count($this->query["set"]) > 0 || !is_null($this->query["select"]) )
		{
			$this->parent->createError(self::MESSAGE_AFTER_FIELDS);
			return $this;
		}

		if( func_num_args() == 1 && is_string($fields) && !in_array($fields, $this->query["columns"]) )
		{
			$this->query["columns"][] = $fields;
			return $this;
		}

		return call_user_func_array(array($this, "columns"), func_get_args());
	}

	/**
	 * add 'values' to insert query
	 * @param string|array $values
	 * @return mysqlClass_Insert
	 */
	public function values($values)
	{
		$columnCount = count($this->query["columns"]);

		if( $columnCount == 0 )
		{
			$this->parent->createError(self::MESSAGE_BEFORE_VALUES);
			return $this;
		}

		if( count($this->query["set"]) > 0 || !is_null($this->query["select"]) )
		{
			$this->parent->createError(self::MESSAGE_AFTER_FIELDS);
			return $this;
		}

		if( func_num_args() == 1 && $columnCount == 1 && !is_array($values) )
		{
			$this->query["values"][] = array($values);
			return $this;
		}

		// count params
		$count = 0;
		$values = array();

		foreach( func_get_args() as $value )
		{
			if( !is_array($value) )
			{
				$count++;
				$values[] = $this->parent->escape($value);
			}
			else
			{
				foreach( $value as $_key => $_value )
				{
					$count++;

					if( is_numeric($_key) )
						$values[] = $this->parent->escape($_value);
					else
						$values[$_key] = $this->parent->escape($_value);
				}
			}
		}

		// check if params count match fields
		if( $count == $columnCount )
		{
			$this->query["values"][] = $values;
		}

		// check if fields names are in values
		else if( $count > $columnCount )
		{
			$_values = array();
			
			foreach( $this->query["columns"] as $column )
			{
				if( !isset($values[$column]) ) break;
				$_values[] = $values[$column];
			}
			
			if( count($_values) == $columnCount )
				$this->query["values"][] = $_values;
			else
				$this->parent->createError(self::MESSAGE_VALUES_MISSING);
		}

		// values doesn't match
		else
		{
			$this->parent->createError(self::MESSAGE_VALUES_COUNT);
		}

		return $this;
	}

	/**
	 * add 'set' to insert query
	 * @param string|array $column
	 * @param string $replace
	 * @return mysqlClass_Insert
	 */
	public function set($column, $replace = NULL)
	{
		if( count($this->query["columns"]) > 0 || !is_null($this->query["select"]) )
		{
			$this->parent->createError(self::MESSAGE_AFTER_SET);
			return $this;
		}

		if( is_null($replace) )
		{
			if( is_array($column) )
			{
				foreach( $column as $_column => $_replace )
				{
					if( !is_numeric($_column) )
						if( strpos($_column, "?") === false && strpos($column, "=") === false )
							$this->query["set"][] = $_column . " = " . $this->parent->escape($_replace);
						else
							$this->query["set"][] = str_replace("?", $this->parent->escape($_replace), $_column);
					else
						$this->query["set"][] = $_replace;
				}
			}
			else
			{
				$this->query["set"][] = $column;
			}
		}
		else
		{
			if( strpos($column, "?") === false && strpos($column, "=") === false )
				$this->query["set"][] = $column . " = " . $this->parent->escape($replace);
			else
				$this->query["set"][] = str_replace("?", $this->parent->escape($replace), $column);
		}

		return $this;
	}

	/**
	 * add a select statement to insert query
	 * @param string|mysqlClass_Select $subSelect
	 * @return mysqlClass_Insert
	 */
	public function select($subSelect)
	{
		if( count($this->query["values"]) > 0 || count($this->query["set"]) > 0 )
		{
			$this->parent->createError(self::MESSAGE_AFTER_SELECT);
			return $this;
		}

		if( is_string($subSelect) || $subSelect instanceof mysqlClass_Select )
		{
			$this->query["select"] = $subSelect;
		}

		return $this;
	}
	
	/**
	 * add 'onDuplicate' to query
	 * @param array|string $update
	 * @param string $replace
	 * @return mysqlClass_Insert
	 */
	public function onDuplicate($update, $replace = NULL)
	{
		if( is_null($replace) )
		{
			if( is_array($update) )
			{
				foreach( $update as $_update => $_replace )
				{
					if( !is_numeric($_update) )
						$this->query["duplicate"][] = str_replace("?", $this->parent->escape($_replace), $_update);
					else
						$this->query["duplicate"][] = $_replace;
				}
			}
			else
			{
				$this->query["duplicate"][] = $update;
			}
		}
		else
		{
			$this->query["duplicate"][] = str_replace("?", $this->parent->escape($replace), $update);
		}

		return $this;
	}

	/**
	 * alias of 'onDuplicate'
	 * @param array|string $update
	 * @param string $replace
	 * @return mysqlClass_Insert
	 */
	public function duplicate($update, $replace = NULL)
	{
		return $this->onDuplicate($update, $replace);
	}



	/*
	** build
	*/



	/**
	 * build mysql insert query string
	 * @param integer $formatOffset
	 * @return string
	 */
	public function build($formatOffset = 0)
	{
		$this->formatOffset += $formatOffset;
		$offset = str_pad("", $this->formatOffset, " ");

		// end if no table is set
		if( is_null($this->query["table"]) ) return NULL;

		$query = $this->format ? $offset . "INSERT " : "INSERT ";

		// low priority
		if( $this->query["low"] ) $query .= $this->format ? "\n" . $offset . "    LOW_PRIORITY " : "LOW_PRIORITY ";

		// delayed
		if( $this->query["delayed"] ) $query .= $this->format ? "\n" . $offset . "    DELAYED " : "DELAYED ";

		// high priority
		if( $this->query["high"] ) $query .= $this->format ? "\n" . $offset . "    HIGH_PRIORITY " : "HIGH_PRIORITY ";

		// ignore
		if( $this->query["ignore"] ) $query .= $this->format ? "\n" . $offset . "    IGNORE \n" : "IGNORE ";

		$query .= $this->format ? $offset . "INTO " : "INTO ";

		// table
		$query .= $this->format ? "\n" . $offset . "    " . $this->query["table"] . "\n" : $this->query["table"] . " ";

		// columns
		if( !empty($this->query["columns"]) )
		{
			if( $this->format )
			{
				$query .= $offset . "    (";

				for( $i = 0; $i < count($this->query["columns"]); $i++ )
				{
					$query .= " " . $this->query["columns"][$i];
					$query .= $i < count($this->query["columns"]) - 1 ? "," : NULL;
				}

				$query .= " ) \n";
			}
			else
				$query .= "(" . join(",", $this->query["columns"]) . ") ";

			if( !empty($this->query["values"]) )
			{
				$query .= $this->format ? "VALUES\n" : "VALUES ";

				for( $i = 0; $i < count($this->query["values"]); $i++ )
				{
					if( $this->format )
					{
						$query .= $offset . "    " . "( " . join(", ", $this->query["values"][$i]) . " )";
						$query .= $i < count($this->query["values"]) - 1 ? ",\n" : NULL;
					}
					else
					{
						$query .= "(" . join(",", $this->query["values"][$i]) . ")";
						$query .= $i < count($this->query["values"]) - 1 ? "," : NULL;
					}
				}

				$query .= $this->format ? "\n" : " ";
			}
		}

		// set
		else if( !empty($this->query["set"]) )
		{
			$query .= $this->format ? "SET\n" : "SET ";

			for( $i = 0; $i < count($this->query["set"]); $i++ )
			{
				if( $this->format )
				{
					$query .= $offset . "    " . $this->query["set"][$i] . "";
					$query .= $i < count($this->query["set"]) - 1 ? ", \n" : NULL;
				}
				else
				{
					$query .= "(" . join(",", $this->query["set"]) . ")";
					$query .= $i < count($this->query["values"]) - 1 ? "," : NULL;
				}
			}

			$query .= $this->format ? "\n" : " ";
		}

		// select		
		if( !is_null($this->query["select"]) )
		{
			if( is_string($this->query["select"]) )
			{
				if( $this->format )
					$query .= $offset . "    ( " . $this->query["select"] . " ) \n";
				else
					$query .= "(" . $this->query["select"] . ") ";
			}
			else
			{
				$select = $this->query["select"];

				if( $select instanceof mysqlClass_Select )
				{
					if( $this->format )
						$query .= $offset . "    (" . trim($select->build($this->formatOffset + 4)) . ") \n";
					else
						$query .= "(" . $select->build() . ") ";
				}
			}
		}

		// on duplicate 
		if( !empty($this->query["duplicate"]) )
		{
			if( $this->format )
			{
				$query .= "ON DUPLICATE KEY UPDATE \n";

				for( $i = 0; $i < count($this->query["duplicate"]); $i++ )
				{
					$query .= $offset . "    " . $this->query["duplicate"][$i];
					$query .= $i < count($this->query["duplicate"]) - 1 ? "," : NULL;
					$query .= " \n";
				}
			}
			else
			{
				$query .= "ON DUPLICATE KEY UPDATE " . join(",", $this->query["duplicate"]) . " ";
			}
		}

		return $query;
	}
}

/**
 * Frosted MySQL Library Replace Query Class
 * - - - - - - - - - -
 * Add "REPLACE" functionality to Frosted MySQL Library and will not work without them.
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
class mysqlClass_Replace extends mysqlClass_Abstract implements mysqlClass_Queries
{
	/**
	 * insert error messages
	 * @var string
	 */
	const MESSAGE_AFTER_FIELDS = "you cannot add columns after using 'set' or 'select'";
	const MESSAGE_AFTER_SET = "you cannot use 'set' after adding columns or a select";
	const MESSAGE_AFTER_SELECT = "you cannot use 'select' after adding values or 'set'";
	const MESSAGE_BEFORE_VALUES = "you have to specify a column list before adding values";
	const MESSAGE_VALUES_COUNT = "value count doesn't match columns";
	const MESSAGE_VALUES_MISSING = "columns not found in values";



	/*
	** public 
	*/



	/**
	 * reset insert class
	 * @param boolean $format
	 * @return mysqlClass_Select
	 */
	public function resetQuery($format)
	{
		parent::resetQuery($format);

		$this->query["table"]     = NULL;
		$this->query["low"]       = false;
		$this->query["delayed"]   = false;
		$this->query["ignore"]    = false;
		$this->query["columns"]   = array();
		$this->query["values"]    = array();
		$this->query["set"]       = array();
		$this->query["select"]    = NULL;

		return $this;
	}



	/*
	** query related
	*/


	
	/**
	 * add table to insert query
	 * @param string $table
	 * @return mysqlClass_Insert
	 */
	public function table($table)
	{
		if( is_string($table) )
			$this->query["table"] = $table;
		else if( is_array($table) )
			foreach( $table as $_table => $_name )
				if( !is_numeric($_table) )
					$this->query["table"] = $_table;
				else
					$this->query["table"] = $_name;
		
		return $this;
	}

	/**
	 * alias of 'table'
	 * @param string $table
	 * @return mysqlClass_Insert
	 */
	public function into($table)
	{
		return $this->table($table);
	}

	/**
	 * add 'low priority' to query
	 * @param boolean $low
	 * @return mysqlClass_Insert
	 */
	public function lowPriority($low = true)
	{
		if( (bool)$low )
		{
			$this->query["delayed"] = false;
			$this->query["high"] = false;
		}
		
		$this->query["low"] = (bool)$low;
		return $this;
	}
	
	/**
	 * add 'delayed' to insert query
	 * @param boolean $delayed
	 * @return mysqlClass_Insert
	 */
	public function delayed($delayed = true)
	{
		if( (bool)$delayed )
		{
			$this->query["low"] = false;
			$this->query["high"] = false;
		}
		
		$this->query["delayed"] = (bool)$delayed;
		return $this;
	}

	/**
	 * add 'ignore' to insert query
	 * @param boolean $ignore
	 * @return mysqlClass_Insert
	 */
	public function ignore($ignore = true)
	{
		$this->query["ignore"] = (bool)$ignore;
		return $this;
	}

	/**
	 * add columns to insert query
	 * @param string|array $columns
	 * @return mysqlClass_Insert
	 */
	public function columns($columns)
	{
		if( count($this->query["set"]) > 0 || !is_null($this->query["select"]) )
		{
			$this->parent->createError(self::MESSAGE_AFTER_FIELDS);
			return $this;
		}

		if( func_num_args() == 1 && is_string($columns) && !in_array($columns, $this->query["columns"]) )
		{
			$this->query["columns"][] = $columns;
			return $this;
		}

		foreach( func_get_args() as $column )
		{
			if( !is_array($column) && !in_array($column, $this->query["columns"]) )
			{
				$this->query["columns"][] = $column;
			}
			else
			{
				foreach( $column as $_column )
					if( !in_array($_column, $this->query["columns"]) )
						$this->query["columns"][] = $_column;
			}
		}

		return $this;
	}

	/**
	 * alias of "columns"
	 * @param string|array $fields
	 * @return mysqlClass_Insert
	 */
	public function fields($fields)
	{
		if( count($this->query["set"]) > 0 || !is_null($this->query["select"]) )
		{
			$this->parent->createError(self::MESSAGE_AFTER_FIELDS);
			return $this;
		}

		if( func_num_args() == 1 && is_string($fields) && !in_array($fields, $this->query["columns"]) )
		{
			$this->query["columns"][] = $fields;
			return $this;
		}

		return call_user_func_array(array($this, "columns"), func_get_args());
	}

	/**
	 * add 'values' to insert query
	 * @param string|array $values
	 * @return mysqlClass_Insert
	 */
	public function values($values)
	{
		$columnCount = count($this->query["columns"]);

		if( $columnCount == 0 )
		{
			$this->parent->createError(self::MESSAGE_BEFORE_VALUES);
			return $this;
		}

		if( count($this->query["set"]) > 0 || !is_null($this->query["select"]) )
		{
			$this->parent->createError(self::MESSAGE_AFTER_FIELDS);
			return $this;
		}

		if( func_num_args() == 1 && $columnCount == 1 && !is_array($values) )
		{
			$this->query["values"][] = array($values);
			return $this;
		}

		// count params
		$count = 0;
		$values = array();

		foreach( func_get_args() as $value )
		{
			if( !is_array($value) )
			{
				$count++;
				$values[] = $this->parent->escape($value);
			}
			else
			{
				foreach( $value as $_key => $_value )
				{
					$count++;

					if( is_numeric($_key) )
						$values[] = $this->parent->escape($_value);
					else
						$values[$_key] = $this->parent->escape($_value);
				}
			}
		}

		// check if params count match fields
		if( $count == $columnCount )
		{
			$this->query["values"][] = $values;
		}

		// check if fields names are in values
		else if( $count > $columnCount )
		{
			$_values = array();
			
			foreach( $this->query["columns"] as $column )
			{
				if( !isset($values[$column]) ) break;
				$_values[] = $values[$column];
			}
			
			if( count($_values) == $columnCount )
				$this->query["values"][] = $_values;
			else
				$this->parent->createError(self::MESSAGE_VALUES_MISSING);
		}

		// values doesn't match
		else
		{
			$this->parent->createError(self::MESSAGE_VALUES_COUNT);
		}

		return $this;
	}

	/**
	 * add 'set' to insert query
	 * @param string|array $column
	 * @param string $replace
	 * @return mysqlClass_Insert
	 */
	public function set($column, $replace = NULL)
	{
		if( count($this->query["columns"]) > 0 || !is_null($this->query["select"]) )
		{
			$this->parent->createError(self::MESSAGE_AFTER_SET);
			return $this;
		}

		if( is_null($replace) )
		{
			if( is_array($column) )
			{
				foreach( $column as $_column => $_replace )
				{
					if( !is_numeric($_column) )
						if( strpos($_column, "?") === false && strpos($column, "=") === false )
							$this->query["set"][] = $_column . " = " . $this->parent->escape($_replace);
						else
							$this->query["set"][] = str_replace("?", $this->parent->escape($_replace), $_column);
					else
						$this->query["set"][] = $_replace;
				}
			}
			else
			{
				$this->query["set"][] = $column;
			}
		}
		else
		{
			if( strpos($column, "?") === false && strpos($column, "=") === false )
				$this->query["set"][] = $column . " = " . $this->parent->escape($replace);
			else
				$this->query["set"][] = str_replace("?", $this->parent->escape($replace), $column);
		}

		return $this;
	}

	/**
	 * add a select statement to insert query
	 * @param string|mysqlClass_Select $subSelect
	 * @return mysqlClass_Insert
	 */
	public function select($subSelect)
	{
		if( count($this->query["values"]) > 0 || count($this->query["set"]) > 0 )
		{
			$this->parent->createError(self::MESSAGE_AFTER_SELECT);
			return $this;
		}

		if( is_string($subSelect) || $subSelect instanceof mysqlClass_Select )
		{
			$this->query["select"] = $subSelect;
		}

		return $this;
	}



	/*
	** build
	*/



	/**
	 * build mysql insert query string
	 * @param integer $formatOffset
	 * @return string
	 */
	public function build($formatOffset = 0)
	{
		$this->formatOffset += $formatOffset;
		$offset = str_pad("", $this->formatOffset, " ");

		// end if no table is set
		if( is_null($this->query["table"]) ) return NULL;

		$query = $this->format ? $offset . "REPLACE " : "REPLACE ";

		// low priority
		if( $this->query["low"] ) $query .= $this->format ? "\n" . $offset . "    LOW_PRIORITY " : "LOW_PRIORITY ";

		// delayed
		if( $this->query["delayed"] ) $query .= $this->format ? "\n" . $offset . "    DELAYED " : "DELAYED ";

		// ignore
		if( $this->query["ignore"] ) $query .= $this->format ? "\n" . $offset . "    IGNORE \n" : "IGNORE ";

		$query .= $this->format ? $offset . "INTO " : "INTO ";

		// table
		$query .= $this->format ? "\n" . $offset . "    " . $this->query["table"] . "\n" : $this->query["table"] . " ";

		// columns
		if( !empty($this->query["columns"]) )
		{
			if( $this->format )
			{
				$query .= $offset . "    (";

				for( $i = 0; $i < count($this->query["columns"]); $i++ )
				{
					$query .= " " . $this->query["columns"][$i];
					$query .= $i < count($this->query["columns"]) - 1 ? "," : NULL;
				}

				$query .= " ) \n";
			}
			else
				$query .= "(" . join(",", $this->query["columns"]) . ") ";

			if( !empty($this->query["values"]) )
			{
				$query .= $this->format ? "VALUES\n" : "VALUES ";

				for( $i = 0; $i < count($this->query["values"]); $i++ )
				{
					if( $this->format )
					{
						$query .= $offset . "    " . "( " . join(", ", $this->query["values"][$i]) . " )";
						$query .= $i < count($this->query["values"]) - 1 ? ",\n" : NULL;
					}
					else
					{
						$query .= "(" . join(",", $this->query["values"][$i]) . ")";
						$query .= $i < count($this->query["values"]) - 1 ? "," : NULL;
					}
				}

				$query .= $this->format ? "\n" : " ";
			}
		}

		// set
		else if( !empty($this->query["set"]) )
		{
			$query .= $this->format ? "SET\n" : "SET ";

			for( $i = 0; $i < count($this->query["set"]); $i++ )
			{
				if( $this->format )
				{
					$query .= $offset . "    " . $this->query["set"][$i] . "";
					$query .= $i < count($this->query["set"]) - 1 ? ", \n" : NULL;
				}
				else
				{
					$query .= "(" . join(",", $this->query["set"]) . ")";
					$query .= $i < count($this->query["values"]) - 1 ? "," : NULL;
				}
			}

			$query .= $this->format ? "\n" : " ";
		}

		// select		
		if( !is_null($this->query["select"]) )
		{
			if( is_string($this->query["select"]) )
			{
				if( $this->format )
					$query .= $offset . "    ( " . $this->query["select"] . " ) \n";
				else
					$query .= "(" . $this->query["select"] . ") ";
			}
			else
			{
				$select = $this->query["select"];

				if( $select instanceof mysqlClass_Select )
				{
					if( $this->format )
						$query .= $offset . "    (" . trim($select->build($this->formatOffset + 4)) . ") \n";
					else
						$query .= "(" . $select->build() . ") ";
				}
			}
		}

		return $query;
	}
}

/**
 * Frosted MySQL Library Select Query Class
 * - - - - - - - - - -
 * Add "SELECT" functionality to Frosted MySQL Library and will not work without them.
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
class mysqlClass_Select extends mysqlClass_Abstract implements mysqlClass_Queries
{
	/**
	 * reset select class
	 * @param boolean $format
	 * @return mysqlClass_Select
	 */
	public function resetQuery($format)
	{
		parent::resetQuery($format);

		$this->query["all"]       = false;
		$this->query["distinct"]  = false;
		$this->query["row"]       = false;
		$this->query["high"]      = false;
		$this->query["straight"]  = false;
		$this->query["columns"]   = array();
		$this->query["from"]      = array();
		$this->query["join"]      = array();
		$this->query["where"]     = array();
		$this->query["group"]     = array();
		$this->query["rollup"]    = false;
		$this->query["having"]    = array();
		$this->query["order"]     = array();
		$this->query["limit"]     = NULL;
		$this->query["procedure"] = NULL;
		$this->query["update"]    = false;
		$this->query["lock"]      = false;
		$this->query["union"]     = NULL;
		
		return $this;
	}



	/*
	** query related
	*/



	/**
	 * add 'all' to query
	 * @param boolean $all
	 * @return mysqlClass_Select
	 */
	public function all($all = true)
	{
		if( (bool)$all )
		{
			$this->query["distinct"] = false;
			$this->query["row"] = false;
		}

		$this->query["all"] = (bool)$all;
		return $this;
	}

	/**
	 * add 'distinct' to query
	 * @param boolean $distinct
	 * @return mysqlClass_Select
	 */
	public function distinct($distinct = true)
	{
		if( (bool)$distinct )
		{
			$this->query["all"] = false;
			$this->query["row"] = false;
		}

		$this->query["distinct"] = (bool)$distinct;
		return $this;
	}

	/**
	 * add 'distinct row' to query
	 * @param boolean $distinctRow
	 * @return mysqlClass_Select
	 */
	public function distinctRow($distinctRow = true)
	{
		if( (bool)$distinctRow )
		{
			$this->query["all"] = false;
			$this->query["distinct"] = false;
		}

		$this->query["row"] = (bool)$distinctRow;
		return $this;
	}

	/**
	 * add 'high priority' to query
	 * @param boolean $high
	 * @return mysqlClass_Select
	 */
	public function highPriority($high = true)
	{
		$this->query["high"] = (bool)$high;
		return $this;
	}

	/**
	 * add 'straight join' to query
	 * @param boolean $straightJoin
	 * @return mysqlClass_Select
	 */
	public function straight($straightJoin = true)
	{
		$this->query["straight"] = (bool)$straightJoin;
		return $this;
	}
	
	/**
	 * add columns to select
	 * @param string|array $column
	 * @return mysqlClass_Select
	 */
	public function columns($column = "*")
	{
		// only one string is given
		if( func_num_args() == 1 )
		{
			// string
			if( is_string($column) )
			{
				$this->query["columns"][] = $column;
			}
			
			// array
			else if( is_array($column) )
			{
				foreach( $column as $field => $name )
				{
					if( !is_numeric($field) )
					{
						if( $name instanceof mysqlClass_Select )
						{
							$this->query["columns"][] = array($name, $field);
						}
						else
							$this->query["columns"][] = $field . " AS " . $name;
					}
					else
					{
						if( $name instanceof mysqlClass_Select )
							$this->query["columns"][] = $name;
						else
							$this->columns($name);
					}
				}
			}
			
			// sub select
			else if( $column instanceof mysqlClass_Select )
			{
				$this->query["columns"][] = $column;
			}
			
			return $this;
		}
		
		foreach( func_get_args() as $param )
		{
			if( is_string($param) )
				$this->query["columns"][] = $param;
			else
				$this->columns($param);
		}
		
		return $this;
	}

	/**
	 * add 'from' to query
	 * @param string|array $table,...
	 * @return mysqlClass_Select
	 */
	public function from($table)
	{
		// only one string is set
		if( func_num_args() == 1 && is_string($table) )
		{
			$this->query["from"][] = $table;
			return $this;
		}

		// add all tables to query
		foreach( func_get_args() as $param )
		{
			if( !is_array($param) )
				$this->query["from"][] = $param;
			else
				foreach( $param as $database => $name )
				{
					if( !is_numeric($database) )
						$this->query["from"][] = $database . " AS " . $name;
					else
						$this->query["from"][] = $name;
				}
		}

		return $this;
	}

	/**
	 * add join to query
	 * @param string $type
	 * @param array $tables
	 * @return mysqlClass_Select
	 */
	private function addJoin($type, $tables)
	{
		$join = array("type" => $type, "tables" => array(), "on" => array(), "using" => array());
		
		// format tables
		foreach( $tables as $_tables )
			if( is_array($_tables) )
				foreach( $_tables as $table => $name )
					if( !is_numeric($table) )
						$join["tables"][] = $table . " AS " . $name;
					else
						$join["tables"][] = $name;
			else
				$join["tables"][] = $_tables;
		
		// add join
		$this->query["join"][] = $join;
		
		return $this;
	}

	/**
	 * add 'join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function join($table)
	{
		// prevent php debug notification
		if( $table );
		
		return $this->addJoin("JOIN", func_get_args());
	}

	/**
	 * add 'join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function straightJoin($table)
	{
		// prevent php debug notification
		if( $table );
		
		return $this->addJoin("STRAIGHT_JOIN", func_get_args());
	}

	/**
	 * add 'left join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function leftJoin($table)
	{
		// prevent php debug notification
		if( $table );
		
		return $this->addJoin("LEFT JOIN", func_get_args());
	}

	/**
	 * add 'right join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function rightJoin($table)
	{
		// prevent php debug notification
		if( $table );
		
		return $this->addJoin("RIGHT JOIN", func_get_args());
	}

	/**
	 * add 'inner join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function innerJoin($table)
	{
		// prevent php debug notification
		if( $table );
		
		return $this->addJoin("INNER JOIN", func_get_args());
	}

	/**
	 * add 'cross join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function crossJoin($table)
	{
		// prevent php debug notification
		if( $table );

		return $this->addJoin("CROSS JOIN", func_get_args());
	}

	/**
	 * add 'left outer join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function leftOuterJoin($table)
	{
		// prevent php debug notification
		if( $table );

		return $this->addJoin("LEFT OUTER JOIN", func_get_args());
	}

	/**
	 * add 'right outer join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function rightOuterJoin($table)
	{
		// prevent php debug notification
		if( $table );

		return $this->addJoin("RIGHT OUTER JOIN", func_get_args());
	}

	/**
	 * add 'natural join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function naturalJoin($table)
	{
		// prevent php debug notification
		if( $table );

		return $this->addJoin("NATURAL JOIN", func_get_args());
	}

	/**
	 * add 'natural left join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function naturalLeftJoin($table)
	{
		// prevent php debug notification
		if( $table );

		return $this->addJoin("NATURAL LEFT JOIN", func_get_args());
	}

	/**
	 * add 'natural left outer join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function naturalLeftOuterJoin($table)
	{
		// prevent php debug notification
		if( $table );

		return $this->addJoin("NATURAL LEFT OUTER JOIN", func_get_args());
	}

	/**
	 * add 'natural right join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function naturalRightJoin($table)
	{
		// prevent php debug notification
		if( $table );

		return $this->addJoin("NATURAL RIGHT JOIN", func_get_args());
	}

	/**
	 * add 'natural right outer join' to query
	 * @param string $table
	 * @return mysqlClass_Select
	 */
	public function naturalRightOuterJoin($table)
	{
		// prevent php debug notification
		if( $table );

		return $this->addJoin("NATURAL RIGHT OUTER JOIN", func_get_args());
	}

	/**
	 * add 'on' to last join in query
	 * @param string $on
	 * @param string $replace
	 * @param string $nextRelation
	 * @return mysqlClass_Select
	 */
	public function on($on, $replace = NULL, $nextRelation = mysqlClass::JOIN_AND)
	{
		$last = count($this->query["join"]) - 1;
		
		if( $last >= 0 )
		{
			if( $replace != NULL )
			{
				$this->query["join"][$last]["on"][] = str_replace("?", $this->parent->escape($replace), $on);
				$this->query["join"][$last]["on"][] = $nextRelation == mysqlClass::JOIN_OR ? mysqlClass::JOIN_OR : mysqlClass::JOIN_AND;
			}
			else
			{
				$this->query["join"][$last]["on"][] = $on;
				$this->query["join"][$last]["on"][] = $nextRelation == mysqlClass::JOIN_OR ? mysqlClass::JOIN_OR : mysqlClass::JOIN_AND;
			}
		}
		
		return $this;
	}
	
	/**
	 * add or related 'on' to last join in query
	 * @param string $on
	 * @param string $replace
	 * @param string $nextRelation
	 * @return mysqlClass_Select
	 */
	public function orOn($on, $replace = NULL, $nextRelation = mysqlClass::JOIN_AND)
	{
		$last = count($this->query["join"]) - 1;

		if( $last >= 0 )
		{
			$lastOn = count($this->query["join"][$last]["on"]) - 1;
			
			if( $lastOn >= 0 )
				$this->query["join"][$last]["on"][$lastOn] = mysqlClass::JOIN_OR;
		}

		return $this->on($on, $replace, $nextRelation);
	}

	/**
	 * add 'using' to last join in query
	 * @param string $column
	 * @return mysqlClass_Select
	 */
	public function using($column)
	{
		// prevent php debug notification
		if( $column );
		
		// get last join id
		$last = count($this->query["join"]) - 1;
		
		if( $last >= 0 )
			foreach( func_get_args() as $columns )
				if( is_array($columns) )
				{
					foreach( $columns as $column )
						if( !in_array($column, $this->query["join"][$last]["using"]) )
							$this->query["join"][$last]["using"][] = $column;
				}
				else
				{
					if( !in_array($columns, $this->query["join"][$last]["using"]) )
						$this->query["join"][$last]["using"][] = $columns;
				}
		
		return $this;
	}

	/**
	 * add 'where' to query
	 * @param string $condition
	 * @param string $replace
	 * @param string $nextRelation
	 * @return mysqlClass_Select
	 */
	public function where($condition, $replace = NULL, $nextRelation = mysqlClass::WHERE_AND)
	{
		// add condition
		if( !is_null($replace) )
		{
			if( is_array($replace) )
			{
				// escape all values
				foreach( $replace as &$value ) $value = $this->parent->escape($value);

				// format sub-query
				if( $this->format )
				{
					$condition = str_replace("ANY(?)", "\nANY\n(\n    ?\n)", $condition);
					$condition = str_replace("IN(?)", "\nIN\n(\n    ?\n)", $condition);
					$condition = str_replace("SOME(?)", "\nSOME\n(\n    ?\n)", $condition);
				}

				$glue = $this->format ? ",\n    " : ",";
				$this->query["where"][] = str_replace("?", join($glue, $replace), $condition);
			}
			else if( $replace instanceof mysqlClass_Select )
				$this->query["where"][] = array($condition, $replace);
			else
				$this->query["where"][] = str_replace("?", $this->parent->escape($replace), $condition);
		}
		else
			$this->query["where"][] = $condition;
		
		// add relation
		if( strtoupper($nextRelation) == mysqlClass::WHERE_OR )
			$this->query["where"][] = mysqlClass::WHERE_OR;
		else
			$this->query["where"][] = mysqlClass::WHERE_AND;

		return $this;
	}

	/**
	 * add 'or' related 'where' to query
	 * @param string $condition
	 * @param string $replace
	 * @param string $nextRelation
	 * @return mysqlClass_Select
	 */
	public function orWhere($condition, $replace = NULL, $nextRelation = mysqlClass::WHERE_AND)
	{
		if( !empty($this->query["where"]) )
			$this->query["where"][(count($this->query["where"]) - 1)] = mysqlClass::WHERE_OR;

		return $this->where($condition, $replace, $nextRelation);
	}

	/**
	 * add 'group by' to query
	 * @param string $field
	 * @param string $order
	 * @return mysqlClass_Select
	 */
	public function groupBy($field, $order = mysqlClass::GROUP_ASC)
	{
		if( strtoupper($order) == mysqlClass::GROUP_DESC )
			$this->query["group"][] = $field . " " . mysqlClass::GROUP_DESC;
		else
			$this->query["group"][] = $field . " " . mysqlClass::GROUP_ASC;

		return $this;
	}

	/**
	 * alias of 'groupBy'
	 * @param string $field
	 * @param string $order
	 * @return mysqlClass_Select
	 */
	public function group($field, $order = mysqlClass::GROUP_ASC)
	{
		return $this->groupBy($field, $order);
	}

	/**
	 * add 'with rollup' to query
	 * @param boolean $rollup
	 * @return mysqlClass_Select
	 */
	public function withRollup($rollup = true)
	{
		$this->query["rollup"] = (bool)$rollup;
		return $this;
	}

	/**
	 * add 'having' to group
	 * @param string $condition
	 * @param string $replace
	 * @param string $nextRelation
	 * @return mysqlClass_Select
	 */
	public function having($condition, $replace = NULL, $nextRelation = mysqlClass::HAVING_AND)
	{
		// add condition
		if( !is_null($replace) )
		{
			if( is_array($replace) )
			{
				foreach( $replace as &$value )
					$value = $this->parent->escape($value);

				if( $this->format )
					$condition = str_replace("IN(?)", "\nIN\n(\n    ?\n)", $condition);
				
				$glue = $this->format ? ",\n    " : ",";
				$this->query["having"][] = str_replace("?", join($glue, $replace), $condition);
			}
			else if( $replace instanceof mysqlClass_Select )
			{
				$this->query["having"][] = array($condition, $replace);
			}
			else
				$this->query["having"][] = str_replace("?", $this->parent->escape($replace), $condition);
		}
		else
			$this->query["having"][] = $condition;

		// add relation
		if( strtoupper($nextRelation) == mysqlClass::HAVING_OR )
			$this->query["having"][] = mysqlClass::HAVING_OR;
		else
			$this->query["having"][] = mysqlClass::HAVING_AND;

		return $this;
	}
	
	/**
	 * add to previous 'or' related 'having' to query
	 * @param string $condition
	 * @param string $replace
	 * @return mysqlClass_Select
	 */
	public function orHaving($condition, $replace = NULL)
	{
		if( !empty($this->query["having"]) )
		{
			$this->query["having"][(count($this->query["having"]) - 1)] = mysqlClass::HAVING_OR;
		}

		return $this->having($condition, $replace, mysqlClass::HAVING_AND);
	}

	/**
	 * add 'order' to query
	 * @param string $field
	 * @param string $order
	 * @return mysqlClass_Select
	 */
	public function orderBy($field, $order = mysqlClass::ORDER_ASC)
	{
		if( strtoupper($order) == mysqlClass::ORDER_DESC )
			$this->query["order"][] = $field . " " . mysqlClass::ORDER_DESC;
		else
			$this->query["order"][] = $field . " " . mysqlClass::ORDER_ASC;

		return $this;
	}

	/**
	 * alias of 'orderBy'
	 * @param string $field
	 * @param string $order
	 * @return mysqlClass_Select
	 */
	public function order($field, $order = mysqlClass::ORDER_ASC)
	{
		return $this->orderBy($field, $order);
	}

	/**
	 * add 'limit' to query
	 * @param integer $limit
	 * @param integer $offset
	 * @return mysqlClass_Select
	 */
	public function limit($limit, $offset = NULL)
	{
		$this->query["limit"] = $limit;

		if( !is_null($offset) && is_numeric($offset) )
		{
			$this->query["limit"] = $offset . ", " . $limit;
		}

		return $this;
	}

	/**
	 * add 'procedure' to query
	 * @param string $procedure
	 * @param string|array $arguments
	 * @return mysqlClass_Select
	 */
	public function procedure($procedure, $arguments = array())
	{
		// prevent php debug notification
		if( $arguments );
		
		if( func_num_args() == 1 )
		{
			$this->query["procedure"] = $procedure;
		}
		else if( func_num_args() == 2 )
		{
			if( is_array($arguments) )
				$this->query["procedure"] = $procedure . "(" . join(",", $arguments) . ")";
			else
				$this->query["procedure"] = $procedure . "(" . $arguments . ")";
		}
		else if( func_num_args() >= 2 )
		{
			$arguments = func_get_args();
			$procedure = array_shift($arguments);
			$list = array();
			
			foreach( $arguments as $argument )
			{
			 	if( is_array($argument) )
					$list = array_merge($list, $argument);
				else
					$list[] = $argument;
			}

			$this->query["procedure"] = $procedure . "(" . join(",", $list) . ")";
		}
		
		return $this;
	}

	/**
	 * add 'with rollup' to query
	 * @param boolean $update
	 * @return mysqlClass_Select
	 */
	public function forUpdate($update = true)
	{
		if( (bool)$update )
		{
			$this->query["lock"] = false;
		}

		$this->query["update"] = (bool)$update;
		return $this;
	}

	/**
	 * add 'with rollup' to query
	 * @param boolean $lock
	 * @return mysqlClass_Select
	 */
	public function lockInShareMode($lock = true)
	{
		if( (bool)$lock )
		{
			$this->query["update"] = false;
		}

		$this->query["lock"] = (bool)$lock;
		return $this;
	}
	
	/**
	 * add a union select to query
	 * @param mysqlClass_Select|string $select
	 * @return mysqlClass_Select
	 */
	public function union($select)
	{
		$this->query["union"] = $select;
		return $this;
	}



	/*
	** build
	*/



	/**
	 * build mysql select query string
	 * @param integer $formatOffset
	 * @return string
	 */
	public function build($formatOffset = 0)
	{
		$this->formatOffset += $formatOffset;
		$offset = str_pad("", $this->formatOffset, " ");

		// end if no table is set
		if( empty($this->query["from"]) ) return NULL;

		$query = $this->format ? $offset . "SELECT " : "SELECT ";

		// all
		if( $this->query["all"] ) $query .= $this->format ? "\n" . $offset . "    ALL " : "ALL ";

		// distinct
		if( $this->query["distinct"] ) $query .= $this->format ? "\n" . $offset . "    DISTINCT " : "DISTINCT ";

		// distinct row
		if( $this->query["row"] ) $query .= $this->format ? "\n" . $offset . "    DISTINCTROW " : "DISTINCTROW ";

		// high priority
		if( $this->query["high"] ) $query .= $this->format ? "\n" . $offset . "    HIGH_PRIORITY " : "HIGH_PRIORITY ";

		// straight
		if( $this->query["straight"] ) $query .= $this->format ? "\n" . $offset . "    STRAIGHT_JOIN " : "STRAIGHT_JOIN ";

		$query .= $this->format ? $offset . "\n" : NULL;

		// columns
		if( !empty($this->query["columns"]) )
		{
			if( $this->format )
			{
				for( $i = 0; $i < count($this->query["columns"]); $i++ )
				{
					$value = $this->query["columns"][$i];

					if( is_array($value) )
					{
						if( $value[0] instanceof mysqlClass_Select )
						{
							$value[0] = $value[0]->build($this->formatOffset + 4);
							$value[0] = trim($value[0]);
						}

						$this->query["columns"][$i] = "(" . $value[0] . ") AS " . $value[1];
					}
					else if( $value instanceof mysqlClass_Select )
					{
						$this->query["columns"][$i]  = "(" . $value->build($this->formatOffset + 4) . ")";
					}

					$query .= $offset . "    " . $this->query["columns"][$i];
					$query .= $i < count($this->query["columns"]) - 1 ? "," : NULL;
					$query .= " \n";
				}
			}
			else
			{
				for( $i = 0; $i < count($this->query["columns"]); $i++ )
				{
					if( is_array($this->query["columns"][$i]) )
					{
						$select = $this->query["columns"][$i][0];

						if( $select instanceof mysqlClass_Select )
							$select = $select->build();

						$this->query["columns"][$i]  = "(" . $select . ") AS " . $this->query["columns"][$i][1];
					}
					else if( $this->query["columns"][$i] instanceof mysqlClass_Select )
					{
						$select = $this->query["columns"][$i];

						if( $select instanceof mysqlClass_Select )
							$select = $select->build();

						$this->query["columns"][$i]  = "(" . $select . ")";
					}
				}

				$query .= join(",", $this->query["columns"]) . " ";
			}
		}
		else
		{
			$query .= $this->format ? $offset . "    *\n" : "* ";
		}

		// from
		if( !empty($this->query["from"]) )
		{
			if( $this->format )
			{
				$query .= $offset . "FROM \n";

				for( $i = 0; $i < count($this->query["from"]); $i++ )
				{
					$query .= $offset . "    " . $this->query["from"][$i];
					$query .= $i < count($this->query["from"]) - 1 ? "," : NULL;
					$query .= " \n";
				}
			}
			else
			{
				$query .= "FROM " . join(",", $this->query["from"]) . " ";
			}
		}

		// join
		if( !empty($this->query["join"]) )
		{
			foreach( $this->query["join"] as $join )
			{
				$query .= $this->format ? $offset . $join["type"] . "\n" : $join["type"] . " ";

				if( $this->format )
				{
					for( $i = 0; $i < count($join["tables"]); $i++ )
					{
						$query .= $offset . "    " . $join["tables"][$i];
						$query .= $i < count($join["tables"]) - 1 ? "," : NULL;
						$query .= " \n";
					}

					if( !empty($join["on"]) )
					{
						$query .= $offset . "ON\n";

						for( $i = 0; $i < count($join["on"]); $i = $i + 2 )
						{
							$query .= $offset . "    " . $join["on"][$i];
							$query .= $i < count($join["on"]) - 2 ? " \n" . $offset . $join["on"][$i + 1] . " " : NULL;
							$query .= " \n";
						}
					}
					else if( !empty($join["using"]) )
					{
						$query .= $offset . "USING\n";
						$query .= $offset . "(\n";

						for( $i = 0; $i < count($join["using"]); $i++ )
						{
							$query .= $offset . "    " . $join["using"][$i];
							$query .= $i < count($join["using"]) - 1 ? "," : NULL;
							$query .= " \n";
						}

						$query .= $offset . ")\n";
					}
				}
				else
				{
					// tables
					$query .= join(",", $join["tables"]) . " ";

					// on
					if( !empty($join["on"]) )
					{
						$on  = array_slice($join["on"], 0, -1);
						$query .= "ON " . join(" ", $on) . " ";
					}

					// using
					else
						$query .= "USING (" . join(",", $join["using"]) . ") ";
				}
			}
		}

		// where
		if( !empty($this->query["where"]) )
		{
			if( $this->format )
			{
				$query .= $offset . "WHERE \n";

				for( $i = 0; $i < count($this->query["where"]); $i = $i + 2 )
				{
					if( is_array($this->query["where"][$i]) )
					{
						$select = $this->query["where"][$i][1];

						if( $select instanceof mysqlClass_Select )
						{
							$select = $select->build($this->formatOffset + 4);
							$select = trim($select);
						}

						$query .= $offset . "    " . str_replace("?", "\n" . $offset . "    (" . $select . ")", $this->query["where"][$i][0]);
						$query .= $i < count($this->query["where"]) - 2 ? " \n" . $offset . $this->query["where"][$i + 1] . " " : NULL;
						$query .= " \n";
					}
					else
					{
						$query .= $offset . "    " . $this->query["where"][$i];
						$query .= $i < count($this->query["where"]) - 2 ? " \n" . $offset . $this->query["where"][$i + 1] . " " : NULL;
						$query .= " \n";
					}
				}
			}
			else
			{
				for( $i = 0; $i < count($this->query["where"]); $i = $i + 2 )
				{
					if( is_array($this->query["where"][$i]) )
					{
						$select = $this->query["where"][$i][1];

						if( $select instanceof mysqlClass_Select )
							$select = $select->build();

						$this->query["where"][$i]  = str_replace("?", "(" . $select . ")", $this->query["where"][$i][0]);
					}
				}

				$where  = array_slice($this->query["where"], 0, -1);
				$query .= "WHERE " . join(" ", $where) . " ";
			}
		}

		// group by
		if( !empty($this->query["group"]) )
		{
			if( $this->format )
			{
				$query .= $offset . "GROUP BY \n";

				for( $i = 0; $i < count($this->query["group"]); $i++ )
				{
					$query .= $offset . "    " . $this->query["group"][$i];
					$query .= $i < count($this->query["group"]) - 1 ? "," : NULL;
					$query .= " \n";
				}
			}
			else
			{
				$query .= "GROUP BY " . join(",", $this->query["group"]) . " ";
			}

			// rollup
			if( $this->query["rollup"] )
				$query .= $this->format ? "WITH ROLLUP \n" : "WITH ROLLUP ";

			// having
			if( !empty($this->query["having"]) )
			{
				if( $this->format )
				{
					$query .= $offset . "HAVING \n";

					for( $i = 0; $i < count($this->query["having"]); $i = $i + 2 )
					{
						if( is_array($this->query["having"][$i]) )
						{
							$select = $this->query["having"][$i][1];

							if( $select instanceof mysqlClass_Select )
							{
								$select = $select->build($this->formatOffset + 4);
								$select = trim($select);
							}

							$query .= $offset . "    " . str_replace("?", "\n" . $offset . "    (" . $select . ")", $this->query["having"][$i][0]);
							$query .= $i < count($this->query["having"]) - 2 ? " \n" . $this->query["having"][$i + 1] . " " : NULL;
							$query .= " \n";
						}
						else
						{
							$query .= $offset . "    " . $this->query["having"][$i];
							$query .= $i < count($this->query["having"]) - 2 ? " \n" . $this->query["having"][$i + 1] . " " : NULL;
							$query .= " \n";
						}
					}
				}
				else
				{
					for( $i = 0; $i < count($this->query["having"]); $i = $i + 2 )
					{
						if( is_array($this->query["having"][$i]) )
						{
							$select = $this->query["having"][$i][1];

							if( $select instanceof mysqlClass_Select )
								$select = $select->build();

							$this->query["having"][$i]  = str_replace("?", "(" . $select . ")", $this->query["having"][$i][0]);
							$this->query["having"][$i] .= $i < count($this->query["having"]) - 2 ? " " . $this->query["having"][$i + 1] . " " : NULL;
						}
					}

					$having = array_slice($this->query["having"], 0, -1);
					$query .= "HAVING " . join(" ", $having) . " ";
				}
			}
		}

		// order
		if( !empty($this->query["order"]) )
		{
			if( $this->format )
			{
				$query .= $offset . "ORDER BY \n";

				for( $i = 0; $i < count($this->query["order"]); $i++ )
				{
					$query .= $offset . "    " . $this->query["order"][$i];
					$query .= $i < count($this->query["order"]) - 1 ? "," : NULL;
					$query .= " \n";
				}
			}
			else
			{
				$query .= "ORDER BY " . join(",", $this->query["order"]) . " ";
			}
		}

		// limit
		if( !empty($this->query["limit"]) )
		{
			if( $this->format )
				$query .= $offset . "LIMIT \n" . $offset  . "    " . $this->query["limit"] . "\n";
			else
				$query .= "LIMIT " . $this->query["limit"] . " ";
		}

		// procedure
		if( !empty($this->query["procedure"]) )
		{
			if( $this->format )
				$query .= $offset . "PROCEDURE \n" . $offset  . "    " . $this->query["procedure"] . " ";
			else
				$query .= "PROCEDURE " . $this->query["procedure"] . " ";
		}


		// for update
		if( $this->query["update"] )
			$query .= $this->format ? $offset . "FOR UPDATE \n" : "FOR UPDATE ";

		// lock in share mode
		if( $this->query["lock"] )
			$query .= $this->format ? $offset . "LOCK IN SHARE MODE \n" : "LOCK IN SHARE MODE ";

		// union
		if( !is_null($this->query["union"]) )
		{
			$select = $this->query["union"];

			if( is_string($select) )
				if( $this->format )
					$query = "(" . $query. ") \nUNION \n(\n" . trim($select) . ")";
				else
					$query = "(" . $query. ") UNION (" . $select . ")";

			if( $select instanceof mysqlClass_Select )
				if( $this->format )
					$query = "(" . $query. ") \nUNION \n(\n" . trim($select->build()) . ")";
				else
					$query = "(" . $query. ") UNION (" . $select->build() . ")";
		}

		return $query;
	}
}

/**
 * Frosted MySQL Library Truncate Query Class
 * - - - - - - - - - -
 * Add "TRUNCATE" functionality to Frosted MySQL Library and will not work without them.
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
class mysqlClass_Truncate extends mysqlClass_Abstract implements mysqlClass_Queries
{
	/**
	 * reset update class
	 * @param boolean $format
	 * @return mysqlClass_Truncate
	 */
	public function resetQuery($format)
	{
		parent::resetQuery($format);
		$this->query["table"] = NULL;

		return $this;
	}



	/*
	** query related
	*/



	/**
	 * add tables to query
	 * @param string|array $table
	 * @return mysqlClass_Truncate
	 */
	public function table($table)
	{
		if( !is_array($table) )
				$this->query["table"] = $table;
		else
			foreach( $table as $database => $name )
			{
				if( !is_numeric($database) )
					$this->query["table"] = $database;
				else
					$this->query["table"] = $name;
			}
		
		return $this;
	}



	/*
	** build
	*/



	/**
	 * build mysql update query string
	 * @param integer $formatOffset
	 * @return string
	 */
	public function build($formatOffset = 0)
	{
		// end if no table is set
		if( empty($this->query["table"]) ) return NULL;

		if( $this->format )
		{
			$this->formatOffset += $formatOffset;
			$offset = str_pad("", $this->formatOffset, " ");

			return "TRUNCATE TABLE\n" . $offset . "    " . $this->query["table"];
		}

		return "TRUNCATE TABLE " . $this->query["table"];
	}
}

/**
 * Frosted MySQL Library Update Query Class
 * - - - - - - - - - -
 * Add "UPDATE" functionality to Frosted MySQL Library and will not work without them.
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
class mysqlClass_Update extends mysqlClass_Abstract implements mysqlClass_Queries
{
	/**
	 * update error messages
	 * @var string
	 */
	const MESSAGE_ORDER = "you cannot use 'order' if you update more than one table";
	const MESSAGE_LIMIT = "you cannot use 'limit' if you update more than one table";



	/*
	** public 
	*/



	/**
	 * reset update class
	 * @param boolean $format
	 * @return mysqlClass_Update
	 */
	public function resetQuery($format)
	{
		parent::resetQuery($format);

		$this->query["tables"] = array();
		$this->query["low"]    = false;
		$this->query["ignore"] = false;
		$this->query["set"]    = array();
		$this->query["where"]  = array();
		$this->query["order"]  = array();
		$this->query["limit"]  = NULL;
		
		return $this;
	}



	/*
	** query related
	*/



	/**
	 * add 'low priority' to query
	 * @param boolean $low
	 * @return mysqlClass_Update
	 */
	public function lowPriority($low = true)
	{
		$this->query["low"] = (bool)$low;
		return $this;
	}

	/**
	 * add 'ignore' to query
	 * @param boolean $ignore
	 * @return mysqlClass_Update
	 */
	public function ignore($ignore = true)
	{
		$this->query["ignore"] = (bool)$ignore;
		return $this;
	}

	/**
	 * add tables to query
	 * @param string|array $table
	 * @return mysqlClass_Update
	 */
	public function table($table)
	{
		// only one string is set
		if( func_num_args() == 1 && is_string($table) )
		{
			$this->query["tables"][] = $table;
			return $this;
		}

		// add all tables to query
		foreach( func_get_args() as $param )
		{
			if( !is_array($param) )
				$this->query["tables"][] = $param;
			else
				foreach( $param as $database => $name )
				{
					if( !is_numeric($database) )
						$this->query["tables"][] = $database . " AS " . $name;
					else
						$this->query["tables"][] = $name;
				}
		}
		
		return $this;
	}
	
	/**
	 * add 'set' to query
	 * @param string|array $column
	 * @param string $replace
	 * @return mysqlClass_Update
	 */
	public function set($column, $replace = NULL)
	{
		if( is_null($replace) )
		{
			if( is_array($column) )
			{
				foreach( $column as $_column => $_replace )
				{
					if( !is_numeric($_column) )
						$this->query["set"][] = str_replace("?", $this->parent->escape($_replace), $_column);
					else
						$this->query["set"][] = $_replace;
				}
			}
			else
			{
				$this->query["set"][] = $column;
			}
		}
		else
		{
			$this->query["set"][] = str_replace("?", $this->parent->escape($replace), $column);
		}
		
		return $this;
	}

	/**
	 * add 'where' to query
	 * @param string $condition
	 * @param string $replace
	 * @param string $nextRelation
	 * @return mysqlClass_Update
	 */
	public function where($condition, $replace = NULL, $nextRelation = mysqlClass::WHERE_AND)
	{
		// add condition
		if( !is_null($replace) )
		{
			if( is_array($replace) )
			{
				// escape all values
				foreach( $replace as &$value ) $value = $this->parent->escape($value);
				
				// format sub-query
				if( $this->format )
				{
					$condition = str_replace("ANY(?)", "\nANY\n(\n    ?\n)", $condition);
					$condition = str_replace("IN(?)", "\nIN\n(\n    ?\n)", $condition);
					$condition = str_replace("SOME(?)", "\nSOME\n(\n    ?\n)", $condition);
				}

				$glue = $this->format ? ",\n    " : ",";
				$this->query["where"][] = str_replace("?", join($glue, $replace), $condition);
			}
			else if( $replace instanceof mysqlClass_Select )
				$this->query["where"][] = array($condition, $replace);
			else
				$this->query["where"][] = str_replace("?", $this->parent->escape($replace), $condition);
		}
		else
			$this->query["where"][] = $condition;

		// add next relation
		if( strtoupper($nextRelation) == mysqlClass::WHERE_OR )
			$this->query["where"][] = mysqlClass::WHERE_OR;
		else
			$this->query["where"][] = mysqlClass::WHERE_AND;

		return $this;
	}

	/**
	 * add 'or' related 'where' to query
	 * @param string $condition
	 * @param string $replace
	 * @param string $nextRelation
	 * @return mysqlClass_Update
	 */
	public function orWhere($condition, $replace = NULL, $nextRelation = mysqlClass::WHERE_AND)
	{
		if( !empty($this->query["where"]) )
			$this->query["where"][(count($this->query["where"]) - 1)] = mysqlClass::WHERE_OR;
		
		return $this->where($condition, $replace, $nextRelation);
	}
	
	/**
	 * add 'order' to query
	 * @param string $field
	 * @param string $order
	 * @return mysqlClass_Update
	 */
	public function orderBy($field, $order = mysqlClass::ORDER_ASC)
	{
		if( count($this->query["tables"]) >= 2 )
		{
			$this->parent->createError(self::MESSAGE_ORDER);
			return $this;
		}

		if( strtoupper($order) == mysqlClass::ORDER_DESC )
			$this->query["order"][] = $field . " " . mysqlClass::ORDER_DESC;
		else
			$this->query["order"][] = $field . " " . mysqlClass::ORDER_ASC;

		return $this;
	}

	/**
	 * alias of 'orderBy'
	 * @param string $field
	 * @param string $order
	 * @return mysqlClass_Update
	 */
	public function order($field, $order = mysqlClass::ORDER_ASC)
	{
		return $this->orderBy($field, $order);
	}

	/**
	 * add 'limit' to query
	 * @param integer $limit
	 * @return mysqlClass_Update
	 */
	public function limit($limit)
	{
		if( count($this->query["tables"]) >= 2 )
		{
			$this->parent->createError(self::MESSAGE_LIMIT);
			return $this;
		}

		$this->query["limit"] = $limit;

		return $this;
	}



	/*
	** build
	*/



	/**
	 * build mysql update query string
	 * @param integer $formatOffset
	 * @return string
	 */
	public function build($formatOffset = 0)
	{
		$this->formatOffset += $formatOffset;
		$offset = str_pad("", $this->formatOffset, " ");

		// end if no table is set
		if( empty($this->query["tables"]) ) return NULL;

		$query = $this->format ? $offset . "UPDATE " : "UPDATE ";

		// low priority
		if( $this->query["low"] ) $query .= "LOW_PRIORITY ";

		// ignore
		if( $this->query["ignore"] ) $query .= "IGNORE ";

		$query .= $this->format ? $offset . "\n" : NULL;

		// tables
		if( count($this->query["tables"]) == 1 )
			$query .= $this->format ? $offset . "    " . $this->query["tables"][0] . "\n" : $this->query["tables"][0] . " ";
		else if( $this->format )
			for( $i = 0; $i < count($this->query["tables"]); $i++ )
			{
				$query .= $offset . "    " . $this->query["tables"][$i] . "";
				$query .= $i < count($this->query["tables"]) - 1 ? "," : NULL;
				$query .= "\n";
			}
		else
			$query .= join(",", $this->query["tables"]) . " ";

		// set
		if( !empty($this->query["set"]) )
		{
			$query .= $this->format ? "SET\n" : "SET ";

			if( $this->format )
				for( $i = 0; $i < count($this->query["set"]); $i++ )
				{
					$query .= $offset . "    " . $this->query["set"][$i] . "";
					$query .= $i < count($this->query["set"]) - 1 ? ", \n" : NULL;
				}
			else
				$query .= join(",", $this->query["set"]);

			$query .= $this->format ? "\n" : " ";
		}

		// where
		if( !empty($this->query["where"]) )
		{
			if( $this->format )
			{
				$query .= $offset . "WHERE \n";

				for( $i = 0; $i < count($this->query["where"]); $i = $i + 2 )
				{
					if( is_array($this->query["where"][$i]) )
					{
						$select = $this->query["where"][$i][1];

						if( $select instanceof mysqlClass_Select )
						{
							$select = $select->build($this->formatOffset + 4);
							$select = trim($select);
						}

						$query .= $offset . "    " . str_replace("?", "\n" . $offset . "    (" . $select . ")", $this->query["where"][$i][0]);
						$query .= $i < count($this->query["where"]) - 2 ? " \n" . $offset . $this->query["where"][$i + 1] . " " : NULL;
						$query .= " \n";
					}
					else
					{
						$query .= $offset . "    " . $this->query["where"][$i];
						$query .= $i < count($this->query["where"]) - 2 ? " \n" . $offset . $this->query["where"][$i + 1] . " " : NULL;
						$query .= " \n";
					}
				}
			}
			else
			{
				for( $i = 0; $i < count($this->query["where"]); $i = $i + 2 )
				{
					if( is_array($this->query["where"][$i]) )
					{
						$select = $this->query["where"][$i][1];

						if( $select instanceof mysqlClass_Select )
							$select = $select->build();

						$this->query["where"][$i]  = str_replace("?", "(" . $select . ")", $this->query["where"][$i][0]);
					}
				}

				$where  = array_slice($this->query["where"], 0, -1);
				$query .= "WHERE " . join(" ", $where) . " ";
			}
		}

		// add order
		if( !empty($this->query["order"]) && count($this->query["tables"]) == 1 )
		{
			if( $this->format )
			{
				$query .= $offset . "ORDER BY \n";

				for( $i = 0; $i < count($this->query["order"]); $i++ )
				{
					$query .= $offset . "    " . $this->query["order"][$i];
					$query .= $i < count($this->query["order"]) - 1 ? "," : NULL;
					$query .= " \n";
				}
			}
			else
			{
				$query .= "ORDER BY " . join(",", $this->query["order"]) . " ";
			}
		}

		// add limit
		if( !empty($this->query["limit"]) && count($this->query["tables"]) == 1 )
		{
			$query .= $this->format ? $offset . "LIMIT \n" . $offset  . "    " : "LIMIT ";
			$query .= $this->query["limit"];
		}

		return $query;
	}
}



/*
** collection classes
*/



/**
 * MySQL Collection Class
 * - - - - - - - - - -
 * Collection to further handle or filter mysql results by mysqlClass.
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
class mysqlClass_Collection implements IteratorAggregate, Countable, ArrayAccess
{	
	/**
	 * collection rows
	 * @var mysqlClass_CollectionItem array
	 */
	private $rows = array();

	/**
	 * active filters
	 * @var array
	 */
	private $filters = array();

	/**
	 * active filters
	 * @var boolean
	 */
	private $isFiltersLoaded = true;

	/**
	 * total records
	 * @var integer
	 */
	private $totalRecords = 0;

	/**
	 * logical operators
	 * @var string
	 */
	const LOGIC_EQ   = "eq";
	const LOGIC_SEQ  = "seq";
	const LOGIC_NEQ  = "neq";
	const LOGIC_SNEQ = "sneq";
	const LOGIC_LT   = "lt";
	const LOGIC_GT   = "gt";
	const LOGIC_LTE  = "lte";
	const LOGIC_GTE  = "gte";
	const LOGIC_LIKE = "like";
	const LOGIC_IN   = "in";



	/*
	** public
	*/



	/**
	 * destructor
	 */
	public function __destruct()
	{
		unset($this->rows);
		unset($this->filters);
	}



	/*
	** item getter
	*/



	/**
	 * get all collection items as array
	 * @return array
	 */
	public function getItems()
	{
		return $this->rows;
	}

	/**
	 * get collection size
	 * @return integer
	 */
	public function getSize()
	{
		return $this->totalRecords;
	}

	/**
	 * gets an specific collection item by position
	 * @param int $position
	 * @return mysqlClass_CollectionItem
	 */
	public function getItem($position)
	{
		if( $position >= 0 && $position < $this->totalRecords )
		{
			return array_slice($this->rows, $position, 1);
		}

		return false;
	}

	/**
	 * get collection item by id
	 * @param mixed $id
	 * @return mysqlClass_CollectionItem
	 */
	public function getItemById($id)
	{
		if( isset($this->rows[$id]) )
		{
			return $this->rows[$id];
		}

		return false;
	}

	/**
	 * get first item in collection
	 * @return mysqlClass_CollectionItem
	 */
	public function getFirstItem()
	{
		if( !empty($this->rows) )
		{
			return array_slice($this->rows, 0, 1);
		}
		
		return false;
	}

	/**
	 * get last item in collection
	 * @return mysqlClass_CollectionItem
	 */
	public function getLastItem()
	{
		if( !empty($this->rows) )
		{
			return array_slice($this->rows, -1, 1);
		}
		
		return false;
	}

	/**
	 * get first collection item by column value
	 * @param string $column
	 * @param mixed $value
	 * @return mysqlClass_CollectionItem
	 */
	public function getItemByColumnValue($column, $value)
	{
		foreach( $this->rows as $item )
		{
			if( $item instanceof mysqlClass_CollectionItem && $item->getData($column) === $value )
			{
				return $item;
			}
		}

		return false;
	}

	/**
	 * get all collection items by column value
	 * @param string $column
	 * @param mixed $value
	 * @return array
	 */
	public function getItemsByColumnValue($column, $value)
	{
		$items = array();
		
		foreach( $this->rows as $item )
		{
			if( $item instanceof mysqlClass_CollectionItem && $item->getData($column) === $value )
			{
				$items[] = $item;
			}
		}
		
		return $items;
	}

	/**
	 * retrieve empty collection item
	 * @return mysqlClass_CollectionItem
	 */
	public function getNewEmptyItem()
	{
		return new mysqlClass_CollectionItem();
	}

	/**
	 * get a new collection item with default data
	 * @param array $data
	 * @return mysqlClass_CollectionItem
	 */
	public function getNewItemWithData($data = array())
	{
		$item = new mysqlClass_CollectionItem();
		
		foreach( $data as $key => $value )
			if( !empty($key) )
				$item->setData($key, $value);
		
		return $item;
	}



	/*
	** getter
	*/



	/**
	 * retrieve all item ids
	 * @return array
	 */
	public function getAllIds()
	{
		return array_keys($this->rows);
	}

	/**
	 * retrieve column values from all collection items
	 * @param string $columnName
	 * @param boolean $unique
	 * @return array
	 */
	public function getColumnValues($columnName, $unique = false)
	{
		$columnValues = array();

		foreach( $this->rows as $item )
		{
			if( !($item instanceof mysqlClass_CollectionItem) )
				continue;
			
			$value = $item->getData($columnName);

			if( $unique )
			{
				if( !in_array($value, $columnValues) ) $columnValues[] = $value;
			}
			else
			{
				$columnValues[] = $value;
			}
		}

		return $columnValues;
	}
	
	/**
	 * implementation of IteratorAggregate
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->rows);
	}
	
	
	
	/*
	** public methods
	*/



	/**
	 * add an item to collection
	 * @param mysqlClass_CollectionItem $item
	 * @throws Exception
	 * @return mysqlClass_Collection
	 */
	public function addItem($item)
	{
		$itemId = $item->getData("id");
		
		if( !is_null($itemId) )
		{
			if( isset($this->rows[$itemId]) )
			{
				throw new Exception("item with the same id '" . $itemId . "' already exists");
			}

			$this->rows[$itemId] = $item;
		}
		else
		{
			$this->rows[] = $item;
		}
		
		$this->totalRecords++;
		return $this;
	}

	/**
	 * set data for all collection items
	 * @param string $key
	 * @param mixed $value
	 * @return mysqlClass_Collection
	 */
	public function setDataToAll($key, $value=null)
	{
		if( is_array($key) )
		{
			foreach( $key as $_key => $_value )
			{
				$this->setDataToAll($_key, $_value);
			}
			
			return $this;
		}
		
		foreach( $this->rows as $item )
		{
			if( $item instanceof mysqlClass_CollectionItem)
				$item->setData($key, $value);
		}

		return $this;
	}
	
	/**
	 * return the index of given object or id
	 * @param mysqlClass_CollectionItem|string|integer $object
	 * @return integer
	 */
	public function indexOf($object)
	{
		$position = 0;
		
		if( $object instanceof mysqlClass_CollectionItem )
		{
			$object = $object->getData("id");
		}
		
		foreach( $this->rows as $id => $item)
		{
			if( $id === $object )
			{
				return $position;
			}
			
			++$position;
		}
		
		return false;
	}
	
	/**
	 * check if item id exists in collection
	 * @param integer|string $id
	 * @return boolean
	 */
	public function exists($id)
	{
		return isset($this->rows[$id]);
	}

	/**
	 * check if the collection is empty or not
	 * @return boolean
	 */
	public function isEmpty()
	{
		if( $this->totalRecords == 0 )
		{
			return true;
		}
		
		return false;
	}

	/**
	 * check if given object exists in collection
	 * @param mysqlClass_CollectionItem $item
	 * @return bool
	 */
	public function contains($item)
	{
		if( $item instanceof mysqlClass_CollectionItem )
		{
			return isset($this->rows[$item->getData("id")]);
		}
		
		return false;
	}

	/**
	 * get collection item count
	 * @return integer
	 */
	public function count()
	{
		return count($this->rows);
	}

	/**
	 * alias of count
	 * @return integer
	 */
	public function length()
	{
		return $this->count();
	}

	/**
	 * serializes collection items
	 * @return string
	 */
	public function serialize()
	{
		return serialize($this->rows);
	}

	/**
	 * unserialize data and store into collection
	 * @param string $data
	 */
	public function unserialize($data)
	{
		$this->rows = unserialize($data);
	}
	
	/**
	 * remove item from collection by item id
	 * @param string|integer $id
	 * @return mysqlClass_Collection
	 */
	public function removeItemById($id)
	{
		if(isset($this->rows[$id]))
		{
			unset($this->rows[$id]);
			$this->totalRecords--;
		}
		
		return $this;
	}

	/**
	 * clear collection items
	 * @return mysqlClass_Collection
	 */
	public function clear()
	{
		$this->rows = array();
		return $this;
	}

	/**
	 * reset collection
	 * @return mysqlClass_Collection
	 */
	public function reset()
	{
		return $this->clear();
	}
	
	
	
	/*
	** filter 
	*/



	/**
	 * adds an column to filter
	 * @param string $column
	 * @param string $value
	 * @param string $logic
	 * @return mysqlClass_Collection
	 */
	public function addColumnToFilter($column, $value, $logic = self::LOGIC_EQ)
	{
		$filter = array();
		$filter["field"] = $column;
		$filter["value"] = $value;
		$filter["logic"] = strtolower($logic);
		$this->filters[] = $filter;
		
		$this->filterCollection($column, $value, $logic);
		
		return $this;
	}

	/**
	 * alias of addColumnToFilter
	 * @param string $field
	 * @param string $value
	 * @param string $logic
	 * @return mysqlClass_Collection
	 */
	public function addFieldToFilter($field, $value, $logic = self::LOGIC_EQ)
	{
		return $this->addColumnToFilter($field, $value, $logic);
	}

	/**
	 * gets an collection of filtered items
	 * @param string $field
	 * @param string $value
	 * @param string $logic
	 * @return mysqlClass_Collection
	 */
	public function filterCollection($field, $value, $logic = self::LOGIC_EQ)
	{
		$filteredCollection = new self();
		
		// only convert value once
		if( $logic == self::LOGIC_IN )
			$value = is_array($value) ? $value : explode(",", $value);
		
		foreach( $this->rows as $item )
		{
			if( !($item instanceof mysqlClass_CollectionItem) )
				continue;
			
			switch( $logic )
			{
				case self::LOGIC_IN:
					if( in_array($item->getData($field), $value) ) $filteredCollection->addItem($item);
				break;

				case self::LOGIC_LIKE:
					if( strpos(strtolower($item->getData($field)), strtolower($value)) !== false ) $filteredCollection->addItem($item);
				break;

				case self::LOGIC_GT:
					if( intval($item->getData($field)) > intval($value) ) $filteredCollection->addItem($item);
				break;

				case self::LOGIC_LT:
					if( intval($item->getData($field)) < intval($value) ) $filteredCollection->addItem($item);
				break;

				case self::LOGIC_GTE:
					if( intval($item->getData($field)) >= intval($value) ) $filteredCollection->addItem($item);
				break;

				case self::LOGIC_LTE:
					if( intval($item->getData($field)) <= intval($value) ) $filteredCollection->addItem($item);
				break;

				case self::LOGIC_NEQ:
					if( $item->getData($field) != $value ) $filteredCollection->addItem($item);
				break;

				case self::LOGIC_SNEQ:
					if( $item->getData($field) !== $value ) $filteredCollection->addItem($item);
				break;

				case self::LOGIC_SEQ:
					if( $item->getData($field) === $value ) $filteredCollection->addItem($item);
				break;

				case self::LOGIC_EQ:
				default:
					if( $item->getData($field) == $value ) $filteredCollection->addItem($item);
				break;

			}
		}
		
		$this->isFiltersLoaded = true;
		$this->rows = $filteredCollection->getItems();
		
		unset($filteredCollection);
		
		return $this;
	}
	
	
	
	/*
	** callbacks 
	*/



	/**
	 * walk through the collection and returns array with results
	 * @param string $callback
	 * @param array $arguments
	 * @return array
	 */
	public function walk($callback, $arguments = array())
	{
		$results = array();
		
		foreach( $this->rows as $id => $item )
		{
			array_unshift($arguments, $item);		
			$results[$id] = call_user_func_array($callback, $arguments);
		}
		
		return $results;
	}

	/**
	 * 
	 * @param $callback
	 * @param array $arguments
	 * @return mysqlClass_Collection
	 */
	public function each($callback, $arguments = array())
	{
		foreach( $this->rows as $id => $item )
		{
			array_unshift($arguments, $item);
			$this->rows[$id] = call_user_func_array($callback, $arguments);
		}
		
		return $this;
	}
	
	
	
	/*
	** output
	*/



	/**
	 * return collection as xml
	 * @return string
	 */
	public function toXml()
	{
		$xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$xml .= "<collection>\n";
		$xml .= "    <totalRecords>" . $this->totalRecords . "</totalRecords>\n";
		$xml .= "    <items>\n";

		foreach( $this->rows as $item )
		{
			if( $item instanceof mysqlClass_CollectionItem )
				$xml .= $item->toXml(true);
		}

		$xml .= "    <items>\n";
		$xml .= "<collection>\n";

		return $xml;
	}

	/**
	 * return collection as array
	 * @param array $requiredFields
	 * @return array
	 */
	public function toArray($requiredFields = array())
	{
		$array = array();
		$array["totalRecords"] = $this->totalRecords;
		$array["items"] = array();
		
		foreach( $this->rows as $id => $item )
		{
			if( $item instanceof mysqlClass_CollectionItem )
				$array["items"][$id] = $item->toArray($requiredFields);
		}
		
		return $array;
	}

	/**
	 * return collection as string
	 * @return string
	 */
	public function toString()
	{
		return $this->serialize();
	}
	
	
	
	/*
	** array access
	*/



	/**
	 * implementation of ArrayAccess
	 * @param string $id
	 * @param mixed $value
	 */
	public function offsetSet($id, $value)
	{
		$this->rows[$id] = $value;
	}

	/**
	 * implementation of ArrayAccess
	 * @param string $id
	 * @return boolean
	 */
	public function offsetExists($id)
	{
		return isset($this->rows[$id]);
	}

	/**
	 * implementation of ArrayAccess
	 * @param string $id
	 */
	public function offsetUnset($id)
	{
		unset($this->rows[$id]);
	}

	/**
	 * implementation of ArrayAccess
	 * @param string $id
	 * @return mysqlClass_CollectionItem
	 */
	public function offsetGet($id)
	{
		return isset($this->rows[$id]) ? $this->rows[$id] : false;
	}
}

/**
 * MySQL Collection Item Class
 * - - - - - - - - - -
 * Item representation of mysqlClass_Collection.
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
class mysqlClass_CollectionItem
{
	/**
	 * internal data storage array
	 * @var mixed array
	 */
	private $data = array();

	/**
	 * if some data was changed
	 * @var boolean
	 */
	private $hasChangedData = false;

	/**
	 * cache for formatted names
	 * @var string array
	 */
	private $formatNameCache = array();



	/*
	** public
	*/



	/**
	 * handle all function calls the class didn't know
	 * @param string $method
	 * @param mixed array $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		$type = substr($method, 0, 3);
		$key = $this->formatFunctionName(substr($method, 3));
		
		if( $type == "get" )
		{
			return $this->getData($key);
		}
		
		if( $type == "set" )
		{
			$param = isset($args[0]) ? $args[0] : NULL;
			return $this->setData($key, $param);
		}

		if( $type == "uns" )
		{
			return $this->unsetData($key);
		}

		if( $type == "has" )
		{
			return $this->hasData($key);
		}
		
		trigger_error("function not found " . get_class($this) . "::" . $method . "();", E_USER_ERROR);
		return false;
	}

	/**
	 * helper function to set internal data
	 * @param string $key
	 * @param mixed $value
	 * @return mysqlClass_CollectionItem
	 */
	public function setData($key, $value)
	{
		$this->data[$key] = $value;
		$this->setDataChanged();

		return $this;
	}

	/**
	 * helper function to get internal data
	 * @param string $key
	 * @return mixed
	 */
	public function getData($key = NULL)
	{
		if( $key == NULL )
		{
			return $this->data;
		}
		
		if( isset($this->data[$key]) )
		{
			return $this->data[$key];
		}

		return NULL;
	}

	/**
	 * helper function to unset internal data
	 * @param string $key
	 * @return mysqlClass_CollectionItem
	 */
	public function unsetData($key = NULL)
	{
		if( $key == NULL )
		{
			$this->data = array();
			return $this;
		}
		
		unset($this->data[$key]);
		$this->setDataChanged();

		return $this;
	}

	/**
	 * helper function to check if data is set
	 * @param string $key
	 * @return boolean
	 */
	public function hasData($key = NULL)
	{
		if( $key == NULL )
		{
			return empty($this->data);
		}
		
		return isset($this->data[$key]);
	}

	/**
	 * check if some data is set
	 * @return boolean
	 */
	public function isEmpty()
	{
		if( empty($this->data) )
		{
			return true;
		}

		return false;
	}

	/**
	 * if some data is changed
	 * @return boolean
	 */
	public function hasChangedData()
	{
		return $this->hasChangedData;
	}

	/**
	 * clear changed data info
	 * @return mysqlClass_CollectionItem
	 */
	public function clearDataChanged()
	{
		$this->setDataChanged(false);
		return $this;
	}

	/**
	 * get item as xml
	 * @param boolean $itemOnly
	 * @return string
	 */
	public function toXml($itemOnly = false)
	{
		$xml = "";
		
		if( !$itemOnly )
		{
			$xml .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			$xml .= "<data>\n";
		}
			
		$xml .= "    <item>\n";
		
		foreach( $this->data as $key => $value )
		{
			$xml .= "    <" . $key . ">" . $value . "</" . $key . ">\n";
		}
		
		$xml .= "    </item>\n";

		if( !$itemOnly ) $xml .= "</data>\n";
		
		return $xml;
	}

	/**
	 * return object as array
	 * @param array $requiredFields
	 * @return array
	 */
	public function toArray($requiredFields = array())
	{
		if( !empty($requiredFields) )
		{
			$data = array();
			
			foreach( $requiredFields as $field )
				$data[$field] = $this->getData($field);
			
			return $data;
		}
		
		return $this->data;
	}



	/*
	** private
	*/
	
	
	
	/**
	 * formats the name of the called function
	 * @param string $name
	 * @return string
	 */
	private function formatFunctionName($name)
	{
		if( isset($this->formatNameCache[$name]) )
			return $this->formatNameCache[$name];

		$format = preg_replace('/(.)([A-Z])/', "$1_$2", $name);
		$format = strtolower($format);

		$this->formatNameCache[$name] = $format;
		return $format;
	}

	/**
	 * set the changed status
	 * @param boolean $changed
	 * @return void
	 */
	private function setDataChanged($changed = true)
	{
		$this->hasChangedData = $changed;
		return;
	}
}



/*
** exception classes
*/



/**
 * Frosted MySQL Library Exception Classes
 * - - - - - - - - - -
 * Add special exception names to Frosted MySQL Library.
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
class mysqlClass_Connection_Exception extends Exception {}
class mysqlClass_Database_Exception extends Exception {}
class mysqlClass_Query_Exception extends Exception {}
class mysqlClass_Permission_Exception extends Exception {}
class mysqlClass_Create_Exception extends Exception {}
class mysqlClass_Unknown_Function_Exception extends Exception {}

