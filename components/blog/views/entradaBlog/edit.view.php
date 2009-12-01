<?php

$m = Model::getInstance();

YuppLoader::loadScript("components.blog", "Messages");

?>

<html>
   <head>
      <?php echo h("css", array("name" => "main") ); ?>
   </head>
   <body>
      
      <h1><?php echo DisplayHelper::message("blog.entrada.edit.title"); ?></h1>
      
      <?php $obj = $m->get('object'); ?>
      
      <?php echo DisplayHelper::errors( $obj ); ?>
      
      <div class="entrada create">
         <form action="save" method="get">
         
           <input type="hidden" name="id" value="<?php echo $m->get('id'); ?>" />
         
           <?php echo DisplayHelper::message("blog.entrada.label.title"); ?>:<br/>
           <input type="text" name="titulo" value="<?php echo $obj->getTitulo(); ?>" />
           <br/><br/>
           
           <?php echo DisplayHelper::message("blog.entrada.label.text"); ?>:<br/>
           <textarea name="texto"><?php echo $obj->getTexto(); ?></textarea>
           <br/><br/>
         
         
           <input type="submit" name="doit" value="<?php echo DisplayHelper::message("blog.entrada.action.save"); ?>" />
           <?php echo Helpers::link( array("action"     => "show",
                                           "id"         => $m->get('id'),
                                           "body"       => DisplayHelper::message("blog.entrada.action.cancel")) ); ?>
         
         </form>
      </div>
      
   </body>
</html>