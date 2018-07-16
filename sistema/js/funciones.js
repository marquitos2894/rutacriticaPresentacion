// JavaScript Document

$( function() {
    var tabs = $( "#tabs" ).tabs();
    tabs.find( ".ui-tabs-nav" ).sortable({
      axis: "x",
      stop: function() {
        tabs.tabs( "refresh" );
      }
    });
  } );
  
$( function() {
    var tabs = $( "#tabs2" ).tabs();
    tabs.find( ".ui-tabs-nav" ).sortable({
      axis: "x",
      stop: function() {
        tabs.tabs( "refresh" );
      }
    });
  } );
  
  
$(document).ready(function(e) {	
		// alert("left " + $('#divActB').offset().left + "  top " +  $('#divActB').offset().top);
		
	  $("#divActA").draggable({
			start: function() { 
			},
			 drag: function() {
				 $('#lnAB').attr({'x1':$('#divActA').offset().left - 9 + 100,
					 			  'y1':$('#divActA').offset().top - 30 + 50});
			},
			stop: function() {       
			 
			}
      });
	  
	  $("#divActB").draggable({
			start: function() { 
			},
			 drag: function() {   
			 $('#lnAB').attr({'x2':$('#divActB').offset().left - 9,
					 		  'y2':$('#divActB').offset().top - 30 + 50});
			      
			},
			stop: function() {  

			}
      });
	  
	 
		
 });


$(document).ready(function(e) {	
		    
	$('#btnImportar').click(function(e) {
		cargar_datos();
    });	
	
	    $("#divActA").draggable({
      start: function() { 
      },
      drag: function() {       
      },
      stop: function() {       
      }
    });
	
 });
 
 
function cargar_datos(){
	var archivos = document.getElementById("archivos");
  	var archivo = archivos.files;
  
	
  var data = new FormData();  
  for(i=0; i<archivo.length; i++){
    data.append('archivo'+i,archivo[i]);
  }

	 
  $.ajax({
	data:data,
    url:"importar_excel.php", 
    type:'POST',
    contentType:false,
    processData:false,
    cache:false,
	beforeSend: function(){
	  	//$("#divResultado").html("Procesando, espere por favor...");
	},
	success: function(response){
	    $("#divResultado").html(response);
	},
	error: function(msj) {
  		$("#divResultado").html(msj);
	}
  });

}