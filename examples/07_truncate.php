<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();

// connect to mysql
#$sql->connect();



// it is only possible to truncate one table at a time
$sql->truncate("table_name")->showQuery()/*->run()*/;

// alternative ways
$sql->truncate(array("table_name"))->showQuery()/*->run()*/;
$sql->truncate(array("table_name" => "alias"))->showQuery()/*->run()*/;
$sql->truncate()->table("table_name")->showQuery()/*->run()*/;
$sql->truncate()->table(array("table_name"))->showQuery()/*->run()*/;
$sql->truncate()->table(array("table_name" => "alias"))->showQuery()/*->run()*/;
