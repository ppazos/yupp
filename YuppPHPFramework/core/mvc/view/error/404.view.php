<?php

$ctx = YuppContext::getInstance();

$m = Model::getInstance();

YuppLoader::loadScript('apps.core', 'Messages');
YuppLoader :: load('core.mvc', 'DisplayHelper');

global $_base_dir;

?>
<html>
  <head>
    <style>
      body {
        font-family: arial, verdana, tahoma;
        font-size: 12px;
        background-color: #efefef;
      }
      .message {
        background: #fff url(<?php echo $_base_dir; ?>/images/shadow.jpg) bottom repeat-x;
        border: 1px solid #ccc;
        border-style: solid solid none solid;
        padding: 7px 12px;
        font-weight: bold;
      }
      .body {
        border: 1px solid #ccc;
        padding: 7px 12px;
        background-color: #f5f5f5;
      }
      .text {
        border: 1px solid #ccc;
        padding: 7px 12px;
        background-color: #fff;
        margin: 10px;
        min-height: 80px;
        overflow: auto;
      }
    </style>
  </head>
  <body>
    <h1><?php echo h('img', array('src'=>'app_32.png', 'align'=>'top')); ?> Error 404</h1>
    <div class="message">
      <?php echo DisplayHelper::message('error.404.NotFound'); ?>
    </div>
    <div class="body">
      Aplicaci&oacute;n: <?php echo $ctx->getApp(); ?><br/>
      Controlador: <?php echo $ctx->getController(); ?><br/>
      Acci&oacute;n: <?php echo $ctx->getAction(); ?><br/>
      <div class="text">
        <?php echo $m->get('message'); ?>
      </div>
    </div>
  </body>
</html>