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
      <h1>Create user flow: Fill User and Pass</h1>
      
      <?php if ($m->flash('message')) : ?>
         <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php endif; ?>
      
      <?php if ( ($u = $m->get('usuario')) !== NULL && $u->hasErrors() ) : ?>
         <?php echo DisplayHelper::errors( $u ); ?>
      <?php endif; ?>
      
      <div class="login">
         <form action="<?php echo Helpers::url( array("action"=>"createUser", "event"=>"userIdFilled") ); ?>" method="post">
         
           Email:<br />
           <input type="text" name="email" value="<?php echo $m->get('email'); ?>" />
           <br/><br/>
         
           Pass:<br />
           <input type="text" name="pass" value="<?php echo $m->get('pass'); ?>" />
           <br/><br/>
         
           <input type="submit" name="doit" value="Next" />
         </form>
      </div>
      
   </body>
</html>