<?php

$m = Model::getInstance();

global $_base_dir;

YuppLoader :: load('core.mvc', 'DisplayHelper');

?>
<html>
  <head>
    <style>
      body {
         font-family: arial, verdana, tahoma;
         font-size: 12px;
         background-color: #efefef;
      }
      table {
         border: 1px solid #000;
         /* spacing: 0px; */
         border-collapse: separate;
         border-spacing: 0px;
      }
      th {
         border-bottom: 1px solid #ddd;
         padding: 5px;
         background-color: #ddd;
         color: #fff;
         background: #fff url(<?php echo $_base_dir; ?>/images/shadow.jpg) bottom repeat-x;
      }
      td {
         border-bottom: 1px solid #ddd;
         padding: 5px;
         background-color: #f5f5f5;
      }
      #actions {
          background: #fff url(<?php echo $_base_dir; ?>/images/shadow.jpg) bottom repeat-x;
          border: 1px solid #ccc;
          border-style: solid none solid none;
          padding: 7px 12px;
      }
      #actions a {
        padding-right: 5px;
        padding-left: 5px;
      }
      
      .order_desc, .order_asc {
         background-position: 0px;
         background-repeat: no-repeat;
         padding-left: 12px;
      }
      .order_asc {
         background-image: url(<?php echo $_base_dir; ?>/images/order_asc.gif);
      }
      .order_desc {
         background-image: url(<?php echo $_base_dir; ?>/images/order_desc.gif);
      }
    </style>
  </head>
  <body>
    <h1>List</h1>
      
    <div align="center"><?php echo $m->flash('message'); ?></div>
    
    <div id="actions">
      <?php
        $ctx = YuppContext::getInstance();
        $app = $m->get('app');
        if ( !isset($app) ) $app = $ctx->getApp();
      ?>
      <a href="create?app=<?php echo $app; ?>&class=<?php echo $m->get('class') ?>">Create</a>
    </div>
    <br/>
    
    <?php echo DisplayHelper::model( $m->get('list'), "list", $m->get('class') ); ?>
      
    </br></br>
      
    <?php echo h('pager', array('count'=>$m->get('count'), 'max'=>$m->get('max'), 'offset'=>$m->get('offset'))); ?>
  </body>
</html>