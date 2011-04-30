<?php

$m = Model::getInstance();

?>

<html>
  <head>
    <title>Helpers Test: Url Test</title>
  </head>
  <body>
    <h1>Helpers Test: Url Test</h1>
   
    Nombre de la aplicacion actual: <b>tests</b><br/>
    Nombre del controlador actual: <b>helpersTest</b><br/><br/>
   
    <div style="width: 700px; height: 220px; padding:10px; padding-right:10px; background-color: #ffff80; border: 1px dashed #000" id="content_div">
    
      <b>Especificando solo la accion, se toma como aplicacion y controlador los actuales:</b><br/>
      <?php echo h('url', array('action' => 'nombreAccion')); ?>
      <br/><br/>
      
      <b>Especificando accion y controlador, se toma como aplicacion el actual:</b><br/>
      <?php echo h('url', array('controller' => 'nombreController',
                                'action'     => 'nombreAccion')); ?>
      <br/><br/>
      
      <b>Especificando aplicacion, controlador y accion:</b><br/>
      <?php echo h('url', array('app'  => 'nombreAplicacion',
                                'controller' => 'nombreController',
                                'action'     => 'nombreAccion')); ?>
      <br/><br/>
      
      <b>Agregando un par de parametros:</b><br/>
      <?php echo h('url', array('app'  => 'nombreAplicacion',
                                'controller' => 'nombreController',
                                'action'     => 'nombreAccion',
                                'id'         => 555,
                                'name'       => 'Dilbert' )); ?>
      <br/>
      
    </div>
    
    <hr/>
    
    Este test muestra el uso del helper url, el cual sirve para generar una URLs validas dentro de Yupp Framework.<br/>
    Estas URLs sirven para crear links y para decirle a los formularios HTML a donde enviar la informacion.
    
    <br/>
    
    <?php echo h("link", array("action" => "index",
                               "body"   => "volver") ); ?>
    
  </body>
</html>