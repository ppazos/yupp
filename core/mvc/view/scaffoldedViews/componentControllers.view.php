<?php
  $m = Model::getInstance();
  $component = $m->get('component');
?>

<html>
  <head>
  </head>
  <body>
    <h1>Componente: <?php echo $component; ?></h1>
    <h2>Controladores:</h2>
    <?php
      $component_dir = dir("./apps/".$component."/controllers");
      $suffix = "Controller.class.php";
      $prefix = "apps.".$component.".controllers.";
      echo "<ul>";
      while (false !== ($controller = $component_dir->read()))
      {
         if ( !String::startsWith( $controller, ".") )
         {
            $controller = substr($controller, strlen($prefix), -strlen($suffix));
            $logic_controller = String::firstToLower( $controller );

            echo '<li>[ <a href="'.Helpers::url( array("component"=>$component, "controller"=>$logic_controller, "action"=>"index") ).'">'. $controller .'</a> ]</li>';
         }
      }
      echo "</ul>";
    ?>
  </body>
</html>