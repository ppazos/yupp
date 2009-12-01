<?php

$m = Model::getInstance();

YuppLoader::loadScript("components.blog", "Messages");

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
         *width: 150px;
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
      <?php echo h("js",  array("name" => "prototype-1.6.0.2") ); ?>
      
      <script type="text/javascript">
      window.onload=function(){
         Nifty("div.flash","transparent");
         Nifty("ul.postnav a","transparent");
      }
      </script>
   
      <?php echo h("css", array("name" => "main") ); ?>
   
   </head>
   <body>
      
      <h1><?php echo DisplayHelper::message("blog.entrada.show.title"); ?></h1>
      
      <?php if ($m->flash('message')) { ?>
        <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php } ?>
      
      <?php $obj = $m->get('object'); ?>
      
      <ul class="postnav">
        <li><?php echo Helpers::link( array("controller" => "entradaBlog",
                                            "action"     => "list",
                                            "body"       => DisplayHelper::message("blog.entrada.action.list")) ); ?></li>
        <li><?php echo Helpers::link( array("controller" => "comentario",
                                            "action"     => "create",
                                            "id"         => $obj->getId(),
                                            "body"       => DisplayHelper::message("blog.entrada.action.addComment")) ); ?></li>
        <li><?php echo Helpers::link( array("action"     => "edit",
                                            "id"         => $obj->getId(),
                                            "body"       => DisplayHelper::message("blog.entrada.action.edit")) ); ?></li>
        <li><?php echo Helpers::link( array("action"     => "delete",
                                            "id"         => $obj->getId(),
                                            "body"       => DisplayHelper::message("blog.entrada.action.delete")) ); ?></li>
      </ul>
      <br/><br/>
      
      <?php echo Helpers::template( array("controller" => "entradaBlog",
                                          "name"       => "details",
                                          "args"       => array("entrada" => $obj)
                                         ) ); ?>
      
      <?php $i = 1; ?>
      <?php foreach ( $obj->getComentarios() as $com ) : ?>
         <div class="entrada">
           <div class="top">
             <div class="left">
               <?php echo DisplayHelper::message("blog.entrada.label.comment"); ?> # <?php echo $i; ?>
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