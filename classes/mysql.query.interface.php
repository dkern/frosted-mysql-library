<?php
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
