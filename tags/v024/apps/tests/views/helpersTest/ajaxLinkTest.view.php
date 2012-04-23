<?php

$m = Model::getInstance();

?>

<html>
  <head>
    <title>Helpers Test: Ajax Link Test</title>
    
    <?php /*echo h("js", array("name" => "prototype_161") ); */ ?>
    <?php echo h("js", array("name" => "jquery/jquery-1.3.1.min") ); ?>
    
    <script type="text/javascript">
     // Handlers para Prototype
     /*
     var before_function = function(req, json) {
       
       $('content_div').innerHTML = "Cargando...";
     }
     var after_function = function(req, json) {
      
       if (!json) json = req.responseText.evalJSON();
       else       json = json.evalJSON();
        
       $('content_div').innerHTML = json.mensaje;
     }
     */
    </script>
    
    <script type="text/javascript">
     // Handlers para JQuery
     var before_function = function(req, json) {
       
       $('#content_div').html( "Cargando..." );
     }
     var after_function = function(json) {
      
       //alert(json);
       //alert(json.mensaje); // undefined
       
       var obj = eval('('+json+')');
       
       //alert(obj.mensaje); // Hola mundo!
       
       $('#content_div').html( obj.mensaje );
     }
    </script>
  </head>
  <body>
    <h1>Helpers Test: Ajax Link Test</h1>
    
    <?php echo h("ajax_link", array("action" => "ajaxLinkTest",
                               "doit"   => "true",
                               "body"   => "haz clic en el link",
                               "after"  => "after_function",
                               "before" => "before_function" ) ); ?>
    <br/><br/>
   
    <div style="width: 500px; height: 200px; padding:10px; padding-right:10px; background-color: #ffff80; border: 1px dashed #000" id="content_div">
    </div>
    
    <hr/>
    
    Este test muestra el uso del helper ajax_link.
    <ul>
      <li>Al cliquear en el link, un pedido AJAX es enviado al servidor.</li>
      <li>El servidor responde mediante una respuesta JSON.</li>
      <li>La respuesta JSON es mostrada dentro de una DIV HTML.</li>
    </ul>
    Todo esto se hace sin recargar la pagina, a diferencia de un link comun donde si se recarga toda la pagina.
    
    <br/>
    
    <?php echo h("link", array("action" => "ajaxLinkTest",
                               "body"   => "reiniciar") ); ?>
    |
    <?php echo h("link", array("action" => "index",
                               "body"   => "volver") ); ?>

  </body>
</html>