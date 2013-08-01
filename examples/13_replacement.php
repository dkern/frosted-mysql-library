<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();

// set example data
$sql->setDatabase("database_name");
$sql->setPrefix("table_prefix_");
$select = $sql->select()->from("table_name");

// connect to mysql
#$sql->connect();



// there are two (four) pre defined replacements to use in every query (raw or by class)
// {DB}/{DATABASE} and {PRE}/{PREFIX}

// the following will use:
// SELECT * FROM database_name.table_prefix_table_name
echo $sql->replaceQuery("SELECT * FROM {DB}.{PRE}table_name");

// all class query or raw queries by query() or qry() will automatically replace the
// data in every string, there is no need to call replaceQuery() manually.
$result = $sql->query("SELECT * FROM {DB}.{PRE}table_name");

// all repalcements are gloabl
// to add a custom replacement do this on the main class instance
$sql->addReplacement("{CUSTOM}", "value");
echo $sql->replaceQuery("SELECT * FROM {CUSTOM}");



// class generated queries got another replace in every where(), orWhere(), having(), 
// orHaving, set() or onDuplicate() function.

// if a second parameter is provided, the function will automatically escape them and 
// replace the '?' in the expression
$name = "eisbehr";
$select->where("name = ?", $name);

// the example above is equal to the following
$select->where("name = '" . mysql_real_escape_string($name) . "'");
