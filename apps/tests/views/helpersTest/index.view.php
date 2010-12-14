<?php

$m = Model::getInstance();

?>

<html>
  <head>
    <title>Helpers Test: Index</title>
  </head>
  <body>
    <h1>Helpers Test: Index</h1>
    
    Haz clic para ver la demo.<br/>
    
    <?php $tests = $m->get('tests'); ?>
    <ul>
      <?php foreach ($tests as $test) : ?>
        <li><?php echo h('link', array('action' => $test, 'body' => $test)); ?></li>
      <?php endforeach; ?>
    </ul>    
    
  </body>
</html>