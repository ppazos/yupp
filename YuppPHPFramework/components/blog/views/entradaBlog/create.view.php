<?php

$m = Model::getInstance();

YuppLoader::loadScript("components.blog", "Messages");

YuppLoader::load("core.mvc.form", "YuppForm2");

?>
<html>
   <head>
      <?php echo h("css", array("name" => "main") ); ?>
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
      
      <div class="entrada create">
        <?php
          $f = new YuppForm2(array("component"=>"blog", "controller"=>"entradaBlog", "action"=>"create"));
          $f->add( YuppForm2::text(array('name'=>"titulo", 'value'=>$m->get('titulo'), 'label'=>DisplayHelper::message("blog.entrada.label.title"))) )
            ->add( YuppForm2::bigtext(array('name'=>"texto", 'value'=>$m->get('texto'), 'label'=>DisplayHelper::message("blog.entrada.label.text"))) )
            ->add( YuppForm2::submit(array('name'=>'doit', 'label'=>DisplayHelper::message("blog.entrada.action.create"))) )
            ->add( YuppForm2::submit(array('action'=>'list', 'label'=>DisplayHelper::message("blog.entrada.action.cancel")) ) );
          YuppFormDisplay2::displayForm( $f );
        ?>
      </div>
      
   </body>
</html>