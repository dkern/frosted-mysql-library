<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();



// the Frosted MySQL Library contains a function to escape all values based on the mysql standard
// just pass a value an receive a query-ready escaped representation.

// the functions "e" and "__" are both an alias of "escape"
// all calls are equal:
$value = $sql->escape("value");
$value = $sql->__("value");
$value = $sql->e("value");



// examples of string literals
echo $sql->escape("string") . "\n";

// examples of number literals
echo $sql->escape(0) . "\n";
echo $sql->escape(1) . "\n";
echo $sql->escape(1.10) . "\n";
echo $sql->escape(-1) . "\n";
echo $sql->escape(-32032.6809e+10) . "\n";

// examples of date/time literals
echo $sql->escape("2012-12-31 11:30:45") . "\n";
echo $sql->escape("1970-1999") . "\n";

// examples of hex literals
echo $sql->escape(0x123) . "\n";
echo $sql->escape(0x636174) . "\n";
echo $sql->escape("0x123") . "\n";

// examples of boolean literals
echo $sql->escape(true) . "\n";
echo $sql->escape(false) . "\n";

// examples of null literals
echo $sql->escape(NULL) . "\n";
echo $sql->escape(NULL, false) . "\n";
