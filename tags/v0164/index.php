<?php

/**
 * Created on 22/03/2008
 * index.php
 * 
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
session_start();

include_once ('core/core.YuppSession.class.php');
include_once ('core/core.YuppLoader.class.php');

// Para handlear WARNINGS y tirar exceptions.
// E_ALL, E_WARNING, E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT

set_error_handler("my_warning_handler", E_ALL);

function my_warning_handler($errno, $errstr, $errfile, $errline, $errcontext) {
   throw new Exception( $errstr );
}

YuppLoader :: load("core.config", "YuppConfig");
YuppLoader :: load("core.config", "YuppConventions");

YuppLoader :: load("core.basic", "String");

YuppLoader :: load("core.support", "I18nMessage");
YuppLoader :: load("core.support", "YuppContext");

YuppLoader :: load("core.web", "UrlProcessing");
YuppLoader :: load("core.web", "PageHistory");
YuppLoader :: load("core.web", "RequestManager");

// WebFlow
YuppLoader :: load("core.mvc.webflow", "CurrentFlows");
YuppLoader :: load("core.mvc.webflow", "WebFlow");
YuppLoader :: load("core.mvc.webflow", "State");
YuppLoader :: load("core.mvc.webflow", "Transition");

YuppLoader :: load("core.mvc", "YuppController"); // FIXME: No se si es necesario cargarlo xq no se usa directamente.
YuppLoader :: load("core.mvc", "Helpers");
YuppLoader :: load("core.mvc", "DisplayHelper");
YuppLoader :: load("core.mvc", "ViewCommand");
YuppLoader :: load("core.mvc", "Model");

// DBG
YuppLoader :: load("core", "FileSystem");
// /DBG

YuppLoader :: load("core.utils", "Logger");

// La DB a incluir ahora se resuelve en DAL.
//YuppLoader::load( "core.db",         "DatabaseMySQL" );
//YuppLoader::load( "core.db",         "DatabaseSQLite" ); // test, deberia haber un cargador dependiendo de la config del dbms.
YuppLoader :: load("core.db", "Datatypes");
YuppLoader :: load("core.db", "DAL");

// Hay dependencia mutua entre AH, PO y PM...
YuppLoader :: load("core.persistent", "ArtifactHolder");
YuppLoader :: load("core.persistent", "PersistentManager");
YuppLoader :: load("core.persistent", "PersistentObject");

// TEST
YuppLoader :: load("core.routing", "Filter");
YuppLoader :: load("core.routing", "Mapping");
//YuppLoader :: load("core.routing", "ControllerFilter"); // before y after filters
YuppLoader :: load("core.routing", "YuppControllerFilter"); // FIXME: prueba!
YuppLoader :: load("core.routing", "Executer");

YuppLoader :: load("core.utils", "YuppStats");

// ============================================================
// Configuro logger para que no muestre mensajes:
// Comentar esta linea para ver los logs.
Logger::getInstance()->off(); 
// ============================================================

// Carga clases del modelo.
YuppLoader :: loadModel();

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