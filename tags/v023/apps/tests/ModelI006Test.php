<?php

// Ejecutar test:
// http://localhost:8081/YuppPHPFramework/apps/tests/ModelI006Test.php

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

function my_warning_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
   echo "<hr>Warning Failed:
     ErrNo '$errno'<br />
     Str '$errstr'<br />
     File '$errfile'<br />
     Line '$errline'<br />
     Context ";
    Logger::struct($errcontext);
    echo "<br /><hr />";
    
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

YuppLoader::load("tests.model.i006", "Contenido1");
YuppLoader::load("tests.model.i006", "Recipiente1");
YuppLoader::load("tests.model.i006", "Vaso1");

// Sin esto al hacer reload no carga DatabaseMySQL
YuppLoader::refresh();

class ModelI006Test {

	public function runTest()
	{
		$this->test1();
      $this->test2();
      $this->test3();
	}
	 
	private function test1()
	{
      PersistentManager::getInstance()->generateAll();
      
      echo YuppConventions::tableName('Contenido1') . "<br/>";
      echo YuppConventions::tableName('Recipiente1') . "<br/>";
      echo YuppConventions::tableName('Vaso1') . "<br/>";
      
      /**
       * Resultado>
       * 
       * CREATE TABLE test_i006_contenido1 (id INT(11) DEFAULT 1 PRIMARY KEY, elemento VARCHAR(30) NULL, volumen FLOAT NULL, class TEXT NOT NULL, deleted BOOL NOT NULL);
       * 
       * CREATE TABLE test_i005_contenido (
       *   id INT(11) DEFAULT 1 PRIMARY KEY,
       *   elemento VARCHAR(30) NULL,
       *   volumen FLOAT NULL,
       *   class TEXT NOT NULL,
       *   deleted BOOL NOT NULL
       * );
       * 
       * CREATE TABLE test_i006_vaso1 (id INT(11) DEFAULT 1 PRIMARY KEY, marca TEXT NULL, class TEXT NOT NULL, deleted BOOL NOT NULL, contenido_id INT(11) NULL, super_id_recipiente1 INT(11) NOT NULL);
       * 
       * CREATE TABLE test_i005_vaso (
       *   id INT(11) DEFAULT 1 PRIMARY KEY,
       *   marca TEXT NULL,
       *   class TEXT NOT NULL,
       *   deleted BOOL NOT NULL,
       *   contenido_id INT(11) NULL,
       *   super_id_recipiente INT(11) NOT NULL
       * );
       * 
       * CREATE TABLE test_i006_recipiente1 (id INT(11) DEFAULT 1 PRIMARY KEY, material VARCHAR(30) NULL, capacidad FLOAT NULL, tieneTapa BOOL NULL, class TEXT NOT NULL, deleted BOOL NOT NULL);
       * 
       * CREATE TABLE test_i005_recipiente (
       *   id INT(11) DEFAULT 1 PRIMARY KEY,
       *   material VARCHAR(30) NULL,
       *   capacidad FLOAT NULL,
       *   tieneTapa BOOL NULL,
       *   class TEXT NOT NULL,
       *   deleted BOOL NOT NULL
       * );
       * 
       * ALTER TABLE test_i006_vaso1 ADD FOREIGN KEY (super_id_recipiente1) REFERENCES test_i006_recipiente1(id);
       * 
       * ALTER TABLE test_i005_vaso
       *   ADD FOREIGN KEY (super_id_recipiente)
       *   REFERENCES test_i005_recipiente(id);
       * 
       * ALTER TABLE test_i006_vaso1 ADD FOREIGN KEY (contenido_id) REFERENCES test_i005_contenido(id);
       * 
       * ALTER TABLE test_i005_vaso
       *   ADD FOREIGN KEY (contenido_id)
       *   REFERENCES test_i005_contenido(id);
       */
      
      // TODO: verificar si la tabla para Nariz y Cara fue creada.
      //$dal = DAL::getInstance();
      $dal = new DAL('tests');
      
      if ( $dal->tableExists( YuppConventions::tableName('Contenido1') ) )
      {
         echo "Test 1 correcto";
      }
      else
      {
         echo "Test 1 Incorrecto";
      }
      
      if ( $dal->tableExists( YuppConventions::tableName('Recipiente1') ) )
      {
         echo "Test 1 correcto";
      }
      else
      {
         echo "Test 1 Incorrecto";
      }
      
      if ( $dal->tableExists( YuppConventions::tableName('Vaso1') ) )
      {
         echo "Test 1 correcto";
      }
      else
      {
         echo "Test 1 Incorrecto";
      }
      
   }
   
   private function test2()
   {
      $vaso = new Vaso1(
        array(
          "material"  => "vidrio",
          "marca"     => "coca cola",
          "capacidad" => 5.0,
          "contenido" => new Contenido1(
            array(
              "elemento" => "agua",
              "volumen"  => 4.5
            )
          )
        )
      );
      
      
      // En este test, Vaso es duenio de Contenido asi que lo salva en cascada.
      
      // WARNING:
      // Como el Vaso no es duenio del contenido, el contenido no se salva
      // al salvar el vaso, hay que hacerlo antes de guardar el vaso.
      /*
      if (!$vaso->getContenido()->save())
      {
         Logger::struct( $vaso->getContenido()->getErrors(), "Falla test 2.1" );
      }
      else
      {
         echo "Guarda contenido<br/>";
      }
      */
      
      if (!$vaso->save())
      {
         Logger::struct( $vaso->getErrors(), "Falla test 2.2" );
      }
      else
      {
         echo "Guarda vaso<br/>";
      }
   }
   
   private function test3()
   {
      $vasos = Vaso1::listAll( new ArrayObject() ); // FIXME: que el parametro no sea obligatorio!
      
      foreach ($vasos as $vaso)
      {
         echo '<ul>';
           echo '<li>'. $vaso->getMaterial() .'</li>';
           echo '<li>'. $vaso->getCapacidad() .'</li>';
           echo '<li>'. $vaso->getTieneTapa() .'</li>';
           echo '<li>'. $vaso->getMarca() .'</li>';
           echo '<ul>';
             echo '<li>'. $vaso->getContenido()->getElemento() .'</li>';
             echo '<li>'. $vaso->getContenido()->getVolumen() .'</li>';
           echo '</ul>';
         echo '</ul>';
      }
      
      echo "Fin test 3 'listAll'";
   }

}

// Corro el test
$test = new ModelI006Test();
$test->runTest();

?>