<!-- search for users -->
<?php
YuppLoader::load('core.mvc', 'DisplayHelper');
YuppLoader::loadScript('apps.twitter', 'Messages');
?>
<form class="form-search" method="post" action="<?php echo h('url', array('action'=>'find')); ?>">
  <div class="icon-search icon-black"></div>
  <input type="text" class="input-medium search-query" name="q" value="<?php echo $m->get('q'); ?>" />
  <button type="submit" class="btn"><?php echo msg('twitter.user.search.label'); ?></button>
</form>