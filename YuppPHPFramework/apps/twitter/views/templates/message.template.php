<div class="message">
<?php YuppLoader::load('apps.twitter.helpers', 'THelpers'); ?>

<?php THelpers::gravatar(40, $message->getCreatedBy()); ?>

<?php echo h('link', array('controller' => 'user',
                           'action'     => 'timeline',
                           'body'       => $message->getCreatedBy()->getName(),
                           'id'         => $message->getCreatedBy()->getId()) ); ?>

<?php echo $message->getText(); ?>

<?php echo $message->getCreatedOn(); ?>
</div>