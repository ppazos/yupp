<?php

$m = Model::getInstance();

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
         padding-top: 35px;
         display: inline-block;
      }
      table, tr, td {
         margin: 0px;
         padding: 0px;
         border: 0px;
      }
      #apps {
         background-color: #fff;
         padding: 10px;
      }
      .menu_btn {
         padding: 15px;
         padding-left: 20px;
         padding-right: 20px;
         margin-top: 5px;
         margin-left: 5px;
         /*width: 110px;*/
         text-align: center;
      }
      .menu_btn.active {
         background-color: #fff; /* tab activa del menu superior */
      }
      .menu_btn img {
         border: 0px;
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
    <table id="top" cellspacing="0">
      <tr>
        <td id="logo">
          <?php echo h('img', array('src'=>'yupp_logo.png')); ?>
        </td>
        <td id="top_right">
          <h1>Yupp PHP Framework</h1>
          <div class="right menu_btn active">
            <a href="<?php echo h('url', array(
                       'component'=>'core',
                       'controller'=>'core',
                       'action'=>'dbStatus'));?>">
              <?php echo h('img', array('src'=>'db_64.png')); ?>
              <br/>
              Base de datos
            </a>
          </div>
          <div class="right menu_btn">
            <a href="<?php echo h('url', array(
                       'component'=>'core',
                       'controller'=>'core',
                       'action'=>'index'));?>">
              <?php echo h('img', array('src'=>'app_64.png')); ?>
              <br/>
              Aplicaciones
            </a>
          </div>
        </td>
      </tr>
    </table>
    <div id="apps">
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
      ?>
    </div>
  </body>
</html>