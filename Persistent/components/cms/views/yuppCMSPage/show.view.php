<?php

$m = Model::getInstance();

//YuppLoader::loadScript("components.blog", "Messages");

$obj = $m->get('page');

?>

<html>
   <head>
   </head>
   <body>
      <h1>Detalle de pagina</h1>
      
      <?php if ($m->flash('message')) : ?>
         <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php endif; ?>
      
      <ul class="postnav">
        <li><?php echo Helpers::link( array("controller" => "page",
                                            "action"     => "list",
                                            "body"       => "Paginas") ); ?></li>
        <li><?php echo Helpers::link( array("action"     => "edit",
                                            "id"         => $obj->getId(),
                                            "body"       => "Editar") ); ?></li>
        <li><?php echo Helpers::link( array("action"     => "delete",
                                            "id"         => $obj->getId(),
                                            "body"       => "Eliminar") ); ?></li>
      </ul>
      <br/><br/>
                                         
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
          <th>Sitio</th>
          <td><?php echo $obj->getSite()->getName(); ?></td>
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
          <td><?php echo $obj->getDescription(); ?></td>
        </tr>
        <tr>
          <th>Palabras clave</th>
          <td><?php echo $obj->getKeywords(); ?></td>
        </tr>
        <tr>
          <th>Comentarios</th>
          <td><?php echo $obj->getComments(); ?></td>
        </tr>
      </table><br/>
      
      <h1>Zonas de la pagina</h1>
      
      Esto deberia tal vez editarse en la skin actual... (TODO)<br/>
      
      <?php echo h("link", array("action" => "edit_zones",
                                 "id"     => $obj->getId(),
                                 "body"   => "Editar zonas") ); ?>
      <br/>
      <?php $i = 1; ?>
      <?php foreach ( $obj->getZones() as $zone ) : ?>
        Zona # <?php echo $i; ?> (<?php echo $zone->getName(); ?>)<br/>
        <?php echo $zone->getWidth(); ?> x <?php echo $zone->getHeight(); ?>
        <?php $i++; ?>
        <br/>
      <?php endforeach; ?>
      
      <?php if ($i ==1): ?>
        La pagina no tiene zonas creadas.
      <?php endif; ?>
      
   </body>
</html>