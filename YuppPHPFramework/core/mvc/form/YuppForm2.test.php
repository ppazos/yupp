<?php
/*
 * Created on 18/05/2009
 * YuppForm2.test.php
 */

include_once("core.mvc.form.YuppForm2.class.php");
include_once("../core.mvc.Helpers.class.php"); // Lo necesita YuppForm2
include_once("../../support/core.support.YuppContext.class.php"); // Lo necesita Helpers
include_once("../../core.YuppSession.class.php"); // Lo necesita YuppContext
?>

<html>
   <head>
      <style>
         /* Estilo para YuppForm */
         .field_container {
            width: 540px;
            text-align: left;
            display: block;
            padding-top: 10px;
         }
         .field_container .label {
            /*display: inline;*/
            float: left;
            padding-right: 10px;
            vertical-align: top;
         }
         .field_container .field {
            /*display: block;*/
            /*float: left;*/
            /*display: inline;*/
         }
         .field_container .field input {

         }
         .field_container .field input[type=text] {
            width: 400px;
         }
         .field_container .field input[type=submit] {
            width: 100px;
         }
         .field_container .field textarea {
            width: 540px;
            height: 200px;
         }
         .group {
            border: 2px solid #000;
            padding: 5px;
         }
      </style>
   </head>
   <body>
      <h1>Test de YuppForm</h1>
      
<?php
echo '<pre>';
print_r( $_POST );
print_r( $_REQUEST );
print_r( $_GET );
print_r( $_FILES );
echo '</pre>';

//$f = new YuppForm2( array('app'=>'blog', 'controller'=>'user', 'action'=>'login', 'isAjax'=>true) );
$f = new YuppForm2( array('actionUrl'=>'#') );

$group = new YuppFormField2Group("Rangos de edades");
$group->add( YuppForm2::check( array('name'=>"rango1[]", 'value'=>'0..10', 'label'=>"0..10") ) )
      ->add( YuppForm2::check( array('name'=>"rango1[]", 'value'=>'11..20', 'label'=>"11..20") ) )
      ->add( YuppForm2::check( array('name'=>"rango1[]", 'value'=>'21..30', 'label'=>"21..30") ) )
      ->add( YuppForm2::check( array('name'=>"rango1[]", 'value'=>'31..40', 'label'=>"31..40") ) );


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
  ->add( $group )
  ->add( YuppForm2::check( array('name'=>"esMayor", 'value'=>'true',  'label'=>"check 1") ) )
  ->add( YuppForm2::text( array('name'=>"company",  'value'=>'Sun Microsystems',  'label'=>"Institucion") ) )
  ->add( YuppForm2::text( array('name'=>"position", 'value'=>'CEO', 'label'=>"Cargo") ) )
  ->add( YuppForm2::file( array('name'=>"archivo1", 'label'=>"Archivo") ) )
  ->add( YuppForm2::submit( array('name'  =>"doit", 'label'=>"Crear")) )
  ->add( YuppForm2::submit( array('action'=>"list", 'label'=>"Cancelar")) );
  
//print_r( $f );  

YuppFormDisplay2::displayForm( $f );
 
?>

   </body>
</html>
