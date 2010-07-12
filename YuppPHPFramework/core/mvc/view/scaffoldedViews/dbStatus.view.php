<?php

$m = Model::getInstance();

global $_base_dir;

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
      #actions {
          background: #fff url(<?php echo $_base_dir; ?>/images/shadow.jpg) bottom repeat-x;
          border: 1px solid #ccc;
          border-style: solid none solid none;
          padding: 7px 12px;
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
      #apps ul {
         margin: 0px;
         padding: 0px;
         /*position: relative;*/
         /*left: 0px;*/
         list-style: none;
      }
      #apps li {
       width: 460px;
       min-height: 200px;
       /*height: auto;*/ /* necesario para que el anchor se expanda al alto 100% */
       /*_height: 100px;*/ /* IE6 */
       /*border: 1px solid #000;*/
       /*display: -moz-inline-stack;*/ /* FF2*/
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
         /*width: 370px;*/
         width: 100%;
         /*border: 1px solid #000;*/
         margin: 0px;
         padding: 5px;
         padding-top: 3px;
         padding-right: 0px;
         float: left;
      }
      
      .classes {
         overflow: auto;
         height: 190px;
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
              <?php echo h('img', array('src'=>'db_64.png')); ?><br/>
              Base de datos
            </a>
          </div>
          <div class="right menu_btn">
            <a href="<?php echo h('url', array(
                       'component'=>'core',
                       'controller'=>'core',
                       'action'=>'index'));?>">
              <?php echo h('img', array('src'=>'app_64.png')); ?><br/>
              Aplicaciones
            </a>
          </div>
        </td>
      </tr>
    </table>
    
    <div id="actions">
      <?php echo h('link', array(
                   'component'=>'core',
                   'controller'=>'core',
                   'action'=>'createApp',
                   'body'=>'Nueva Aplicacion'));?>
    </div>
    
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
      ?>
       
      <ul>
      <?php
        $componentModelClasses = $m->get('componentModelClasses');
        foreach ($componentModelClasses as $component => $classInfo) :
      ?>
        <li>
          <div class="app_details classes">
           <?php
              echo "<h3>Clases de $component:</h3>";
              foreach ( $classInfo as $class => $info )
              {
                 echo  '<div><b>'. $class .'</b> se guarda en la tabla: <b>'. $info['tableName'] .'</b> (' . $info['created'] .')</div>';
              }
           ?>
          </div>
        </li>
      <?php
         endforeach;
      ?>
      </ul>
    </div>
  </body>
</html>