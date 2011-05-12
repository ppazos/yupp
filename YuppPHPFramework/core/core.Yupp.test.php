<?php
include_once('core.YuppSession.class.php');
include_once('./basic/core.basic.String.class.php');
include_once('./config/core.config.YuppConfig.class.php');
include_once('./support/core.support.YuppContext.class.php');
include_once('./config/core.config.PackageNames.class.php');
include_once('core.Yupp.class.php');
 
$yupp = new Yupp();

print_r( $yupp->getMode() );
echo "<br/>";

chdir('../');

print_r($yupp->getAppNames());
echo "<br/>";
?>
