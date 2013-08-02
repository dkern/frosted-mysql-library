Frosted MySQL Library for PHP
=============================

The `Frosted MySQL Library` is an extension to the default `mysql` and `mysqli` functions of php.
With it you can easily create complex queries and get big advantages while development.
Beginners or infrequent developers will get an easy entry to mysql and pro's can do it's work much faster and with more possibilities.
Just take a look to the examples or read the little documentation below.

As of the official mysql documentation, the `Frosted MySQL Library` can create **100%** of all `INSERT`, `UPDATE`, `DELETE`, `REPLACE`, `TRUNCATE` queries and nearly every `SELECT` query you want
(the only exception are the sql parameters and the file output in the `SELECT` query).

All classes in the library has fully support for code-completion in IDEs like `PHPStorm` or `Aptana` and are documented with `PHPDoc`.

##Features##
* support `mysql` and `mysqli`
* optional persistent connections
* `read-only` and `write` permissions
* in-query replacements
* automatically escaping all values
* create queries in `oop` way
* detailed error messages
* receive results in many formats
* dynamically create `collections` of results
* formatted query output if wished
* dynamic parameters to create queries in many ways
* many alias methods to write the code as you think
* fully documented with `PHPDoc`
* support for auto complete in IDEs like `PHPStorm` or `Aptana`
* easy to configure, easy to use
* _and much more ..._

###Installation###
To use the `Frosted MySQL Library` just include the `packed` file, or every single file in the `classes/` folder (the `mysql.config.php` is optional) in your scripts.
With the `packed` version you receive the full library within one file and is the best solution for the most.
If you doesn't use all features you can include all needed files by your own.

```PHP
require_once("packed/mysql.class.packed.php");
include("packed/mysql.config.php")
```

###Config & Examples###
After including the `Frosted MySQL Library` you just have to configure the mysql server settings and start using it.
Take a look to the `examples/02_config.php`, to see which settings are available and how to do this in different ways.
A manual way to configure the library is seen below.
In the `examples/` folder you will find more different examples to get you into the code.

```PHP
$sql = new mysqlClass();
$sql->setHostname("localhost");
$sql->setUsername("root");
$sql->setPassword("Pass1234");
$sql->setDatabase("frosted");

$sql->connect();

// start using Frosted MySQL Library ...
```

###Create Queries###
As the whole library, creating a query is very simple and straight-forward.
Just write the code as you would, when writing an mysql query.
If you use an IDE like PHPStorm or Aptana you will take advantage of the automatically code-completion which can show you all possible options. 

```PHP
$result = $sql->select("id")
              ->from("users")
              ->where("login = ?", "frosted@eisbehr.de")
              ->limit(1);
```

This example above is equal to the mysql query:
```MYSQL
SELECT id FROM users WHERE login = 'frosted@eisbehr.de' LIMIT 1
```

The order of query functions is not relevant, you can mix the order as you want or specify some options later.
```PHP
$query = $sql->select("id")
             ->limit(1)
             ->from("users");

$result = $query->where("login = ?", "frosted@eisbehr.de");
```

###Complex & Sub-Queries###
Like possible in usual mysql queries, you can use sub-queries even with `Frosted MySQL Library`.
Functions like `columns` (select), `where`, `orWhere`, `having`, `orHaving`, `select` (insert) or `union` can receive mysqlClass instances as well.
You can split your queries in many different ones, and use them separately, or write them in-code.
Following an example of an bit complexer query with sub queries in two ways.

```PHP
// create query with in-code sub-queries
$result = $sql->select(array("p.entity_id", "p.sku" => "sku"))
              ->columns(array("n.value" => "name", "t.value" => "image", "s.attribute_set_name"))
			  ->all()
			  ->highPriority()
			  ->straight()
			  ->from(array("{DB}.catalog_product_entity" => "p"))
			  ->from(array("{DB}.eav_attribute" => "a"))
			  ->from(array("{DB}.eav_attribute_set" => "s"))
			  ->from(array("{DB}.catalog_product_entity_varchar" => "n"))
			  ->from(array("{DB}.catalog_product_entity_varchar" => "t"))
			  ->from(array("{DB}.catalog_product_entity_varchar" => "v"))
			  ->where("n.entity_id = p.entity_id")
			  ->where("t.entity_id = p.entity_id")
			  ->where("v.entity_id = p.entity_id")
			  ->where("n.attribute_id = ?", $sql->select("s.attribute_id", true)
											    ->from(array("eav_attribute" => "s", "eav_entity_type" => "e"))
											    ->where("s.attribute_code = 'name'")
											    ->where("s.entity_type_id = e.entity_type_id")
											    ->where("e.entity_type_code = 'catalog_product'"))
			  ->where("t.attribute_id = ?", $sql->select("s.attribute_id", true)
											    ->from(array("eav_attribute" => "s", "eav_entity_type" => "e"))
											    ->where("s.attribute_code = ?", "image_transparent")
											    ->where("s.entity_type_id = e.entity_type_id")
											    ->where("e.entity_type_code = 'catalog_product'"))
			  ->where("v.attribute_id = a.attribute_id")
			  ->where("p.attribute_set_id = s.attribute_set_id")
			  ->where("s.attribute_set_name NOT IN(?)", array("123456", "654321"))
			  ->where("a.attribute_code = ?", "product_info")
			  ->where("t.value != ?", "no_selection")
			  ->where("v.value = ?", "new")
			  ->order("rand()")
			  ->limit(10)
			  ->lockInShareMode()
			  ->union($sql->select(true)
						  ->from("{DB}.product_buffer_table")
						  ->where("product_info = ?", "new"))
			  ->run()
			  ->getCollection();
```

You can even split all sub-queries to own instances an use them separately. Just as you like ...

```PHP
// create and use sub-query
$nameAttribute =  $sql->select("s.attribute_id", true)
					  ->from(array("eav_attribute" => "s", "eav_entity_type" => "e"))
					  ->where("s.attribute_code = 'name'")
					  ->where("s.entity_type_id = e.entity_type_id")
					  ->where("e.entity_type_code = 'catalog_product'");
$nameId = $nameAttribute->showQuery()->run();

// create and use sub-query
$imageAttribute = $sql->select("s.attribute_id", true)
					  ->from(array("eav_attribute" => "s", "eav_entity_type" => "e"))
					  ->where("s.attribute_code = ?", "image_transparent")
					  ->where("s.entity_type_id = e.entity_type_id")
					  ->where("e.entity_type_code = 'catalog_product'");
$imageId = $imageAttribute->showQuery()->run();

// create and use sub-query
$productBuffer =  $sql->select(true)
					  ->from("{DB}.product_buffer_table")
					  ->where("product_info = ?", "new");
$buffered = $productBuffer->showQuery()->run();

// create and use main query with all sub-queries as variable
$products =       $sql->select(array("p.entity_id", "p.sku" => "sku"))
					  ->columns(array("n.value" => "name", "t.value" => "image", "s.attribute_set_name"))
					  ->all()
				  	  ->highPriority()
					  ->straight()
					  ->from(array("{DB}.catalog_product_entity" => "p"))
				  	  ->from(array("{DB}.eav_attribute" => "a"))
				  	  ->from(array("{DB}.eav_attribute_set" => "s"))
				  	  ->from(array("{DB}.catalog_product_entity_varchar" => "n"))
					  ->from(array("{DB}.catalog_product_entity_varchar" => "t"))
					  ->from(array("{DB}.catalog_product_entity_varchar" => "v"))
					  ->where("n.entity_id = p.entity_id")
					  ->where("t.entity_id = p.entity_id")
					  ->where("v.entity_id = p.entity_id")
					  ->where("n.attribute_id = ?", $nameAttribute)
					  ->where("t.attribute_id = ?", $imageAttribute)
					  ->where("v.attribute_id = a.attribute_id")
					  ->where("p.attribute_set_id = s.attribute_set_id")
					  ->where("s.attribute_set_name NOT IN(?)", array("123456", "654321"))
					  ->where("a.attribute_code = ?", "product_info")
					  ->where("t.value != ?", "no_selection")
					  ->where("v.value = ?", "new")
					  ->order("rand()")
					  ->limit(10)
					  ->lockInShareMode()
					  ->union($productBuffer)
					  ->run()
					  ->getCollection();
```

###Run Query###
You can create or change a query as long as you want.
The instance will be available all the time.
When you finished creating your query, it's time to send them to the mysql server and run them.
For doing this, just call `run()` on the query instance, or `run(true)` to receive the raw `mysql resource` or the `mysqli_result`, according to the functions you use.

```PHP
// create query
$query = $sql->select()->from("users");

// add option
$query->limit(1000);

// run query
$result = $query->run();
```

###Results###
When using the `Frosted MySQL Library` you don't have to fetch the mysql results by your own anymore, like `while( $row = mysql_fetch_assoc($result) ) { ... }`.
It's easy to receive the results in all ways you want and need. The `fetch()` function is all you need from now on.

```PHP
// create and run query
$result = $sql->select()->from("users")->run();

// mysql_fetch_assoc()
$assoc = $result->fetch();
$assoc = $result->fetch(mysqlClass::FETCH_ASSOC);

// mysql_fetch_array()
$array = $result->fetch(mysqlClass::FETCH_ARRAY);

// mysql_fetch_row()
$row = $result->fetch(mysqlClass::FETCH_ROW);

// loop trough the results
foreach( $assoc as $rowNumber => $row )
    // do your work
```

But if you sill want to use the default php mysql fetch functionality you can receive the raw `mysql resource` or the `mysqli_result`, according to the functions you use, by use `run(true)`.
```PHP
// create and run query
$rawResult = $sql->select()->from("users")->run(true);

// loop through the results
while( $row = mysql_fetch_assoc($rawResult) )
    // do your work
```

###Collections###
A `collection` is another result type you can receive for a result.
It's a powerful tool to handle mysql results.
You can receive specific data from collections, run further filters, replace data, output all rows in different formats and much more.

```PHP
// create and run query
$result = $sql->select()->from("users")->run();

// get loaded collection
$collection = $result->getCollection();

// alternative ways
$collection = $result->fetch(mysqlClass::FETCH_OBJ);
$collection = $result->fetch(mysqlClass::FETCH_OBJECT);
$collection = $result->fetch(mysqlClass::FETCH_COLLECTION);

// loop through collection
foreach( $collection as $row )
    // do your work

// get a row by id
$row = $collection->getItemById(1);

// get a row by position
$row = $collection->getItem(10);

// further filter on collection
// receive only rows where id is greater than 10
$collection->addColumnToFilter("id", 10, mysqlClass_Collection::LOGIC_GT);

// output all as xml
echo $collection->toXml();
```

###Alternative Calls###
To support your coding style and to use the classes in many, many different ways, the most query functions have dynamical parameters.
The default parameters are more an hint as an strict requirement. An example with select columns:

```PHP
// this
$sql->select("id", "name", "email");

// is the same as
$sql->select(array("id", "name", "email"));

// is the same as
$sql->select("id", array("name", "email"));

// is the same as
$sql->select()->columns("id", "name", "email");

// is the same as
$sql->select()->columns(array("id", "name", "email"));

// is the same as
sql->select()->columns(array("id"), "name", "email");

// is the same as
$sql->select("id")->column("name")->column("email");

// is the same as
$sql->select()->column("id")->column("name")->column("email");

// is the same as
$sql->select(array("id" => "id", "name" => "name", "email" => "email");

// and so on ...
```

###Multiple Query Instances###
By default the `Frosted MySQL Library` will create only one instance per query at a time to get the best possible speed.
This means that you can only build one query type at once (one `SELECT`, one `INSERT`, one `UPDATE`, ...).

If you need more instances of a query, like for sub-queries, add an boolean `true` as last parameter of the query construct.

```PHP
// create a select instance 
$select_1 = $sql->select("id");

// this will override the first query instance
// $select_1 will now be the same as $select_2
$select_2 = $sql->select("id", "name");

// create a new instance
// $select_3 is a whole new query and $select_2 is still available
$select_3 = $sql->select("id", true);
```

###Debug & Printing###
For easy debugging or if you want to use `exception` instead or errors, set the `verbose` option to the `mysqlClass`.
Another help to debug queries is to print them directly with `showQuery()` or receive them as string to use it somewhere else with `getQuery()`.
If you want to print the query or maybe show them somewhere enable `setFormat` to the `mysqlClass`, to receive all query formatted for better readability.

```PHP
// set options
$sql->setVerbose(true);
$sql->setFormat(true);

// create query
$queryString = $sql->update("table_name")
				   ->set("column_1 = ?", "colOne")
				   ->set("column_2 = ?", "colTwo")
				   ->set("column_3 = ?", "colThree")
				   ->set("column_4 = ?", "colFour")
				   ->where("id = ?", 1337)
				   ->limit(1)
				   ->showQuery()
				   ->getQuery();
```

This will pass the query string to the `$queryString` variable and print the query directly to the browser.
The output will look like this:

```MYSQL
UPDATE 
    table_name
SET
    column_1 = 'colOne', 
    column_2 = 'colTwo', 
    column_3 = 'colThree', 
    column_4 = 'colFour'
WHERE 
    id = 1337 
LIMIT 
    1
```
