<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();



// the Frosted MySQL Library supports read-only connections, in this case, the class
// tries to allocate if the query is a writing query and stop the execution.

// to enable it, set the connection type
$sql->setConnectionType(mysqlClass::CONNECTION_TYPE_READ);

// this queries will both create an error/exception
$sql->query("DELETE FROM test");
$sql->delete()->from("test")->run();

// to set the connection to write again, set the connection type again
$sql->setConnectionType(mysqlClass::CONNECTION_TYPE_WRITE);

// not the query will run
$sql->query("DELETE FROM test");
