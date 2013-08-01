<?php
/**
 * Frosted MySQL Library Configuration Class
 * - - - - - - - - - -
 * This class is an optional way to pass the configuration to the Frosted MySQL Library
 * instance. If you use the communication class often, it would be the easiest way to
 * handle the config data with a configuration class, because you don't have to specify
 * the settings all the time again.
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
class mysqlClass_Config
{
	/**
	 * mysql hostname
	 * @var string
	 */
	const hostname = "localhost";

	/**
	 * mysql port number, default '3306'
	 * @var string
	 */
	const port = "3306";

	/**
	 * mysql username
	 * @var string
	 */
	const username = "root";

	/**
	 * password to access the database
	 * @var string
	 */
	const password = "";

	/**
	 * database to select by default
	 * @var string
	 */
	const database = "";

	/**
	 * prefix to separate tables, only necessary when you want to use prefix replacement
	 * @var string
	 */
	const prefix   = "";



	/**
	 * use a persistent mysql connection
	 * @var boolean
	 */
	const persistent = true;

	/**
	 * use mysqli instead of default mysql
	 * @var boolean
	 */
	const mysqli = false;



	/**
	 * if enabled the class will throw exceptions with stack trace instead of error messages
	 * @var boolean
	 */
	const verbose = false;

	/**
	 * better readable format of sql queries, only necessary when you want to show them
	 * @var boolean
	 */
	const format = false;
}
