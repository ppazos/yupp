<?php

$m = Model::getInstance();

?>

<html>
  <head>
    <title>Helpers Test: Img Test</title>
  </head>
  <body>
    <h1>Helpers Test: Img Test</h1>
   
    <div style="width: 500px; height: 200px; padding:10px; padding-right:10px; background-color: #ffff80; border: 1px dashed #000" id="content_div">
      <?php echo h('img', array('app'=>'tests',
                                'src'=>'yupp_powered.png',
                                'w'=>'185',
                                'h'=>'38',
                                'text'=>'Yupp PHP Framework')); ?>
    </div>
    
    <hr/>
    
    Este test muestra el uso del helper img, el cual sirve para generar una etiqueta IMG de HTML con la ruta correcta a la imagen requerida.
    <ul>
      <li>Como en la llamada al helper se especifica la aplicacion 'tests', la imagen es cargada desde '/apps/tests/images'.</li>
      <li>Esto permite ordenar y separar las imagenes que son de uno u otra aplicacion.</li>
    </ul>
    
    <br/>
    
    <?php echo h("link", array("action" => "index",
                               "body"   => "volver") ); ?>
    
  </body>
</html>