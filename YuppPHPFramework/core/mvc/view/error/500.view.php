<?php

$ctx = YuppContext::getInstance();

$m = Model::getInstance();

YuppLoader::loadScript('apps.core', 'Messages');
YuppLoader :: load('core.mvc', 'DisplayHelper');

global $_base_dir;

?>
<html>
  <head>
    <style>
      body {
        font-family: arial, verdana, tahoma;
        font-size: 12px;
        background-color: #efefef;
      }
      .message {
        background: #fff url(<?php echo $_base_dir; ?>/images/shadow.jpg) bottom repeat-x;
        border: 1px solid #ccc;
        border-style: solid solid none solid;
        padding: 7px 12px;
        font-weight: bold;
      }
      .body {
        border: 1px solid #ccc;
        padding: 7px 12px;
        background-color: #f5f5f5;
      }
      .text {
        border: 1px solid #ccc;
        padding: 7px 12px;
        background-color: #fff;
        margin: 10px;
        min-height: 80px;
        overflow: auto;
      }
      .code {
        width: 98%;
        height: 300px;
        overflow: auto;
        background-color: #eef;
        border: 1px solid #669;
        padding: 10px;
      }
      .lineNumber {
        color: #999;
        margin-right: 10px;
      }
    </style>
  </head>
  <body>
    <h1><?php echo h('img', array('src'=>'app_32.png', 'align'=>'top')); ?> Error 500</h1>
    <div class="message">
      <?php echo DisplayHelper::message('error.500.InternalServerError'); ?>
    </div>
    <div class="body">
      Aplicaci&oacute;n: <?php echo $ctx->getApp(); ?><br/>
      Controlador: <?php echo $ctx->getController(); ?><br/>
      Acci&oacute;n: <?php echo $ctx->getAction(); ?><br/>
      <div class="text">
        <?php echo $m->get('message'); ?></br>
        <pre><?php echo $m->get('traceString'); ?></pre>
        <div class="code"><?php
          //$trace = $m->get('trace');
          //var_dump($trace);
          
          $lines = explode( "\n", $m->get('traceString') );
          /*
           * Array
           * (
           *   [0] => #0 [internal function]: my_warning_handler(2, 'simplexml_load_...', 'C:\wamp\www\Yup...', 255, Array)
           *   [1] => #1 C:\wamp\www\YuppPHPFramework\apps\movix\controllers\apps.movix.controllers.MovieController.class.php(255): simplexml_load_string('<div>???<div id...')
           *   [2] => #2 C:\wamp\www\YuppPHPFramework\core\mvc\core.mvc.YuppController.class.php(69): MovieController->seleccionarAction(Array)
           *   [3] => #3 [internal function]: YuppController->__call('seleccionar', Array)
           *   [4] => #4 C:\wamp\www\YuppPHPFramework\core\routing\core.routing.Executer.class.php(176): MovieController->seleccionar()
           *   [5] => #5 C:\wamp\www\YuppPHPFramework\core\web\core.web.RequestManager.class.php(181): Executer->execute(NULL)
           *   [6] => #6 C:\wamp\www\YuppPHPFramework\index.php(78): RequestManager::doRequest()
           *   [7] => #7 {main}
           * )
           * 
           * o
           * 
           * #0 C:\wamp\www\YuppPHPFramework\apps\movix\controllers\apps.movix.controllers.MovieController.class.php(246): my_warning_handler(8, 'Undefined offse...', 'C:\wamp\www\Yup...', 246, Array)
           * #1 C:\wamp\www\YuppPHPFramework\core\mvc\core.mvc.YuppController.class.php(69): MovieController->seleccionarAction(Array)
           * #2 [internal function]: YuppController->__call('seleccionar', Array)
           * #3 C:\wamp\www\YuppPHPFramework\core\routing\core.routing.Executer.class.php(176): MovieController->seleccionar()
           * #4 C:\wamp\www\YuppPHPFramework\core\web\core.web.RequestManager.class.php(181): Executer->execute(NULL)
           * #5 C:\wamp\www\YuppPHPFramework\index.php(78): RequestManager::doRequest()
           * #6 {main}
           */
           
          /*
           * Quiero mostrar el contenido del archivo en el indice 1 y la linea 255
           * Es la linea 1 solo si la 0 empieza con internal function, si no es la 0
           */
          $idx = 0;
          if (strpos($lines[0], '[internal function]') !== false) $idx = 1;
          
          $fileInfo = substr( $lines[$idx], 3); // Saca el #1
          $fileInfo = explode( "):", $fileInfo ); // Me quedo con C:\wamp\www\YuppPHPFramework\apps\movix\controllers\apps.movix.controllers.MovieController.class.php(255, ver que saca tambien el ultimo parentesis )
          list($file, $line) = explode( "(", $fileInfo[0]); // Divido por el primer parentesis
          
          $code = file($file);
          
          // Me quedo con algunas lineas cerca de $line (13 para arriba y 13 para abajo)
          $start = $line-13;
          if ($start < 0) $start = 0;
          for ($i=$start; $i<$start+26; $i++)
          {
             echo '<span class="lineNumber">#'.$i.'</span> ';
             // $i-1 porque el indice en el array empieza de 0 y las lineas cuentan desde 1
             if ($line==$i) echo '<b>'.htmlentities($code[$i-1]).'</b><br/>';
             else echo htmlentities($code[$i-1]).'<br/>';
          }
        ?></div>
      </div>
    </div>
  </body>
</html>