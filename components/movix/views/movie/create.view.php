<?php

$m = Model::getInstance();

YuppLoader::load("core.mvc.form", "YuppForm2");
YuppLoader::load("core.basic", "YuppDateTime");

$movie = $m->get('movie');

?>
<html>
   <head>
      <style>
         /* Estilo para YuppForm */
         .field_container {
            width: 540px;
            text-align: left;
         	display: block;
            padding-top: 10px;
         }
         .field_container .label {
            display: inline;
            padding-right: 10px;
            vertical-align: top;
         }
         .field_container .field {
            display: block;
         }
         .field_container .field input {

         }
         .field_container .field input[type=text] {
         	width: 400px;
         }
         .field_container .field input[type=submit] {
            width: 100px;
         }
         .field_container .field textarea {
            width: 540px;
            height: 200px;
         }
      </style>
   </head>
   <body>
      <h1>Crear</h1>
      
      <?php if (isset($movie))
              echo DisplayHelper::errors( $movie );
      ?>

      <?php
         $f = new YuppForm2( array('component'=>'movix', 'controller'=>'movie', 'action'=>'create') );
         $f->add( YuppForm2::text( array('name'=>'name', 'label'=>'Nombre') ) )
           ->add( YuppForm2::submit( array('name'  =>'doit',     'label'=>'Guardar cambios')) )
           ->add( YuppForm2::submit( array('action'=>'index',    'label'=>'Cancelar')) );
         
         YuppFormDisplay2::displayForm( $f );
      ?>
      </div>
   </body>
</html>