<?php
/**
 * Clase Template, parsea los templates usados por el sistema.
 * 
 * Esta clase se encarga de parsear los templates usados por el sistema
 * nos permite seleccionar un template, pasarle un contexto con las 
 * variables u objetos que vamos a usar para procesar el template.
 *
 * @author Gastón Mura <gastonmura@hotmail.com>
 * @version 1.0
 * @package Template
 */
class Template{

	//atributos
	private $contexto;
	private $template_cont;
	private $delimitador_inicio;
	private $delimitador_final;
	private $template_dir;

	public function __construct($template = NULL,$contexto=array(), $delimitador_inicio = "{" , $delimitador_final = "}",$template_dir = "templates/" ){
		
		$this->setContexto($contexto); 
		$this->setTemplate($template);
		$this->setDelimitadorInicio($delimitador_inicio); 
		$this->setDelimitadorFinal($delimitador_final);
		$this->setTemplateDir($template_dir);
	}

	// Esta function recibe el nombre de un template ($template)
	// se fija si existe y si es asi lo abre y lo devuelve
	public static function readTemplate($template){
	  
	  $chars="";
	  if( file_exists($template) ){
	    
			if( ! $pt=fopen($template,"r") ){
				return "Error: Imposible abrir el Template ".$template;
			}else{
				while(! feof($pt) ){
					$chars.=fgets($pt);
				}
				fclose($pt);
					return $chars;
			}

	  }else{
	  	return "Error: Template no encontrado ".$template;
	  }
	}

	// Funcion que me permite extender un template
	// utilizando como base a otro
	private function extendTemplate(){

	// recupero delimitadores
	$delIni = $this->getDelimitadorInicio();
	$delFin = $this->getDelimitadorFinal();

	// me fijo si la plantilla quiere extender de alguna otra
	preg_match("/".$delIni."\%\s*extends\s*\"([.a-zA-Z0-9-_]+)\"\s*\%".$delFin."/si", $this->template_cont ,$salida);

	// TO2 esta asignacion ouede dar error arreglarla
	$plantilla_extends = $salida[1];

	// si es asi, conpruebo que el template exista
	if( !empty($salida) ){
		
		// abro el template del cual extiende el templete actual
		if ( file_exists( $this->getTemplateDir().$plantilla_extends) ){
			$template_base = self::readTemplate( $this->getTemplateDir().$plantilla_extends );
			
		}else{
			
			return false;
		}

		// TO2 solucionar el tema de cuando entre los tags de blokes no hay nada {%block ejemplo%}{endblock}, de esa manera no parsea bien
		// junta el sigueinte tag de bloque
		
		//busco todos los bloques de contenido del template actual (no del que extiende)
		preg_match_all("/".$delIni."\%\s*block\s*([a-zA-Z0-9_-]+)\s*\%".$delFin."\s*(.+?)\s*".$delIni."\%\s*endblock\s*\%".$delFin."/si", $this->template_cont ,$blokes,PREG_SET_ORDER);

		foreach ($blokes as $b => $bloke) {
	
			// blokes del template actual	
			$_patron = $bloke[0]; // patron completo encontrado en el template
			$_nombre_bloke = $bloke[1]; // nombre del bloke
			$_contenido_bloke = $bloke[2]; // contenido del bloke

			// busco los mismos blokes en el template que extiende
			preg_match("/".$delIni."\%\s*block\s*(".$_nombre_bloke.")\s*\%".$delFin."\s*".$delIni."\%\s*endblock\s*\%".$delFin."/si", $template_base ,$bloke_extends);

			// si encuentro el bloke
			if( !empty($bloke_extends) ){

				// recupero el patron a reemplazar
				$_patron_extends = $bloke_extends[0];
				// lo reemplazo
				$template_base = str_ireplace( $_patron_extends,$_contenido_bloke,$template_base );
			}
		}

		// una ves que reemplace todos los blokes del template base
		// con los blokes definidos en el template actual, seteo
		// el nuevo template
		$this->setTemplate($template_base);
	}

	}


	// Esta funcion analiza el html del template dado y reemplaza todas las
	// var que este contiene por su correspondiente valor enviado en la var
	// contexto.
	public function parsearTemplate(){
		
		//recupero los delimitadores
		$delIni = $this->getDelimitadorInicio();
		$delFin = $this->getDelimitadorFinal();

		//no borra las variables que no esten en el contexto
		//rescato del template todas las variables y las guardo en el array salida
		preg_match_all("/".$delIni.$delIni."\s*([a-zA-Z0-9_-]+)\s*".$delFin.$delFin."/i", $this->template_cont ,$salida,PREG_SET_ORDER);

		
		$arrayVar = array();
		//pasamos las var encontradas, a minusculas para 
		//que no sea sensible a mayusculas, minusculas.
		foreach ($salida as $key => $value) {
			//$arrayFixSalida[$key][0] = strtolower($value[0]);
			//$arrayFixSalida[$key][1] = strtolower($value[1]);
			$arrayVar[strtolower($value[1])] = strtolower($value[1]);
		}
		//var_dump($arrayVar);
		//$arrayVar=array_change_key_case( $arrayVar , CASE_LOWER);
		//var_dump($arrayVar);
		//var_dump($salida);

		foreach ($arrayVar as $key => $value) {
			
				if( $this->contexto[$value] != "" )
					$this->template_cont = $this->reemplazarVar($value,$this->contexto[$value],$this->template_cont);
				
		}

	}

// Esta funcion analiza el html del template dado en busca de estructuras de control
	// del tipo "for", cuando encuentra una estructura valida, busca en el contexto la lista 
	// de objetos utilizada en el for y parsea el contenido entre las etiquetas for/endfor
	// con los atributos del objeto dado
	private function paresearForObjTemplate(){

	// recupero los delimitadores
	$delIni = $this->getDelimitadorInicio();
	$delFin = $this->getDelimitadorFinal();

	
	preg_match_all("/".$delIni."\%\s*for\s*([a-zA-Z0-9_-]+)\s*in\s*([a-zA-Z0-9_-]+)\s*\%".$delFin."\s*(.+?)\s*".$delIni."\%\s*endfor\s*\%".$delFin."/si", $this->template_cont ,$salida,PREG_SET_ORDER);


	if(!$salida)
		return false;
	
	foreach ($salida as $indice => $for) {
		
		$_patron = $for[0];
		$_prefix = $for[1];         // (p).attr
		$_lista_obj = $for[2];      // ($lista_obj)
		$_contenido = $for[3];      // el contenido entre los for/endfor

		//echo "<br> patron: ".$_patron." prefijo: ".$_prefix." lista: ".$_lista_obj." content: ".$_contenido;

		$template_item = "";
		$i = 1;

		//controlo que el argumento del contexto sea un arreglo
		if(  is_array( $this->contexto[$_lista_obj] ) === false  ){
			
			 if( is_object( $this->contexto[$_lista_obj] ) === false ){
			 	$this->template_cont = str_replace($_patron, "", $this->template_cont);	
				return false;	
			 }else{
			 	$obj[0] = $this->contexto[$_lista_obj];
			 	$this->contexto[$_lista_obj] = $obj;
			 }
			
		}		
		
		//echo "<br> antes de girar por los obj";
			//recorro la lista de objetos
			foreach ( $this->contexto[$_lista_obj] as $k => $obj) {

				// si el elemento que estoy mirando no es un objeto 
				// hacemos un continue y pasamos al otro obj, suponiendo 
				// que en el arreglo puedan haber distintos tipos de objetos
				if( ! is_object($obj) )
					continue;

				//echo "<br> sigo girando ".$obj;

				// controlo que la clase exista
				if( class_exists(get_class($obj),false) ){

					$primer_item = true;

					//busco las variables en el cuerpo del for
					preg_match_all("/".$delIni.$delIni."\s*".$_prefix ."\.([.a-zA-Z0-9_-]+)\s*".$delFin.$delFin."/", $_contenido ,$cuerpo_for,PREG_SET_ORDER);
					

					foreach ($cuerpo_for as $key => $value){
						
						//nombre de la variable a reemplazar
						$nombre_var = $_prefix.".".$value[1];
						$nombre_var_indice = $_prefix.".i";

						// TO2 esto esta mal tengo que seguir y no hacer nada con la var
						// no reemplazarla por nada, dejarla para que al final si nadie la reemplazo
						// borrarla
						// si no obtengo ningun valor salteo la var
						//echo "<br>i: ".$i." var antes: ".$nombre_var."  valor:".$valor."<br>";
						
						if( ($valor = $this->getValorAttr($obj,$value[1])) === false )
						{	
							//$valor = "";
							$template_item = Template::reemplazarVar($nombre_var,$valor,$template_item);
							continue;
						}
						
						//echo "var despues: ".$nombre_var."  valor:".$valor."   i:".$i."<br>";


						if( $primer_item ){
							$template_item .= Template::reemplazarVar($nombre_var,$valor,$_contenido);
							$template_item = Template::reemplazarVar($nombre_var_indice,$i,$template_item);
							$primer_item = false;
						}else{
							$template_item = Template::reemplazarVar($nombre_var,$valor,$template_item);
							$template_item = Template::reemplazarVar($nombre_var_indice,$i,$template_item);
						}

					}

				}		
			
				$i++;
			}
		
			$this->template_cont = str_replace($_patron, $template_item, $this->template_cont);	
			
		}
		//echo "<br> COETEN:<br>".$this->template_cont;
		$this->paresearForObjTemplate();
	}

/**
* Retorna el valor de una variable template o false.
*
* Esta funcion recibe dos parametros, el primero es el objeto de donde extraeremos el valor de
* la varible template, y el segundo es el nombre de la variable template.
* 
* @param object $obj      Objeto de donde sacaremos el valor de la var template.
* @param string $cadAttr  Cadena que representa la variable encontrada en el template.
* @return string | false  Retorna el valor de la varible o false en caso de que no exita la variable.
*/
private function getValorAttr($obj,$cadAttr){

		$reflector = new ReflectionClass(get_class($obj));

		$tockens = explode(".",$cadAttr);
		
		if( count($tockens) > 1 ){

			$valor = $obj;

			foreach ($tockens as $j => $v) {
				
				$nombre_metodo = "get".ucfirst(strtolower($v));

				if( $reflector->hasMethod($nombre_metodo) ){
					
					try{

						if( ! is_object($valor) ){
							return false;
						}

						$valor = $valor->$nombre_metodo();			

						if( ((count($tockens)-1) != $j) && ( @get_class($valor) !== false )){
							$reflector = new ReflectionClass(get_class($valor));
						}
					 
					}catch(Exception $e){
						echo "<br>Error: ".$e->getMessage();
					}

				}else{
					return false;
				}
			}

		}else{
		
			$nombre_metodo = "get".ucfirst(strtolower($cadAttr));

			if( $reflector->hasMethod($nombre_metodo) ){	
				$valor = $obj->$nombre_metodo();	
			}else{
				return false;
			}
		}

		return $valor;
	}

	// funcion que recorre un array
	// {% foreach  arreglo as key->value %} xxx {%endfor%}
	// brindan la var {{i}} por defecto
	private function paresearForeachArrayTemplate(){

	// recupero los delimitadores
	$delIni = $this->getDelimitadorInicio();
	$delFin = $this->getDelimitadorFinal();

	preg_match_all("/".$delIni."\%\s*foreach\s*([a-zA-Z0-9_-]+)\s*as\s*([a-zA-Z0-9_-]+)\s*\-\>\s*([a-zA-Z0-9_-]+)\s*\%".$delFin."\s*(.+?)\s*".$delIni."\%\s*endforeach\s*\%".$delFin."/si", $this->template_cont ,$salida,PREG_SET_ORDER);

	foreach ($salida as $indice => $for) {
		
		$_patron = $for[0];
		$_array = ( is_array($this->contexto[$for[1]]) ) ? $this->contexto[$for[1]] : array();      // array a recorrer, tiene que ser enviado en el contexto
		$_clave = $for[2];      // clave que vamos a usar
		$_valor = $for[3];      // valor de esa clave en el array
		$_contenido = $for[4];  // contenido del foreach
		$i = 1;

		$template_item="";
		$primer_item = true;

		//recorro la lista de objetos
		foreach ( $_array as $k => $v) {
				
			if( is_object($v) )
				continue;

			if( $primer_item ){
				$template_item .= Template::reemplazarVar($_clave,$k,$_contenido);
				$template_item = Template::reemplazarVar($_valor,$v,$template_item);
				$template_item = Template::reemplazarVar("i",$i,$template_item);
				$primer_item = false;
			}else{
				$template_item .= Template::reemplazarVar($_clave,$k,$_contenido);
				$template_item = Template::reemplazarVar($_valor,$v,$template_item);
				$template_item = Template::reemplazarVar("i",$i,$template_item);
			}	
			
			$i++;
		}
	
		$this->template_cont = str_replace($_patron, $template_item, $this->template_cont);	
	}
	}

	// loop estructura repetitiva
	// {% loop n%} xxxx {%endloop%}
	// n puede ser un numero o una variable que se este en el contexto
	private function paresearLoopTemplate(){

	// recupero delimitadores
	$delIni = $this->getDelimitadorInicio();
	$delFin = $this->getDelimitadorFinal();

	preg_match_all("/".$delIni."\%\s*loop\s*([a-zA-Z0-9]+)\s*\%".$delFin."\s*(.+?)\s*".$delIni."\%\s*endloop\s*\%".$delFin."/si", $this->template_cont ,$salida,PREG_SET_ORDER);

	foreach ($salida as $indice => $loop) {
		
		$_patron = $loop[0];
		$_n = ( is_numeric($loop[1]) ) ? (integer)$loop[1] : ( ( is_string($loop[1]) ) ? (( array_key_exists($loop[1],$this->contexto) )?(integer)$this->contexto[$loop[1]]:0) : 0 ) ; // me fijo si "n" es un numero si no lo es lo pongo en 0
		$_contenido = $loop[2];  // contenido del loop
		$i = 1;

		$template_item="";

		while( $i <= $_n ){
			
			$template_item .= Template::reemplazarVar("i",$i,$_contenido);
			$i++;
		}
	
		$this->template_cont = str_replace($_patron, $template_item, $this->template_cont);	
	}
	}

	private function paresearIfTemplate(){

	// recupero delimitadores
	$delIni = $this->getDelimitadorInicio();
	$delFin = $this->getDelimitadorFinal();

	preg_match_all("/".$delIni."\%\s*if\s*(not)?\s*([a-zA-Z0-9_-]+)\s*\%".$delFin."\s*(.+?)\s*(".$delIni."\%\s*else\s*\%".$delFin."\s*(.+?)\s*)?".$delIni."\%\s*endif\s*\%".$delFin."/si", $this->template_cont ,$salida,PREG_SET_ORDER);

	foreach ($salida as $indice => $if) {
		
		$_patron = $if[0];
		$_condicion = ( array_key_exists(1,$if) ) ? strtolower($if[1]): ""; // si niego o no la variable
		$_var = strtolower($if[2]);       // variable pasada a minusculas
		$_contenido = $if[3]; // el contenido del if
		$_contenido_else = ( array_key_exists(5,$if) ) ? $if[5] : ""; // el contenido del else

		//busco la variable en el contexto que le pasamos al template
		$_contexto_var = ( array_key_exists($_var,$this->contexto) ) ? $this->contexto[$_var] : false;

		//lo primero que hago es ver la condicion
		//si es "not" entonces la varible mencionada
		//tiene que ser false
		if($_condicion == "not"){
			
			//  se considera falso a:
			// false, 0, "", null, "0"

			if( !$_contexto_var ) 
			{
				//echo "<br>not/var==false /----> var ".$_var."  valor ".$_contexto_var;
				$this->template_cont = str_replace($_patron, $_contenido, $this->template_cont);
			}else{
				//echo "<br>not/var!=false /----> var ".$_var."  valor ".$_contexto_var;				
				$this->template_cont = str_replace($_patron, $_contenido_else, $this->template_cont);
			}
		}


		if($_condicion == ""){ 
		
			//  se considera true a:
			// true, 1, !="", !=null, n!=0

			if( $_contexto_var )
			{
				//echo "<br>sin not/var==true /----> var ".$_var."  valor ".$_contexto_var;
				$this->template_cont = str_replace($_patron, $_contenido, $this->template_cont);
			}else{
				//echo "<br>sin not/var!=true /----> var ".$_var."  valor ".$_contexto_var;
				$this->template_cont = str_replace($_patron, $_contenido_else, $this->template_cont);

			}
		}

	}
	}

//if que compara mayor, menor e igual con numeros y string.
private function paresearIfCompareTemplate(){

	// recupero delimitadores
	$delIni = $this->getDelimitadorInicio();
	$delFin = $this->getDelimitadorFinal();

	preg_match_all("/".$delIni."\%\s*if\s*([a-zA-Z0-9_-]+)\s*(\<\=|\<|\>\=|\>|\=\=|\!\=)\s*([a-zA-Z0-9_-]+)\s*\%".$delFin."\s*(.+?)\s*(".$delIni."\%\s*else\s*\%".$delFin."\s*(.+?)\s*)?".$delIni."\%\s*endif\s*\%".$delFin."/si", $this->template_cont ,$salida,PREG_SET_ORDER);

	foreach ($salida as $indice => $if) {
		
		$_patron = $if[0];
		$_var1 = strtolower($if[1]); // variable pasada a minusculas
		$_condicion = $if[2]; // si niego o no la variable
		$_var2 = strtolower($if[3]); // variable pasada a minusculas
		$_contenido = $if[4]; // el contenido del if
		$_contenido_else = ( array_key_exists(6,$if) ) ? $if[6] : ""; // el contenido del else

		//busco la variable en el contexto que le pasamos al template
		//si no esta en el contexto usamos var comocadena para comparar
		$_contexto_var1 = ( array_key_exists($_var1,$this->contexto) ) ? $this->contexto[$_var1] : $_var1;
		$_contexto_var2 = ( array_key_exists($_var2,$this->contexto) ) ? $this->contexto[$_var2] : $_var2;
		
		$_resultado = false;

		//con un switch voy a la condicion
		switch($_condicion){

			case "<": 
				if( $_contexto_var1 < $_contexto_var2 )
					$_resultado = true;
			break;

			case "<=": 
				if( $_contexto_var1 <= $_contexto_var2 )
					$_resultado = true;
			break;

			case ">": 
				if( $_contexto_var1 > $_contexto_var2 )
					$_resultado = true;
			break;

			case ">=": 
				if( $_contexto_var1 >= $_contexto_var2 )
					$_resultado = true;
			break;

			case "==": 
				if( $_contexto_var1 == $_contexto_var2 )
					$_resultado = true;
			break;

			case "!=": 
				if( $_contexto_var1 != $_contexto_var2 )
					$_resultado = true;
			break;
		}

		//dependiendo el resultado muestro el contenido de if o del else
		if(	$_resultado )
		{
			$this->template_cont = str_replace($_patron, $_contenido, $this->template_cont);
		}else{

			$this->template_cont = str_replace($_patron, $_contenido_else, $this->template_cont);
		}
	
	}
}

	// TO2 ver como parseo las var que son objetos
	// Esta funcion reemplaza una var por su contenido en un template dado
	public function reemplazarVar($pattern, $valor, $template){

		// recupero delimitadores
		$delIni = $this->getDelimitadorInicio(); 
		$delFin = $this->getDelimitadorFinal();

		return preg_replace("/".$delIni.$delIni."\s*".$pattern."\s*".$delFin.$delFin."/i",$valor,$template);
	}

	public function renderTemplate(){

		//parseo el template antes de mosrarlo
		$this->extendTemplate();
		$this->paresearLoopTemplate();
		$this->paresearForObjTemplate();
		$this->paresearForeachArrayTemplate();
		$this->paresearIfTemplate();
		$this->paresearIfCompareTemplate();
		$this->parsearTemplate();

		// recupero delimitadores
		$delIni = $this->getDelimitadorInicio(); 
		$delFin = $this->getDelimitadorFinal();

		//y si quedan variables sueltas, las borra
		preg_match_all("/".$delIni.$delIni."\s*([.a-zA-Z0-9_-]+)\s*".$delFin.$delFin."/", $this->template_cont ,$salida,PREG_SET_ORDER);

		foreach ($salida as $key => $value) 
			$this->template_cont = $this->reemplazarVar($value[1],"",$this->template_cont);


		//si quedan blokes de contenido sueltos los borra
		preg_match_all("/".$delIni."\%\s*block\s*([a-zA-Z0-9_-]+)\s*\%".$delFin."\s*".$delIni."\%\s*endblock\s*\%".$delFin."/si", $this->template_cont ,$blokes,PREG_SET_ORDER);

		foreach ($blokes as $b => $bloke) 
			$this->template_cont = str_ireplace($bloke[0],"",$this->template_cont);	

	}


	// Esta funcion muestra el template, antes de eso busca las varibles 
	// que estan sin reemplazar y las borra
	public function showTemplate(){
		
		// renderizo el template
		$this->renderTemplate();
		// y lo muestro
		echo $this->template_cont;
	}

	// si creo el template sin parametros
	// puedo setearle un template ya abierto 
	// con readTemplate() o enviarle el nombre 
	//del template que quiero abrir
	
	public function setTemplate($template){	
		if( file_exists( $this->getTemplateDir().$template) )	
			$this->template_cont = self::readTemplate( $this->getTemplateDir().$template );
		else
			$this->template_cont = $template;
	}

	public function getTemplate(){
		return $this->template_cont;
	}

	public function getContexto(){
		return $this->contexto;
	}

	public function setContexto($contexto){
		$this->contexto = array_change_key_case($contexto, CASE_LOWER);
	}

	public function setDelimitadorInicio($delimitador){
		
		$this->delimitador_inicio = preg_quote($delimitador);
	}

	public function getDelimitadorInicio(){
		return $this->delimitador_inicio;	
	}

	public function setDelimitadorFinal($delimitador){

		$this->delimitador_final = preg_quote($delimitador);	
	}

	public function getDelimitadorFinal(){
		return $this->delimitador_final;	
	}

	public function getTemplateDir(){
		return $this->template_dir;
	}

	public function setTemplateDir($template){
		$this->template_dir = $template;
	}
}

?>