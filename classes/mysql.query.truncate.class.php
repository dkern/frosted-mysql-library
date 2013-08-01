<?php
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
}
