<?php

YuppLoader::load('core.mvc', 'DisplayHelper');
YuppLoader::loadScript('apps.twitter', 'Messages');

$m = Model::getInstance();
$user = $m->get('user'); // viene si hubo un error

$hasErrorName = $user != NULL && $user->getErrors()->hasFieldErrors('name');
$hasErrorEmail = $user != NULL && $user->getErrors()->hasFieldErrors('email');
$hasErrorUsername = $user != NULL && $user->getErrors()->hasFieldErrors('username');
$hasErrorPassword = $user != NULL && $user->getErrors()->hasFieldErrors('password');

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
        width: 500px;
      }
      .form-actions {
        margin-bottom: 0px;
        padding-bottom: 0px;
      }
    </style>
  </head>
  <body>
    <form class="form-horizontal well" action="<?php echo h('url', array('action'=>'register')) ?>">
      <fieldset>
        <legend><?php echo msg('twitter.user.register.title'); ?></legend>
        <div class="control-group<?php echo (($hasErrorName)?' error':''); ?>">
          <label for="name"><?php echo msg('twitter.user.register.name'); ?></label>
          <div class="controls">
            <input type="text" class="input-xlarge" name="name" id="name">
            <span class="help-block"><?php echo DisplayHelper::fieldErrors($user, 'name'); ?></span>
          </div>
        </div>
        <div class="control-group<?php echo (($hasErrorEmail)?' error':''); ?>">
          <label for="email"><?php echo msg('twitter.user.register.email'); ?></label>
          <div class="controls input-prepend">
            <span class="add-on">@</span>
            <input type="text" class="input-xlarge" name="email" id="email">
            <span class="help-block"><?php echo DisplayHelper::fieldErrors($user, 'email'); ?></span>
          </div>
        </div>
        <div class="control-group<?php echo (($hasErrorUsername)?' error':''); ?>">
          <label for="username"><?php echo msg('twitter.user.register.username'); ?></label>
          <div class="controls">
            <input type="text" class="input-xlarge" name="username" id="username">
            <span class="help-block"><?php echo DisplayHelper::fieldErrors($user, 'username'); ?></span>
          </div>
        </div>
        <div class="control-group<?php echo (($hasErrorPassword)?' error':''); ?>">
          <label for="password"><?php echo msg('twitter.user.register.password'); ?></label>
          <div class="controls">
            <input type="password" class="input-xlarge" name="password" id="password">
            <span class="help-block"><?php echo DisplayHelper::fieldErrors($user, 'password'); ?></span>
          </div>
        </div>
      </fieldset>
      <div class="form-actions">
        <input type="submit" value="Register" name="doit" class="btn btn-primary" />
        <input type="submit" value="Cancel" name="_action_login" class="btn" />
      </div>
    </form>
  </body>
</html>