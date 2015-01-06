#!C:\wamp\bin\php\php5.4.3\php.exe -q
<?php
require_once("../Templates.php");
require_once("../Constantes.php");
require_once("../Conexion.php");

/***************************************************************************/
/*
/* Version: v1.1.0
/* Fecha: 23/12/2014 
/* Autor: Gaston Mura
/* Email: gastonmura@hotmail.com
/* Obs: Orm orientado al motor Mysql
/*
/***************************************************************************/

// Nos  conectamos a la Db
$link = new Conexion();
$conexion = $link->conexion();

$relaciones =  null;

// Consultamos las tablas de nuestra Db 
$consulta = $conexion->query("show tables");
$array_clases = array();
$array_clases_comun = array();

while ( $row = $consulta->fetch_row() ){

$nombre_tabla = $row[0];

$nombre_clase = "o".ucfirst(strtolower($row[0]));
$nombre_archivo = $nombre_clase.".php";	

$nombre_clase_comun = ucfirst(strtolower($row[0]));
$nombre_archivo_comun = $nombre_clase_comun.".php";	

$nombre_pk = NULL;
$atributos_clase = "";
$atributos_constructor = "";
$set_atributos_constructor = "";
$get_set_functions = "";
$_describe_table = "";
$fkeys = "";
$engine = "";


array_push($array_clases, $nombre_clase);
array_push($array_clases_comun, $nombre_clase_comun);

// Consulto las fk de la tabla
$consultaFk = $conexion->query("show create table ".$row[0]);


if( $rowFk = $consultaFk->fetch_row() ){

	// guardo todo el sql de la creacion de la tabla
	$sql_create = $rowFk[1];


	// busco las fks
	preg_match_all("/CONSTRAINT\s*\`(?P<fk_nombre>.+?)`\s*FOREIGN\s*KEY\s*\(\`(?P<fk_local>.+?)\`\)\s*REFERENCES\s*\`(?P<fk_tabla>.+?)\`\s*\(\`(?P<fk_reference>.+?)\`\)/si", $sql_create ,$fks,PREG_SET_ORDER);

	foreach ($fks as $i => $fk) {
		
		$fk_nombre = $fk['fk_nombre'];
		$fk_local = $fk['fk_local'];
		$fk_tabla = $fk['fk_tabla'];
		$fk_reference = $fk['fk_reference'];

		$fkeys .= "\$fk['".$fk_local."']= new Fk( '".$fk_nombre."', '".$fk_local."', '".$fk_tabla."', '".$fk_reference."' );\n";
		$fkeys .= "\$fk[".$i."]= new Fk( '".$fk_nombre."', '".$fk_local."', '".$fk_tabla."', '".$fk_reference."' );\n";

		// guardo la clave foranea, la tabla de la cual sale la fk, el campo de donde sale
		// la tabla a la cual apunta y el campo al cual apunta, todo esto para poder
		// crear una lista de obj en la clase apuntada por esta fk
		$relaciones[] = array($row[0],$fk_local,$fk_tabla,$fk_reference); 
	}
	
	// guardo el tipo de tabla 
	preg_match("/ENGINE=(?P<engine>[.a-zA-Z0-9_-]+)/si", $sql_create ,$eng);
	$engine = $eng['engine'];	

}

	$consulta2 = $conexion->query("describe ".$row[0]);
	while ( $row2 = $consulta2->fetch_assoc() ){

		// creo los atributos
		// si el nombre de un campo de tabla comienza con
		// numero, le agrego un _ adelante, las var que comienzan
		// con numero no son validas en php
		$_nombre = strtolower( ( is_numeric( substr($row2['Field'],0,1) ) )?"_".$row2['Field']:$row2['Field'] );  

		$_tipo = $row2['Type'];
		$_es_null = $row2['Null'];
		$_es_k_primaria = ( $row2['Key'] == 'PRI' ) ? true : false;
		$_default = ( is_null($row2['Default']) )? "" : $row2['Default'];
		$_extra = $row2['Extra'];


		//$_describe_table .= "array_push(self::\$table, new Describe_tables('".$row2['Field']."','".$row2['Type']."','".$row2['Null']."','".$row2['Key']."','".$row2['Default']."','".$row2['Extra']."','".$nombre_tabla."'));\n";
		$_describe_table .= "\$fields['".$row2['Field']."'] = new Field('".$row2['Field']."','".$row2['Type']."','".$row2['Null']."','".$row2['Key']."','".$row2['Default']."','".$row2['Extra']."');\n";
		
		$atributos_clase .= "private \$".$_nombre.";\n";


		if( $_es_k_primaria ) 
			$nombre_pk = $row2['Field'];


		// creo el constructor con todos los atributos opcionales, el unico que no pongo es la clave
		if( $_es_k_primaria === false){ //$_es_null == "NO" && 

			$atributos_constructor.=( $_default != "" ) ? "\$".$_nombre." = ".$_default.", " : "\$".$_nombre."=NULL, ";			
			$set_atributos_constructor .= "\$this->set".ucfirst($_nombre)."(\$".$_nombre.");\n";
		}

		// creo getters y setters
		//if( $_es_k_primaria == false ){	
		$get_set_functions .= "public function set".ucfirst($_nombre)."(\$".$_nombre."){\n";
		$get_set_functions .= "\$this->".$_nombre."=\$".$_nombre.";\n";
		$get_set_functions .= "}\n";
		//}

		$get_set_functions .= "public function get".ucfirst($_nombre)."(){\n";
		$get_set_functions .= "return \$this->".$_nombre.";\n";
		$get_set_functions .= "}\n";

	}

$atributos_constructor = substr( $atributos_constructor,0,( strlen($atributos_constructor)-2) );

// creo template de la clase
$contexto = array(
"nombre_tabla"=>$nombre_tabla,
"nombre_clase"=>$nombre_clase,
"atributos_clase" => $atributos_clase,
"nombre_pk" => $nombre_pk,
"atributos_constructor"=>$atributos_constructor,
"set_atributos_constructor"=>$set_atributos_constructor,
"get_set_functions"=>$get_set_functions,
"describe_table"=>$_describe_table,
"get_pk"=> ucfirst($nombre_pk),
"sql_create"=>$sql_create,
"engine"=>$engine,
"fkeys"=>$fkeys
);


// creo el archivo de la clase
$plantilla_clase = new Template("./oClase_generica.tpl",$contexto);
$plantilla_clase->parsearTemplate();
$clase = $plantilla_clase->getTemplate();
if( ! $pt = fopen( "../core/orm/".$nombre_archivo, "w" ) ){
	return "Error: Imposible crear clase!";
}else{

	fputs($pt,$clase);
	fclose($pt);
}


$contexto2 = array(
"nombre_clase"=>$nombre_clase,
"nombre_clase_comun"=>$nombre_clase_comun
);

// creo el archivo que hereda d ela clase generica
$plantilla_clase_comun = new Template("./Clase_generica.tpl",$contexto2);
$plantilla_clase_comun->renderTemplate();
$clase_comun = $plantilla_clase_comun->getTemplate();
if( ! $pt = fopen( "../clases/".$nombre_archivo_comun, "w" ) ){
	return "Error: Imposible crear clase!";
}else{
	fputs($pt,$clase_comun);
	fclose($pt);
}


}
 
// creo archivo  include_all
if( ! $pt = fopen( "../core/orm/IncludeAll.php", "w" ) ){
	return "Error: Imposible crear clase!";
}else{

	fputs($pt,"<?php\n");

	foreach ($array_clases as $i => $clase) {
		fputs($pt,"include_once('".$clase.".php');\n");
	}
	
	fputs($pt,"?>\n");
	fclose($pt);
}



// creo archivo  include_all de las clases comunes
if( ! $pt = fopen( "../clases/IncludeAllComun.php", "w" ) ){
	return "Error: Imposible crear clase!";
}else{

	fputs($pt,"<?php\n");

	foreach ($array_clases_comun as $i => $clase) {
		fputs($pt,"include_once('".$clase.".php');\n");
	}
	
	fputs($pt,"?>\n");
	fclose($pt);
}



// creando relaciones entre tablas
//array(1) { [0]=> array(4) { [0]=> string(7) "usuario" [1]=> string(5) "grupo" [2]=> string(6) "grupos" [3]=> string(2) "id" } } 
for($i=0; $i< count($relaciones);$i++){

$parametro_privado = "private $".strtolower($relaciones[$i][0]).";
{{atributos_de_relacion}}";

$funcion_lista = "
public function get".ucfirst($relaciones[$i][0])."(){
	return o".ucfirst($relaciones[$i][0])."::filter(\"".$relaciones[$i][1]." == '\".\$this->getPk().\"'\");
}
{{relation_functions}}";


	$contexto_relacion = array(
	'atributos_de_relacion'=>$parametro_privado,
	'relation_functions'=>$funcion_lista
	);

	$arch = '../core/orm/o'.ucfirst($relaciones[$i][2]).'.php';
	$tmp = new Template($arch,$contexto_relacion);
	$tmp->parsearTemplate();

	// guardo el archivo parseado
	if( ! $pt = fopen($arch, "w" ) ){
		return "Error: Imposible parsear clase!";
	}else{

		fputs($pt,$tmp->getTemplate());
		fclose($pt);
	}

}


// limpiando varibles suelta en las clases de relacion
//for($i=0; $i< count($relaciones);$i++){
foreach ($array_clases as $i => $clase) {	
	
	$arch = '../core/orm/'.$clase.'.php';
	$tmp = new Template($arch);
	$tmp->renderTemplate();

	// guardo el archivo parseado
	if( ! $pt = fopen($arch, "w" ) ){
		return "Error: Imposible limpiar clase!";
	}else{

		fputs($pt,$tmp->getTemplate());
		fclose($pt);
	}
}

// fin de la conexion
?>