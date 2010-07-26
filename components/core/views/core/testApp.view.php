<?php

$m = Model::getInstance();

$results = $m->get('results');
$app = $m->get('app');

?>
<html>
   <head>
      <style>
      .ERROR {
         border: 1px solid #cc0000;
         padding: 5px;
         background-color: #ffcccc;
         margin-bottom: 2px;
      }
      .EXCEPTION {
         border: 1px solid #cccc00;
         padding: 5px;
         background-color: #ffffcc;
         margin-bottom: 2px;
      }
      .OK {
         border: 1px solid #00cc00;
         padding: 5px;
         background-color: #ccffcc;
         margin-bottom: 2px;
      }
      textarea {
         width: 100%;
         height: 100px;
      }
      </style>
   </head>
   <body>
      <h1>Resultado del testing de <?php echo $app->getName(); ?></h1>
      
      <div align="center"><?php echo $m->flash('message'); ?></div>
      
      <?php foreach ($results as $result) : ?>
        <div class="<?php echo $result['type']; ?>">
          <b><?php echo $result['msg']; ?></b><br/>
          <?php if (!empty($result['trace'])) : ?>
            <textarea><?php echo $result['trace']; ?></textarea>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
   </body>
</html>