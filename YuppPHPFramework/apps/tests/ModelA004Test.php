<?php

// Ejecutar test:
// http://localhost:8081/YuppPHPFramework/apps/tests/ModelA004Test.php

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

YuppLoader::load("tests.model.A004", "Pagina");


// Sin esto al hacer reload no carga DatabaseMySQL
YuppLoader::refresh();

class ModelA004Test {

	public function runTest()
	{
		$this->test1();
      $this->test2();
      //$this->test3();
      //$this->test4();
	}
	 
	private function test1()
	{
      PersistentManager::getInstance()->generateAll();
      
      echo YuppConventions::tableName('Pagina') . "<br/>";
      
      /**
       * Resultado>
       * 
       * CREATE TABLE test_a004_pagina (id INT(11) DEFAULT 1 PRIMARY KEY, titulo VARCHAR(255) NULL, contenido MEDIUMTEXT NULL, class TEXT NOT NULL, deleted BOOL NOT NULL, owner_id INT(11) NULL);
       * 
       * CREATE TABLE test_a004_pagina_subpages_test_a004_pagina (id INT(11) DEFAULT 1 PRIMARY KEY, owner_id INT(11) NOT NULL, ref_id INT(11) NOT NULL, type INT(11) NOT NULL, deleted BOOL NOT NULL, class TEXT NOT NULL, ord INT(11) NULL);
       * 
       * ALTER TABLE test_a004_pagina_subpages_test_a004_pagina ADD FOREIGN KEY (owner_id) REFERENCES test_a004_pagina(id);
       * 
       * ALTER TABLE test_a004_pagina_subpages_test_a004_pagina ADD FOREIGN KEY (ref_id) REFERENCES test_a004_pagina(id);
       * 
       * ALTER TABLE test_a004_pagina ADD FOREIGN KEY (owner_id) REFERENCES test_a004_pagina(id);
       * 
       */
      
      // TODO: verificar si la tabla para Nariz y Cara fue creada.
      //$dal = DAL::getInstance();
      $dal = new DAL('tests');
      
      if ( $dal->tableExists( YuppConventions::tableName('Pagina') ) )
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
       * SELECT MAX(id) AS max FROM test_a004_pagina
       * 
       * INSERT INTO test_a004_pagina ( titulo ,contenido ,class ,deleted ,owner_id ,id ) VALUES ('Pagina raiz' ,'This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don\'t really have to worry about it. It can be useful later on if you\'re trying to...' ,'Pagina' ,'0' ,NULL ,'1' );
       * 
       * SELECT MAX(id) AS max FROM test_a004_pagina
       * 
       * INSERT INTO test_a004_pagina ( titulo ,contenido ,class ,deleted ,owner_id ,id ) VALUES ('Subpagina de raiz 1' ,'This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don\'t really have to worry about it. It can be useful later on if you\'re trying to...' ,'Pagina' ,'0' ,'1' ,'2' );
       * 
       * SELECT MAX(id) AS max FROM test_a004_pagina
       * 
       * INSERT INTO test_a004_pagina ( titulo ,contenido ,class ,deleted ,owner_id ,id ) VALUES ('Sub subpagina de raiz 1' ,'This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don\'t really have to worry about it. It can be useful later on if you\'re trying to...' ,'Pagina' ,'0' ,'2' ,'3' );
       *
       * SELECT count(id) as cant FROM test_a004_pagina_subpages_test_a004_pagina WHERE (test_a004_pagina_subpages_test_a004_pagina.owner_id=2 AND test_a004_pagina_subpages_test_a004_pagina.ref_id=3)
       * 
       * SELECT MAX(id) AS max FROM test_a004_pagina_subpages_test_a004_pagina
       * 
       * INSERT INTO test_a004_pagina_subpages_test_a004_pagina ( owner_id ,ref_id ,type ,ord ,class ,deleted ,id ) VALUES ('2' ,'3' ,'2' ,NULL ,'ObjectReference' ,'0' ,'1' );
       * 
       * SELECT MAX(id) AS max FROM test_a004_pagina
       * 
       * INSERT INTO test_a004_pagina ( titulo ,contenido ,class ,deleted ,owner_id ,id ) VALUES ('Sub subpagina de raiz 2' ,'This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don\'t really have to worry about it. It can be useful later on if you\'re trying to...' ,'Pagina' ,'0' ,'2' ,'4' );
       * 
       * SELECT count(id) as cant FROM test_a004_pagina_subpages_test_a004_pagina WHERE (test_a004_pagina_subpages_test_a004_pagina.owner_id=2 AND test_a004_pagina_subpages_test_a004_pagina.ref_id=4)
       * 
       * SELECT MAX(id) AS max FROM test_a004_pagina_subpages_test_a004_pagina
       * 
       * INSERT INTO test_a004_pagina_subpages_test_a004_pagina ( owner_id ,ref_id ,type ,ord ,class ,deleted ,id ) VALUES ('2' ,'4' ,'2' ,NULL ,'ObjectReference' ,'0' ,'2' );
       * 
       * SELECT count(id) as cant FROM test_a004_pagina_subpages_test_a004_pagina WHERE (test_a004_pagina_subpages_test_a004_pagina.owner_id=1 AND test_a004_pagina_subpages_test_a004_pagina.ref_id=2)
       * 
       * SELECT MAX(id) AS max FROM test_a004_pagina_subpages_test_a004_pagina
       * 
       * INSERT INTO test_a004_pagina_subpages_test_a004_pagina ( owner_id ,ref_id ,type ,ord ,class ,deleted ,id ) VALUES ('1' ,'2' ,'2' ,NULL ,'ObjectReference' ,'0' ,'3' );
       * 
       * SELECT id FROM test_a004_pagina WHERE id=2
       * 
       * UPDATE test_a004_pagina SET titulo='Subpagina de raiz 1' ,contenido='This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don\'t really have to worry about it. It can be useful later on if you\'re trying to...' ,class='Pagina' ,deleted='0' ,owner_id='1' WHERE id=2
       * 
       * 
       */
      
      $p1 = new Pagina(
        array(
          "titulo" => "Pagina raiz",
          "contenido" => "This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don't really have to worry about it. It can be useful later on if you're trying to..."
        )
      );
      $p11 = new Pagina(
        array(
          "titulo" => "Subpagina de raiz 1",
          "contenido" => "This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don't really have to worry about it. It can be useful later on if you're trying to...",
          "owner" => $p1
        )
      );
      $p111 = new Pagina(
        array(
          "titulo" => "Sub subpagina de raiz 1",
          "contenido" => "This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don't really have to worry about it. It can be useful later on if you're trying to...",
          "owner" => $p11
        )
      );
      $p112 = new Pagina(
        array(
          "titulo" => "Sub subpagina de raiz 2",
          "contenido" => "This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don't really have to worry about it. It can be useful later on if you're trying to...",
          "owner" => $p11
        )
      );
      
      
      // subpaginas de p11
      $p11->addToSubpages($p111);
      $p11->addToSubpages($p112);
      
      // subpaginas de p1
      $p1->addToSubpages($p11);
      
      // Guarda en cascada porque el lado con cardinalidad 1 de la relacion
      // es el responsable del lado con cardinalidad *.
      if (!$p1->save())
      {
         Logger::struct( $p1->getErrors(), "Falla test A004.2 1" );
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
           foreach( $mano->getPaginas() as $Pagina )
           {
             echo '<ul>';
               echo '<li>'. (($Pagina->getUniaLarga())?'Larga':'Corta') .'</li>';
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
          "Paginas"   => array(
              new Pagina(
                array(
                  "uniaLarga" => true
                )
              ),
              new Pagina(
                array(
                  "uniaLarga" => false
                )
              )
          )
        )
      );
      

      if (!$mano->save())
      {
         Logger::struct( $mano->getErrors(), "Falla salvar A004.4" );
         echo "Test A004.4 correcto<br/>"; // quiero probar que tira error violando una constraint.
      }
      else
      {
         echo "Guarda Ok<br/>";
         echo "Test A004.4 fallido<br/>";
      }
   }

}

// Corro el test
$test = new ModelA004Test();
$test->runTest();

?>