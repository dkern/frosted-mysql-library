<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");




// if an mysqlClass_Config class exists, the Frosted MySQL Library will use the configuration by default
// on every instance with no further actions required, just create a new instance.
// example configuration: examples/mysql.config.php
$sql = new mysqlClass();

// for example print currently set configuration
print_r($sql->getConfigArray());



// set or get the configuration manually
$sql->setUsername("un_overwritten");
$sql->setDatabase("db_overwritten");
echo $sql->getUsername() . "\n";
echo $sql->getDatabase() . "\n";



// or use "setData" and "getData"
$sql->setData("username", "un_overwritten_again");
$sql->setData("password", "db_overwritten_again");
echo $sql->getData("username") . "\n";
echo $sql->getData("password") . "\n";



// or set and get the whole configuration as array
$config = array();
$config["hostname"]   = "hostname";
$config["port"]       = "port";
$config["username"]   = "username";
$config["password"]   = "password";
$config["database"]   = "database";
$config["prefix"]     = "prefiw";
$config["persistent"] = true;
$config["mysqli"]     = true;
$config["verbose"]    = true;
$config["format"]     = true;

$sql->setConfigArray($config);
print_r($sql->getConfigArray());


// and of curse, reset configuration
$sql->resetConfig();
