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

###Queries###
As the whole library, creating a query is very simple and straight-forward.
Just write the code as you would, when writing an mysql query.
If you use an IDE like PHPStorm or Aptana you will take advantage of the automatically code-completion which can show you all possible options. 

```PHP
$result = $sql->select("id")
              ->from("users")
              ->where("login = ?", "frosted@eisbehr.de")
              ->limit(1)
              ->run()
              ->fetch();
```

This is equal to the mysql query:

```MYSQL
SELECT id FROM users WHERE login = 'frosted@eisbehr.de' LIMIT 1
```

###Sub-Queries###

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
$sql->select(array("id" => "id", "name" => "name", "email" => "email");

// and so on ...
```

###Results###

###Collection###

###Replacement###
