<?php
/*
 * Created on 28/02/2008
 * utils.YuppLoader.test.php
 */

session_start();

include_once("core.YuppLoader.class.php"); // Como esta clase se guarda en session la tengo que incluir antes de abrir la session.

// Si usara serialize/unserialize no seria necesario declarar antes de la session las clases !!!!
// OKa funka fenomeno....


if (class_exists(YuppSession)) echo "OK<br/>";
else echo "SESSION ADMIN NO EXISTE...<br/>";

//$cl = YuppLoader::getInstance();
//$cl->load( "component1.model", "Factura" );
YuppLoader::load( "component1.model", "Factura" );


echo "<pre>";
//print_r( $cl->getLoadedClasses() );
print_r( YuppLoader::getLoadedClasses() );

print_r( $_SESSION );

echo "</pre>";

?>
