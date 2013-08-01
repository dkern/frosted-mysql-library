<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create default mysqlClass instance
$sql = new mysqlClass();

// create instance with verbose option
$sql = new mysqlClass(true);



// the class has the ability to run "read only" queries and block all "write" actions
// receive a "read only" instance
$sql = new mysqlClass(false, mysqlClass::CONNECTION_TYPE_READ);

// or set the connection type on an class instance
$sql->setConnectionType(mysqlClass::CONNECTION_TYPE_READ);
