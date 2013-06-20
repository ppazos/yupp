<!-- new message -->
<?php
YuppLoader::load('core.mvc', 'DisplayHelper');
YuppLoader::loadScript('apps.twitter', 'Messages');
?>
<form class="well" action="<?php echo h('url', array('controller'=>'message', 'action'=>'sendMessage')) ?>">
  <legend><?php echo msg('twitter.user.twitt.title'); ?></legend>
  <div class="control-group">
    <div class="controls">
      <textarea name="text" style="width:100%;" rows="3" placeholder="<?php echo msg('twitter.user.twitt.write'); ?>" onkeyup="count(this);"></textarea>
    </div>
  </div>
  <div style="text-align: right;">
    <span id="charNum">160</span>
    <input type="submit" value="Send" name="doit" class="btn btn-primary" />
  </div>
</form>