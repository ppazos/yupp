<?php
/*
 * Created on 23/03/2008
 * lapagina.view.php
 */

$m = Model::getInstance();

?>

<html>
   <head>
      <style>
      
      table {
         border: 2px solid #000080;
         /* spacing: 0px; */
         border-collapse: separate;
         border-spacing: 0px;
      }
      
      th {
         border: 1px solid #000080;
         padding: 5px;
         background-color: #000080;
         color: #fff;
      }
      
      td {
         border: 1px solid #69c;
         padding: 5px;
      }
      
      </style>
   </head>
   <body>
      
      <h1>Edit</h1>
      
      <div align="center"><?php echo $m->flash('message'); ?></div>
      
      <!--
      DE ESTA PAGINA TENDRIA QUE VER EL TEMA DEL BINDINDG CON EL MODELO AL SUBMITEAR.<br/><br/>
      
      TODO: Accion para submitear el form. Podria tener algun tipo de controller estandar que
      haga acciones CRUD, para esto le tengo que pasar el nombre de la clase!.<br/><br/>
      -->
      
      <?php echo DisplayHelper::errors( $m->get('object') ); ?>
      
      <form action="save" method="get"><!-- PONERLE POST LUEGO, ahora esta get para debuguear nomas. -->
      
        <input type="hidden" name="id"    value="<?php echo $m->get('object')->aGet('id'); ?>" />
        <input type="hidden" name="class" value="<?php echo $m->get('object')->aGet('class'); ?>" />
      
        <?php echo DisplayHelper::model( $m->get('object'), "edit" ); ?><br/>
      
        <input type="submit" value="Save" />
        <a href="show?class=<?php echo $m->get('object')->aGet('class') ?>&id=<?php echo $m->get('object')->aGet('id') ?>">Cancel</a>
      
      </form>
      
   </body>
</html>