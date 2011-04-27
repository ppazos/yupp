<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
session_start();

include_once ('core/core.YuppSession.class.php');

// Necesaria para YuppLoader, si se declara este include adentro de YuppLoader.php, no lo toma.
include_once ('core/config/core.config.FileNames.class.php');
include_once ('core/core.YuppLoader.class.php');

// Para handlear WARNINGS y tirar exceptions.
// E_ALL, E_WARNING, E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT

set_error_handler("my_warning_handler"); //, E_ALL); // Si le saco el segundo parametro me muestra warnings.

function my_warning_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
//   echo "<hr>Warning Failed:
//     ErrNo '$errno'<br />
//     Str '$errstr'<br />
//     File '$errfile'<br />
//     Line '$errline'<br />
//     Context ";
//     Logger::struct($errcontext);
//   echo "<br /><hr />";
    
   throw new Exception( $errstr );
}


/*
DateTime date_create ( [string $time [, DateTimeZone $timezone]] )
DateTimeZone timezone_open ( string $timezone )
date_timezone_set ( DateTime $object, DateTimeZone $timezone );
date_timezone_set( date_create('now'), timezone_open('America/Montevideo') );
*/

// ===========================================================================
// FIX: para llamadas a date() para PHP 5.2.10
//bool date_default_timezone_set ( string $timezone_identifier )
// TODO: que la timezone se especifique en la configuracion
date_default_timezone_set( 'America/Montevideo' );

// ===========================================================================

YuppLoader :: load('core.web', 'RequestManager');
YuppLoader :: load('core.mvc', 'YuppController'); // Se usa en cada controlador que lo extiende.
YuppLoader :: load('core.mvc', 'Helpers'); // Usado para acceder a la funcion h()
YuppLoader :: load('core.utils', 'Logger');


// ============================================================
// TODO: mover a la configuracion
// Configuro logger para que no muestre mensajes:
// Comentar esta linea para ver los logs.
Logger::getInstance()->off(); 
//Logger::getInstance()->setFile("logger.txt");
// ============================================================


//[SCRIPT_NAME] => /Persistent/index.php
// Dejo algunas variables globales utiles:

/*
 * Directorio base de la aplicacion, donde se encuentra EntryPoint,
 * desde el cual se pueden calcular URLs relativas y absolutisarlas
 * concatenandoles el base_dir. Necesario para el helper de links.
 */
$_base_dir = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));


// Hace el request y catchea por posibles errores.
try
{
   RequestManager :: doRequest();
}
catch (Exception $e)
{
   // FIXME: mostrar la vista de error 500
   echo '<html><body>';
     echo '<h1>Ha ocurrido un error!</h1>'; // TODO: i18n
     echo '<div style="border:1px solid #333; padding:10px; width:800px;">';
     
       echo '<div style="border:1px solid #333; background-color:#ffffaa; overflow:auto; padding:5px; margin-bottom:2px;">';
         echo 'Mensaje:'; // TODO: i18n
       echo '</div>';
       echo '<div style="border:1px solid #333; background-color:#ffff80; overflow:auto; padding:10px;">';
         echo $e->getMessage() . " [" . $e->getFile()." : ".$e->getLine() . "]";
       echo '</div>';
       
       //print_r( $e->getTrace() );
       echo '<div style="border:1px solid #333; background-color:#ffaaaa; overflow:auto; padding:5px; margin-bottom:2px; margin-top:10px;">';
         echo 'Traza:'; // TODO: i18n
       echo '</div>';
       echo '<div style="border:1px solid #333; background-color:#ff8080; overflow:auto; padding:10px;"><pre>';
         echo $e->getTraceAsString();
       echo '</pre></div>';
     echo '</div>';
   echo '</body></html>';
   exit();
}

?>