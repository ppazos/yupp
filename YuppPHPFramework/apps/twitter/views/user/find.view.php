<?php

$m = Model::getInstance();

$users = $m->get('users');

YuppLoader::load('apps.twitter.helpers', 'THelpers');

?>
<html>
  <layout name="twitter" />
  <head>
    <style type="text/css">
      body {
        padding: 10px;
      }
      form {
        padding: 10px;
      }
      .user-result {
        width: 150px;
        display: inline-block;
        align: left;
        margin-bottom: 5px;
      }
      .user-result img {
        vertical-align: middle;
        margin-right: 5px;
      }
    </style>
  </head>
  <body>
    <div class="well">
      <legend>Search result:</legend>
        <!-- search result -->
        <?php
        foreach ($users as $user)
        {
           echo '<div class="user-result">';
           // TODO: template para mostrar cada usuario 
           THelpers::gravatar(40, $user);
           
           echo h('link', array('action'=>'timeline',
                                'id'=>$user->getId(),
                                'body'=>$user->getName())), (($user->getId()==TUser::getLogged()->getId())?' (you)':'');
           echo '</div>';
        }
        ?>
    </div>
  </body>
</html>