<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();

// connect to mysql
#$sql->connect();



// it is possible to build every mysql delete query with Frosted MySQL Library
// the mostly used query deletes by only one table
$sql->delete("table_name")
	->where("id >= ?", 1000)
	->orderBy("id")
	->limit(10)
	->showQuery()
	/*->run()*/;

// but is is even possible to run an delete over more tables
$sql->delete("table_name", "other_table")
	->using("reference_table")
	->where("table_name.id = other_table.id")
	->where("other_table.id = reference_table.id")
	->showQuery()
	/*->run()*/;
