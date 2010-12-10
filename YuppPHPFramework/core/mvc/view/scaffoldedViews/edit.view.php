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
    <h1>Edit</h1>
    
    <div align="center"><?php echo $m->flash('message'); ?></div>
    
    <?php $clazz = $m->get('object')->aGet('class'); ?>
    
    <div id="actions">
      <a href="list?class=<?php echo $clazz ?>">List</a>
    </div>
    <br/>
      
      <!--
      DE ESTA PAGINA TENDRIA QUE VER EL TEMA DEL BINDINDG CON EL MODELO AL SUBMITEAR.<br/><br/>
      
      TODO: Accion para submitear el form. Podria tener algun tipo de controller estandar que
      haga acciones CRUD, para esto le tengo que pasar el nombre de la clase!.<br/><br/>
      -->
      
    <?php echo DisplayHelper::errors( $m->get('object') ); ?>
      
    <form action="save" method="post">
        <input type="hidden" name="id"    value="<?php echo $m->get('object')->aGet('id'); ?>" />
        <input type="hidden" name="class" value="<?php echo $m->get('object')->aGet('class'); ?>" />
      
        <?php echo DisplayHelper::model( $m->get('object'), "edit" ); ?><br/>
      
        <input type="submit" value="Save" />
        <a href="show?class=<?php echo $m->get('object')->aGet('class') ?>&id=<?php echo $m->get('object')->aGet('id') ?>">Cancel</a>
    </form>
  </body>
</html>