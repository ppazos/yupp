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
    </style>
  </head>
  <body>
    <h1>Show</h1>
        
    <div align="center"><?php echo $m->flash('message'); ?></div>
    
    <?php $clazz = $m->get('object')->aGet('class'); ?>
    <?php $id    = $m->get('object')->aGet('id'); ?>
    
    <div id="actions">
      <a href="edit?class=<?php echo $clazz ?>&id=<?php echo $id ?>">Edit</a>
      |
      <a href="delete?class=<?php echo $clazz ?>&id=<?php echo $id ?>">Delete</a>
      |
      <a href="list?class=<?php echo $clazz ?>">List</a>
    </div>
    <br/>
    
    <?php echo DisplayHelper::model( $m->get('object'), "show" ); ?>     
  </body>
</html>