<?php

$m = Model::getInstance();

//YuppLoader::loadScript("components.blog", "Messages");

$skin = $m->get('skin');

?>

<html>
   <head>
      <style>
        #top span {
        	 border: 1px solid #000;
          background-color: #ffffcc;
          width: 260px;
          height: 130px;
          padding: 5px;
          position: relative;
          display: block;
          float: left;
          overflow: auto;
        }
        #skin {
          margin-right: 5px;
        }
        #templatePage {
          margin-right: 5px;
        }
        #selectedZone {
        }
        
        #editZones {
          clear:    left;
        	 width:    768px;
          height:   500px;
          border:   1px solid #333;
          overflow: auto;
          position: relative;
        }
        .newZone {
        	 width:  100px;
          height: 80px;
          /* border: 1px dashed #333; */
          background-color: #ffaa00;
          position: absolute;
        }
        
        .selectedZone {
          background-color: #ffdd00;
          color: #003399;
        }
        
        .ui-resizable-s, .ui-resizable-n {
          width: 30px;
        }
        
        input[name=selected_zone_height], input[name=selected_zone_width], input[name=selected_zone_posX], input[name=selected_zone_posY] {
        	 width: 40px;
        }
        /*
        #editZones ul .ui-selected div {
          background: #ffdd00 none repeat scroll 0 0;
          color: #0033992;
        }
        #editZones ul li {
          list-style-type: none;
          position: absolute;
        }
        */
      </style>
   
      <?php echo h('js', array('component'=>'cms', 'name'=>'jquery-1.3.1.min')); ?>
      <?php echo h('js', array('component'=>'cms', 'name'=>'jquery-ui--drag-resize-select--1.6rc5.min')); ?>
      
      <script type="text/javascript">
        // Dada una medida del tipo dddpx devuelve solo ddd.
        //
        var _num = function( sizepx ) {
          return sizepx.substr( 0, sizepx.length - 2 );
        }
      </script>
      
      <script type="text/javascript">
        
        var zoneCount = 0; // TODO: inicializar con la cantidad de zonas que tiene la pagina de la skin.
        var selectedZone = null;
        
        // 10 bgs para las zonas que se vayan creando.
        var newZoneBGs = [
        	 '#ffaa00',
          '#ff00aa',
          '#aaff00',
          '#00ffaa',
          '#00aaff',
          '#aa00ff',
          '#ffffaa',
          '#ffaaff',
          '#aaffff',
          '#ffaaaa'
        ];
       
        // jQuery
        $(document).ready( function() {
           
           // Si estoy editando quiero que se creen las zonas existentes
           <?php if ( $skin->getId() !== NULL ): ?>
           
              /*
               <?php Logger::struct($skin->getTemplatePage()); ?>
             */
             
              <?php foreach( $skin->getTemplatePage()->getTemplateZones() as $zone ): ?>
              
                zoneCount++;
                newZoneId = "zone_"+zoneCount;
            
                // Agrega una nueva div de zone a la div con id "editZones".
                $("#editZones").append('<div class="newZone" id="'+newZoneId+'">[::<?php echo $zone->getName(); ?>::]</div>');
             
                // Posiciona y dimensiona correctamente
                $("#"+newZoneId).css('left',   <?php echo $zone->getPosX(); ?>);
                $("#"+newZoneId).css('top',    <?php echo $zone->getPosY(); ?>);
                $("#"+newZoneId).css('width',  <?php echo $zone->getWidth(); ?>);
                $("#"+newZoneId).css('height', <?php echo $zone->getHeight(); ?>);
                
                $("#"+newZoneId).css('background-color', newZoneBGs[(zoneCount-1) % 10]);
                
                // Campos hidden para mantener datos de cada zone.
                // Cuando se submitee hay que actualizar los valores de estos campos.
                $("#skin_edit_form").append('<input type="text" name="zone_id[]"     value="'+newZoneId+'" />');
                $("#skin_edit_form").append('<input type="text" name="zone_name[]"   value="<?php echo $zone->getName(); ?>" />');
                $("#skin_edit_form").append('<input type="text" name="zone_posX[]"   value="'+ _num($("#"+newZoneId).css('left'))  +'" />');
                $("#skin_edit_form").append('<input type="text" name="zone_posY[]"   value="'+ _num($("#"+newZoneId).css('top'))   +'" />');
                $("#skin_edit_form").append('<input type="text" name="zone_width[]"  value="'+ _num($("#"+newZoneId).css('width')) +'" />');
                $("#skin_edit_form").append('<input type="text" name="zone_height[]" value="'+ _num($("#"+newZoneId).css('height'))+'" /><br/>');
             
                
                // ==================================================================================
                 // Seleccion de zone:
                 //
                 $("#"+newZoneId).click( function(evn, ui) {
   
                   if (selectedZone)
                     selectedZone.removeClass('selectedZone');
                     
                   selectedZone = $("#"+evn.target.id);
                   selectedZone.addClass('selectedZone');
                   
                   //alert( "SELECTED ZONE ID: " + selectedZone.attr('id'));
                   
                   // Muestro el nombre actual en selected_name.
                   zone_index = selectedZone.attr('id').substr(5,1) - 1; // saco el "x" del id "zone_x".
                   $(':text[name="selected_zone_name"]').val( $('input[name="zone_name[]"]')[ zone_index ].value );
                   
                   $(':text[name="selected_zone_posX"]'  ).val( _num(selectedZone.css('left'))   );
                   $(':text[name="selected_zone_posY"]'  ).val( _num(selectedZone.css('top'))    );
                   $(':text[name="selected_zone_width"]' ).val( _num(selectedZone.css('width'))  );
                   $(':text[name="selected_zone_height"]').val( _num(selectedZone.css('height')) );
                   
                   // Quiero ver quien es el padre:
                   //alert(element.parent().size());
                   //alert(element.parent().get(0).id);
                   
                 });
                 // ==================================================================================
                 
                 // ==================================================================================
                 // La nueva zona es dragable con estos parametros:
                 // http://projects.sevir.org/storage/jQueryD&DSample/d&dsample2.html
                 $("#"+newZoneId).draggable(
                    {
                       zIndex:        1000,
                       containment:  '#editZones',
                       revert:        false,
                       opacity:       0.7,
                       cursor:       'hand',
                       snap:          true,
                       snapMode:     'both',
                       snapTolerance: 5,
                       stop: function(evn, ui) {
                        
                          $(':text[name="selected_zone_posX"]'  ).val($(this).position().left);
                          $(':text[name="selected_zone_posY"]'  ).val($(this).position().top);
                          $(':text[name="selected_zone_width"]' ).val( _num($(this).css('width'))  );
                          $(':text[name="selected_zone_height"]').val( _num($(this).css('height')) );
                       }
                    }
                 );
                 
                 // La nueva zona tambien es resizable:
                 // http://docs.jquery.com/UI/Resizable
                 $("#"+newZoneId).resizable(
                    {
                       knobHandles: true,
                       handles:     "all",
                       minWidth:    50,
                       minHeight:   50,
                       stop: function(evn, ui) {
                        
                          element = $(this);
                          
                          // Ahora estoy probando todo con absolute! y anda bien!
                          //element.css('position', 'relative'); // Cambio la propiedad "position", que se pone en "absolute", a "relative."
                          
                          // Cuando termina de resizear queda position absolute!
                          // Le puse a "#editZones" position "relative" en la css y no parece que necesite restar la posicion aca.
                          //element.css('top',  element.position().top  - top);
                          //element.css('left', element.position().left - left);
                          
                          $(':text[name="selected_zone_posX"]'  ).val( _num(element.css('left'))   );
                          $(':text[name="selected_zone_posY"]'  ).val( _num(element.css('top'))    );
                          $(':text[name="selected_zone_width"]' ).val( _num(element.css('width'))  );
                          $(':text[name="selected_zone_height"]').val( _num(element.css('height')) );
                       }
                    }
                 );
             
              <?php endforeach; ?>
           
           <?php endif; ?>
         
         
           // Cambio el tamanio por defecto de los controles de resize de arriba y abajo.
           $(".ui-resizable-s").css({'width': '30px'});
           $(".ui-resizable-n").css({'width': '30px'});
         
         
           // Para calcular la posicion de las zonas con respecto a su contenedor.
           var container = $('#editZones');
           var top  = container.position().top;
           var left = container.position().left;
        
           //alert("top: " + top + ", left: " + left);
         
         
           // ======================================================================================
           // Salvar la skin itera por las zonas para tomar sus datos y guardarlos.
           //
           $("#save_skin_button").click( function() {
            
              zone_posX_fields   = $(':input[name="zone_posX[]"]');
              zone_posY_fields   = $(':input[name="zone_posY[]"]');
              zone_width_fields  = $(':input[name="zone_width[]"]');
              zone_height_fields = $(':input[name="zone_height[]"]');
            
              $("#editZones > div").each( function (index) {
                 
                 // Usando campos hidden
                 zone_posX_fields  [index].value = _num( $(this).css('left')   ); // si pido con position, me da mal!
                 zone_posY_fields  [index].value = _num( $(this).css('top')    );
                 zone_width_fields [index].value = _num( $(this).css('width')  );
                 zone_height_fields[index].value = _num( $(this).css('height') );
                 
              });


              // Submit
              $('#skin_edit_form').submit();
              
           });
           // ======================================================================================
         
           // ======================================================================================
           // Al precionar y soltar alguna tecla en "selected_zone_name", 
           // se actualiza el nombre de la zona.
           //
           $(':input[name="selected_zone_name"]').keyup( function(evn) {
            
             if (selectedZone)
             {
               zone_index = selectedZone.attr('id').substr(5) - 1; // saco el "xx" del id "zone_xx".
               
               // Pone el valor actual de "selected_zone_name" en el "zone_name" que corresponde a la zona (se usa el indice de su id).

               $('input[name="zone_name[]"]')[ zone_index ].value = $(this).val();
               
               // En la zona le pongo el nombre modificado.
               selectedZone.text( "[::" + $(this).val() + "::]" );
             }
             /*else
             {
                alert("no selected zone");
             }*/
             
           });
           // ======================================================================================
         
           // ======================================================================================
           // Agrega una nueva zona a la skin al clickear en el botton "add_zone_button".
           // 
           $("#add_zone_button").click( function() {
           	
              zoneCount++;
              newZoneId = "zone_"+zoneCount;
            
              // Agrega una nueva div de zone a la div con id "editZones".
              $("#editZones").append('<div class="newZone" id="'+newZoneId+'">[::'+newZoneId+'::]</div>');
              
              $("#"+newZoneId).css('background-color', newZoneBGs[(zoneCount-1) % 10]);
              
              // Quiero en 0,0 del container.
              //$("#"+newZoneId).css('left', left);
              //$("#"+newZoneId).css('top',  top);
              
              // Campos hidden para mantener datos de cada zone.
              // Cuando se submitee hay que actualizar los valores de estos campos.
              $("#skin_edit_form").append('<input type="text" name="zone_id[]"     value="'+newZoneId+'" />');
              $("#skin_edit_form").append('<input type="text" name="zone_name[]"   value="'+newZoneId+'" />');
              $("#skin_edit_form").append('<input type="text" name="zone_posX[]"   value="'+ _num($("#"+newZoneId).css('left'))  +'" />');
              $("#skin_edit_form").append('<input type="text" name="zone_posY[]"   value="'+ _num($("#"+newZoneId).css('top'))   +'" />');
              $("#skin_edit_form").append('<input type="text" name="zone_width[]"  value="'+ _num($("#"+newZoneId).css('width')) +'" />');
              $("#skin_edit_form").append('<input type="text" name="zone_height[]" value="'+ _num($("#"+newZoneId).css('height'))+'" /><br/>');
              
              // ==================================================================================
              // Seleccion de zone:
              //
              $("#"+newZoneId).click( function(evn, ui) {

                if (selectedZone)
                  selectedZone.removeClass('selectedZone');
                  
                selectedZone = $("#"+evn.target.id);
                selectedZone.addClass('selectedZone');
                
                //alert( "SELECTED ZONE ID: " + selectedZone.attr('id'));
                
                // Muestro el nombre actual en selected_name.
                zone_index = selectedZone.attr('id').substr(5,1) - 1; // saco el "x" del id "zone_x".
                $(':text[name="selected_zone_name"]').val( $('input[name="zone_name[]"]')[ zone_index ].value );
                
                $(':text[name="selected_zone_posX"]'  ).val( _num(selectedZone.css('left'))   );
                $(':text[name="selected_zone_posY"]'  ).val( _num(selectedZone.css('top'))    );
                $(':text[name="selected_zone_width"]' ).val( _num(selectedZone.css('width'))  );
                $(':text[name="selected_zone_height"]').val( _num(selectedZone.css('height')) );
                
                // Quiero ver quien es el padre:
                //alert(element.parent().size());
                //alert(element.parent().get(0).id);
                
              });
              // ==================================================================================
              
              // ==================================================================================
              // La nueva zona es dragable con estos parametros:
              // http://projects.sevir.org/storage/jQueryD&DSample/d&dsample2.html
              $("#"+newZoneId).draggable(
                 {
                    zIndex:        1000,
                    containment:  '#editZones',
                    revert:        false,
                    opacity:       0.7,
                    cursor:       'hand',
                    snap:          true,
                    snapMode:     'both',
                    snapTolerance: 5,
                    stop: function(evn, ui) {
                     
                       $(':text[name="selected_zone_posX"]'  ).val($(this).position().left);
                       $(':text[name="selected_zone_posY"]'  ).val($(this).position().top);
                       $(':text[name="selected_zone_width"]' ).val( _num($(this).css('width'))  );
                       $(':text[name="selected_zone_height"]').val( _num($(this).css('height')) );
                    }
                 }
              );
              
              // La nueva zona tambien es resizable:
              // http://docs.jquery.com/UI/Resizable
              $("#"+newZoneId).resizable(
                 {
                    knobHandles: true,
                    handles:     "all",
                    minWidth:    50,
                    minHeight:   50,
                    stop: function(evn, ui) {
                     
                       element = $(this);
                       
                       // Ahora estoy probando todo con absolute! y anda bien!
                       //element.css('position', 'relative'); // Cambio la propiedad "position", que se pone en "absolute", a "relative."
                       
                       // Cuando termina de resizear queda position absolute!
                       // Le puse a "#editZones" position "relative" en la css y no parece que necesite restar la posicion aca.
                       //element.css('top',  element.position().top  - top);
                       //element.css('left', element.position().left - left);
                       
                       $(':text[name="selected_zone_posX"]'  ).val( _num(element.css('left'))   );
                       $(':text[name="selected_zone_posY"]'  ).val( _num(element.css('top'))    );
                       $(':text[name="selected_zone_width"]' ).val( _num(element.css('width'))  );
                       $(':text[name="selected_zone_height"]').val( _num(element.css('height')) );
                    }
                 }
              );
           }); // add_zone_button
        });
      </script>
   </head>
   <body>
      <h1>Editar skin</h1>
      
      <?php echo DisplayHelper::errors( $skin ); ?>
      
      <div id="informacion_skin"></div>
      
      <form id="skin_edit_form" action="<?php echo h("url", array('action'=>'save')); ?>" method="post">
        <div id="top">
           <span id="skin">
             <label for="skin_name">
               <?php echo DisplayHelper::message("cms.skin.label.name"); ?>
               <input type="text" name="skin_name" value="<?php echo $skin->getName(); ?>" />
             </label>
             <br/>
             <input type="button" id="save_skin_button" value="Guardar skin" />
           </span>
         
           <span id="templatePage">
             <label for="template_page_name">
               <?php echo DisplayHelper::message("cms.skin.label.name"); ?>
               <input type="text" name="template_page_name" value="<?php echo $skin->getTemplatePage()->getName(); ?>" />
             </label>
             <br/>
             <label for="template_page_title">
               <?php echo DisplayHelper::message("cms.skin.label.title"); ?>
               <input type="text" name="template_page_title" value="<?php echo $skin->getTemplatePage()->getTitle(); ?>" />
             </label>
             <br/>
             <input type="button" id="add_zone_button" value="Agregar zona" />
           </span>
         
           <span id="selectedZone">
             <label for="selected_zone_name">
               <?php echo DisplayHelper::message("cms.skin.label.selected_zone_name"); ?>
               <input type="text" name="selected_zone_name" />
             </label><br/>
             <label for="selected_zone_posX">
               <?php echo DisplayHelper::message("cms.skin.label.selected_zone_posX"); ?>
               <input type="text" name="selected_zone_posX" />
             </label><br/>
             <label for="selected_zone_posY">
               <?php echo DisplayHelper::message("cms.skin.label.selected_zone_posY"); ?>
               <input type="text" name="selected_zone_posY" />
             </label><br/>
             <label for="selected_zone_width">
               <?php echo DisplayHelper::message("cms.skin.label.selected_zone_width"); ?>
               <input type="text" name="selected_zone_width" />
             </label><br/>
             <label for="selected_zone_height">
               <?php echo DisplayHelper::message("cms.skin.label.selected_zone_height"); ?>
               <input type="text" name="selected_zone_height" />
             </label>
           </span>
        </div>
      
        <div id="editZones">
        </div>
      
      </form>
      
   </body>
</html>