<?php
class Fk{

	//atributos
	private $fk_nombre;
	private $fk_local;
	private $fk_tabla;
	private $fk_reference;


	//constructor
	public function __construct($fk_nombre, $fk_local, $fk_tabla, $fk_reference)
	{
		$this->setFk_nombre($fk_nombre);
		$this->setFk_local($fk_local);
		$this->setFk_tabla($fk_tabla);
		$this->setFk_reference($fk_reference);
	}

	//getters/setters
	public function __toString(){
		return $this->getFk_nombre();
	}
	
	public function getFk_nombre(){
		return $this->fk_nombre;
	}
	public function setFk_nombre($fk_nombre){
		return $this->fk_nombre=$fk_nombre;
	}

	public function getFk_local(){
		return $this->fk_local;
	}
	public function setFk_local($fk_local){
		return $this->fk_local=$fk_local;
	}

	public function getFk_tabla(){
		return $this->fk_tabla;
	}
	public function setFk_tabla($fk_tabla){
		return $this->fk_tabla=$fk_tabla;
	}

	public function getFk_reference(){
		return $this->fk_reference;
	}
	public function setFk_reference($fk_reference){
		return $this->fk_reference=$fk_reference;
	}

}
?>