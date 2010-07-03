<?php

$m = Model::getInstance();

$apps = $m->get('apps');

?>

<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
      body {
         font-family: arial, verdana, tahoma;
         font-size: 12px;
         background-color: #efefef;
      }
      h1 {
         margin: 0px;
         padding-top: 10px;
         display: inline-block;
      }
      #apps ul {
         margin: 0px;
         padding: 0px;
         /*position: relative;*/
         /*left: 0px;*/
         list-style: none;
      }
      #apps li {
       width: 250px;
       min-height: 100px;
       height: 100px; /* necesario para que el anchor se expanda al alto 100% */
       _height: 100px; /* IE6 */
       /*border: 1px solid #000;*/
       display: -moz-inline-stack; /* FF2*/
       display: inline-block;
       vertical-align: top; /* BASELINE CORRECCIÓN*/
       padding: 5px;
       margin: 0px;
       /*margin-right: 5px;*/
       /*margin-bottom: 7px;*/
       zoom: 1; /* IE7 (hasLayout)*/
       *display: inline; /* IE */
       background-color: #ffff80;
      }
      #apps li:hover {
         background-color: #99ddff;
         /*cursor: pointer;*/
      }
      #apps li a {
         /*height: 100%;*/
         display: block;
         /*text-decoration: none;*/
         /*color: #000;*/
      }
      .app_icon {
         display: inline-block;
         vertical-align: top;
         width: 64px;
         /*border: 1px solid #000;*/
         margin: 0px;
         margin-right: 3px;
         padding: 0px;
         float: left;
      }
      .app_icon img {
         border: 0px;
      }
      .app_details {
         display: inline-block;
         vertical-align: top;
         width: 181px;
         /*border: 1px solid #000;*/
         margin: 0px;
         padding: 0px;
         padding-top: 3px;
         float: left;
      }
      table#top {
         width: 100%;
      }
      td#logo {
         /*display: inline-block;*/
         width: 64px;
      }
      td#top_right {
         /*width: 100%;*/
         /*border: 1px solid #000;*/
      }
      .right {
         float: right;
      }
    </style>
  </head>
  <body>
    <table id="top">
      <tr>
        <td id="logo">
          <?php echo h('img', array('src'=>'yupp_logo.png')); ?>
        </td>
        <td id="top_right">
          <h1>Yupp PHP Framework</h1>
          <div class="right">
            <a href="<?php echo h('url', array(
                       'component'=>'core',
                       'controller'=>'core',
                       'action'=>'dbStatus'));?>">
              <div class="app_icon">
                <?php echo h('img', array('src'=>'db_64.png')); ?>
              </div>
              <div class="app_details">
                Base de datos
              </div>
            </a>
          </div>
          <div class="right">
            <a href="<?php echo h('url', array(
                       'component'=>'core',
                       'controller'=>'core',
                       'action'=>'index'));?>">
              <div class="app_icon">
                <?php echo h('img', array('src'=>'app_64.png')); ?>
              </div>
              <div class="app_details">
                Aplicaciones
              </div>
            </a>
          </div>
        </td>
      </tr>
    </table>
    <br/><br/>
    <div id="apps">
      <ul>
        <?php foreach ($apps as $app) : ?>
          <li>
              <div class="app_icon">
                <a href="<?php echo h('url', array(
                            'component'=>$app->getName(),
                            'controller'=>$app->getDescriptor()->entry_point->controller,
                            'action'=>$app->getDescriptor()->entry_point->action));
                         ?>" title="Ejecutar aplicacion">
                  <?php
                    // Si no existe la imagen del icono de la aplicacion, muestra la imagen por defecto.
                    try {
                       echo h('img', array(
                          'component'=>$app->getName(),
                          'src'=>'app_64.png',
                          'w'=>64,
                          'h'=>64
                       ));
                    } catch (Exception $e) {
                       //echo $e->getMessage();
                       echo h('img', array('src'=>'app_64.png', 'w'=>64, 'h'=>64,));
                    }
                  ?>
                </a>
              </div>
              <div class="app_details">
                <b><?php echo $app->getDescriptor()->name; ?></b><br/>
                <?php echo $app->getDescriptor()->description; ?><br/>
                <?php
                if ($app->hasBootstrap())
                {
                   //echo 'Tiene BS<br/>';
                   echo h('link', array("action"        => "executeBootstrap",
                                        "body"          => "Ejecutar arranque",
                                        "componentName" => $app->getName()) );
                }
                //else  echo 'No tiene BS<br/>';
                ?>
              </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <hr/>

<?php /*
    <h1>Index</h1>

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
               echo "Existe modelo para el que no se generaron las tablas &iquest;desea crear las tablas ahora?<br/>";
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
         
         $componentModelClasses = $m->get('componentModelClasses');
         foreach ($componentModelClasses as $component => $classInfo)
         {
            echo "<h3>$component:</h3>";
            echo "<ul>";
            foreach ( $classInfo as $class => $info )
            {
               echo  '<li>Clase: <b>'. $class .'</b> se guarda en la tabla: <b>'. $info['tableName'] .'</b> (' . $info['created'] .')</li>';
            }
            echo "</ul>";
         }
         echo "<hr/>";
         
      ?>

      <h2>Componentes</h2>
      Ejecutar scripts de arranque para precargar datos necesarios para el funcionamiento de los componentes del sistema.<br/>
      <ul>
         <?php foreach ($m->get('components') as $component): ?>
           <?php if (!String::startsWith($component, ".")) : ?>
            <li>
               <?php echo $component; ?>
               <?php if (file_exists("components/$component/bootstrap/components.$component.bootstrap.Bootstrap.script.php")): ?>
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
      
         while (false !== ($component = $dir->read()))
         {
            if ( !String::startsWith($component, ".") && $component !== "core" && is_dir("./components/".$component) )
            {
               echo "<h3>$component:</h3>";
               
               $dirpath = "./components/".$component."/controllers";
               
               if ( file_exists($dirpath) )
               {
                  $component_dir  = dir($dirpath);
                  
                  echo "<ul>";
                  while (false !== ($controller = $component_dir->read()))
                  {
                     if ( !String::startsWith( $controller, ".") ) // No quiero los archivos que empiezan con "." por el . y el ..
                     {
                        $prefix = "components.".$component.".controllers.";
                        
                        $controller = substr($controller, strlen($prefix), -strlen($suffix));
                        //$logic_controller = strtolower( substr($controller, 0, 1) ) . substr($controller, 1, strlen($controller));
                        $logic_controller = String::firstToLower( $controller );
   
                        echo '<li>[ <a href="'.Helpers::url( array("component"=>$component, "controller"=>$logic_controller, "action"=>"index") ).'">'. $controller .'</a> ]</li>';

                     }
                  }
                  echo "</ul>";
               }
               else echo "No se ha creado el directorio de controladores<br/>";
            }
         }
      ?>
      <hr/>
*/ ?>      
      
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