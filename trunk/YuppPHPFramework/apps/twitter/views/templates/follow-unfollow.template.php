<form class="form-horizontal" action="<?php echo h('url', array('controller'=>'user', 'action'=>'follow')) ?>">
  <input type="hidden" name="id" value="<?php echo $user->getId();?>" />
  <?php if ($logged->followingContains($user)) : ?>
    <input type="submit" value="Unfollow" name="unfollow" class="btn btn-warning" />
  <?php else : ?>
    <input type="submit" value="Follow" name="follow" class="btn btn-primary" />
  <?php endif; ?>
  <?php echo $user->getName(); ?>
</form>