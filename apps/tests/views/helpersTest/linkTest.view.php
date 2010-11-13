<?php

$m = Model::getInstance();

?>

<html>
  <head>
    <title>Helpers Test: Link Test</title>
  </head>
  <body>
    <h1>Helpers Test: Link Test</h1>
      
    <?php echo h("link", array("action" => "linkTest",
                               "doit"   => "true",
                               "body"   => "haz clic en el link") ); ?>
    <br/><br/>
   
    <div style="width: 500px; height: 200px; padding:10px; padding-right:10px; background-color: #ffff80; border: 1px dashed #000" id="content_div">
      <?php echo $m->get('mensaje'); ?>
    </div>
    
    <hr/>
    
    Este test muestra el uso del helper link.
    <ul>
      <li>Al cliquear en el link, un pedido HTTP es enviado al servidor.</li>
      <li>El servidor responde mediante una respuesta HTML.</li>
      <li>La pagina HTML es mostrada al usuario en el navegador.</li>
    </ul>
    Es el test de un link comun en el cual se recarga toda la pagina (la misma u otra), a diferencia del helper ajax_link que no requiere la recarga de una pagina completa.
    
    <br/>
    
    <?php echo h("link", array("action" => "linkTest",
                               "body"   => "reiniciar") ); ?>
    |
    <?php echo h("link", array("action" => "index",
                               "body"   => "volver") ); ?>

  </body>
</html>