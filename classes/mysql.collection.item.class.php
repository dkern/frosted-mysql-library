<?php
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
