<span style="padding:10px; display: inline-block; vertical-align: middle;">
  <?php /*echo h('locale_chooser');*/ ?>
  <?php if (($u = TUser::getLogged()) !== NULL) : ?>
    <?php echo msg('twitter.user.welcome'); ?> 
    <?php echo h('link', array('controller' => 'user',
                               'action'     => 'timeline',
                               'body'       => $u->getName()) ); ?>
    <?php THelpers::gravatar(20, $u); ?>
    /
    <?php echo h('link', array('controller' => 'user',
                               'action'     => 'logout',
                               'body'       => 'logout') ); ?>
  <?php endif; ?>
</span>