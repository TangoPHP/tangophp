<?php 
require_once("include/Paginator.php");
require_once("include/Templates.php");
require_once("include/clases/IncludeAllComun.php");

// paginado
$pagina = ( ! isset( $_REQUEST['pagina'] ) ) ? (integer) 1 : (integer) $_REQUEST['pagina'];
$inicio_paginas = ( $pagina == 1 ) ? 0 : ( $pagina - 1 ) * HILOS_POR_PAGINA;
$d = Denuncias::filter("idDenuncia >='1' :limit: ".$inicio_paginas.",".HILOS_POR_PAGINA);
$total_resultados = Denuncias::count();

	if ( $total_resultados > HILOS_POR_PAGINA ){
		
		$paginator = new Paginator($total_resultados ,HILOS_POR_PAGINA);
		$paginator->setPaginaActual($pagina);
		$paginator->setLink("?pagina=");

		$contexto = array(
			"links"=>$paginator->obtenerLinkDelSector(),
			"RESULTADOS"=>$total_resultados,
			"LINK_ULTIMO"=>$paginator->linkUltimo(),
			"LINK_PRIMERO"=>$paginator->linkPrimero(),
			"LINK_SIGUIENTE"=>$paginator->linkSiguiente(),
			"LINK_ANTERIOR"=>$paginator->linkAnterior(),
			"NUMERO_PAGINA"=>$paginator->getPaginaActual(),
		);
		
		$item_paginas = new Template("templates/paginado.html",$contexto);
		$item_paginas->renderTemplate();
	}

$hay_paginas = ( isset($item_paginas) && $item_paginas!= "" ) ? $item_paginas->getTemplate() : false;	

// fin paginado //	

$contexto = array(
	'anio' =>date("Y"),
	'denuncia' => $d,
	'gaston' => 'GaStOn 2015',
	'hay_paginas' => $hay_paginas
);

$index = new Template('templates/index.html',$contexto);
$index->showTemplate();
?>