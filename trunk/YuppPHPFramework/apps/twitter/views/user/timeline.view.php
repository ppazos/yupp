<?php

YuppLoader::load('core.mvc', 'DisplayHelper');
YuppLoader::loadScript('apps.twitter', 'Messages');

$m = Model::getInstance();

$messages = $m->get('messages');
$user = $m->get('user'); // usuario al que pertenece la timeline

?>
<html>
  <layout name="twitter" />
  <head>
    <style type="text/css">
      #charNum {
        width: 30px;
        display: inline-block;
        text-align: right;
      }
      .message {
        margin-top: 5px;
      }
      .message img {
        vertical-align: middle;
        margin-right: 5px;
      }
    </style>
    <?php echo h('js',  array('app'=>'twitter', 'name'=>'jquery-1.7.1.min')); ?>
    <script type="text/javascript">
    // FIXME: igual sigue escribiendo luego de los 160 chars (je twitter tambien asi que no es un problema)
    // http://that-matt.com/2010/04/updated-textarea-maxlength-with-jquery-plugin/
    // http://www.hscripts.com/scripts/JavaScript/character-count.php
    function count(that)
    {
      var val = 160 - that.value.length;
      if (val>=0)
      {
        $('#charNum').text(val);
       
        if (val < 15)
        {
           console.log('<15');
           $('#charNum').css('color', '#b94a48');
        }
        else if (val < 40)
        {
           console.log('<40');
           $('#charNum').css('color', '#C09853');
        }
        else
        {
           console.log('>=30');
           $('#charNum').css('color', '#000'); 
        }
      }
    }
    </script>
  </head>
  <body>
    <div class="row-fluid">
      <div class="span4">
          <div class="row-fluid">
            <div class="span12"><!-- ponerle 12 es como ponerle 100%, si le pongo 6 queda de la mitad del width -->
              <!-- follow/unfollow this user if it's not me, twitt if it's me -->
              <?php
                $logged = TUser::getLogged();
                if ($logged->getId() != $user->getId()) :
                   Helpers::template(array('controller'=>'templates', 'name'=>'follow-unfollow', 'args'=>array('user'=>$user, 'logged'=>$logged)));
                else :
                   Helpers::template(array('controller'=>'templates', 'name'=>'twitt', 'args'=>array('m'=>$m)));
                endif;
              ?>
            </div>
          </div>
          <div class="row-fluid">
            <div class="span12">
              <!-- users followed by $user -->
              <div id="following" class="well">
                <legend><?php echo $user->getName(), msg('twitter.user.timeline.isFollowing'); ?>:</legend>
                <?php foreach ($user->getFollowing() as $followingUser) : ?>
                  <?php echo h('link', array('action'=>'timeline', 'id'=>$followingUser->getId(), 'body'=>$followingUser->getName())), '<br />'; ?>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
      </div>
      <div class="span8">
      <!-- timeline -->
        <div id="timeline" class="well">
          <legend><?php echo $user->getName(); ?> timeline:</legend>
          <?php
            foreach ($messages as $message)
            {
               Helpers::template(array('controller'=>'templates', 'name'=>'message', 'args'=>array('message'=>$message)));
            }
          ?>
        </div>
      </div>
    </div>
  </body>
</html>