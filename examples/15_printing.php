<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();



// if you want to get a formatted output set the option to the class
// with this the output has a better readability
$sql->setFormat(true);

// it is possible to print all queries to browser and even run them afterwards
$sql->select()
	->from("table_name")
	->where("1 = 1")
	->showQuery()
	/*->run()*/;

// or pass the query as string to a variable
$string = $sql->select()
			  ->from("table_name")
			  ->where("1 = 1")
			  ->getQuery();
