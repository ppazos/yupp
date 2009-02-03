<?php

$m = Model::getInstance();
//YuppLoader::loadScript("components.blog", "Messages");
YuppLoader::load("core.mvc.form", "YuppForm");

$site = $m->get('site');

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
      <h1>Crear pagina para el sitio "<?php echo $site->getName(); ?>"</h1>
      
      <?php echo DisplayHelper::errors( $m->get('page') ); ?>
      
      <div class="page create">
      <?php
         $f = new YuppForm("cms", "yuppCMSPage", "create");
         $f->add( YuppFormField::text   ("name",        $m->get('name'),        "Nombre") )
           ->add( YuppFormField::text   ("title",       $m->get('title'),       "Titulo") )
           ->add( YuppFormField::bigtext("description", $m->get('description'), "Descripcion") )
           ->add( YuppFormField::bigtext("keywords",    $m->get('keywords'),    "Keywords") )
           ->add( YuppFormField::submit ("doit", "", "Crear") )
           ->add( YuppFormField::submit ("", "list", "Cancelar") )
           ->add( YuppFormField::hidden ("site_id",     $m->get('site_id')) );
         YuppFormDisplay::displayForm( $f );
      ?>
      </div>
   </body>
</html>