<?php
/**
 * Clase Pagina, representa una página, de la clase Paginator
 * 
 * Esta clase representa uno de los números de página de la barra de paginación
 * y tiene atributos tales como, página esta activa, el número de página 
 * y el link para acceder a esa página de registros.
 *
 * @author Gastón Mura <gastonmura@hotmail.com>
 * @version 1.0
 * @package Paginator
 */
class Pagina{

	private $paginaNumero;
	private $paginaActiva;
	private $paginaLink;

	public function __construct($paginaNumero, $paginaActiva = 'inactivo', $paginaLink = "?pagina=" ){

		$this->paginaNumero=$paginaNumero;
		$this->paginaActiva=$paginaActiva;
		$this->paginaLink=$paginaLink;
	}

	public function getPaginaNumero(){
		return $this->paginaNumero;
	}

	public function getPaginaActiva(){
		return $this->paginaActiva;
	}

	public function getPaginaLink(){
		return $this->paginaLink;
	}

	public function __toString(){
		return $this->paginaNumero;
	}
}
?>