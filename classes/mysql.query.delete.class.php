<?php
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
}
