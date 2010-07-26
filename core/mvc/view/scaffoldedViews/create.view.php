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
      
      <h1>Create</h1>
      
      <div align="center"><?php echo $m->flash('message'); ?></div>
      
      <!--
      DE ESTA PAGINA TENDRIA QUE VER EL TEMA DEL BINDINDG CON EL MODELO AL SUBMITEAR.<br/><br/>
      
      TODO: Accion para submitear el form. Podria tener algun tipo de controller estandar que
      haga acciones CRUD, para esto le tengo que pasar el nombre de la clase!.<br/><br/>
      -->
      
      <?php echo DisplayHelper::errors( $m->get('object') ); ?>
      
      <form action="create" method="get">
      
        <input type="hidden" name="class" value="<?php echo $m->get('object')->aGet('class'); ?>" />
      
        <?php echo DisplayHelper::model( $m->get('object'), "edit" ); ?><br/>
      
        <input type="submit" name="doit" value="Create" />
      
        <a href="list?class=<?php echo $m->get('object')->aGet('class') ?>">Cancel</a>
      
      </form>
   </body>
</html>