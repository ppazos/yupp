<?php

$m = Model::getInstance();

YuppLoader::load('core.mvc', 'DisplayHelper');
YuppLoader::loadScript('apps.twitter', 'Messages');

?>
<html>
  <head>
    <?php echo h('css', array('app'=>'twitter', 'name'=>'twitter.bootstrap')); ?>
    <style type="text/css">
      body {
        padding: 10px;
      }
      form {
         padding: 10px;
         width: 430px;
      }
      .alert {
        margin: 10px 0;
      }
      .form-actions {
        margin-bottom: 0px;
        padding-bottom: 0px;
      }
      .well .top-action {
        text-align:right;
      }
    </style>
  </head>
  <body>
    
    <?php if ($m->flash('error') != NULL) : ?>
      <div class="alert alert-error"><?php echo msg($m->flash('error')); ?></div>
    <?php endif; ?>
     <?php if ($m->flash('message') != NULL) : ?>
      <div class="alert alert-info"><?php echo msg($m->flash('message')); ?></div>
    <?php endif; ?>
    
    <form class="form-horizontal well" action="<?php echo h('url', array('action'=>'login')) ?>" method="post">
    
      <div class="top-action">
        <a href="<?php echo h('url', array('action'=>'register')); ?>" class="btn btn-inverse">Register</a>
      </div>
    
      <legend>Login</legend>
      <div class="control-group">
          <label for="username">username</label>
          <div class="controls">
            <input type="text" class="input-xlarge" name="username" id="username" placeholder="username">
            <!--<span class="help-block">Supporting help text</span>-->
          </div>
      </div>
      <div class="control-group">
          <label for="password">password</label>
          <div class="controls">
            <input type="password" class="input-xlarge" name="password" placeholder="password">
            <!--<span class="help-block">Supporting help text</span>-->
          </div>
      </div>
      <div class="form-actions">
        <input type="submit" value="Login" name="doit" class="btn btn-primary" />
        <input type="submit" value="Cancel" name="doit" class="btn" />
      </div>
    </form>
  </body>
</html>