<?php

$m = Model::getInstance();

YuppLoader::loadScript("components.blog", "Messages");

?>

<html>
  <head>
    <?php echo h("css", array("name" => "main") ); ?>
  </head>
  <body>
      
    <h1><?php echo DisplayHelper::message("blog.comentario.list.title"); ?></h1>
   
    <?php echo DisplayHelper::errors( $m->get('object') ); ?>
   
    <div class="entrada create">
      <form action="<?php echo Helpers::url( array("action"=>"create") ); ?>" method="get">
      
        <input type="hidden" name="id" value="<?php echo $m->get('id'); ?>" />
        
        <?php echo DisplayHelper::message("blog.comentario.list.label.comment"); ?>:<br/>
        <textarea name="texto"><?php echo $m->get('texto'); ?></textarea>
        <br/><br/>
      
        <input type="submit" name="doit" value="<?php echo DisplayHelper::message("blog.entrada.action.addComment"); ?>" />
        
        <?php echo Helpers::link( array("controller" => "entradaBlog",
                                        "action" => "list",
                                        "body" => DisplayHelper::message("blog.comentario.action.cancel")) ); ?>      
      </form>
    </div>
      
  </body>
</html>