<?php
// include packed version of Frosted MySQL Library
include("../packed/mysql.class.packed.php");

// include configuration
include("../examples/mysql.config.php");

// create instance
$sql = new mysqlClass();

// connect to mysql
#$sql->connect();



// it is possible to build nearly every select query with Frosted MySQL Library
$result = $sql->select()
			  ->from("table_name")
			  ->where("id = ?", 1337)
			  ->limit(1)
			  ->showQuery()
			  /*->run()*/;

// grouping
$result = $sql->select(array("COUNT(1)" => "count"))
			  ->from("user_table")
			  ->where("active = ?", 1)
			  ->group("user_group", mysqlClass::GROUP_DESC)
			  ->withRollup()
			  ->having("login_count >= ?", 10)
			  ->order("count")
			  ->showQuery()
			  /*->run()*/;

// join with 'on'
$result = $sql->select()
			  ->from("t1")
			  ->leftJoin("t2", "t3", "t4")
			  ->on("t2.a = t1.a")
			  ->on("t3.b = t1.b")
			  ->on("t4.c = t1.c")
			  ->orOn("test = 1")
			  ->showQuery()
			  /*->run()*/;

// join with 'using'
$result = $sql->select()
			  ->from("user_table")
			  ->rightOuterJoin("user_reference_1", "t3", "t4")
			  ->using("id", "name")
			  ->showQuery()
			  /*->run()*/;

// 'on' and 'using' will only add options to the last join in query.
// if more than one join exists in a query the options has to be set after every join
$result = $sql->select("dashboard_data.headline", "dashboard_data.message", "dashboard_messages.image_id", "images.filename")
			  ->from("dashboard_data")
			  ->innerJoin("dashboard_messages")
			  ->on("dashboard_message_id = dashboard_messages.id")
			  ->innerJoin("images")
			  ->on("dashboard_messages.image_id = images.image_id")
			  ->using("id", "name")
			  ->showQuery()
			  /*->run()*/;

// a bit more bigger and complex query
$result = $sql->select(array("p.entity_id", "p.sku" => "sku", "n.value" => "name", "t.value" => "image", "s.attribute_set_name"))
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
											    ->from(array("{DB}.eav_attribute" => "s", "{DB}.eav_entity_type" => "e"))
											    ->where("s.attribute_code = 'name'")
											    ->where("s.entity_type_id = e.entity_type_id")
											    ->where("e.entity_type_code = 'catalog_product'"))
			  ->where("t.attribute_id = ?", $sql->select("s.attribute_id", true)
											    ->from(array("{DB}.eav_attribute" => "s", "{DB}.eav_entity_type" => "e"))
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
			  ->showQuery()
			  /*->run()*/
			  /*->getCollection()*/;

// it is even possible to split the query above and use each sub query itself
$nameAttribute =  $sql->select("s.attribute_id", true)
					  ->from(array("{DB}.eav_attribute" => "s", "{DB}.eav_entity_type" => "e"))
					  ->where("s.attribute_code = 'name'")
					  ->where("s.entity_type_id = e.entity_type_id")
					  ->where("e.entity_type_code = 'catalog_product'");
$nameId = $nameAttribute->showQuery()/*->run()*/;

$imageAttribute = $sql->select("s.attribute_id", true)
					  ->from(array("{DB}.eav_attribute" => "s", "{DB}.eav_entity_type" => "e"))
					  ->where("s.attribute_code = ?", "image_transparent")
					  ->where("s.entity_type_id = e.entity_type_id")
					  ->where("e.entity_type_code = 'catalog_product'");
$imageId = $imageAttribute->showQuery()/*->run()*/;

$productBuffer =  $sql->select(true)
					  ->from("{DB}.product_buffer_table")
					  ->where("product_info = ?", "new");
$buffered = $productBuffer->showQuery()/*->run()*/;

$products =       $sql->select(array("p.entity_id", "p.sku" => "sku", "n.value" => "name", "t.value" => "image", "s.attribute_set_name"))
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
					  ->showQuery()
					  /*->run()*/
					  /*->getCollection()*/;
