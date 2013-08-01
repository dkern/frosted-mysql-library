<?php
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
