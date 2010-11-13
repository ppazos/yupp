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

YuppLoader::load("tests.model.m010", "M010_Persona");


// Sin esto al hacer reload no carga DatabaseMySQL
YuppLoader::refresh();

class ModelM010Test {

	public function runTest()
	{
		$this->test1();
      $this->test2();
      $this->test3();
	}
	 
	private function test1()
	{
      PersistentManager::getInstance()->generateAll();
      
      echo YuppConventions::tableName('M010_Persona') . "<br/>";
      
      /**
       * Resultado>
       * 
       * CREATE TABLE test_m010_persona (
       *   id INT(11) DEFAULT 1 PRIMARY KEY,
       *   nombre TEXT NULL,
       *   class TEXT NOT NULL,
       *   deleted BOOL NOT NULL
       * );
       * 
       * CREATE TABLE test_m010_persona_hijos_test_m010_persona (
       *   id INT(11) DEFAULT 1 PRIMARY KEY,
       *   owner_id INT(11) NOT NULL,
       *   ref_id INT(11) NOT NULL,
       *   type INT(11) NOT NULL,
       *   deleted BOOL NOT NULL,
       *   class TEXT NOT NULL,
       *   ord INT(11) NULL
       * );
       * 
       */
      
      // TODO: verificar si la tabla para Nariz y Cara fue creada.
      //$dal = DAL::getInstance();
      $dal = new DAL('tests');
      
      if ( $dal->tableExists( YuppConventions::tableName('M010_Persona') ) )
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
       * FROM test_m010_persona
       * 
       * INSERT INTO test_m010_persona (
       *   nombre ,class ,deleted ,id
       * )
       * VALUES (
       *   'Isabel de York' ,'M010_Persona' ,'0' ,'1'
       * );
       * 
       * INSERT INTO test_m010_persona (
       *   nombre ,class ,deleted ,id
       * )
       * VALUES (
       *   'Enrique VIII' ,'M010_Persona' ,'0' ,'2'
       * );
       * 
       * INSERT INTO test_m010_persona (
       *   nombre ,class ,deleted ,id
       * ) VALUES (
       *   'Elizabeth I' ,'M010_Persona' ,'0' ,'3'
       * );
       * 
       * SELECT obj.nombre, obj.class, obj.deleted, obj.id
       * FROM test_m010_persona_hijos_test_m010_persona ref, test_m010_persona obj
       * WHERE (ref.owner_id=3 AND obj.id=ref.ref_id) 
       * 
       * SELECT count(id) as cant
       * FROM test_m010_persona_hijos_test_m010_persona
       * WHERE (test_m010_persona_hijos_test_m010_persona.owner_id=2 AND test_m010_persona_hijos_test_m010_persona.ref_id=3)
       * 
       * SELECT MAX(id) AS max
       * FROM test_m010_persona_hijos_test_m010_persona
       * 
       * INSERT INTO test_m010_persona_hijos_test_m010_persona (
       *   owner_id ,ref_id ,type ,ord ,class ,deleted ,id
       * )
       * VALUES ('2' ,'3' ,'1' ,NULL ,'ObjectReference' ,'0' ,'1' );
       * 
       * INSERT INTO test_m010_persona ( nombre ,class ,deleted ,id )
       * VALUES ('Eduardo VI' ,'M010_Persona' ,'0' ,'4' );
       * 
       * SELECT id FROM test_m010_persona WHERE id=4
       * 
       * SELECT obj.nombre, obj.class, obj.deleted, obj.id
       * FROM test_m010_persona_hijos_test_m010_persona ref, test_m010_persona obj
       * WHERE (ref.owner_id=4 AND obj.id=ref.ref_id) 
       * 
       * SELECT count(id) as cant
       * FROM test_m010_persona_hijos_test_m010_persona
       * WHERE (test_m010_persona_hijos_test_m010_persona.owner_id=2 AND test_m010_persona_hijos_test_m010_persona.ref_id=4)
       * 
       * SELECT MAX(id) AS max FROM test_m010_persona_hijos_test_m010_persona
       * 
       * INSERT INTO test_m010_persona_hijos_test_m010_persona (
       *   owner_id ,ref_id ,type ,ord ,class ,deleted ,id
       * )
       * VALUES ('2' ,'4' ,'1' ,NULL ,'ObjectReference' ,'0' ,'2' );
       * 
       * SELECT count(id) as cant FROM test_m010_persona_hijos_test_m010_persona WHERE (test_m010_persona_hijos_test_m010_persona.owner_id=1 AND test_m010_persona_hijos_test_m010_persona.ref_id=2)
       * 
       * SELECT MAX(id) AS max FROM test_m010_persona_hijos_test_m010_persona
       * 
       * INSERT INTO test_m010_persona_hijos_test_m010_persona ( owner_id ,ref_id ,type ,ord ,class ,deleted ,id ) VALUES ('1' ,'2' ,'1' ,NULL ,'ObjectReference' ,'0' ,'3' );
       * 
       */
       
      // WARNING:
      // Guarda en cascada por el belongsTo de M010_Persona
      $persona = new M010_Persona(
        array(
          "nombre" => "Isabel de York",
          "hijos"  => array(
            new M010_Persona( // hijo
              array(
                "nombre" => "Enrique VIII",
                "hijos"  => array(
                  new M010_Persona( // nieto
                    array(
                      "nombre" => "Elizabeth I"
                    )
                  ),
                  new M010_Persona( // nieto
                    array(
                      "nombre" => "Eduardo VI"
                    )
                  )
                )
              )
            )
          )
        )
      );
      
      if (!$persona->save())
      {
         Logger::struct( $persona->getErrors(), "Falla test m010" );
      }
      else
      {
         echo "Guarda Ok<br/>";
      }
   }
   
   private function test3()
   {
      $personas = M010_Persona::listAll( new ArrayObject() ); // FIXME: que el parametro no sea obligatorio!
      
      foreach ($personas as $persona)
      {
         echo '<ul>';
           echo '<li>'. $persona->getNombre() .'</li>';
           echo '<ul>';
           foreach( $persona->getHijos() as $hijo )
           {
              echo '<li>'. $hijo->getNombre() .'</li>';
              echo '<ul>';
              foreach( $hijo->getHijos() as $nieto )
              {
                 echo '<li>'. $nieto->getNombre() .'</li>';
              }
              echo '</ul>';
           }
           echo '</ul>';
         echo '</ul>';
      }
      
      echo "Fin test 3 'listAll'";
   }

}

// Corro el test
$test = new ModelM010Test();
$test->runTest();

?>