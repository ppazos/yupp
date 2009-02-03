<?php

$m = Model::getInstance();
//YuppLoader::loadScript("components.blog", "Messages");
YuppLoader::load("core.mvc.form", "YuppForm");

$site = $m->get('object');

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
      <h1>Editar sitio</h1>
      
      <?php echo DisplayHelper::errors( $site ); ?>
      
      <div class="site edit">
      <?php
         $f = new YuppForm("cms", "yuppCMSSite", "save");
         
         $f->add( YuppFormField::text("name",           $site->getName(),        "Nombre") )
           ->add( YuppFormField::bigtext("description", $site->getDescription(), "Descripcion") )
           ->add( YuppFormField::bigtext("keywords",    $site->getKeywords(),    "Keywords") )
           
           ->add( YuppFormField::hidden("id", $site->getId()) )
           
           ->add( YuppFormField::submit("doit", "", "Salvar") )
           ->add( YuppFormField::submit("", "list", "Cancelar") );
           
         YuppFormDisplay::displayForm( $f );
      ?>
      </div>
   </body>
</html>