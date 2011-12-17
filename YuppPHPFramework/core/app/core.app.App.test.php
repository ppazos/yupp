<?php
/*
 * Created on 30/06/2010
 * core.App.test.php
 */

include_once('./basic/core.basic.String.class.php');
include_once('./config/core.config.YuppConventions.class.php');
include_once('./support/core.support.YuppContext.class.php');
include_once('./mvc/core.mvc.ViewCommand.class.php');
include_once('./mvc/core.mvc.YuppController.class.php');
include_once('core.YuppSession.class.php');
include_once('core.App.class.php');

chdir('../');

include_once('core.YuppLoader.class.php');

/*
$app = new App('georef_stub');

print_r( $app->getModel() );

$comm = $app->execAction('geoRef','filtroCalles', new ArrayObject(array('dpto'=>'Artigas','calle'=>'Rivera')));

print_r($comm);
*/

$app = new App('blog');

print_r( $app->getModel() );

print_r( $app->getDescriptor() );

echo '<h1>Intento crear app carlox</h1>';
App::create('carlox');

?>