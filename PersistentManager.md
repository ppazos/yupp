# Introduction #

Add your content here.


# Métodos #


# Comentarios #

## General: ##

TODOs GRANDEs

  1. Mantener las asociaciones:
    1. Si salvo un objeto que ya esta guardado deberia:
      1. verificar que los objetos asociados, tanto por hasOne o hasMany, siguien ahi o no, si no:
        1. es hasOne: el id del objeto deberia ponerse en null.
        1. es hasMany: deberia eliminar las asociaciones en las tablas intermedias. (en lugar de preguntar/actualizar , podria eliminar todo y actualizar todo, hay que ver que es mas costoso en tiempo).
  1. PARA SOPORTE DE HERENCIA
    1. ES NECESARIO poner nullables los atributos de las clases que no son hijas de PO, asi clases hermanas pueden agregarse a la tabla y no saltan restricciones de la tabla porque tiene atributos en null.
    1. Solucion: todos los atributos menos los inyectados, como id, deleted y class, son nullables, ya que si mando un null a un atributo no nulo va a saltar en la validacion de las constraints en lugar de dejarlo pasar hasta la validacion de la db.


## Método save\_assoc ##


> // Considera la direccion de la relacion del owner con el child.
> // VERIFICAR: el owner de la relacion, como esta ahora, es la parte fuerte declarada o asumida,
> //            pero la relacion podria ser bidireccional y sin restricciones estructurales,
> //            instancias de child pueden tener varios owners sin que estos tengas asociados
> //            a esos childs, o sea, las relaciones instanciadas son l2r.
> //            Como esta ahora al pedir relaciones l2r, como ahora no tiene info en la base
> //            q diga q son asi, se instancia la relacion como bidir, por lo que no queda
> //            el mismo snapshot que fue el que se salvo.

> // En una relacion n-n bidireccional, es necesario verificar si la instancia de esa relacion
> // es tambien bidireccional (si tengo visibilidad para ambos lados desde cada elemento de la relacion).


## Método save\_object ##

> // FIX: faltaba validar clases relacionadas
> // http://code.google.com/p/yupp/issues/detail?id=50
> // FIXME: Para la instancia ppal, si pasa el validate de PO.save, viene y
> //        lo ejecuta de nuevo aca. Subir alguna bandera para que no lo haga.
> //if (!$obj->validate()) return false;
> // El validate ahora se hace en el save\_cascade


## Método get\_object ##

HERENCIA EN MULTIPLE TABLA
Cargo el registro de la clase que me mandan por su id, esto es para verificar si la clase que me mandan es realmente la clase de la instancia que me piden. Si $persistentClass no esta mapeada en la misma  tabla que el atributo "class" del registro, cargo el registro de la clase que diga la columna "class", ya que ese registro es el que tiene todos los ids inyectados por MTI y es la que me deja cargar todos los registros de instancias parciales para luego unirlos y generar una unica instanca, que es la que me piden.


## Método get\_many\_assoc\_lazy ##

  * FIXME: el problema de hacer el fetch con una consulta es que no puedo saver si el/los objetos ya estan cargados en el ArtifactHolder, no se si esto sea un problema... tal vez si lo cargo aunque ya este cargado lo unico que hago es agregarlo de nuevo en el ArtifactHolder y lsito... hay que ver. Por cada atributo tengo una lista de objetos de ese tipo para traer.
  * TODO: ver quien es el duenio de la relacion!
  * VERIFY!!!: Como la relacion existe, si uno no es el duenio, DEBE ser el otro.


## Método delete ##

  * TODO: setear deleted a la instancia si se pudo hacer el delete en la tabla!
  * TODO: Que pasa si una instancia tiene belongsTo esta instancia, pero tambien tiene belongsTo otra instancia de otra cosa? Lo mas logico seria no eliminarla. ???
  * TODO: Esto borra solo un objeto, falta ver el tema de los objetos asociados y el borrado en cascada...
  * TODO: Si es MTI se que se va a llamar varias veces seguidas a DAL.delete, porque no dejar que las consultas se acumulen en un buffer (string) en DAL y luego se ejecuten todas juntas, es mas, podria rodear con BEGIN y COMMIT para hacerla transaccional.


## remove\_assoc ##

```
FIXME: si obj1 y obj2 son el mismo objeto, y se tiene relacion 1<->*
       con ese objeto, siempre va a decir que obj2 es owner del obj1,
       porque la relacion es identica (va a dar que obj1 es owner de
       obj2, porque se hace por definicion de la clase no de la instancia).
```

## isMappedOnSameTable ##

```
      /*
      // Chekeo ambos casos de subclass primero...
      if ( is_subclass_of($class1, $class2) )
      {
         return self::isMappedOnSameTableSubclass( $class1, $class2 );
      }
      else if ( is_subclass_of($class2, $class1) )
      {
         return self::isMappedOnSameTableSubclass( $class2, $class1 );
      }
      else
      {
         $c1_ins = new $class1();
         $c2_ins = new $class1();
      
         // SOLUCION COMPLICADA PERO CORRECTA.
         // Me tengo que fijar si pertenecen a la misma estructura de herencia (si son primas o hermanas).
         // Luego me fijo en alguna superclase comun y desde ahi busco en que tabla se mapean.
         // ...
         
         // No lo podria hacer simplemente comparando withTable? se que si tiene y son distintos se mapean en distintas tablas,
         // y si una no tiene ya se que la que tiene va en otra tabla aunque pertenezca a la misma estructura de herencia.
         // Pero si ninguna tiene withTable, tengo que encontrar quien define la tabla para cada clase y ver si son la misma...
         // Para este caso (que incluye a los otros tengo) la funcion tableName que deberia dar el nombre de la tabla para 
         // cualquier instancia, tenga o no withTable declarado en la instancia.
         $table1 = YuppConventions::tableName( $c1_ins );
         $table2 = YuppConventions::tableName( $c2_ins );
         
         return ($table1 === $table2);
      }
      */
```

### Método comentado ###

```
   // Hace el insert, si no existe, o updatea si existe.
   public static function _save( PersistentObject &$obj )
   {
      Logger::log("PersistentManager::save");

     // FIXME 1: Si tengo asociado 1 objeto persistente, y tengo la instancia cargada (no solo el id),
     // tengo q ver si tengo que persistirla o no, o sea si es en cascada. Si tengo que persistir,
     // hago una cola de objetos a persistir, y cada vez que encuentro uno nuevo lo meto en la cola
     // con su instancia y cuando termino con el objeto actual, vuelvo a ese y repito el procedimiento
     // hasta tener la cola vacia.

     // Salva en cascada los objetos simples relacionados...
     // FIXME: El problema de hacerlo asi es que cuando salvo el objeto asociado se le asigna un id,
     // y ese id no lo puedo salvar en el objeto que lo tiene asociado porque ya lo salve antes.
     // Par resolver est ose deberia hacer lo siguiente:
     // 1. Manejar la estrcutura como stack. Y Salvar el ultimo primero, asi el id generado queda disponible para el padre,
     //    el problema es como darse cuenta quien es el padre en el stack...
     // A -> B -> C,D
     // 1: Salvo D, quiero ponerle el id a B
     // 2: Salvo C, quiero ponerle el id a B
     // 3: Salvo B, quiero ponerle el id a A
     // 4: Salvo A.

     // 1: identifico la relacion mediante la clase padre B, el nombre del atributo "unD_id", y el nomnbre de la clase D. "b_un_d_id_d",
     // esto lo guardo aparte (asociado a esa instancia de D) y antes de salvar D.
     // Cuando salvo D, con su id y la key de la relacion, busco la clase en el stack que
     // tenga esa relacion (el tema es que puede haber otra B con la misma relacion, pero es otra instancia!),
     // lo mejor es pasarle tambien la B o crear un back-ref temporal para poder saber a quien setearle el id.
     //
     // SOL!!!!!!!!
     // O si capaz, hago la recorrida BFS, salvo primero todas las clases directamente asociadas con la actual,
     // pero la hago recursiva y en la vuelta de la recurcion seteo los ids!!!!


     // Este id identifica el momento de la operacion de salvado y se usa para marcar todos los elementos que se salvaron en la misma operacion.
     // Sirve tembien para saber cuales objetos fueron salvados en la operacion actual, de modo de cortar posibles loops de salvado
     // por haber loops en las asociaciones del modelo.
     $sessId = time();


     $dal = DAL::getInstance();
     $objTableName = PersistentManager::tableName( get_class($obj) );

     // ======================
     // TODO: Deberia generar FK para cada elemento asociado!!!!!
     // ======================

      $assocObjectsQueue = array(); // Cola de objetos simples asociados.
      $assocObjectsQueue[] = $obj; // Inicializo con el objeto que quiero guardar.

      // Estructura para saber cuales son los "padres" (origen de la relacion) de cada objeto.
      $assocOwners = array(); // VERIFY: ESTO ALCANZA???

      // Salva cada objeto y los que se tienen asociados
      while ( sizeof($assocObjectsQueue) > 0 )
      {
         $objToSave = array_pop( $assocObjectsQueue );

         //
         //echo "QUEUE: <br>";
         //print_r( $assocObjectsQueue );
         //echo "OBJ TO SAVE: <br>";
         //print_r( $objToSave );
         //

         // Si el objeto no fue salvado en la operacion actual...
         if (!$objToSave->isSaved( $sessId ))
         {
            $tableName = PersistentManager::tableName( get_class($objToSave) );

            if ( !$dal->exists( $tableName, $obj->getId() ) ) // Si no tiene id, hago insert, si no update.
            {
               // Solo se deberian mandar atributos simples!!!!!!!!!!!!!!!!
               $dal->insert( $tableName, $objToSave ); // Salva los objetos, con sus datos simples.
            }
            else
            {
               $dal->update( $tableName, $objToSave );
            }

            // Marco como salvado
            $objToSave->setSessId( $sessId );


            // Encolo demas objetos relacionados...
            $assocObjects = $objToSave->getSimpleAssocValues(); // Podria chekear si debe o no salvarse en cascada...
            // TODO: si es null algun objeto asociado, tengo que poner el atributo en NULL en la base!!!!!

            //echo "XXXXXXXXXXXXX<br>";
            //print_r( $assocObjects );
            //echo "YYYYYYYYYYYYY<br>";

            // TODO: La solucion seria salvar el objeto padre y los hijos en la misma vuelta,
           //       asi poder salvar las referencias, con lo que hay que tener cuidado es
           //       cuando se salvan los hijos tambien se debe hacer en 2 niveles pero no
           //       se deben salvar xq ya se salvaron. Podria hacerse recursivo!
           //
            // Si quiero salvar los ObjectReference aca me hacen falta los ids de los objetos asociados... los cuales deberia salvarlos antes...
            //foreach ( $assocObjects as $aobj )
            //{
            //}

            $assocObjectsQueue = array_merge($assocObjectsQueue, $assocObjects);

            // =======================================================================================================================
            // ESTO ES NECESARIO PARA HACER EL LOAD !!!!!!!!!!!!!!!!!!!
            //
            // TODO: para los objetos asociados por has many tengo que generar tablas intermedias a mano para mantener las relaciones.
            // Deberia poder crear e insertar usando DAL.
            // La idea que tengo es hacer una operacion para crear tablas (ya esta) con el nombre de
            // los 2 objetos concatenados (el padre primero y luego el hijo).
            // Luego, quiero poder hacer insert en esa tabla, de objetos dinamicos, en su representacion de
            // array asociativo, nombreCampo=>valor, y los campos serian los ids del padre y del hijo.
            //
            //$refTableName = NO TENGO EL NOMBRE DE LA CLASE PADRE! CON LA COLA PIERDO LA REFERENCIA AL PADRE!!! ME FALTA ALGUNA ESTRUCTURA...
            //$dal->createTable( $refTableName, new ObjectReference() ); // Esto deberia hacerse en el generate, no aca...

            // =======================================================================================================================
            // TODO: para las relaciones 1..* deberia borrar los objetos asociados actualmente y agregar los objetos con los que viene.
            // 1. Esto se podira hacer borrando todos los asociados actualmente en la base y guardando los que trae.
            // 2. La solucion mas sofisticada es ver que objetos fueron modificados, cuando detecto una modificacion guardo ese objeto.
            //    (se podria usar un atributo "version" o simplemente una bandera de modificado)
            //
            // =======================================================================================================================
            // TODO: Agregarle los objetos del hasMany. Puedo tener varios declarados en hasMany, cada valor es una lista de objetos.
            //
            $manyAssocObjects = $objToSave->getManyAssocValues(); // Es una lista de listas de objetos.

            foreach ($manyAssocObjects as $objList)
            {
               // TODO: La solucion seria salvar el objeto padre y los hijos en la misma vuelta,
               //       asi poder salvar las referencias, con lo que hay que tener cuidado es
               //       cuando se salvan los hijos tambien se debe hacer en 2 niveles pero no
               //       se deben salvar xq ya se salvaron. Podria hacerse recursivo!
               //
               // Si quiero salvar los ObjectReference aca me hacen falta los ids de los objetos asociados... los cuales deberia salvarlos antes...
               //foreach ( $objList as $aobj )
               //{
               //}
               $assocObjectsQueue = array_merge($assocObjectsQueue, $objList);
            }

            // (FIXED) uso el sessId para saber si salve o no, marcando los salvados.
            // FIXME: No puedo caer en problemas de loops, o sea si tengo A->B->C->A que A se salve de nuevo porque
            // lo tiene aosciado C y C se salve porque lo tiene A.
            // Tengo que introducir algun algoritmo que me permita saber que objetos ya fueron salvados y cuales no.
            // (por lo menos un atributo de marca para cada objeto asi voy marcando los salvados)
         }
      }
   } // save
```


### Método comentado ###

```
// para getMultipleTableInheritance que filtre la solucion.
function filter_not_null( $array )
{
   return $array !== NULL;
}
```