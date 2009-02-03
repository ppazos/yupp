<?php

$m = Model::getInstance();

//YuppLoader::loadScript("components.blog", "Messages");

/* La pagina a mostrar */
$obj = $m->get('object');      

?>

<html>
   <head>
      <title><?php echo $obj->getTitle(); ?></title>
   </head>
   <body>
      <?php if ($m->flash('message')): ?>
         <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php endif; ?>
      
      <?php foreach ( $obj->getZones() as $zone ): ?>
      
        <!-- Hacer el html y css de las zonas deberia estar en un template aparte -->
        <?php $x = $zone->getPosX(); ?>
        <?php $y = $zone->getPosY(); ?>
        <?php $w = $zone->getWidth(); ?>
        <?php $h = $zone->getHeight(); ?>
        
        <!-- Zone div -->
        <div style="left: <?php echo $x; ?>; top: <?php echo $y; ?>; width: <?php echo $w; ?>; height: <?php echo $h; ?>; border: 1px solid #000;">
          -- <?php echo $zone->getName(); ?> --<br /><br />
          
          <?php foreach ( $zone->getModules() as $module ): ?>
            <!-- TODO: El modulo deberia tener un template asociado, 
                 segun su tipo y la skin seleccionada del sitio -->
            
            <?php echo String::firstToLower( $module->getClass() ); ?>
            
            <?php Helpers::template( array("controller" => "module",
                                           "name"       => "display",
                                           "args"       => array("module" => $module) ) ); ?>
          <?php endforeach; ?>
        </div>
      
      <?php endforeach; ?>
      <hr />
      <table>
        <tr>
          <th>Nombre</th>
          <td><?php echo $obj->getName(); ?></td>
        </tr>
        <tr>
          <th>Titulo</th>
          <td><?php echo $obj->getTitle(); ?></td>
        </tr>
        <tr>
          <th>Status</th>
          <td><?php echo $obj->getStatus(); ?></td>
        </tr>
        <tr>
          <th>Creada</th>
          <td><?php echo $obj->getCreatedOn(); ?></td>
        </tr>
        <tr>
          <th>Modificada</th>
          <td><?php echo $obj->getLastUpdate(); ?></td>
        </tr>
        <tr>
          <th>Descripcion</th>
          <td><?php echo $obj->getDesription(); ?></td>
        </tr>
        <tr>
          <th>Palabras clave</th>
          <td><?php $obj->getKeywords(); ?></td>
        </tr>
        <tr>
          <th>Comentarios</th>
          <td><?php echo $obj->getComments(); ?></td>
        </tr>
      </table>
      
   </body>
</html>