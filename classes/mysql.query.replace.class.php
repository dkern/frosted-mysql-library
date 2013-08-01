<?php
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
}
