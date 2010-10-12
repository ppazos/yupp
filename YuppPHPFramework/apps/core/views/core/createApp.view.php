<?php

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
      form {
         border: 1px solid #ccc;
         background-color: #efefef;
         padding: 10px;
      }
      label {
         display: inline-block;
         width: 210px;
         vertical-align: top;
         text-align: right;
      }
      input[type=text], textarea {
          width: 200px;
      }
    </style>
  </head>
  <body>
    <h1>Nueva aplicacion</h1>
      
    <div align="center"><?php echo $m->flash('message'); ?></div>
      
    <form action="<?php echo h('url', array('action'=>'createApp')); ?>" method="post">
        
        <label>Nombre de la aplicaci&oacute;n:</label>
        <input type="text" name="name" value="" />
        <br/>
        
        <label>Descripcion:</label>
        <textarea name="description"></textarea>
        <br/>
        
        <label>Lenguages:</label>
        <input type="text" name="langs" value="" /> (ejemplo: es en it pt)
        <br/><br/>
        
        <label>Nombre del controlador principal:</label>
        <input type="text" name="controller" value="" />
        <br/><br/>
      
        <input type="submit" name="doit" value="Crear" />
        <a href="<?php echo h('url', array('action'=>'index')); ?>">Cancelar</a>
      
    </form>
  </body>
</html>