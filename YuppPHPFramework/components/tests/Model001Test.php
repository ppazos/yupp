<?php

// Ejecutar test:
// http://localhost:8081/YuppPHPFramework/components/tests/Model001Test.php

// ===============================================================
// Se incluye esto para poder usar funcionalidades del framework
// ===============================================================

session_start();

chdir('../../'); // setea el dir a la raiz del proyecto

echo getcwd();

include_once ('core/core.YuppSession.class.php');
include_once ('core/core.YuppLoader.class.php');

// Para handlear WARNINGS y tirar exceptions.
// E_ALL, E_WARNING, E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT

set_error_handler("my_warning_handler", E_ALL);

function my_warning_handler($errno, $errstr, $errfile, $errline, $errcontext) {
   
   //print_r ( get_declared_classes () );
   //print_r( $errcontext );
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
YuppLoader :: load("core.routing", "Router");
YuppLoader :: load("core.routing", "YuppControllerFilter"); // FIXME: no deberia ser parte del paquete routing, esta aca solo porque es usada desde el Executer...
YuppLoader :: load("core.routing", "Executer");

YuppLoader :: load("core.utils", "YuppStats");

// ============================================================
// Configuro logger para que no muestre mensajes:
// Comentar esta linea para ver los logs.
//Logger::getInstance()->off();
// ============================================================

// Carga clases del modelo.
//YuppLoader :: loadModel();

// ===============================================================
// / Se incluye esto para poder usar funcionalidades del framework
// ===============================================================


// Manejador de errores de ASSERT para mostrar mensajes de error:

// Active assert and make it quiet
//assert_options(ASSERT_ACTIVE, 1);
//assert_options(ASSERT_WARNING, 1);
//assert_options(ASSERT_QUIET_EVAL, 1);

// Create a handler function
function my_assert_handler($file, $line, $code)
{
	echo "<hr>Assertion Failed:
        File '$file'<br />
        Line '$line'<br />
        Code '$code'<br /><hr />";
}

// Set up the callback
assert_options(ASSERT_CALLBACK, 'my_assert_handler');



// ===============================================================
// EMPIEZA CODIGO DEL TEST
// ===============================================================


YuppLoader::load("tests.model.001", "Botella");


// Sin esto al hacer reload no carga DatabaseMySQL
YuppLoader::refresh();

class Model001Test {

	public function runTest()
	{
		$this->test1();
      $this->test2();
	}
	 
	private function test1()
	{
      PersistentManager::getInstance()->generateAll();
      
      /**
       * Resultado>
       * CREATE TABLE test_001_botella (
       *   id INT(11) DEFAULT 1 PRIMARY KEY, 
       *   material VARCHAR(30) NULL, 
       *   capacidad FLOAT NULL, 
       *   tapaRosca BOOL NULL, 
       *   class TEXT NOT NULL, 
       *   deleted BOOL NOT NULL
       * );
       */
      
      // TODO: verificar si la tabla para Botella fue creada.
      
      /*
		$xml = $this->getXML();
		$institucion = ParseAltaPacientesV1::parseOrganizacion( $xml->organizacion);
		$pacientes = ParseAltaPacientesV1::parsePacientes($xml->pacientes, $institucion);	      

      if( assert(sizeof($pacientes) === 2))
      {
         echo "Test 1 correcto";	
      } 
      else
      {
         echo "Test 1 INcorrecto";
      }
      */
   }
   
   private function test2()
   {
      $bot = new Botella(
        array(
          "material" => "vidrio",
          "capacidad" => 1.5, 
          "tapaRosca" => true
        )
      );
      
      if ( is_bool( $bot->getTapaRosca() ) ) echo "Correcto, es boolean 1<br/>";
      else echo "Inorrecto, no es boolean 1<br/>";
      
      if (!$bot->save())
      {
         print_r($bot->getErrors());
      }
      
      if ( is_bool( $bot->getTapaRosca() ) ) echo "Correcto, es boolean 2<br/>";
      else echo "Inorrecto, no es boolean 2<br/>";
      
      $bot2 = Botella::get( $bot->getId() );
      
      if ( is_bool( $bot2->getTapaRosca() ) ) echo "Correcto, es boolean 3<br/>";
      else echo "Inorrecto, no es boolean 3<br/>";
      
      if ( is_string( $bot2->getTapaRosca() ) ) echo "Es string 4<br/>";
      
      echo "En realidad es: " . gettype( $bot2->getTapaRosca() ) . "<br/>";
      
      if ( $bot2->getTapaRosca() ) echo "OK, es tapa rosca!<br/>";
      else echo "ERROR, dice que no es tapa rosca<br/>";
   }

}

// Corro el test
$test = new Model001Test();
$test->runTest();

?>