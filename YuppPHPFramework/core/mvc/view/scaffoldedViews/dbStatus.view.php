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
    
    <h1>DB Status</h1>

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
  </body>
</html>