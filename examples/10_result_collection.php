<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create default mysqlClass instance
$sql = new mysqlClass();

// connect to mysql
$sql->connect();



// the Frosted MySQL Library can fetch the results to the php known formats like assoc, array or row
// a special fetch type is the collection
$collection = $sql->select()->from("table_name")->run()->fetch(mysqlClass::FETCH_COLLECTION);

// or even
$collection = $sql->select()->from("table_name")->run()->getCollection();



// once the mysql result is fetch as collection the returned value is a new collection
// class instance and is ready to use
$collection = $sql->select("id", "name", "email")->from("user_table")->run()->getCollection();



// it's easy to receive information about returned rows
$count = $collection->getSize();
$isCollectionEmpty = $collection->isEmpty();

// every by mysql returned row is an "item" now, and represented by mysqlClass_CollectionItem
// the same as: foreach( $collection->getItems() as $item )
foreach( $collection as $item )
	echo $item->getName() . " - " . $item->getData("email") . "\n";

// a collection can be filtered even after the mysql query is finished
$collection->addColumnToFilter("id", 10); // equal LOGIC_EQ
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_EQ);   // id == 10
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_SEQ);  // id === 10
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_GT);   // id > 10
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_GTE);  // id >= 10
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_IN);   // id IN(10)
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_LIKE); // id LIKE (%10%)
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_LT);   // id < 10
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_LTE);  // id <= 10
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_NEQ);  // id != 10
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_SNEQ); // id !== 10

// receive items from the collection on different ways
$all = $collection->getItems();                                        // all item
$first = $collection->getFirstItem();                                  // get first returned item
$last = $collection->getLastItem();                                    // get last returned item
$email = $collection->getItemsByColumnValue("email", "test@test.com"); // get all items with email 'test@test.com'
$position = $collection->getItem(4);                                   // get item on position '4'
$id = $collection->getItemById(2);                                     // get item with row id '2'

// a collection delivers different function to represent itself
$xml = $collection->toXml();
$array = $collection->toArray();
$string = $collection->toString();
