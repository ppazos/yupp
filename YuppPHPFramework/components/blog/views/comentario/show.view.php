<?php
// Esta view no se usa, y si se usara hay que corregirle los links usando helpers.

$m = Model::getInstance();

?>

<html>
   <head>
      <style type="text/css">
      ul.postnav, ul.postnav li {
         margin: 0px;
         padding: 0px;
         list-style-type:none;
      }
      ul.postnav li { 
         float:left;
         margin-right: 5px;
      }
      ul.postnav a {
         display:block;
         padding: 5px 10px 5px 10px;
         background: #C7FF5A;
         color: #666;
         text-decoration:none;
         text-align:center;
      }
      ul.postnav a:hover { background: #A8E52F; color:#FFF; }
      </style>
      
      
      <?php echo h("css", array("name" => "niftyCorners") ); ?>
      <?php echo h("js",  array("name" => "niftycube") ); ?>
      
      
      <script type="text/javascript">
      window.onload=function(){
      Nifty("ul.postnav a","transparent");
      }
      </script>
   
      <style>
      .entrada {
         width: 400px;
         border: 1px solid #999999;
         background-color: #f7f7f7;
         padding-bottom: 40px;
         *padding-bottom: 0px;
         margin-bottom: 5px;
      }
      
      .top {
         font-weight: bold;
      }
      
      .top .left {
         background-color: #eeeeee;
         float: left;
         width: 180px;
         *width: 199px;
         padding: 10px;
      }
      
      .top .right {
         background-color: #eeeeee;
         float: left;
         text-align:right;
         width: 180px;
         *width: 199px;
         padding: 10px;
      }
      
      .content {
          padding: 10px;
         padding-top: 25px;
         *padding-top: 0px;
      }
      
      .bottom .left {
         background-color: #eeeeee;
         float: left;
         width: 180px;
         *width: 199px;
         padding: 10px;
      }
      
      .bottom .right {
         background-color: #eeeeee;
         float: left;
         text-align:right;
         width: 180px;
         *width: 199px;
         padding: 10px;
      }
      </style>
   </head>
   <body>
      
      <h1>Detalle de entrada</h1>
      
      <div align="center"><?php echo $m->flash('message'); ?></div>
      
      <?php $obj = $m->get('object'); ?>
      
      <?php $id = $obj->aGet('id'); ?>
      <ul class="postnav">
        <li><a href="list">Listar entradas</a></li>
        <li><a href="comentario/create?id=<?php echo $id; ?>">Agregar comentario</a></li>
        <li><a href="edit?id=<?php echo $id ?>">Editar entrada</a></li>
        <li><a href="delete?id=<?php echo $id ?>">Eliminar entrada</a></li>
      </ul>
      
      <br/><br/>
      
      <div class="entrada">
        <div class="top">
          <div class="left">
            (<?php echo $obj->getId(); ?>)
            <a href="show?id=<?php echo $obj->getId(); ?>">
              <?php echo $obj->getTitulo(); ?>
            </a>
          </div>
          <div class="right">
            <?php echo $obj->getFecha(); ?>
          </div>
        </div>
        <br/>
        <div class="content">
          <?php echo $obj->getTexto(); ?>
        </div>
        <div class="bottom">
          <div class="left">
            <a href="comentario/create?id=<?php echo $obj->getId(); ?>">
              Agregar comentario
            </a>
          </div>
          <div class="right">
            Comentarios: <?php echo count( $obj->getComentarios() ); ?>
          </div>
        </div>
      </div>
      
      <?php $i = 1; ?>
      <?php foreach ( $obj->getComentarios() as $com ) : ?>
         <div class="entrada">
           <div class="top">
             <div class="left">
               Comentario # <?php echo $i; ?>
             </div>
             <div class="right">
               <?php echo $com->getFecha(); ?>
             </div>
           </div>
           <br/>
           <div class="content">
             <?php echo $com->getTexto(); ?>
           </div>
         </div>
         <?php $i++; ?>
      <?php endforeach; ?>
      
   </body>
</html>