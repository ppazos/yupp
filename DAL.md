# Introduction #

Add your content here.


### Comentarios en método createTable2 ###

```
// TODO:
      // ESTA LLAMADA: $dbms_type = $this->db->getTextType( $type, $maxLength );
      // Deberia cambiarse por: $this->db->getDBType( $attrType, $attrConstraints ); // Y todo el tema de ver el largo si es un string lo hace adentro.

      // Obs: REFERENCES no me crea la FK, no se si porque no existe la tabla  la que hago referencia o porque se define de otra forma.
      // Asi funca: ALTER TABLE `prueba` ADD FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`);

      // VERIFY: posible problema, si estoy creando una tabla con referencias a otra y esa otra no esta creada, capaz salta la base.
      // capaz deveria crear las tablas y luego todas las FKs.


// Esta forma no funciona para MYSQL
      /*
      $q_fks = "";
      foreach ( $fks as $fk )
      {
         // colName INTEGER REFERENCES other_table(column_name),
//         $q_fks .= $fk['name'] . " " . 
//                   $this->db->getDBType($fk['type'], $constraints ) .
//                   " REFERENCES " . $fk['table'] . "(". $fk['refName'] ."), ";
      }
      */
      
      /* ESTA FORMA FUNCIONA.
      // Si la tabla de referencia no existe, tira un error.
      // Las FKs se deben crear luego de las tablas y agregar mediante: 
      //   ALTER TABLE `prueba` ADD FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`);
      //
      foreach ( $fks as $fk )
      {
         // FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`)
         $q_fks .= "FOREIGN KEY (" . $fk['name'] . ") " .
                   "REFERENCES " . $fk['table'] . "(". $fk['refName'] ."), ";
      }
      */
```


### Comentarios método update ###

```
// TODO:
// MTI
// Si el objeto tiene id, tambien tendra los ids de sus ancestros en $multipleTableIds,
// lo que hay que hacer es igual a insert, solo crear las instancias parciales,
// luego setearle el id a cada una (este paso no estaba en el insert), y luego crear
// la consulta como siempre y hacer el update.



      // UPDATE `carlitos`.`persona_linda` SET `nombre` = 'Pablsdf', `tel` = '709sd9217', `edad` = '3' WHERE `persona_linda`.`id` =1
      // UPDATE `carlitos`.`persona_linda` SET `nombre` = 'Pablon' WHERE `persona_linda`.`id` =1
```


### Métodos para implementar ###

```
   // TODO: eliminacion del esquema actual (todas las tablas)


public function backup ( $tableName )
   {
      // TODO
   }

   public function deleteTable( $tableName )
   {
      // TODO
   }

   /* TODO: Respaldo de la base actual
   public function dumpDatabase()
   {
      //include 'config.php';
      //include 'opendb.php';

      //$backupFile = $this->database . date("Y-m-d-H-i-s") . '.sql';
      $backupFile = $this->database . '.sql';

      echo "DUMP: " . $backupFile . "<br/>";
      // ahora no estoy usando pass
      $command = "mysqldump --opt -h $this->url -u $this->user $this->database > $backupFile";
      //$command = "mysqldump --opt -h $this->url -u $this->user -p $this->pass $this->database | gzip > $backupFile";

      echo $command;

      system($command);
      //include 'closedb.php';
   }
   */
```


### Comentarios método tableColInfo ###

```
       /* Devuelve un array de descripciones de columnas:
        * Array
        * (
        *    [Field] => id
        *    [Type] => int(11)
        *    [Null] => NO
        *    [Key] => PRI
        *    [Default] => 
        *    [Extra] => 
        * )
        */
```