<?php
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
