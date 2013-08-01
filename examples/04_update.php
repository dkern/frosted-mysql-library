<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();

// connect to mysql
#$sql->connect();



// it is possible to build every mysql update query with Frosted MySQL Library
// the mostly used update is for updating only one table
$sql->update("table_name")
	->set("name = ?", "eisbehr")
	->set("email = ?", "test@test.com")
	->where("id >= ?", 1000)
	->orderBy("id")
	->limit(10)
	->showQuery()
	/*->run()*/;

// but is is even possible to run an update over many tables
$sql->update("table_name", "other_table")
	->set(array("name = ?" => "eisbehr", "email = ?" => "test@test.com"))
	->where("table_name.id = other_table.id")
	->showQuery()
	/*->run()*/;
