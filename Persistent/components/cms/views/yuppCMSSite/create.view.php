<?php

$m = Model::getInstance();
//YuppLoader::loadScript("components.blog", "Messages");
YuppLoader::load("core.mvc.form", "YuppForm");

?>
<html>
   <head>
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
         	width: 350px;
         }
         .field_container .field input[type=submit] {
            width: 100px;
         }
         .field_container .field textarea {
            width: 350px;
            height: 140px;
         }
      </style>
   </head>
   <body>
      <h1>Crear sitio</h1>
      
      <?php echo DisplayHelper::errors( $m->get('object') ); ?>
      
      <div class="site create">
      <?php
         $f = new YuppForm("cms", "yuppCMSSite", "create");
         
         $f->add( YuppFormField::text("name",        $m->get('name'),        "Nombre") )
           ->add( YuppFormField::bigtext("description", $m->get('description'), "Descripcion") )
           ->add( YuppFormField::bigtext("keywords",    $m->get('keywords'),    "Keywords") )
           
           ->add( YuppFormField::submit("doit", "", "Crear") )
           ->add( YuppFormField::submit("", "list", "Cancelar") );
           
         YuppFormDisplay::displayForm( $f );
      ?>
      </div>
   </body>
</html>