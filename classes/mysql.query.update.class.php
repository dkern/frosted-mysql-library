<?php
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
}
