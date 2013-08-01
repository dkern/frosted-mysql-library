<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();

// connect to mysql
#$sql->connect();



// of course is it possible to add sub-queries by string to every function.
// but function as columns (select), where, orWhere, having, orHaving or select (insert)
// can even handle class instances

// to create a second instance just add an boolean 'true' to the parameter list
$result = $sql->select("id", "email")
			  ->from("user_table")
			  ->where("id IN(?)", $sql->select("id", true)->from("user_ids")->where("active = ?", 1))
			  ->order("email", mysqlClass::ORDER_DESC)
			  ->showQuery()
			  /*->run()*/;

// it is possible to pass the sub-querie to an variable and use it later or single
$userIds = $sql->select("id", true)->from("user_ids")->where("active = ?", 1);

$result = $sql->select("id", "email")
			  ->from("user_table")
			  ->where("id IN(?)", $userIds)
			  ->order("email", mysqlClass::ORDER_DESC)
			  ->showQuery()
			  /*->run()*/;

$ids = $userIds->showQuery()/*->run()*/;
