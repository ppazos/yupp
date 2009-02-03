<?php

$m = Model::getInstance();

//YuppLoader::loadScript("components.blog", "Messages");

$site = $m->get('object');

?>

<html>
   <head>
   </head>
   <body>
      <h1>Detalle del sitio</h1>
      
      <?php if ($m->flash('message')) { ?>
         <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php } ?>
      
      <ul class="postnav">
        <li><?php echo Helpers::link( array("controller" => "yuppCMSSite",
                                            "action"     => "list",
                                            "body"       => "Sitios") ); ?></li>
        <li><?php echo Helpers::link( array("action"     => "edit",
                                            "id"         => $site->getId(),
                                            "body"       => "Editar") ); ?></li>
        <li><?php echo Helpers::link( array("action"     => "delete",
                                            "id"         => $site->getId(),
                                            "body"       => "Eliminar") ); ?></li>
        <li><?php echo Helpers::link( array("controller" => "yuppCMSPage",
                                            "action"     => "list",
                                            "site_id"    => $site->getId(),
                                            "body"       => "Paginas del sitio") ); ?></li>
      </ul>
      <br/><br/>
                                         
      <table>
        <tr>
          <th>Nombre</th>
          <td><?php echo $site->getName(); ?></td>
        </tr>
        <tr>
          <th>Descripcion</th>
          <td><?php echo $site->getDescription(); ?></td>
        </tr>
        <tr>
          <th>Palabras clave</th>
          <td><?php echo $site->getKeywords(); ?></td>
        </tr>
      </table><br/>
      
   </body>
</html>