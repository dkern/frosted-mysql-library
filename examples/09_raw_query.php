<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();

// connect to mysql
$sql->connect();



// the query function of the base Frosted MySQL Library can receive all query string directly
$result = $sql->query("SELECT * FROM table_name");

// "qry" is an alias of "query"
$sql->qry("DELETE FROM table_name");

// the only additions to normal mysql-functions is, that you can still use the
// replacement functionality (see examples/13_replacement.php) and automatically
// fetch the results to any supported format
$assocArray = $sql->query("SELECT * FROM {DB}.{PRE}table_name", true);

// or to another fetch format
$collection = $sql->query("SELECT * FROM {DB}.{PRE}table_name", true, mysqlClass::FETCH_COLLECTION);
