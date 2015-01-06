<?php
require_once("include/core/Dbsql.php");
require_once("include/core/Table.php");
require_once("include/core/Field.php");
require_once("include/core/Fk.php");

class {{nombre_clase}} extends Dbsql {

// atributos
protected static $table;
{{atributos_clase}}
{{atributos_de_relacion}}
//constructor
public function __construct({{atributos_constructor}}){

{{set_atributos_constructor}}
}

//Table
protected static function getTable(){

self::$table = new Table();	

$table_name = "{{nombre_tabla}}";

$pk_name = "{{nombre_pk}}"; 

$fk = array();

$fields = array();

$engine = "{{engine}}";

$sql_create = <<<'EOD'
{{sql_create}}
EOD;
//addslashes('');

{{fkeys}}

{{describe_table}}

self::$table->setName($table_name);
self::$table->setPkey($pk_name);
self::$table->setFkey($fk);
self::$table->setFields($fields);
self::$table->setEngine($engine);
self::$table->setSql_create($sql_create);

return self::$table;
}


//funciones de relacion
{{relation_functions}}
//geters/seters
{{get_set_functions}}

public function __toString(){
	//return "{{nombre_clase}}";
	return $this->getPk();
}


public function getPk(){
	return $this->get{{get_pk}}();
}

public function save(){

	return $this->__save( $this );
}

public function delete(){

	return $this->__delete( $this );
}


// Metodos static
public static function all(){
	$db = new Dbsql();
	return $db->__all( self::getTable()->getName() );
}

public static function filter($criterio){
	$db = new Dbsql();
	return $db->__filter( $criterio, self::getTable()->getName() );
}

public static function pk( $pk ){
	$db = new Dbsql();
	return $db->__pk( $pk, self::getTable() );
}

public static function count(){
	$db = new Dbsql();
	return $db->__count( self::getTable()->getName()  );
}


}
?>