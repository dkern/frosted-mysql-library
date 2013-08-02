<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();

// connect to mysql
#$sql->connect();



// it is possible to build every mysql insert query with Frosted MySQL Library
// Frosted MySQL Library support all types of inserting data as mysql does

// insert by column list
$sql->insert("table_name")
	->columns("name", "email", "timestamp")
	->values("eisbehr", "test@test.com", time())
	->showQuery()
	/*->run()*/;

// insert and set every column
$sql->insert("table_name")
	->set("name", "eisbehr")
	->set("email", "test@test.com")
	->set("timestamp", time())
	->showQuery()
	/*->run()*/;

// insert by sub-select
$sql->insert("table_name")
	->columns("name", "email", "timestamp")
	->select($sql->select("name", "email", "timestamp")->from("sub_table"))
	->showQuery()
	/*->run()*/;

// use on duplicate
$sql->insert("table_name")
	->columns("name", "email", "timestamp")
	->values("eisbehr", "test@test.com", time())
	->onDuplicate("email = VALUES(email)")
	->showQuery()
	/*->run()*/;

// add big amounts of data to a single query
$query = $sql->insert("table_name")
			 ->columns("id", "name", "email", "timestamp");

for( $i = 0; $i < 10; $i++ )
{
	$query->values($i, "eisbehr", "test@test.com", time());
}


$query->showQuery()/*->run()*/;
