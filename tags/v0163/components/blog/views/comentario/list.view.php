<?php

$m = Model::getInstance();

?>

<html>
   <head>
      <?php echo h("css", array("name" => "main") ); ?>
   </head>
   <body>
   
      <h1>Comentarios</h1>
      
      <div align="center"><?php echo $m->flash('message'); ?></div>

      <br/><br/>

      <?php
      foreach ( $m->get('list') as $obj ) {
      ?>
      <div class="entrada">
        <div class="top">
          <div class="left">
            (<?php echo $obj->getId(); ?>)
          </div>
          <div class="right">
            <?php echo $obj->getFecha(); ?>
          </div>
        </div>
        <br/>
        <div class="content">
          <?php echo $obj->getTexto(); ?>
        </div>
      </div>
      <?php
      }
      ?>
      
      <?php if ( $m->get('offset')-$m->get('max') >= 0 ) { ?>
      [ <a href="?class=<?php echo $m->get('class') ?>&max=<?php echo $m->get('max'); ?>&offset=<?php echo ($m->get('offset')-$m->get('max')); ?>">Previous</a> ]
      <?php } ?>
      
      <?php echo (int)($m->get('offset')/$m->get('max') + 1); ?> / <?php echo (int)($m->get('count')/$m->get('max') + 1); ?>
      
      <?php if ( $m->get('offset')+$m->get('max') < $m->get('count') ) { ?>
      [ <a href="?class=<?php echo $m->get('class') ?>&max=<?php echo $m->get('max'); ?>&offset=<?php echo ($m->get('offset')+$m->get('max')); ?>">Next</a> ]
      <?php } ?>
   
   </body>
</html>