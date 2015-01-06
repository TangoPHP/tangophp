function paginar_objetos(id_cuerpo, parametros, url, pagina){
	
	var cuerpo = $(id_cuerpo);
	
	$.ajax({
		type: 'post',
		dataType: 'html',
		url: url,
		data: '{ '+ parametros +'pagina:'+ pagina +'}',
		sync:false,
		beforeSend: function(obj){
			$("#cargandoPaginado").html("<div class='cargando'></div>");
			cuerpo.fadeOut(300, function(){
				$("#cargandoPaginado").fadeIn(300);			   
			});
		},
		success: function(json){
						
			$("#cargandoPaginado").fadeOut(200,function(){
			   	cuerpo.html(json);
				cuerpo.fadeIn(500, function(){$("#cargandoPaginado").html("");});
			});	
		}
	});
}