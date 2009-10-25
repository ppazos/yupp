<?php

$m = Model::getInstance();

YuppLoader::loadScript("components.blog", "Messages");

?>

<html>
   <layout name="blog" />
   <head>
      <?php echo h("css", array("name" => "main") ); ?>
      <style>
      input[type=text] {
      	width: 160px;
      }
      .login {
      	background-color: #eee;
         border: 2px solid #ccc;
         width: 190px;
      }
      </style>
   </head>
   <body>
      <h1><?php echo DisplayHelper::message("blog.usuario.login.title"); ?></h1>
      
      <?php if ($m->flash('message')) : ?>
         <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php endif; ?>
      
      <div class="login">
         <form action="<?php echo Helpers::url( array("action"=>"login") ); ?>" method="post">
         
           <?php echo DisplayHelper::message("blog.usuario.label.email"); ?>:<br />
           <input type="text" name="email" value="<?php echo $m->get('email'); ?>" />
           <br/><br/>
           
           <?php echo DisplayHelper::message("blog.usuario.label.clave"); ?>:<br />
           <input type="text" name="clave" value="<?php echo $m->get('clave'); ?>" />
           <br/><br/>
         
           <input type="submit" name="doit" value="<?php echo DisplayHelper::message("blog.usuario.login.title"); ?>" />
         </form>
      </div>
      
   </body>
</html>