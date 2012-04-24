<?php

YuppLoader::load('core.testing', 'TestCase');

YuppLoader::load('tests.model.A004', 'Pagina');

class TestCaseA004 extends TestCase {

   private $bot;

   public function run()
   {      
      // $this->test1(); // Verifica generacion de tablas
      //$this->test2(); // Genera arbol de subpaginas para salvar en cascada
      $this->testSalvadoEnCascadaConLoop(); // Genera p1->p2->p3->p1 con hasManys y salva p1 en cascada
      //$this->reset();      
   }
   
   public function test1()
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
      
      $this->assert($dal->tableExists( YuppConventions::tableName('Pagina') ), 'TestCaseA004: Test generar tabla');
   }
   
   public function test2()
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
      
      $this->assert($p1->save(), 'TestCaseA004.test2: Test salvar hasMany en cascada', array('errors'=>print_r($p1->getErrors(), true)));
      
      // TODO: en el test verificar que se guardo todo y que puedo cargar las relaciones ok
   }
   
   public function testSalvadoEnCascadaConLoop()
   {
      /**
       * Instancias:
       * a1->a2
       * a2->a3
       * a3->a1
       */
       
      $p1 = new Pagina(
        array(
          "titulo" => "Pagina raiz",
          "contenido" => "This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don't really have to worry about it. It can be useful later on if you're trying to..."
        )
      );
      $p2 = new Pagina(
        array(
          "titulo" => "Subpagina de raiz 1",
          "contenido" => "This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don't really have to worry about it. It can be useful later on if you're trying to...",
          "owner" => $p1
        )
      );
      $p3 = new Pagina(
        array(
          "titulo" => "Sub subpagina de raiz 1",
          "contenido" => "This step is usually done transparently as most compilers perform it and then invoke the assembler themselves, so you don't really have to worry about it. It can be useful later on if you're trying to...",
          "owner" => $p2
        )
      );
      
      $p1->addToSubpages($p2);
      $p2->addToSubpages($p3);
      $p3->addToSubpages($p1);

      //Logger::getInstance()->on();
      $str = '<pre>'.print_r($p1, true).'</pre>';
      $this->assert($p1->save(), 'TestCaseA004.testSalvadoEnCascadaConLoop: Test salvar hasMany en cascada', array('errors'=>$str));
      //Logger::getInstance()->off();
      
      // TODO: test que verifique que se guardo todo y que se pueden cargar todas las relaciones
   }
   
   public function reset()
   {
      // TODO
   }
}

?>