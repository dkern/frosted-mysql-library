<?php
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
	 * @param boolean $nextRelation
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
	 * @param boolean $nextRelation
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
}
