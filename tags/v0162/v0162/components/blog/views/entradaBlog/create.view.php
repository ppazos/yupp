<?php

$m = Model::getInstance();

YuppLoader::loadScript("components.blog", "Messages");

YuppLoader::load("core.mvc.form", "YuppForm");

?>

<html>
   <head>
      <?php echo h("css", array("name" => "main") ); ?>
      <?php echo h("js",  array("name" => "prototype-1.6.0.2") ); ?>
      <style>
         /* Estilo para YuppForm */
         .field_container {
            width: 450px;
            text-align: right;
         	display: block;
            padding-top: 10px;
         }
         .field_container .label {
            display: inline;
            padding-right: 10px;
            vertical-align: top;
         }
         .field_container .field {
            display: inline;
         }
         .field_container .field input {

         }
         .field_container .field input[type=text] {
         	width: 380px;
         }
         .field_container .field input[type=submit] {
            width: 100px;
            //float: right;
            //marging-bottom: 15px;
         }
         .field_container .field textarea {
            width: 380px;
            height: 140px;
         }
      </style>
      
   </head>
   <body>
      
      <h1><?php echo DisplayHelper::message("blog.entrada.create.title"); ?></h1>
      
      <?php echo DisplayHelper::errors( $m->get('object') ); ?>
      
      <?php
      /*
      <div class="entrada create">
         <form action="<?php echo Helpers::url( array("action"=>"create") ); ?>" method="get">
         
           <?php echo DisplayHelper::message("blog.entrada.label.title"); ?>:<br/>
           <input type="text" name="titulo" value="<?php echo $m->get('titulo'); ?>" />
           <br/><br/>
           
           <?php echo DisplayHelper::message("blog.entrada.label.text"); ?>:<br/>
           <textarea name="texto"><?php echo $m->get('texto'); ?></textarea>
           <br/><br/>
         
           <input type="submit" name="doit" value="<?php echo DisplayHelper::message("blog.entrada.action.create"); ?>" />
           <?php echo Helpers::link( array("action" => "list",
                                           "body"   => DisplayHelper::message("blog.entrada.action.cancel")) ); ?>
         </form>
      </div>
      */
      ?>
      
      <div class="entrada create">
      <?php
         $f = new YuppForm("blog", "entradaBlog", "create");
         $f->add( YuppFormField::text("titulo", $m->get('titulo'), DisplayHelper::message("blog.entrada.label.title")) )
           ->add( YuppFormField::bigtext("texto" , $m->get('texto') , DisplayHelper::message("blog.entrada.label.text")) )
           ->add( YuppFormField::submit("doit", "", DisplayHelper::message("blog.entrada.action.create")) )
           ->add( YuppFormField::submit("", "list", DisplayHelper::message("blog.entrada.action.cancel")) );
         YuppFormDisplay::displayForm( $f );
      ?>
      </div>
      
   </body>
</html>