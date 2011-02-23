<?php

$m = Model::getInstance();

$apps = $m->get('apps');

global $_base_dir;

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    
    <?php echo h('js', array('name'=>'jquery/jquery-1.5.min')); ?>
    
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
        argin-top: 5px;
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
      
      /** App Filters **/
      .app_filter {
        padding: 3px; /* Para hacer mas faciles de cliquear los links con 1 letra */
      }
      .highlight {
        font-weight: bold;
      }
    </style>
    <script type="text/javascript">
    
      var selectedLink;
    
      $(document).ready( function() {
        
        /*
        $.each( $('.app'), function (i, l) {
          //alert( $(l).attr('class') );
        });
        */
        
        $('.app_filter').click( function()
        {
          link = $(this);
          letra = link.attr('class').substr(11); // trato a la class "app_filter letra" como string para sacar la letra
          //letra = link.attr('class');
          
          //alert('='+letra+"=");
          filter(letra.toLowerCase());
          
          if (selectedLink) selectedLink.removeClass('highlight');
          link.addClass('highlight');
          selectedLink = link;
        });
        
      });
      
      function filter(letra)
      {
        //alert('letra: '+ letra);
        $.each( $('.app'), function (idx, elem)
        {
          app = $(elem);
          
          if (letra == 'all') app.show();
          else
          {
            appName = app.attr('class').substr(4); // Trato a la class 'app appName' como un string para obtener el appName
            if (letra != appName[0].toLowerCase()) app.hide();
            else app.show();
          }
        });
      }
    </script>
  </head>
  <body>
    <table id="top" cellspacing="0">
      <tr>
        <td id="logo">
          <?php echo h('img', array('src'=>'yupp_logo.png')); ?>
        </td>
        <td id="top_right">
          <h1>Yupp PHP Framework</h1>
          <div class="right menu_btn">
            <a href="<?php echo h('url', array(
                       'component'=>'core',
                       'controller'=>'core',
                       'action'=>'dbStatus'));?>">
              
              <?php echo h('img', array('src'=>'db_64.png')); ?><br/>
              Base de datos
            </a>
          </div>
          <div class="right menu_btn active">
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
                   'body'=>'Nueva Aplicacion'));?> | 
      
      <?php
        // TODO: hacerlo inteligente: solo mostrar letras de aplicacioens existentes.
        $letras = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','W','X','Y','Z');
      ?>
      <a href="#" class="app_filter all">Todas</a>
      <?php foreach ($letras as $letra) : ?>
        <a href="#" class="app_filter <?php echo $letra; ?>"><?php echo $letra; ?></a>
      <?php endforeach; ?>
    </div>
    
    <div id="apps">
      <div align="center"><?php echo $m->flash('message'); ?></div>
    
      <ul>
        <?php foreach ($apps as $app) : ?>
          <li class="app <?php echo $app->getName(); ?>">
              <div class="app_icon">
                <a href="<?php echo h('url', array(
                            'component'=>$app->getName(),
                            'controller'=>$app->getDescriptor()->entry_point->controller,
                            'action'=>$app->getDescriptor()->entry_point->action));
                         ?>" title="Ejecutar aplicacion">
                  <?php
                    // Si no existe la imagen del icono de la aplicacion, muestra la imagen por defecto.
                    try {
                       echo h('img', array('component'=>$app->getName(), 'src'=>'app_64.png', 'w'=>64, 'h'=>64));
                    } catch (Exception $e) {
                       //echo $e->getMessage();
                       echo h('img', array('src'=>'app_64.png', 'w'=>64, 'h'=>64));
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
                   echo h('link', array("action"        => "executeBootstrap",
                                        "body"          => "Ejecutar arranque",
                                        "componentName" => $app->getName()) );
                }
                //else  echo 'No tiene BS<br/>';
                ?>
                <?php
                if ($app->hasTests())
                {
                   echo h('link', array("action" => "testApp",
                                        "body"   => "Ejecutar tests",
                                        "name"   => $app->getName()) );
                }
                ?>
              </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
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