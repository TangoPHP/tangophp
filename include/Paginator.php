<?php
require_once("Pagina.php");

/**
 * Clase Paginator, pagina objetos
 * 
 * Esta clase se encarga de parsear los templates usados por el sistema
 * nos permite seleccionar un template, pasarle un contexto con las 
 * variables u objetos que vamos a usar para procesar el template.
 *
 * @author Gastón Mura <gastonmura@hotmail.com>
 * @version 1.0
 * @package Paginator
 */
class Paginator{
	//atributos
	private $cantidadResultados;
	private $registrosPorPagina;
	private $paginaActual;
	private $link;
	private $css_activo;
	private $css_inactivo;
	//contructor
	public function __construct($cantidadResultados,$registrosPorPagina, $css_activo = 'activo', $css_inactivo = 'inactivo')
	{
		$this->cantidadResultados = $cantidadResultados;
		$this->registrosPorPagina = $registrosPorPagina;
		$this->css_inactivo = $css_inactivo;
		$this->css_activo = $css_activo;
	}

	public function getSectorActual(){
		return ceil( $this->getPaginaActual() / $this->registrosPorPagina );
	}

	public function getFinalSector(){
		return $this->registrosPorPagina * $this->getSectorActual();
	}

	public function getInicioSector(){
		return ( $this->getFinalSector() - $this->registrosPorPagina ) ;
	}


	public function obtenerLinkDelSector(){
		
		return array_slice($this->obtenerPaginas($this->getLink()),$this->getInicioSector(),$this->registrosPorPagina + 1 );
	
	}

	public function linkUltimo(){
		return $this->getLink().$this->CantidadDePaginas();
	}

	public function linkPrimero(){
		return $this->getLink()."1";
	}

	public function linkSiguiente(){
		$l = ( ($this->getPaginaActual() + 1) <= $this->CantidadDePaginas() ) ? ($this->getPaginaActual() + 1) : 1;
		return  $this->getLink().$l;
						
	}

	public function linkAnterior(){
		$l = ( ($this->getPaginaActual() - 1) >= 1 ) ? ($this->getPaginaActual() - 1) : $this->CantidadDePaginas();
		return  $this->getLink().$l;
	}

	public function obtenerPaginas($link = "?pagina="){

		$lista_pagina=array();
		
		$paginas_totales = $this->CantidadDePaginas();

		for ($i=1; $i <= $paginas_totales ; $i++) { 

			$pActiva = ($this->paginaActual == $i) ? $this->css_activo : $this->css_inactivo;
			array_push($lista_pagina, new Pagina($i,$pActiva,$link.$i));
		}
		return $lista_pagina;
	}

	
	public function CantidadDePaginas(){
		return  ceil( $this->getCantidadResultados() / $this->getregistrosPorPagina());
	}

	private function EsValida($pagina){

		if( ! is_int($pagina) )
			return false;
		
		if( $pagina <= 0 || $pagina > $this->CantidadDePaginas() )
			return false;
		
		return true;
	}

	//geters/seters
	public function setLink($link){
		$this->link = $link;
	}
	public function getLink(){
		return $this->link;
	}
	public function getPaginaActual(){
		return $this->paginaActual;
	}
	public function getListado(){
		return $this->listado;
	}
	public function getRegistrosPorPagina(){
		return $this->registrosPorPagina;
	}	
	public function getCantidadResultados(){
		return $this->cantidadResultados;
	}

	public function getCssActivo(){
		return $this->css_activo;
	}	
	public function setCssActivo($css_activo){
		$this->css_activo = $css_activo;
	}

	public function getCssInactivo(){
		return $this->css_inactivo;
	}	
	public function setCssInactivo($css_inactivo){
		$this->css_inactivo = $css_inactivo;
	}

	public function setPaginaActual($pagina){

		if( $this->EsValida($pagina))
			$this->paginaActual = $pagina;
		else
			$this->paginaActual = 1;
	}

	public function setListado($listado){
		$this->listado=$listado;
	}
	public function setRegistrosPorPagina($registrosPorPagina){
			$this->registrosPorPagina=$registrosPorPagina;
	}

}
?>