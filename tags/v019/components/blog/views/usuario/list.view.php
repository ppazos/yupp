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
      
      <?php echo h('pager', array('offset'=>$m->get('offset'), 'max'=>$m->get('max'), 'count'=>$m->get('count'))); ?>
   
   </body>
</html>