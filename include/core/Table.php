<?php
class Table{

	//atributos
	private $name;
	private $pkey;
	private $fkey = array();
	private $fields = array();
	private $engine;
	private $sql_create;

	//constructor
	public function __construct( $name=NULL, $pkey=NULL, $fkey=NULL, $field=NULL ){

		$this->setName($name);
		$this->setPkey($pkey);
		$this->setFkey($fkey);
		$this->setFields($field);
	}


	public function __toString(){
		return $this->getName();
	}

	//getters/setters
	public function setName($name){
		$this->name = $name;
	}
	public function getName(){
		return $this->name;
	}

	public function setPkey($pkey){
		$this->pkey = $pkey;
	}
	public function getPkey(){
		return $this->pkey;
	}

	public function setFkey($fkey){

		// si me setean un arreglo de claves foraneas
		// seteo el array a la variable
		if( is_array($fkey) )
			$this->fkey = $fkey;
		else
			array_push($this->fkey, $fkey);
		// si me setean un valor, lo meto en el array
	}
	public function getFkey(){
		return $this->fkey;
	}

	public function setFields($field){

		// si me setean un arreglo de campos
		// seteo el array a la variable
		if( is_array($field) )
			$this->fields = $field;
		else
			array_push($this->fields, $field);
		// si me setean un valor, lo meto en el array
	}
	public function getFields(){
		return $this->fields;
	}	

	public function setEngine($engine){
		$this->engine = $engine;
	}
	public function getEngine(){
		return $this->engine;
	}

	public function setSql_create($sql_create){
		$this->sql_create = $sql_create;
	}
	public function getSql_create(){
		return $this->sql_create;
	}
}
?>