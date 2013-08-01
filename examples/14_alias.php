<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();



// mysql can handle alias names to tables and columns
// to use this feature with Frosted MySQL Library pass the name of the table or column and the alias as array
// as spoken, 'table AS alias' will be 'array("table" => "alias")'

$result = $sql->select(array("COUNT(1)" => "count"))
			  ->from(array("table_name" => "table"))
			  ->where("table.id = ?", 1)
			  ->showQuery()
			  /*->run()*/;
