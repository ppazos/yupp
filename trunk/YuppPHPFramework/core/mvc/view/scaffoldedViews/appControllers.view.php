<?php
  $m = Model::getInstance();
  $app = $m->get('app');
?>

<html>
  <head>
  </head>
  <body>
    <h1>Aplicacion: <?php echo $app; ?></h1>
    <h2>Controladores:</h2>
    <?php
      $app_dir = dir("./apps/".$app."/controllers");
      $suffix = "Controller.class.php";
      $prefix = "apps.".$app.".controllers.";
      echo "<ul>";
      while (false !== ($controller = $app_dir->read()))
      {
         if ( !String::startsWith( $controller, ".") )
         {
            $controller = substr($controller, strlen($prefix), -strlen($suffix));
            $logic_controller = String::firstToLower( $controller );

            echo '<li>[ <a href="'.Helpers::url( array("app"=>$app, "controller"=>$logic_controller, "action"=>"index") ).'">'. $controller .'</a> ]</li>';
         }
      }
      echo "</ul>";
    ?>
  </body>
</html>