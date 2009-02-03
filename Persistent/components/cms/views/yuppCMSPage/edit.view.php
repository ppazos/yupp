<?php

$m = Model::getInstance();

//YuppLoader::loadScript("components.blog", "Messages");
YuppLoader::load("core.mvc.form", "YuppForm");

$site = $m->get('site');
$page = $m->get('page');

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
      <h1>Editar pagina del sitio "<?php echo $site->getName(); ?>"</h1>
      
      <?php echo DisplayHelper::errors( $page ); ?>
      
      <div class="page edit">
      <?php
         $f = new YuppForm("cms", "yuppCMSPage", "save");
         $f->add( YuppFormField::text   ("name",        $page->getName(),        "Nombre") )
           ->add( YuppFormField::text   ("title",       $page->getTitle(),       "Titulo") )
           ->add( YuppFormField::bigtext("description", $page->getDescription(), "Descripcion") )
           ->add( YuppFormField::bigtext("keywords",    $page->getKeywords(),    "Keywords") )
           ->add( YuppFormField::submit ("doit", "", "Guardar") )
           ->add( YuppFormField::submit ("", "list", "Cancelar") )
           ->add( YuppFormField::hidden ("page_id",     $page->getId()) )
           ->add( YuppFormField::hidden ("site_id",     $site->getId()) );
         YuppFormDisplay::displayForm( $f );
      ?>
      </div>
      
   </body>
</html>