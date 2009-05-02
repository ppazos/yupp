<?php

$m = Model::getInstance();

YuppLoader::loadScript("components.blog", "Messages");

?>

<html>
   <layout name="blog" />
   <head>
      <?php echo h("css", array("name" => "niftyCorners") ); ?>
      <?php echo h("js",  array("name" => "niftycube") ); ?>

      <script type="text/javascript">
      window.onload=function(){
         Nifty("div.flash","transparent");
         Nifty("ul.postnav a","transparent");
         //Nifty("ul.postnav div.locale_chooser","transparent");
      }
      </script>

      <?php echo h("css",  array("name" => "main") ); ?>
   </head>
   <body>
      <h1><?php echo DisplayHelper::message("blog.usuario.list.title"); ?></h1>
      
      <?php if ($m->flash('message')) { ?>
      <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php } ?>
      
      <ul class="postnav">
        <li><?php echo h("link", array("action" => "createUser",
                                       "body" => DisplayHelper::message("blog.usuario.list.action.addUser")) ); ?></li>
      </ul>
      <br/><br/>
      
      <table border="1" cellpadding="5" cellspacing="0">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Clave</th>
          <th>Edad</th>
          <th></th>
        </tr>
        <?php
          foreach ( $m->get('list') as $usuario )
          {
            Helpers::template( array("controller" => "usuario",
                                     "name"       => "details",
                                     "args"       => array("usuario" => $usuario) ) );
          }
        ?>
      </table>
      
      <?php if ( $m->get('offset')-$m->get('max') >= 0 ) { ?>
      [ <a href="?class=<?php echo $m->get('class') ?>&max=<?php echo $m->get('max'); ?>&offset=<?php echo ($m->get('offset')-$m->get('max')); ?>"><?php echo DisplayHelper::message("blog.entrada.label.previous"); ?></a> ]
      <?php } ?>
      
      <?php echo (int)($m->get('offset')/$m->get('max') + 1); ?> / <?php echo ceil($m->get('count')/$m->get('max')); ?>
      
      <?php if ( $m->get('offset')+$m->get('max') < $m->get('count') ) { ?>
      [ <a href="?class=<?php echo $m->get('class') ?>&max=<?php echo $m->get('max'); ?>&offset=<?php echo ($m->get('offset')+$m->get('max')); ?>"><?php echo DisplayHelper::message("blog.entrada.label.next"); ?></a> ]
      <?php } ?>
   
   </body>
</html>