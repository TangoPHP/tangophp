<?php
class Field{
	 
	// atributos
	private $field;
	private $type;
	private $null;
	private $key;
	private $default;
	private $extra;

	//constructor
	public function __construct($field,$type,$null,$key,$default,$extra){
		
		$this->setField($field);
		$this->setType($type);
		$this->setNull($null);
		$this->setKey($key);
		$this->setDefault($default);
		$this->setExtra($extra);
	}  

	//getters/setters
	public function __toString(){
		return ucfirst($this->getField());
	}
	public function setField($field){
		$this->field = $field;
	}
	public function getField(){
		return $this->field;
	}
	public function setType($type){
		$this->type = $type;
	}
	public function getType(){
		return $this->type;
	}
	public function setNull($null){
		$this->null = $null;
	}
	public function getNull(){
		return $this->null;
	}
	public function setKey($key){
		$this->key = $key;
	}
	public function getKey(){
		return $this->key;
	}
	public function setDefault($default){
		$this->default = $default;
	}
	public function getDefault(){
		return $this->default;
	}
	public function setExtra($extra){
		$this->extra = $extra;
	}
	public function getExtra(){
		return $this->extra;
	}
}
?>