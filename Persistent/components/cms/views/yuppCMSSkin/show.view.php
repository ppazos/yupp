<?php

$m = Model::getInstance();

//YuppLoader::loadScript("components.blog", "Messages");

$skin = $m->get('object');

?>

<html>
   <head>
      <style>
        #zones {
          clear: left;
          width:    768px;
          height:   500px;
          border:   1px solid #333;
          overflow: auto;
        }
        .zone {
          position: absolute;
          background-color: #ffaa00;
        }
      </style>
      
      <?php echo h('js', array('component'=>'cms', 'name'=>'jquery-1.3.1.min')); ?>
      
      <script type="text/javascript">
      
        // jQuery
        $(document).ready( function() {
        
          // Corregir posicion de las divs de zones segun contenedor #zones.
          var container = $('#zones');
          var top  = container.offset().top;
          var left = container.offset().left;
          
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
        
          var zones = $('.zone');
          
          zones.each( function(i, zone) {

            // Agregar al top actual, el top del container. (si la pos de las zonas es absoluta!).
            $(zone).css('top',  $(zone).position().top + top);
            $(zone).css('left', $(zone).position().left + left);
            $(zone).css('background-color', newZoneBGs[i % 10]);
            
          });
          
          //alert(top);
          //alert(left);
        });
      
      </script>
      
   </head>
   <body>
      <h1>Detalle de la skin: <?php echo $skin->getName(); ?></h1>
      
      <?php if ($m->flash('message')) { ?>
         <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php } ?>
      
      <ul class="postnav">
        <li><?php echo Helpers::link( array("controller" => "yuppCMSSkin",
                                            "action"     => "list",
                                            "body"       => "Skins") ); ?></li>
        <li><?php echo Helpers::link( array("action"     => "edit",
                                            "id"         => $skin->getId(),
                                            "body"       => "Editar") ); ?></li>
        <li><?php echo Helpers::link( array("action"     => "delete",
                                            "id"         => $skin->getId(),
                                            "body"       => "Eliminar") ); ?></li>
      </ul>
      <br/><br/>
                                         
      <div id="zones">
      
        <?php foreach ( $skin->getTemplatePage()->getTemplateZones() as $zone ): ?>
        
          <?php $x = $zone->getPosX(); ?>
          <?php $y = $zone->getPosY(); ?>
          <?php $w = $zone->getWidth(); ?>
          <?php $h = $zone->getHeight(); ?>
        
          <div class="zone" style="left: <?php echo $x; ?>px; top: <?php echo $y; ?>px; width: <?php echo $w; ?>px; height: <?php echo $h; ?>px;">
            [::<?php echo $zone->getName(); ?>::]
            X: <?php echo $x; ?>, Y: <?php echo $y; ?> 
          </div>
        
        <?php endforeach; ?>

      </div>
      
   </body>
</html>