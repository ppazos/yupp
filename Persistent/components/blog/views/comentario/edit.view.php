<?php

$m = Model::getInstance();

?>

<html>
   <head>
     <style>
     .entrada {
         width: 400px;
         border: 1px solid #999999;
         background-color: #f7f7f7;
         padding-bottom: 40px;
         *padding-bottom: 0px;
         margin-bottom: 5px;
      }
      
      div.entrada {
      	padding-bottom: 0px;
      }
      
      form {
      	
         padding: 10px;
         padding-bottom: 0px;
      }
      
      textarea {
      	
         width: 380px;
         height: 160px;
      }
     </style>
   </head>
   <body>
      
      <h1>Agregar comentario</h1>
      
      <div align="center"><?php echo $m->flash('message'); ?></div>
      
      <?php echo DisplayHelper::errors( $m->get('object') ); ?>
      <div class="entrada">
         <form action="create" method="get">
           
           Comentario:<br/>
           <textarea name="texto"><?php echo $m->get('texto'); ?></textarea>
           <br/><br/>
         
           <input type="submit" name="doit" value="Agregar comentario" />
           
           <!--<a href="entradaBlog/list">Cancel</a>-->
           
           <?php echo Helpers::link( array("controller" => "entradaBlog",
                                           "action" => "list",
                                           "body" => "Cancelar") ); ?>
         
         </form>
      </div>
      
   </body>
</html>