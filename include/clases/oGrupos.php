
public function getUsuario(){
	return oUsuario::filter("grupo == '".$this->getPk()."'");
}
_once("include/core/Field.php");
require_once("include/core/Fk.php");

class oGrupos extends Dbsql {

// atributos
protected static $table;
private $id;
private $nombre;
private $desc;
private $codigo;

//constructor
public function __construct($nombre=NULL, $desc=NULL, $codigo=NULL){

$this->setNombre($nombre);
$this->setDesc($desc);
$this->setCodigo($codigo);

}

//Table
protected static function getTable(){

self::$table = new Table();	

$table_name = "grupos";

$pk_name = "id"; 

$fk = array();

$fields = array();

$engine = "InnoDB";

$sql_create = <<<'EOD'
CREATE TABLE `grupos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `codigo` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1
EOD;
//addslashes('');



$fields['id'] = new Field('id','int(11)','NO','PRI','','auto_increment');
$fields['nombre'] = new Field('nombre','varchar(50)','NO','','','');
$fields['desc'] = new Field('desc','varchar(255)','NO','','','');
$fields['codigo'] = new Field('codigo','int(1)','NO','','','');


self::$table->setName($table_name);
self::$table->setPkey($pk_name);
self::$table->setFkey($fk);
self::$table->setFields($fields);
self::$table->setEngine($engine);
self::$table->setSql_create($sql_create);

return self::$table;
}

//geters/seters
public function setId($id){
$this->id=$id;
}
public function getId(){
return $this->id;
}
public function setNombre($nombre){
$this->nombre=$nombre;
}
public function getNombre(){
return $this->nombre;
}
public function setDesc($desc){
$this->desc=$desc;
}
public function getDesc(){
return $this->desc;
}
public function setCodigo($codigo){
$this->codigo=$codigo;
}
public function getCodigo(){
return $this->codigo;
}


public function __toString(){
	//return "oGrupos";
	return $this->getPk();
}


public function getPk(){
	return $this->getId();
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

}

//*********************
?>