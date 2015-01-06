<?php
require("include/Conexion.php");

class Dbsql{

// TO2 ver lo de las claves foraneas y claves compuestas
public function __save( $obj ){
	
	$Query="";
	//$tabla_describe = $obj::describeTable();
	$tabla_describe = $obj::getTable();
	$tabla = $tabla_describe->getName();
	$guion = "";
	$str_op = "INSERT";

	// insert into tabla ( field1, field2, field3, ... fieldn ) values ( val1, val2, val3 ... valn );
	$str_insert = "insert into {{tabla}} ( {{fields}} ) values ( {{values}} )";
	$str_insert_fields ="";
	$str_insert_values ="";

	// update tabla set field1 = val1, field2 = val2 ... fieldn = valn where pk = id;
	$str_update = "update {{tabla}} set {{fields_values}} where {{pk_field}} = {{pk_value}}";
	$str_update_fields_values ="";

	// me fijo si tengo que actualizar o insertar
	if ( $obj->getPk() != NULL ) 
		$str_op = "UPDATE";

	foreach ( $tabla_describe->getFields() as $i => $val ) {
		
		// rescato nombre del campo
		$field = $val->getField();

		// formo el nombre del getter que le corresponde
		// como no puedo tener nombres de atributos que comiencen con un numero
		// en el caso de que el campo de una tabala si lo haga, cuando defino la clase
		// al campo que comienza con numero le pongo un _ adelante
		if( is_numeric(substr($field,0,1)) )
			$guion = "_";
		else
			$guion = "";

		$nombre_metodo = "get".$guion.ucfirst($field);
		$valor = $obj->$nombre_metodo();

		// si es un objeto, entonces es un obj que apuntaba un fk
		// por lo tanto no guardo el obj sino que el valor de su 
		// clave primaria
		if ( is_object($valor) )
			$valor = $valor->getPk();


		switch ($str_op) {

			case "INSERT":
				//TO2 ojo con los valores de cadena que son numeros, solucionar eso mirando el tipo de campo que es para ponerlo entre comillas o no
				if( !is_numeric($valor))  
					$valor = "'".$valor."'";
				
				$str_insert_fields .= "`".$field."`, ";
				$str_insert_values .= $valor.", ";

			break;
			
			case "UPDATE":
				
				if( !is_numeric($valor))
					$valor = "'".$valor."'";
				
				$str_update_fields_values .= "`".$field."`=".$valor.", ";
			
			break;
		}
	}

	switch ($str_op) {

		case "INSERT":

			//$tabla = $val->getTable();
			$str_insert_fields = substr($str_insert_fields,0,(strlen($str_insert_fields)-2));
			$str_insert_values = substr($str_insert_values,0,(strlen($str_insert_values)-2));

			$Query = str_replace("{{tabla}}",$tabla,$str_insert);
			$Query = str_replace("{{fields}}",$str_insert_fields,$Query);
			$Query = str_replace("{{values}}",$str_insert_values,$Query);

		break;
		
		case "UPDATE":
			
			//$tabla = $val->getTable();
			$pk_field = $tabla_describe->getPkey();//$obj->__getPkName();
			$pk_value = $obj->getPk();

			$str_update_fields_values = substr($str_update_fields_values,0,(strlen($str_update_fields_values)-2));
			
			$Query = str_replace("{{tabla}}",$tabla,$str_update);
			$Query = str_replace("{{fields_values}}",$str_update_fields_values,$Query);
			$Query = str_replace("{{pk_field}}",$pk_field,$Query);
			$Query = str_replace("{{pk_value}}",$pk_value,$Query);			
		
		break;
	}
	
	//echo "<br>Query: ".$Query;
	$link = new Conexion();
	$conexion = $link->conexion();
	$consulta = $conexion->query($Query);

	if( !$consulta ) {
        printf("<br>Error: %s", $conexion->error);
        return false;
    }

    if( $str_op == "INSERT" )
    	return $conexion->insert_id;
    else
		return $obj->getPK();

}

public function __delete( $obj ){
	
	$pk = $obj->getPk();
	$tabla = $obj::getTable();

	$query = "delete from ?s where ?s = ?i limit 1";
	$prepare_query = self::prepare($query, $tabla->getName(), $tabla->getPkey(), $pk);
	
	$link = new Conexion();
	$conexion = $link->conexion();
	$consulta = $conexion->query($prepare_query);

	if( !$consulta ) {
        trigger_error("Error: ".$conexion->error, E_USER_ERROR);
        return false;
    }
	
	
	return true;
}


// TO2 chequear y enviar mensajes de error
// Sintaxis de las consultas:
//
// col1 :contain: 'cadena' 		# consulta minima, comparar una columna con algun valor, para comparar se puede usar 
// :and: col2 <= '100'     		# (<=,<,>=,>,==,!=,:contain:,:first:,:last:), en el criterio de consulta podemos hacer 
// :or: col3 != 'cadena'   		# uso del AND u OR para compara mas de un campo, todos los valores a comparar, ya sean 
// .........			   		# cadenas o numero van entre comillas simples '', podemos hacer todos los AND u OR que  
// :and: coln == '101'     		# necesitemos y cada palabra reservada ej, :contains: debe tener un espacio por delante 
//								# y por detras.
//
// :order: col1 ... coln :asc:  # (Opcional) Order indica sobre que columna o columnas hay que ordenar los resultados de la consulta
//								# tambien nos permite indicar si queremos los resultados en orden descendente o ascendente
//								# esto lo hacemos con la sentecia :asc: o :desc:, los cuales son opcionales, si no indicamos
//								# nada, por defecto ordena los resultados de foma ascendente :asc:.
//	
// :limit: 0,50					# (Opcional) Limit nos permite limtar la cantidad de resultados que queremos al hacer la consulta
//								# recibe dos valores, desde que registro queremos recibir y la cantidad de registros a partir 
//								# del mismo.
//
// :fields: col1 col1 ... coln  # (Opcional) Fields nos permite seleccionar que campos queremos que nos devuelva el resultado de 
//								# la consulta, no es obligatorio y si no indicamos nada por defecto devuelve todos los campos
//								# de la tabla sobre la que hacemos la consulta.

public function __filter($criterio,$tabla){
	
	@preg_match_all("/
		\s*
		(?P<operador>:and:|:or:)?
		\s*
		(?P<col_query>[.a-zA-Z0-9-_]+)
		\s+
		(?P<col_criterio>\<\=|\<|\>\=|\>|\=\=|\!\=|:contain:|:first:|:last:)
		\s*
		\'(?P<col_data>.+?)\'
		\s*
		(?P<orden>\s*:order:\s*
			(?P<orden_col>[.a-zA-Z0-9-_\s*]+)+
			(?P<orden_asc_desc>:asc:|:desc:)?
		)?
		\s*
		(?P<limite>:limit:\s*
			(?P<limite_ini>[0-9]+)\s*\,
			\s*
			(?P<limite_cant>[0-9]+)\s*
		)?
		\s*
		(?P<campos>:fields:\s*
			(?P<campos_col>[.a-zA-Z0-9-_\s*]+)+
		)?
		/uxsi",$criterio,$salida,PREG_SET_ORDER);	

	//var_dump($salida);

	if( !empty($salida) ){

		// rescato la posicion del ultimo arreglo del parseo
		$_criterios_pos = count($salida)-1;
		$_criterios = $salida[$_criterios_pos];

		//var_dump($_criterios);

		// parseo datos 
		// quito espacios en blanco detras y adelante
		$_criterios['orden'] = trim($_criterios['orden']);
		$_criterios['limite'] = trim($_criterios['limite']);
		$_criterios['campos'] = trim($_criterios['campos']);


		// verifico que la sentencia existe
		$_order_patron = ( isset($_criterios['orden']) && $_criterios['orden'] != "" ) ? true : false;
		$_limit_patron = ( isset($_criterios['limite']) && $_criterios['limite'] != "" ) ? true : false;
		$_fields_patron = ( isset($_criterios['campos']) && $_criterios['campos'] != "" ) ? true : false;
		
		$_ERROR = "";
		$order_by = " ";
		$limit = " ";
		$fields = " * ";
		$_condicion_pre = " ";

		
		if( $_order_patron ){

			$_order_col = trim($_criterios['orden_col']);
			$_order_asc_desc = trim($_criterios['orden_asc_desc']);

			$_order_col = str_replace(" ",",",$_order_col);
			$_order_asc_desc = ( empty($_criterios['orden_asc_desc']) ) ? "asc" : str_replace(":","",$_criterios['orden_asc_desc']);

			if( empty($_order_col) )
				$_ERROR .= "<br>Error: debe indicar sobre que campo/s quiere ordenar los resultados.";

			$order_by = self::prepare(" order by ?s ?s ", $_order_col, $_order_asc_desc);
		}

		if( $_limit_patron ){

			$_limite_ini = trim($_criterios['limite_ini']);
			$_limite_cant  = trim($_criterios['limite_cant']);

			$limit = self::prepare(" limit ?i , ?i ", $_limite_ini, $_limite_cant);

		}

		if( $_fields_patron ){
			
			$_campos_col = trim($_criterios['campos_col']);
			$_campos_col = ( empty($_criterios['campos_col']) ) ? " * " : str_replace(" ",",",$_campos_col);

			if( empty($_campos_col) )
				$_ERROR .= "<br>Error: debe indicar los campo/s quiere proyectar en su cosulta.";

			$fields = " ".$_campos_col." ";
		}

		foreach ($salida as $k => $_consulta) {

			//var_dump($_consulta);

			$operador = trim($_consulta['operador']);
			$col_query = trim($_consulta['col_query']);
			$col_criterio = trim($_consulta['col_criterio']);
			$col_data = trim($_consulta['col_data']);

			$operador = ( $operador == "" ) ? false : str_replace(":","",$operador);
			$col_data = ( $col_data == "") ? false : $col_data;	

			// Si no hay operador y sabemos que hay por lo menos uno y estamos parseando el segundo criterio
			if( !$operador && count($salida) > 1 && $k > 1)
				$_ERROR .= "<br>Error: falta el operador de comparacion comparacion (<=,<,>=,>,==,!=,:contain:,:first:,:last:).";	

			if( $col_data === false ){
				$_ERROR .= "<br>Error: debe ingresar algun valor entre comillas simples 'valor' para realizar la comparacion.";
			}else{
				// si el dato de comparacion no es un numero, lo escapo con comillas simples
				if ( !is_numeric($col_data) )
					$col_data = "'".$col_data."'";
				
			}

			if( $k == 0 )
				$_condicion_pre = " where ";

			$operador = " ".$operador." ";

			switch( $col_criterio ){

				case '<':
					$_condicion_pre .= $operador." ".$col_query." < ".$col_data." ";
					break;

				case '<=':
					$_condicion_pre .= $operador." ".$col_query." <= ".$col_data." ";
					break;

				case '>':
					$_condicion_pre .= $operador." ".$col_query." > ".$col_data." ";
					break;

				case '>=':
					$_condicion_pre .= $operador." ".$col_query." >= ".$col_data." ";
					break;

				case '==':
					$_condicion_pre .= $operador." ".$col_query." = ".$col_data." ";
					break;

				case '!=':
					$_condicion_pre .= $operador." ".$col_query." != ".$col_data." ";
					break;

				case ':contain:':
					$col_data = str_replace("'","",$col_data);
					$_condicion_pre .= $operador." ".$col_query." like '%".$col_data."%' ";
					break;
					
				case ':first:':
					$col_data = str_replace("'","",$col_data);
					$_condicion_pre .= $operador." ".$col_query." like '".$col_data."%' ";	
					break;	

				case ':last:':
					$col_data = str_replace("'","",$col_data);
					$_condicion_pre .= $operador." ".$col_query." like '%".$col_data."' ";
					break;

				case 'default':
					$_ERROR .= "<br>Error: el criterio de seleccion (".$col_criterio.") no es valido.";
					break;									
			}
		}


		//echo "<br> campos: ".$fields." tabla:".$tabla." condicion.".$_condicion_pre." orden".$order_by." limite:".$limit;

		$Query = self::prepare("select ?s from ?s ?s ?s ?s ", $fields, $tabla, $_condicion_pre, $order_by, $limit);
		
		if( $_ERROR != "" ){
			echo "<br>Error Consulta: ".$Query;
			echo "<br>Listado de errores.";
			echo $_ERROR;
			return $_ERROR;
		}
		$link = new Conexion();
		$conexion = $link->conexion();
		$consulta = $conexion->query($Query);

		if( !$consulta ) {
	        printf("<br>Error: %s", $conexion->error);
	        return false;
    	}

	    $rowArray = array();
	    
	    $clase = "o".ucfirst(strtolower($tabla));

	    //while ( $row = $consulta->fetch_row() ) {
	      while ( $row = $consulta->fetch_assoc() ) {  
	        
	    	$dbToObj = $this->dbToObj($clase,$row);
	        array_push($rowArray ,$dbToObj);
	    }
	    
	    return $rowArray;
	}
	else{
		echo "<br>Error: no existe el campo o la condicion no es valida.";
	}    
}

public function __pk($pk,$tabla){

	$nombre_tabla = $tabla->getName();
	$nombre_pk = $tabla->getPkey();

	$link = new Conexion();
	$conexion = $link->conexion();
	$prepare_query = self::prepare("select * from ?s where ?s=?i limit 1", $nombre_tabla, $nombre_pk, $pk);
	$consulta = $conexion->query($prepare_query);//"select * from ".$nombre_tabla." where ".$nombre_pk."=".$pk." limit 1");
    //$rowArray = array();
    
    $clase = "o".ucfirst(strtolower($nombre_tabla));

    //while ( $row = $consulta->fetch_row() ) {
    //while ( $row = $consulta->fetch_assoc() ) {  
        if( $row = $consulta->fetch_assoc() )
    		$dbToObj = $this->dbToObj($clase,$row);
    	else
    		return false;
        //array_push($rowArray ,$dbToObj);
    //}
    return $dbToObj;//$rowArray;
}

public function __all($tabla){

	$link = new Conexion();
	$conexion = $link->conexion();
	$prepare_query = self::prepare("select * from ?s", $tabla);
	$consulta = $conexion->query($prepare_query);
    $rowArray = array();
    
    $clase = "o".ucfirst(strtolower($tabla));

    //while ( $row = $consulta->fetch_row() ) {
    while ( $row = $consulta->fetch_assoc() ) {  
    	$dbToObj = $this->dbToObj($clase,$row);
        array_push($rowArray ,$dbToObj);
    }
    
    return $rowArray;
}

public function __count($tabla){

	$link = new Conexion();
	$conexion = $link->conexion();
	$prepare_query = self::prepare("select COUNT(*) as nro from ?s", $tabla);
	$consulta = $conexion->query($prepare_query);
    
    $row = $consulta->fetch_assoc();
    $count = $row['nro'];

    return $count;
}

// TO2 puedo usar prepare en consulta para preparar el query
// en caso de que prepare falle me devuelve false
// manejar eso, manejar errores
public static function __raw($consulta,$modo=ASSOC){

	$link = new Conexion();
	$conexion = $link->conexion();
	$consulta = $conexion->query($consulta);

	if ( !$consulta )
		return false;

	$rowArray = array();

    switch($modo){

    	case ASSOC:
			while( $row = $consulta->fetch_array(MYSQLI_ASSOC) ) {  
	        	array_push($rowArray ,$row);
	    	}  
    	break;

    	case NUM:
    		while( $row = $consulta->fetch_array(MYSQLI_NUM) ) {  
	        	array_push($rowArray ,$row);
	    	} 
    	break;	
    }
   
    return $rowArray;
}

//TO2 revisar que indexe bien las propiedades de los obj con los resultados
private function dbToObj($clase,$row){

	$reflector = new ReflectionClass($clase);
	//$newClase = $reflector->newInstanceWithoutConstructor(); comentado por conpatibilidad con php < 5.4
	//echo "<br> Clase: ".$clase."<br>";
	$newClase = new $clase();
	$arrMethod  = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);


	// rescato Fks
	$fks = $clase::getTable()->getFkey();
	// paso todas las claves a minusculas
	$fks = array_change_key_case($fks,CASE_LOWER);

	//var_dump($fks);
	// paso todas las claves a minusculas, asi coinciden con los nombres de los atributos
	// que tambien en el momento de crear las clases los paso a todos a minusculas
	$row = array_change_key_case($row,CASE_LOWER);
	
	foreach ($arrMethod as $j => $m){
		
		if( stristr($m->name,'set') !== false ){

			$nombre_campo = strtolower(str_replace("set","",$m->name));
			
			// como los campos que empiezan con numero no estan permitidos en php
			// pero como nombre de campo en la db es valido, para poder usarlo
			// como atributo en php le agrego adelante un _ en su nombre, en su set y get
			if ( !array_key_exists($nombre_campo,$row) )
				$nombre_campo = strtolower(str_replace("set_","",$m->name));

			$nombre_method = $m->name;	

			//echo "<br> Nombre Campo: ".$nombre_campo."<br>";
			//echo "<br> Nombre Metodo: ".$nombre_method."<br>";

			// si el campo es una clave foraneas
			if( array_key_exists($nombre_campo,$fks) ){
				
				// rescato el valor de la fk
				$fk_local = $row[$nombre_campo];
				
				// rescato la descripcion de la tabla
				$fk_tabla = $fks[$nombre_campo]->getFk_tabla();
				$fk_clase = "o".ucfirst(strtolower($fk_tabla));
				// y con esos datos recupero el obj
				$objFk = $this->__pk($fk_local,$fk_clase::getTable());

				// compruebo que la fk aun exista
				if( $objFk !== false ){
					// seteo el obj 
					$newClase->$nombre_method($objFk);
				}else{
					// seteo el valor del campo, sin rescatar al obj por que no existe
					$newClase->$nombre_method($row[$nombre_campo]);
				}

			}else{	
				$newClase->$nombre_method($row[$nombre_campo]);
			}
		}				
	}

	return $newClase;
}


// El primer argumento tiene que ser la consulta que se quiere preparar
// los demas argumentos son variables y representan el valor que se tiene 
// que reemplazar en la consulta,  ?i, representa un numero (int, float etc)
// y ?s una cadena.
public static function prepare(){

	$str = func_get_arg(0); 
	$_ERROR = "";

	preg_match_all("/(?P<tipo>\?i|\?s)/si",$str,$salida,PREG_SET_ORDER);
	//var_dump($salida);
	foreach ($salida as $i => $val) {
		
		//echo "<br>i: ".$i."  ".$val['tipo']." ---- ".func_get_arg($i+1)." ---<br>";
		$arg = func_get_arg($i+1);

		if ( $arg === false  ){
			echo "<br>Error: faltan argumentos para reemplazar en la cadena ingresada.";
			return false; 
		}
		
		switch ( strtolower($val['tipo']) ) {
			
			case '?i':
				if( is_numeric($arg) ){
					// busco pos del primer ?i
					$pos = stripos($str,"?i");
					$str = substr_replace ($str,$arg,$pos,strlen("?i"));
				}else{
					$_ERROR .= "<br>Error: el argumento ".$arg." no es Numerico.";
				}
			break;
			
			case '?s':
				if( is_string($arg) ){
					// busco pos del primer ?s
					$pos = stripos($str,"?s");
					$str = substr_replace ($str,$arg,$pos,strlen("?s"));
				}else{
					$_ERROR .= "<br>Error: el argumento ".$arg." no es un String.";
				}
			break;
		}
	}

	if ($_ERROR != "") {
		echo "<br>".$_ERROR;
		return false;
	}

	return $str;
}

}
?>