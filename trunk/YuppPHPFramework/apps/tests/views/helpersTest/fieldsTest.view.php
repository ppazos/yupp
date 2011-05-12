<?php

YuppLoader :: load('core.mvc', 'DisplayHelper');

$m = Model::getInstance();

?>
<html>
  <head>
    <title>Helpers Test: Url Test</title>
  </head>
  <body>
    <h1>Helpers Test: Form Fields Test</h1>
   
    Este test muestra el uso de los helpers para generar campos individuales para formularios.
    Brinda una alternativa a YuppForm.<br/><br/>
   
    <div style="width: 700px; overflow: auto; padding:10px; padding-right:10px; background-color: #ffff80; border: 1px dashed #000" id="content_div">
    
      <form method="post" enctype="multipart/form-data">
        Nombre:   <?php echo DisplayHelper::text('nombre', 'Pablo'); ?><br/><br/>
        Texto:   <?php echo DisplayHelper::bigtext('texto', 'Un texto largo...', array('class'=>'miclase')); ?><br/><br/>
        Clave:    <?php echo DisplayHelper::password('clave'); ?><br/><br/>
        Una de dos:<br/>
          a. <?php echo DisplayHelper::radio('opcion', 'uno'); ?><br/>
          b. <?php echo DisplayHelper::radio('opcion', 'dos'); ?><br/><br/>
        Select:   <?php echo DisplayHelper::select('nombreselect', array('pri'=>'primera','seg'=>'segunda','ter'=>'tercera'), $m->get('nombreselect')); ?><br/><br/>
        Si o No:  <?php echo DisplayHelper::check('siono', true); ?><br/><br/>
        Fecha:    <?php echo DisplayHelper::date('fecha', array('y'=>1981, 'm'=>6)); ?><br/><br/>
        Archivo:  <?php echo DisplayHelper::file('archivo'); ?><br/><br/>
        HTML:     <?php DisplayHelper::html('html'); ?><br/><br/>
        Calendar: <?php DisplayHelper::calendar('calendar'); ?><br/><br/>
        Escondido: <?php echo DisplayHelper::hidden('sshhh', 'unvalor'); ?><br/><br/>
                  <?php echo DisplayHelper::submit('doit', 'Enviar'); ?>
      </form>
      
    </div>
    
    <style>
      .yui-calcontainer {
         float: none;
         diplay: inline-block;
         width: 185px;
      }
    </style>
    
    <div style="width: 700px; height: auto; padding:10px; padding-right:10px; background-color: #8080ff; border: 1px dashed #000" id="content_div">
      <?php $model = $m->getAll(); ?>
      <?php foreach ($model as $k=>$v) : ?>
        <pre><?php echo $k; ?>: <?php print_r($v); ?></pre>
      <?php endforeach; ?>
    </div>
    
    <br/>
    
    <?php echo h("link", array("action" => "index",
                               "body"   => "volver") ); ?>
    
  </body>
</html>