<?php

$m = Model::getInstance();

$createDatabaseForApps = $m->get('createDatabaseForApps');
$cfg = YuppConfig::getInstance();

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
       margin: 0;
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
      #actions a {
        text-decoration: none;
      }
      #apps {
        background-color: #fff;
        padding: 10px;
        border: 1px solid #dfdfdf;
        margin: 10px;
      }
      .menu_btn {
         padding: 15px;
         padding-left: 20px;
         padding-right: 20px;
         
         margin-left: 5px;
         /*width: 110px;*/
         text-align: center;
      }
      .menu_btn.active {
        background-color: #fff; /* tab activa del menu superior */
        border-top-right-radius: 5px;
        -moz-border-radius-topright: 5px;
        border-top-left-radius: 5px;
        -moz-border-radius-topleft: 5px;
      }
      .menu_btn img {
        border: 0px;
      }
      #apps ul {
        margin: 0px;
        padding: 0px;
        list-style: none;
      }
      #apps li {
       /*width: 460px;*/
       width: 49%; /* 2 columnas */
       min-height: 140px;
       display: inline-block;
       vertical-align: top; /* BASELINE CORRECCIÓN*/
       padding: 5px;
       margin: 0 0 1px 0;
       zoom: 1; /* IE7 (hasLayout)*/
       *display: inline-block; /* IE */
       /*background-color: #ffff80;*/
       background-color: #efefef;
      }
      #apps li:hover {
        background-color: #99ddff;
      }
      #apps li a {
        display: block;
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
        width: 100%;
        /*border: 1px solid #000;*/
        margin: 0px;
        padding: 5px;
        padding-top: 3px;
        padding-right: 0px;
        float: left;
      }
      .info {
         display: block;
         margin-top: 40px;
         overflow: auto;
         height: 100px;
      }
      table#top {
        width: 100%;
        padding: 5px 10px 0 10px;
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
      .message_ok {
        padding: 10px;
        background-color: #DFF0D8;
        border: 1px solid #D6E9C6;
        color: #468847;
      }
      .createDbContainer {
        margin-bottom: 15px;
      }
      .createDb {
        padding: 2px 2px 2px 10px;
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
            <a href="<?php echo h('url', array('app'=>'core', 'controller'=>'core', 'action'=>'dbStatus'));?>">
              <?php echo h('img', array('src'=>'db_64.png')); ?><br/>
              Base de datos
            </a>
          </div>
          <div class="right menu_btn">
            <a href="<?php echo h('url', array('app'=>'core', 'controller'=>'core', 'action'=>'index'));?>">
              <?php echo h('img', array('src'=>'app_64.png')); ?><br/>
              Aplicaciones
            </a>
          </div>
        </td>
      </tr>
    </table>
    <div id="actions">
      <?php echo h('link', array(
                   'app'=>'core',
                   'controller'=>'core',
                   'action'=>'createApp',
                   'body'=>'Nueva Aplicacion'));?>
    </div>
    <div id="apps">
      <?php if ($m->flash('message') != NULL) : ?>
        <div align="center" class="message_ok"><?php echo $m->flash('message'); ?></div>
      <?php endif; ?>
      
      <!-- fixme: no deberia mostrarse si el modo es produccion, esto es solo para dev -->
      <h2>Informacion del modelo</h2>
      Muestra que tablas fueron generadas para el modelo y que tablas falta generar, 
      y permite generar las tablas que falten.<br/><br/>
      <?php
      
        if (count($createDatabaseForApps) > 0)
        {
           echo '<h3>Las siguientes bases de datos deben crearse:</h3>';
           echo '<div class="createDBContainer">';
           foreach ($createDatabaseForApps as $app)
           {
              $datasource = $cfg->getDatasource($app);
              echo '<div class="createDb">Base: ',$datasource['database'],' App:', $app, ' ';
              echo h('link', array('body'=>'Crear base de datos', 'action'=>'createDb', 'params'=>array('app'=>$app))), '</div>';
           }
           echo '</div>';
        }
        
      
        $allTablesCreated = $m->get('allTablesCreated');
        if ($allTablesCreated !== NULL)
        {
          if (!$allTablesCreated)
          {
            echo 'Existe modelo para el que no se generaron las tablas &iquest;desea crear las tablas ahora?<br/>';
            echo '<h3>';
            echo h('link', array('action'=>'createModelTables', 'body'=>'Crear tablas'));
            echo '</h3>';
          }
          else
          {
            echo '<h3>Se generaron todas las tablas para el modelo.</h3>';
          }
        }
      ?>
      <ul>
        <?php
          $appModelClasses = $m->get('appModelClasses');
          foreach ($appModelClasses as $appName => $classInfo) :
        ?>
        <li>
          <div class="app_details">
            <div class="app_icon">
              <?php
                // Icono de la aplicacion
                // Si no existe la imagen del icono de la aplicacion, muestra la imagen por defecto.
                try {
                  echo h('img', array('app'=>$appName, 'src'=>'app_64.png', 'w'=>64, 'h'=>64));
                } catch (Exception $e) {
                  echo h('img', array('src'=>'app_64.png', 'w'=>64, 'h'=>64));
                }
              ?>
            </div>
            <?php
              // Datasource
              $datasource = $cfg->getDatasource($appName);
           
              echo '<h3>Clases del modelo de "'. $appName .'":</h3>';
              echo '<div class="info">';
              if (in_array($appName, $createDatabaseForApps))
              {
                echo 'Debe crear la base de datos <b>'.$datasource['database'].'</b> para esta aplicacion';
              }
              else if ( count($classInfo) == 0 )
              {
                echo 'No hay clases definidas';
              }
              else
              {
                foreach ( $classInfo as $class => $info )
                {
                  echo '<b>'.$class.'</b> se guarda en la tabla: <b>'.$info['tableName'].'</b> ('.$info['created'].')<br/>';
                }
              }
              echo '</div>';
              //echo '<br/><br/>';
              //print_r($datasource);
            ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </body>
</html>