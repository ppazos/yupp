<?php

$m = Model::getInstance();

Logger::struct($m, "MODELO: ");

YuppLoader::loadScript("components.blog", "Messages");

?>

<html>
   <layout name="blog" />
   <head>
      <?php echo h("css", array("name" => "main") ); ?>
   </head>
   <body>
      <h1>Create user flow: Display User</h1>
      
      <?php if ($m->flash('message')) : ?>
         <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php endif; ?>
      
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
            Helpers::template( array("controller" => "usuario",
                                     "name"       => "details",
                                     "args"       => array("usuario" => $m->get('usuario'))
                                    ) ); ?>
      </table>
      
   </body>
</html>