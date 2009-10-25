<?php

$m = Model::getInstance();

YuppLoader::loadScript("components.blog", "Messages");
YuppLoader::load("core.mvc.form","YuppForm2");

?>

<html>
   <layout name="blog" />
   <head>
      <?php echo h("css", array("name" => "niftyCorners") ); ?>
      <?php echo h("js",  array("name" => "niftycube") ); ?>

      <script type="text/javascript">
      window.onload=function(){
         Nifty("div.flash","transparent");
         Nifty("ul.postnav a","transparent");
         //Nifty("ul.postnav div.locale_chooser","transparent");
      }
      </script>

      <?php echo h("css",  array("name" => "main") ); ?>

   </head>
   <body>
      <h1><?php echo DisplayHelper::message("blog.entrada.list.title"); ?></h1>
      
      <?php if ($m->flash('message')) { ?>
      <div class="flash"><?php echo $m->flash('message'); ?></div>
      <?php } ?>
      
      <ul class="postnav">
        <li><?php echo h("link", array("action" => "create",
                                       "body" => DisplayHelper::message("blog.entrada.list.action.addEntry")) ); ?></li>
      </ul>
      <br/><br/>

      <?php
      foreach ( $m->get('list') as $obj )
      {
        //echo $obj->toJSON(); // Prueba de JSON.
         
        Helpers::template( array("controller" => "entradaBlog",
                                 "name"       => "details",
                                 "args"       => array("entrada" => $obj)
                            ) );
      }
      ?>
      
      <?php echo h('pager', array('offset'=>$m->get('offset'), 'max'=>$m->get('max'), 'count'=>$m->get('count'))); ?>

<?php

/* prueba de envio de archivos
      
      $f = new YuppForm2( array('component'=>'blog', 'controller'=>'entradaBlog', 'action'=>'list') );

$f->add( YuppForm2::text( array('name'=>"name",      'value'=>'carlos',      'label'=>"Nombre") ) )
  ->add( YuppForm2::text( array('name'=>"email",     'value'=>'ppp@ppp.com', 'label'=>"Email" ) ) )
  ->add( YuppForm2::password( array('name'=>"pass",  'value'=>'abc123',      'label'=>"Clave" ) ) )
  ->add( YuppForm2::date( array('name'=>"birthdate", 'value_year'=>1980,     'label'=>"Fecha de nacimiento") ) )
  ->add( YuppForm2::select(
                            array(
                              "name"    => "usertype", 
                              "value"   => "ad", 
                              "label"   => "Tipo",
                              "options" => array(
                                              'us'=>'usuario',
                                              'ed'=>'editor',
                                              'ad'=>'admin',
                                              'pe'=>'pendiente')
                            )
                         )
                      )
  ->add( YuppForm2::select( // TODO: si es multiple, poder decirle varios valores posibles seleccionados.
                            array(
                              "name"    => "nombres[]", 
                              "value"   => "m", 
                              "label"   => "Nombres",
                              "options" => array(
                                              'p'=>'Pablo',
                                              'm'=>'Miguel',
                                              'a'=>'Andres',
                                              'c'=>'Carlos'),
                              "size" => 10,
                              "multiple" => 'true'
                            )
                         )
                      )
  ->add( YuppForm2::radio( array('name'=>"radio_btn_0",   'value'=>1,  'label'=>"opcion 1") ) )
  ->add( YuppForm2::radio( array('name'=>"radio_btn_0",   'value'=>2,  'label'=>"opcion 2") ) )
  ->add( YuppForm2::radio( array('name'=>"radio_btn_0",   'value'=>3,  'label'=>"opcion 3") ) )
  ->add( YuppForm2::check( array('name'=>"esMayor", 'value'=>'true',  'label'=>"check 1") ) )
  ->add( YuppForm2::file( array('name'=>"archivo", 'label'=>"Archivo") ) )
  ->add( YuppForm2::submit( array('name'  =>"doit", 'label'=>"Crear")) )
  ->add( YuppForm2::submit( array('action'=>"list", 'label'=>"Cancelar")) );
   

YuppFormDisplay2::displayForm( $f );

*/

?>
   
   </body>
</html>