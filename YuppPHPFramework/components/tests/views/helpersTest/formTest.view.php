<?php

$m = Model::getInstance();

YuppLoader::load("core.mvc.form", "YuppForm2");

?>

<html>
  <head>
    <title>Helpers Test: Form Test</title>
  </head>
  <body>
    <h1>Helpers Test: Form Test</h1>
   
    <?php if ($m->flash('message')) { ?>
      <div class="flash"><?php echo $m->flash('message'); ?></div><br/>
    <?php } ?>
    
    <div style="width: 700px; height: 220px; padding:10px; padding-right:10px; background-color: #ffff80; border: 1px dashed #000" id="content_div">
    
      <?php
          $f = new YuppForm2(array("component"=>"tests", "controller"=>"helpersTest", "action"=>"formTest", "method"=>"get"));
          $f->add( YuppForm2::text(array('name'=>"titulo", 'value'=>$m->get('titulo'), 'label'=>"Titulo")) )
            ->add( YuppForm2::bigtext(array('name'=>"texto", 'value'=>$m->get('texto'), 'label'=>"Texto")) )
            ->add( YuppForm2::submit(array('name'=>'doit', 'label'=>"Enviar")) )
            ->add( YuppForm2::submit(array('action'=>'index', 'label'=>"Volver") ) );
          YuppFormDisplay2::displayForm( $f );
        ?>
      
    </div>
    
    <hr/>
    
    Este test muestra el uso del helper form, el cual sirve para generar formularios HTML mediante PHP.<br/>
    
    <br/>
    
    <?php echo h("link", array("action" => "index",
                               "body"   => "volver") ); ?>
    
  </body>
</html>