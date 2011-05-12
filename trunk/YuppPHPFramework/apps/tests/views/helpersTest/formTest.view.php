<?php

$m = Model::getInstance();

YuppLoader::load("core.mvc.form", "YuppForm2");

?>

<html>
  <head>
    <title>Helpers Test: Form Test</title>
    <script type="text/javascript">
      function getObj(elemID)
      {
         if (document.all) {
            return document.all(elemID)
         } else if (document.getElementById) {
            return document.getElementById(elemID)
         } else if (document.layers) {
            return document.layers[elemID]
         }
      }
      
      /*
      // Handler para jquery
      function after_function (responseText, statusText, xhr, form)
      {
        //alert("aaaaaa: "+responseText);
      
        var div = getObj('content_div');
        div.innerHTML = responseText;
      }
      */
      
      // Handler para prototype
      function after_function (res)
      {
        //alert("aaaaaa: "+res.responseText);
      
        var div = getObj('content_div');
        div.innerHTML = res.responseText;
      }
      
    </script>
  </head>
  <body>
    <h1>Helpers Test: Form Test</h1>
   
    <?php if ($m->flash('message')) { ?>
      <div class="flash"><?php echo $m->flash('message'); ?></div><br/>
    <?php } ?>
    
    Formulario comun:
    
    <div style="width: 500px; padding:10px; padding-right:10px; background-color: #ffff80; border: 1px dashed #000">
      
      <?php
          $f = new YuppForm2(array("app"=>"tests", "controller"=>"helpersTest", "action"=>"formTest", "method"=>"get"));
          $f->add( YuppForm2::text(array('name'=>"titulo", 'value'=>$m->get('titulo'), 'label'=>"Titulo")) )
            ->add( YuppForm2::bigtext(array('name'=>"texto", 'value'=>$m->get('texto'), 'label'=>"Texto")) )
            ->add( YuppForm2::submit(array('name'=>'doit', 'label'=>"Enviar")) )
            ->add( YuppForm2::submit(array('action'=>'index', 'label'=>"Volver") ) );
          
          YuppFormDisplay2::displayForm( $f );
      ?>
    </div>
    <br/>
    
    Formulario ajax:
    
    <div style="width: 500px; padding:10px; padding-right:10px; background-color: #ffff80; border: 1px dashed #000">
    
      <?php
          $f = new YuppForm2(array("app"=>"tests", "controller"=>"helpersTest", "action"=>"formTest", "isAjax"=>true, "ajaxCallback"=>"after_function"));
          $f->add( YuppForm2::text(array('name'=>"titulo", 'value'=>$m->get('titulo'), 'label'=>"Titulo")) )
            ->add( YuppForm2::bigtext(array('name'=>"texto", 'value'=>$m->get('texto'), 'label'=>"Texto")) )
            ->add( YuppForm2::submit(array('name'=>'doit_ajax', 'label'=>"Enviar")) )
            ->add( YuppForm2::submit(array('action'=>'index', 'label'=>"Volver") ) );
          
          YuppFormDisplay2::displayForm( $f );
      ?>
   
      <div style="width: 95%; height: 25px; padding:10px; padding-right:10px; background-color: #8080ff; border: 1px dashed #000" id="content_div"></div>
      
    </div>
    
    <hr/>
    
    Este test muestra el uso del helper form, el cual sirve para generar formularios HTML mediante PHP.<br/>
    <ul>
      <li>El primer caso muestra un formulario comun que envia los datos usando el metodo GET de HTTP.</li>
      <li>El segundo caso muestra la funcionalidad del formulario AJAX que envia los datos del formulario y recibe informacion del servidor sin necesidad de recargar toda la pagina. Este caso es util para realizar busquedas y obtener resulados que se pueden mostrar sin tener que recargar toda la pagina, ahorrando tiempo.</li>
    </ul>
    
    <br/>
    
    <?php echo h("link", array("action" => "index",
                               "body"   => "volver") ); ?>
    
  </body>
</html>