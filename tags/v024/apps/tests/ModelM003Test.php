<?php

// Ejecutar test:
// http://localhost:8081/YuppPHPFramework/apps/tests/ModelM003Test.php

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

YuppLoader::load("tests.model.m003", "Dedo");
YuppLoader::load("tests.model.m003", "Mano");


// Sin esto al hacer reload no carga DatabaseMySQL
YuppLoader::refresh();

class ModelM003Test {

	public function runTest()
	{
		$this->test1();
      $this->test2();
      $this->test3();
      $this->test4();
	}
	 
	private function test1()
	{
      PersistentManager::getInstance()->generateAll();
      
      echo YuppConventions::tableName('Dedo') . "<br/>";
      echo YuppConventions::tableName('Mano') . "<br/>";
      
      /**
       * Resultado>
       * 
       * CREATE TABLE test_m003_dedo (
       *   id INT(11) DEFAULT 1 PRIMARY KEY,
       *   uniaLarga BOOL NULL,
       *   class TEXT NOT NULL,
       *   deleted BOOL NOT NULL
       * );
       * 
       * CREATE TABLE test_m003_mano (
       *   id INT(11) DEFAULT 1 PRIMARY KEY,
       *   tamanio TEXT NULL,
       *   class TEXT NOT NULL,
       *   deleted BOOL NOT NULL
       * );
       * 
       * CREATE TABLE test_m003_mano_dedos_test_m003_dedo (
       *   id INT(11) DEFAULT 1 PRIMARY KEY,
       *   owner_id INT(11) NOT NULL,
       *   ref_id INT(11) NOT NULL,
       *   type INT(11) NOT NULL,
       *   deleted BOOL NOT NULL,
       *   class TEXT NOT NULL,
       *   ord INT(11) NULL
       * );
       * 
       * ALTER TABLE test_m003_mano_dedos_test_m003_dedo
       *   ADD FOREIGN KEY (owner_id)
       *   REFERENCES test_m003_mano(id);
       * 
       * ALTER TABLE test_m003_mano_dedos_test_m003_dedo
       *   ADD FOREIGN KEY (ref_id)
       *   REFERENCES test_m003_dedo(id);
       * 
       */
      
      // TODO: verificar si la tabla para Nariz y Cara fue creada.
      //$dal = DAL::getInstance();
      $dal = new DAL('tests');
      
      if ( $dal->tableExists( YuppConventions::tableName('Dedo') ) )
      {
         echo "Test 1 correcto";
      }
      else
      {
         echo "Test 1 Incorrecto";
      }
      
      if ( $dal->tableExists( YuppConventions::tableName('Mano') ) )
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
      /**
       * Consultas:
       * 
       * SELECT MAX(id) AS max
       * FROM test_m003_mano
       * 
       * INSERT INTO test_m003_mano (
       *   tamanio ,class ,deleted ,id
       * )
       * VALUES (
       *   'grande' ,'Mano' ,'0' ,'1'
       * );
       * 
       * // Primer dedo
       * SELECT MAX(id) AS max
       * FROM test_m003_dedo
       * 
       * INSERT INTO test_m003_dedo (
       *   unialarga ,class ,deleted ,id
       * )
       * VALUES ('1' ,'Dedo' ,'0' ,'1' );
       * 
       * // Se fija si la relacion entre la mano y el dedo ya existe.
       * SELECT count(id) as cant
       * FROM test_m003_mano_dedos_test_m003_dedo
       * WHERE (
       *   test_m003_mano_dedos_test_m003_dedo.owner_id=1 AND
       *   test_m003_mano_dedos_test_m003_dedo.ref_id=1
       * )
       * 
       * SELECT MAX(id) AS max
       * FROM test_m003_mano_dedos_test_m003_dedo
       * 
       * INSERT INTO test_m003_mano_dedos_test_m003_dedo (
       *   owner_id ,ref_id ,type ,ord ,class ,deleted ,id
       * )
       * VALUES ('1' ,'1' ,'1' ,NULL ,'ObjectReference' ,'0' ,'1' );
       * 
       * 
       * // Segundo dedo
       * SELECT MAX(id) AS max
       * FROM test_m003_dedo
       * 
       * INSERT INTO test_m003_dedo (
       *   unialarga ,class ,deleted ,id
       * )
       * VALUES (
       *   '0' ,'Dedo' ,'0' ,'2'
       * );
       * 
       * // Se fija si la relacion entre la mano y el dedo ya existe
       * SELECT count(id) as cant
       * FROM test_m003_mano_dedos_test_m003_dedo
       * WHERE (
       *   test_m003_mano_dedos_test_m003_dedo.owner_id=1 AND
       *   test_m003_mano_dedos_test_m003_dedo.ref_id=2
       * )
       * 
       * SELECT MAX(id) AS max
       * FROM test_m003_mano_dedos_test_m003_dedo
       * 
       * INSERT INTO test_m003_mano_dedos_test_m003_dedo (
       *   owner_id ,ref_id ,type ,ord ,class ,deleted ,id
       * )
       * VALUES (
       *   '1' ,'2' ,'1' ,NULL ,'ObjectReference' ,'0' ,'2'
       * );
       */
       
      // WARNING:
      // Guarda en cascada los dedos al guardar la mano porque Dedo belongsTo Mano.
      $mano = new Mano(
        array(
          "tamanio" => "grande",
          "dedos"   => array(
              new Dedo(
                array(
                  "uniaLarga" => true
                )
              ),
              new Dedo(
                array(
                  "uniaLarga" => false
                )
              )
          )
        )
      );
      

      if (!$mano->save())
      {
         Logger::struct( $mano->getErrors(), "Falla test m003.2" );
      }
      else
      {
         echo "Guarda Ok<br/>";
      }
      
   }
   
   private function test3()
   {
      $manos = Mano::listAll( new ArrayObject() ); // FIXME: que el parametro no sea obligatorio!
      
      foreach ($manos as $mano)
      {
         echo '<ul>';
           echo '<li>'. $mano->getTamanio() .'</li>';
           foreach( $mano->getDedos() as $dedo )
           {
             echo '<ul>';
               echo '<li>'. (($dedo->getUniaLarga())?'Larga':'Corta') .'</li>';
             echo '</ul>';
           }
         echo '</ul>';
      }
      
      echo "Fin test 3 'listAll'";
   }
   
   // Igual al test2 pero con mas contenido de la capacidad del vaso
   private function test4()
   {
      $mano = new Mano(
        array(
          "tamanio" => "extra large",
          "dedos"   => array(
              new Dedo(
                array(
                  "uniaLarga" => true
                )
              ),
              new Dedo(
                array(
                  "uniaLarga" => false
                )
              )
          )
        )
      );
      

      if (!$mano->save())
      {
         Logger::struct( $mano->getErrors(), "Falla salvar m003.4" );
         echo "Test m003.4 correcto<br/>"; // quiero probar que tira error violando una constraint.
      }
      else
      {
         echo "Guarda Ok<br/>";
         echo "Test m003.4 fallido<br/>";
      }
   }

}

// Corro el test
$test = new ModelM003Test();
$test->runTest();

?>