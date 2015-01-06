<?php
class Validator{

	// Constantes
	const ALFA = 1;
	const NUM = 2;
	const STR = 3;
	const EMAIL = 4;

	private $_ERROR = false;

	// metodos
	public static function validar($var,$tipo=ALFA){

	// limpio la variable de errores
	unset($_SESSION['error_comentarios']);

		// chequeo campos vacios
		if ( self::esVacia($var) ) 
			$_ERROR .= "<br>Error: Debe ingresar alguna variable para validar!.";

		// tipos que no correspondan
		switch ($tipo) {
			
			case ALFA:
				echo "<br>Validar alfanumericos";
			break;
			
			case NUM:
				echo "<br>Validar numericos";
			break;

			case STR:
				echo "<br>Validar string puras, solo letras";
			break;

			case EMAIL:
				echo "<br>Validar emails";
			break;						
		}

		if ( !$_ERROR ) {
			
		}else{
			// caracteres no validos y elimino espacios
			return $var = trim( htmlspecialchars( $var, ENT_QUOTES ) );
		}
	}

	private static function esVacia($var){
		return ( !isset($var) || $var == "" ) ? false : true;
	}

	public static function get_ERROR(){
		return $_ERROR;
	}
}
?>