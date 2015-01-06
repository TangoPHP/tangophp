<?php
require("Constantes.php");

class Conexion{

//atributos
private $db;
private $host;
private $usr;
private $pass;

//constructor
public function __construct($db=DB,$host=HOST,$usr=USR,$pass=PASS){
	
	$this->setDb($db);
	$this->setHost($host);
	$this->setUsr($usr);
	$this->setPass($pass);
} 

//metodos
public function conexion(){
	$conexion = new mysqli($this->getHost(),$this->getUsr(),$this->getPass(),$this->getDb());
	if ( !$conexion )
		trigger_error("Problemas en la conexion",E_USER_ERROR);
	
	return $conexion;
}

//getters/setters
public function setDb($db){
	$this->db = $db;
}
public function getDb(){
	return $this->db;
}
public function setHost($host){
	$this->host = $host;
}
public function getHost(){
	return $this->host;
}
public function setUsr($usr){
	$this->usr = $usr;
}
public function getUsr(){
	return $this->usr;
}
public function setPass($pass){
	$this->pass = $pass;
}
public function getPass(){
	return $this->pass;
}

}
?>