<?php

$m = Model::getInstance();

//YuppLoader::loadScript("components.blog", "Messages");

$skin = $m->get('skin');

?>

<html>
   <head>
      <style>
        #editZones {
        	 width:    768px;
          height:   500px;
          border:   1px solid #333;
          overflow: auto;
        }
        .newZone {
        	 width:  100px;
          height: 80px;
          border: 1px dashed #333;
          background-color: #ffaa00;
        }
        
        #editZones ul .ui-selected div {
          background: #ffdd00 none repeat scroll 0 0;
          color: #0033992;
        }
        #editZones ul li {
          list-style-type: none;
          position: absolute;
        }
      </style>
   
      <?php echo h('js', array('component'=>'cms', 'name'=>'jquery-1.3.1.min')); ?>
      <?php echo h('js', array('component'=>'cms', 'name'=>'jquery-ui--drag-resize-select--1.6rc5.min')); ?>
      
      <script type="text/javascript">
        
        var zoneCount = 0; // TODO: inicializar con la cantidad de zonas que tiene la pagina de la skin.
        
        // jQuery
        $(document).ready( function() {
         
           $("#add_zone_button").click( function() {
           	
              //alert("add zone button click");
              
              zoneCount++;
              newZoneId = "zone_"+zoneCount;
            
              // Agrega una nueva div de zone a la div con id "editZones".
              $("#editZones > ul").append('<li id="'+newZoneId+'_li"><div class="newZone" id="'+newZoneId+'">zxcvzxcv</div></li>');
              
              // La nueva zona es dragable con estos parametros:
              // http://projects.sevir.org/storage/jQueryD&DSample/d&dsample2.html
              $("#"+newZoneId).draggable(
                 {
                    zIndex:        1000,
                    containment:  'parent',
                    revert:        false,
                    opacity:       0.7,
                    cursor:       'hand',
                    snap:          true,
                    snapMode:     'both',
                    snapTolerance: 10,
                    stop: function(evn, ui) {
                       evn.target.innerHTML = "X: "+evn.target.style.top+" Y: "+evn.target.style.left;
                       // TODO: Actualizar datos de la selected_zone
                    }
                 }
              );
              
              // La nueva zona tambien es resizable:
              // http://docs.jquery.com/UI/Resizable
              $("#"+newZoneId).resizable(
                 {
                    start: function(evn, ui) {
                    	  //alert("resize start " + evn.target.offsetWidth + " " + ui); // OK
                       //$("#zone_"+zones).innerHTML = evn.target.width + "px";
                    }
                 }
              );
              
            
           } ); // add_zone_button
   
           /* Voy a intentar hacer el selectable a mano porque me da problemas con el resize
           // Los elementos en editZone son selectables:
           // http://docs.jquery.com/UI/Selectable
           $("#editZones > ul").selectable(
              {
                 toggle: function(evn, ui) {
                    // TODO: actualizar datos en selected_zone
                    //evn.target.style.backgroundColor='#000080';
                    alert("selectable toggle");
                 }
              }
           );
           */
           
        } );
      </script>
   </head>
   <body>
      <h1>Editar skin</h1>
      
      <?php echo DisplayHelper::errors( $skin ); ?>
      
      <form>
        <div id="skin">
          <label for="skin_name">
            <?php echo DisplayHelper::message("cms.skin.label.name"); ?>
            <input type="text" name="skin_name" value="<?php echo $skin->getName(); ?>" />
          </label>
        </div>
      
        <div id="templatePage">
          <label for="template_page_name">
            <?php echo DisplayHelper::message("cms.skin.label.name"); ?>
            <input type="text" name="template_page_name" value="<?php echo $skin->getTemplatePage()->getName(); ?>" />
          </label>
          <label for="template_page_title">
            <?php echo DisplayHelper::message("cms.skin.label.title"); ?>
            <input type="text" name="template_page_title" value="<?php echo $skin->getTemplatePage()->getTitle(); ?>" />
          </label>
          
          <input type="button" id="add_zone_button" value="Agregar zona" />
        </div>
      
        <div id="selectedZone">
          <label for="selected_zone_name">
            <?php echo DisplayHelper::message("cms.skin.label.selected_zone_name"); ?>
            <input type="text" name="selected_zone_name" />
          </label>
          <label for="selected_zone_posX">
            <?php echo DisplayHelper::message("cms.skin.label.selected_zone_posX"); ?>
            <input type="text" name="selected_zone_posX" />
          </label>
          <label for="selected_zone_posY">
            <?php echo DisplayHelper::message("cms.skin.label.selected_zone_posY"); ?>
            <input type="text" name="selected_zone_posY" />
          </label>
          <label for="selected_zone_width">
            <?php echo DisplayHelper::message("cms.skin.label.selected_zone_width"); ?>
            <input type="text" name="selected_zone_width" />
          </label>
          <label for="selected_zone_height">
            <?php echo DisplayHelper::message("cms.skin.label.selected_zone_height"); ?>
            <input type="text" name="selected_zone_height" />
          </label>
        </div>
      
        <div id="editZones">
          <ul></ul>
        </div>
      
      </form>
      
   </body>
</html>