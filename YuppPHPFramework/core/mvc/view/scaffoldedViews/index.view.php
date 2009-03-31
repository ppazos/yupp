<?php

$m = Model::getInstance();

?>

<html>
   <head>
   </head>
   <body>
      <h1>Index</h1>

      <!-- NO ES UTIL EL SELECTOR DE MODO, AHORA SE CAMBIA DESDE LA CONFIG.
      <h2>Seleccion del modo de ejecucion</h2>
      <?php /* echo h('mode_chooser'); */ ?><hr/>
      -->


      <!-- fixme: no deberia mostrarse si el modo es produccion, esto es solo para dev -->
      <h2>Informacion del modelo</h2>
      Muestra que tablas fueron generadas para el modelo y que tablas falta generar, 
      y permite generar las tablas que falten.<br/><br/>
      <?php
         $allTablesCreated = $m->get('allTablesCreated');
         if ($allTablesCreated !== NULL)
         {
            if (!$allTablesCreated)
            {
               echo "Existe modelo para el que no se generaron las tablas, ¿desea crear las tablas ahora?<br/>";
               // TODO: link a una accion que genere las tablas.
               
               echo "<h3>";
               echo h("link",
                      array("action" => "createModelTables",
                            "body"   => "Crear tablas"));
               echo "</h3>";
            }
            else
            {
               echo "Se generaron todas las tablas para el modelo.<br/>";
            }
         }

         $modelTables = &$m->get('modelTables');
         if ( $modelTables = &$m->get('modelTables') !== NULL )
         {
            echo "<ul>";
            foreach ( $modelTables as $class => $info )
            {
               echo  '<li>Clase: <b>'. $class .'</b> se guarda en la tabla: <b>'. $info['tableName'] .'</b> (' . $info['created'] .')</li>';
            }
            echo "</ul>";
         }
         
         echo "<hr/>";
         
         /*
         foreach ( YuppLoader::getLoadedClasses() as $path => $classInfo )
         {
            if ( String::endsWith($classInfo['package'], "model") )
            {
               echo '[ <a href="core/list?class='. $classInfo['class'] .'">'. $classInfo['class'] .'</a> ]<br/>';
            }
         }
         */
      ?>


<?php
/**
 * TODO: verificar si el componente tiene un archivo de Bootstrap, de no tener, no mostrarlo en la lista.
 */
?>


      <h2>Componentes</h2>
      Esta secci&oacute;n le permite ejecutar scripts de inicializaci&oacute;n 
      para los componentes del sistema.<br/>
      <ul>
         <?php foreach ($m->get('components') as $component): ?>
           <?php if (!String::startsWith($component, ".")) : ?>
            <li>
               <?php echo $component; ?>
               <?php if (file_exists("components/$component/components.$component.Bootstrap.script.php")): ?>
                  <?php echo h('link', array("action"        => "executeBootstrap",
                                             "body"          => "Ejecutar Bootstrap",
                                             "componentName" => $component) ); ?>
               <?php else: ?>
                 (no existe script de Bootstrap)
               <?php endif; ?>
            </li>
            <?php endif; ?>
         <?php endforeach; ?>
      </ul>
      <hr/>        
      
      <h2>Ingreso a los controladores</h2>
      <?php
         $dir = dir("./components");
         $suffix = "Controller.class.php";
      
         while (false !== ($component = $dir->read())) :

            if (!String::startsWith($component, ".") && $component !== "core") :
            
               echo "<h3>$component:</h3>";
               
               $component_dir  = dir("./components/".$component."/controllers");
               
               echo "<ul>";
               while (false !== ($controller = $component_dir->read())) :
                  //if ($controller !== "." && $controller !== "..") :
                  if ( !String::startsWith( $controller, ".") ) : // No quiero los archivos que empiezan con "."
                     $prefix = "components.".$component.".controllers.";
                     
                     $controller = substr($controller, strlen($prefix), -strlen($suffix));
                     //$logic_controller = strtolower( substr($controller, 0, 1) ) . substr($controller, 1, strlen($controller));
                     $logic_controller = String::firstToLower( $controller );

                     echo '<li>[ <a href="'.Helpers::url( array("component"=>$component, "controller"=>$logic_controller, "action"=>"index") ).'">'. $controller .'</a> ]</li>';
                     
                     /*
                     $ctx = YuppContext::getInstance();
                     if ($ctx->getComponent() !== NULL && $ctx->getComponent() !== "core") // Si entro por http://localhost:8081/Persistent/blog/ y no tengo controller me repite el /blog en el link.
                        echo '<li>[ <a href="'.$logic_controller.'">'. $controller .'</a> ]</li>';
                     else
                        echo '<li>[ <a href="'.$component.'/'.$logic_controller.'/list">'. $controller .'</a> ]</li>';
                     */
                     
                  endif;
               endwhile;
               echo "</ul>";
               
            endif;
         endwhile;
      ?>
      <hr/>
      
      
      <h2>Estad&iacute;sticas</h2>
      Algunas medidas del sistema.<br/>
      <ul>
        <li>
          <?php echo h('link', array("component"     => "core",
                                     "controller"    => "core",
                                     "action"        => "showStats",
                                     "body"          => "Ver estad&iacute;sticas") ); ?>
        </li>
      </ul>
      <hr/>
      
   </body>
</html>