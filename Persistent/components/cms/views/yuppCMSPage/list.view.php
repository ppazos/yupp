<?php

$m = Model::getInstance();

//YuppLoader::loadScript("components.blog", "Messages");

$site = $m->get('site');

?>

<html>
   <head>
   </head>
   <body>
      <h1>Paginas del sitio "<?php echo $site->getName(); ?>"</h1>
      
      <?php if ($m->flash('message')) { ?>
      <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php } ?>
      
      <ul class="postnav">
        <li><?php echo h("link", array("action"  => "create",
                                       "body"    => "Crear pagina",
                                       "site_id" => $site->getId() ) ); ?></li>
      </ul>
      <br/><br/>

      <table>
        <tr>
            <th>Nombre</th>
            <th>Titulo</th>
            <th>Status</th>
            <th>Creada</th>
            <th>Modificada</th>
            <th>Descripcion</th>
            <th>Palabras clave</th>
            <th>Comentarios</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ( $m->get('list') as $obj ): ?>
          <tr>
            <td><?php echo $obj->getName(); ?></td>
            <td><?php echo $obj->getTitle(); ?></td>
            <td><?php echo $obj->getStatus(); ?></td>
            <td><?php echo $obj->getCreatedOn(); ?></td>
            <td><?php echo $obj->getLastUpdate(); ?></td>
            <td><?php echo $obj->getDesription(); ?></td>
            <td><?php echo $obj->getKeywords(); ?></td>
            <td><?php echo $obj->getComments(); ?></td>
            <td>
              <?php echo h("link", array("action" => "display",
                                         "id"     => $obj->getId(),
                                         "body"   => "[Ver pagina]" ) ); ?>
              <?php echo h("link", array("action" => "show",
                                         "id"     => $obj->getId(),
                                         "body"   => "[Detalles]" ) ); ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
      
      <?php if ( $m->get('offset')-$m->get('max') >= 0 ) { ?>
      [ <a href="?class=<?php echo $m->get('class') ?>&max=<?php echo $m->get('max'); ?>&offset=<?php echo ($m->get('offset')-$m->get('max')); ?>"><?php echo DisplayHelper::message("blog.entrada.label.previous"); ?></a> ]
      <?php } ?>
      
      <?php echo (int)($m->get('offset')/$m->get('max') + 1); ?> / <?php echo (int)($m->get('count')/$m->get('max') + 1); ?>
      
      <?php if ( $m->get('offset')+$m->get('max') < $m->get('count') ) { ?>
      [ <a href="?class=<?php echo $m->get('class') ?>&max=<?php echo $m->get('max'); ?>&offset=<?php echo ($m->get('offset')+$m->get('max')); ?>"><?php echo DisplayHelper::message("blog.entrada.label.next"); ?></a> ]
      <?php } ?>
   
   </body>
</html>