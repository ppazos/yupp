<?php

YuppLoader::load('core.mvc', 'DisplayHelper');
YuppLoader::load('apps.twitter.helpers', 'THelpers');
YuppLoader::loadScript('apps.twitter', 'Messages');

$m = Model::getInstance();

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?php echo h('css', array('app'=>'twitter', 'name'=>'twitter.bootstrap')); ?>
    <style type="text/css">
      body {
        padding: 10px;
      }
      form {
        padding: 0px;
        margin: 0px;
      }
      img {
        vertical-align: bottom;
      }
      legend {
        margin-bottom: 0px;
      }
     .top .well {
        padding: 10px;
        width: 98%;
      }
      .form-actions {
        margin-bottom: 0px;
        padding-bottom: 0px;
      }
      .form-search {
        width: 218px;
        float: right;
        position: relative;
      }
      .form-search .icon-search {
        position: absolute;
        top: 8px;
        left: 8px;
      }
      .form-search .search-query, .form-search .search-query:focus, .form-search .search-query.focused {
        padding-left: 23px;
      }
      .locale_chooser {
        display: inline-block;
        width: 170px;
      }
      .locale_chooser select, .locale_chooser input {
        width: 60px;
        margin: 0 5px 0 0;
      }​
    </style>
    <?php echo $head; ?>
  </head>
  <body>
    <div class="row-fluid top">
      <div class="span12 well">
        <div class="row-fluid">
          <div class="span4">
            <?php Helpers::template(array('controller'=>'templates', 'name'=>'userbox', 'args'=>array())); ?>
          </div>
          <div class="span8">
            <?php echo Helpers::locale_chooser(array('langs'=>array('es','en'))); ?>
            <?php Helpers::template(array('controller'=>'templates', 'name'=>'search', 'args'=>array('m'=>$m))); ?>
          </div>
        </div>
      </div>
    </div>
    
    <?php if ($m->flash('message') != NULL) : ?>
      <div class="alert alert-info"><?php echo msg($m->flash('message')); ?></div>
    <?php endif; ?>
    <?php if ($m->flash('error') != NULL) : ?>
      <div class="alert alert-error"><?php echo msg($m->flash('error')); ?></div>
    <?php endif; ?>
    
    <?php echo $body; ?>
  </body>
</html>