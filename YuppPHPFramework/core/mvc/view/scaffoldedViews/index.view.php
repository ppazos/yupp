<?php

$m = Model::getInstance();

$apps = $m->get('apps');

global $_base_dir;

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    
    <?php echo h('js', array('name'=>'jquery/jquery-1.5.min')); ?>
    <?php echo h('js', array('name'=>'jquery/jquery.corner')); ?>
    <?php /*echo h('css', array('name' => 'niftyCorners') ); */ ?>
    <?php /*echo h('js',  array('name' => 'niftycube') ); */ ?>

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
      #actions a {
         text-decoration: none;
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
       width: 230px;
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
        width: 160px;
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
      
      #twitter_news_count {
         padding-left: 4px;
         padding-right: 4px;
         font-weight: bold;
      }
      #twitter_big_container {
         display: none;
         position: absolute;
         padding: 0px;
      }
      #twitter_img {
         width: 190px;
         height: 64px;
         position: relative;
         top: 11px;
         left: 90px;
         display: inline;
         z-index: 999;
      }
      #twitter_ref {
         display: inline;
         width: 40px;
         height: 30px;
         position: relative;
         left: 15px;
         top: 5px;
         padding: 0xp;
         margin: 0px;
      }
      #twitter_img img {
         border: 0px;
      }
      #twitter_container {
         background-color: #99ccff;
         width: 330px;
         padding: 5px;
         margin-bottom: 20px;
         position: relative;
         height: auto;
      }
      #twitter_news {
         background-color: #cfefff;
         padding: 0px;
         position: relative;
         height: auto;
         font-size: 12px;
         font-family: tahoma;
      }
      .twitter_news_item {
         border-bottom: 1px solid #99ccff;
         padding: 5px;
      }
      div.twitter_news_item:hover{
         background-color: #dffeff;
      }
      #close_twitter {
         position: relative;
         left: -10px;
         top: -15px;
         display: inline;
         text-decoration: none;
         font-weight: bold;
      }
    </style>
    <script type="text/javascript">
    
      var selectedLink; // Link de friltro por letra activo
      var news; // News From Twitter
    
      $(document).ready( function() {
        
        // Bordes redondeados de la caja de twitter
        $('#twitter_container').corner();
        $('#twitter_news').corner();

        
        // Filtro de aplicaciones por letra
        $('.app_filter').click( function()
        {
          link = $(this);
          letra = link.attr('class').substr(11); // trato a la class "app_filter letra" como string para sacar la letra

          filter(letra.toLowerCase());
          
          if (selectedLink) selectedLink.removeClass('highlight');
          link.addClass('highlight');
          selectedLink = link;
        });
        
        // News From Twitter
        
        $.ajax({
          url: "<?php echo h('url', array('action'=>'getNewsFromTwitter')); ?>",
          dataType: 'json',
          context: document.body,
          success: function(res)
          {
            var count = 0; // cantidad de twitts
            
            $.each( res, function( idx, item )
            {
              if (item.text.indexOf('yupp') != -1)
              {
                count++; 

                // Pone el link con tag <a>
                //textWithLink = item.text.replace(/(http:\/\/([a-zA-Z0-9_\-\.]+[.]{1}){2}[a-zA-z0-9_\-\.]+(\/{1}[a-zA-Z0-9_\-\.\?&=#:]+)*\/?)/i, '<a href="$1" target="_blank">$1</a>' );
                
                // si quiero acortar el lin y poner ... luego de un largo maximo:
                // 0 es el texto, 1 el link.
                // el problema es que si tuviera texto despues del link, esto cambia.
                //alert( item.text.split(/(http:\/\/([a-zA-Z0-9_\-\.]+[.]{1}){2}[a-zA-z0-9_\-\.]+(\/{1}[a-zA-Z0-9_\-\.\?&=#:]+)*\/?)/i) );
                partes = item.text.split(/(http:\/\/([a-zA-Z0-9_\-\.]+[.]{1}){2}[a-zA-z0-9_\-\.]+(\/{1}[a-zA-Z0-9_\-\.\?&=#:]+)*\/?)/i);
                
                textWithLink = partes[0] + '<a href="'+ partes[1] +'" target="_blank">'+ partes[1].substring(0, 35) + ((partes[1].length>35)?'...':'') +'</a>';
                
                news = '<div id="twitter_news_'+count+'" class="twitter_news_item">'+ textWithLink +'</div>';
                
                // =============================================
                // Normal funka ok
                $(news).appendTo('#twitter_news');
                // =============================================
                
                // Si quiero bordes redondeados cambia el dom, el append se hace distinto
                //#twitter_news b.niftycorners
                //$('#twitter_news b:first-child').after(news);
                
                //alert($('#twitter_news b:first-child').length);
                //alert( new String() );
                
                // ===============================================
                //corners = $('#twitter_news b:first-child')[0];
                //$(corners).after(news);
                // ===============================================
                
                /*
                $.each($('#twitter_news b:first-child'), function(i, e) {
                    alert( $(e).attr('class') );
                });
                */
              }
            } );
            
            $('#twitter_news_count').css('background-color', 'red');
            $('#twitter_news_count').css('color', 'white');
            $('#twitter_news_count').corner("7px");
            $('#twitter_news_count').text(count);
            
            
            // Muestro el container de las noticias
            newsContainer = $('#twitter_big_container');
            
            //alert($('#twitter_news_count').position().left);
            
            
            // Bordes redondeados para items, el primero y el ultimo
            items = $('.twitter_news_item');
            if (items.length > 0)
            {
               $(items[0]).corner("top");
               $(items[items.length-1]).corner("bottom");
            }
            
            newsContainer.css('top', $('#twitter_news_count').offset().top-23 );
            newsContainer.css('left', $('#twitter_news_count').offset().left-60 );
            //newsContainer.show(); // No se muestra por defecto
          }
        });
        
        $('#close_twitter').click( function() {
           $('#twitter_big_container').hide();
        });
        $('#show_twitter').click( function() {
            
           $('#twitter_big_container').show();
        });
        
      });
      
      /**
       * Oculta o muestra aplicaciones segun el filtro
       */
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
                   'body'=>'Nueva Aplicacion'));?>
      |
      <?php
        // TODO: hacerlo inteligente: solo mostrar letras de aplicacioens existentes.
        $letras = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','W','X','Y','Z');
      ?>
      <a href="#" class="app_filter all">Todas</a>
      <?php foreach ($letras as $letra) : ?>
        <a href="#" class="app_filter <?php echo $letra; ?>"><?php echo $letra; ?></a>
      <?php endforeach; ?>
      |
      <a href="#" id="show_twitter">Novedades <span id="twitter_news_count"></span></a>
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
    
    <div id="twitter_big_container">
      <div id="twitter_ref">
        <img src="<?php echo $_base_dir; ?>/images/twitter_ref.png" />
      </div>
      <div id="twitter_img">
        <a href="http://twitter.com/#!/ppazos" target="_blank"><img src="<?php echo $_base_dir; ?>/images/twitter.png" /></a>
        <a href="#" id="close_twitter">[X]</a>
      </div>
      <div id="twitter_container">
        <div id="twitter_news"></div>
      </div>
    </div>
    
    <!-- dejo estadisticas comentadas
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
    -->
    
  </body>
</html>