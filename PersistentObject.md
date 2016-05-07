# Introducción #

PersistentObject representa la superclase de todos las clases del modelo persistente de las aplicaciones Yupp.


# Métodos #

List es un array con índices enteros.
Map es un array con índices con algún valor, ej. un string.

```
<object> __call(<string>$method, <array>$args)

<PersistentObject >__construct(<array>$args, <boolean>$isSimpleInstance)

<void> aAddTo ($attribute, PersistentObject $value)

<boolean> aContains( $attribute, $value )

<void> addAttribute( $name, $type )

<void> addConstraints( $attr, $constraints )

<void> addHasMany( $name, $clazz, $type = self::HASMANY_COLLECTION )

<void> addHasOne( $name, $clazz )

<object> aGet( $attr )
El tipo del resultado dependerá del tipo del atributo $attr y del tipo de relación (atributo común, hasOne o hasMany)

<void> aRemoveAllFrom ($attribute, $logical = false)

<void> aRemoveFrom ($attribute, $value, $logical = false)

<void> aSet( $attribute, $value )

<boolean> attributeDeclaredOnThisClass( $attr )

<boolean> belonsToClass( $className )

<void> delete( $logical = false )

<void> executeAfterSave()

<void> executeBeforeSave()

<string|NULL> getAttributeByColumn( $colname )
Nombre del atributo por nombre de su correspondiente columna en la base de datos. NULL si $colname no es la columna de ningún atributo de la clase.

<Map> getAttributeTypes()
Mapeo nombre de atributo => clase

<Map> getAttributeValues()
Mapeo nombre de atributo => valor

<Constraint> getConstraintOfClass( $attr, $class )
Restricción de clase $class para el atributo $attr. NULL sino hay correspondencias.

<List<Constraint>> getConstraints( $attr = NULL )
Si $attr es NULL, devuelve todas las restricciones, sino, devuelve solo las restricciones para el atributo $attr.

<Map> getErrors()
Devuelve mapeo de errores para cada atributo.

<List> getFieldErrors( $attr )
Errores para el atributo $attr.

<string> getFullAttributename( $attrWithoutAssocName )

<Map> getHasMany()
Mapeo de atributos y clases en hasMany.

<string|NULL> getHasManyAttributeNameByAssocAttribute( $assocClass, $assocAttribute )
Nombre del atributo hasMany de $this, correspondiente a la relación desde la clase $assocClass en su atributo $assocAttribute. Se usa para detectar casos de relaciones bidireccionales y encontrar correspondencias entre relaciones para saber donde cargar las relaciones cuando se cargan los objetos desde la base de datos.

<Map> getHasOne()
Mapeo de los atributos y las clases en hasOne.


<string|NULL> getHasOneAttributeNameByAssocAttribute( $assocClass, $assocAttribute )
Idem a getHasManyAttributeNameByAssocAttribute pero para hasOne.

<List> getManyAssocAttrNames()
Lista de atributos en hasMany.

<Map> getManyAssocValues()
Valores no nulos para cada atributo hasMany.

<int> getSessId()

<List> getSimpleAssocAttrNames()

<Map> getSimpleAssocValues()

<Map> getSimpleAttrValues()

<> getSuperClassWithDeclaredAttribute( $attr )

<DataType|PersistentObject|NULL> getType( $attr )
Clase o tipo del atributo, NULL sino es un atributo de la clase.

<string> getWithTable()

<> hasAttribute( $attr )

<booleam> hasErrors()

<boolean> hasFieldErrors( $attr )

<List> hasManyAttributesOfClass( $clazz )

<boolean> hasManyOfThis( $clazz )

<List> hasOneAttributesOfClass( $clazz )

<booleam> hasOneOfThis( $clazz )

<booleam> isClean()

isDirty()

isDirtyMany()

isDirtyOne()

isLoopMarked( $sessId )

isOwnerOf( $attr )

isSaved( $sessId )

isSimplePersistentObject( $attr )

nullable( $attr )

preValidate()

registerAfterSaveCallback( Callback $cb )

registerBeforeSaveCallback( Callback $cb )

removeAttribute( $attr )

resetDirty()

resetDirtyMany()

save()

setLoopDetectorSessId( $sessId )

setProperties( ArrayObject $params )

setSessId( $sessId )

setWithTable( $tableName )

single_save()

update_simple_assocs()

validate($validateCascade = false)

validateOnly( $attrs )

```


Comentarios del quitados código

Campo $belongsTo
  * Posiblemente para modelos complejos, el belongsTo tenga que ser a nivel de rol de asociacion no a nivel de clase.

Método call, opción set
  * ESTRATEGIAS DE SET:
    * Inmediato: se actualiza tambien la base. Mas simple, pero se tiene una consulta con cada set.
    * Post set: se actualiza solo en memoria, la base se actualiza al hacer el save. Mas complejo xq se deben verificar cosas que cambiaron para eliminar objetos (asociaciones) de la base, se ahorra consultas al hacer set, pero se hacen mas consultas al hacer save.
  * Sobre todo hay que tener cuidado si se hace un ser de un atributo hasMany, porque si le meto una lista de objetos con set a un atributo hasMany tengo que eliminar las referencias anteriores en la base para que no haya inconsistencias. Por lo que Set Inmediato seria una buena opcion.

Método save
  * Si esta clase es sublase de otra clase persistente, se deben mergear los atributos de toda la estructura de herencia en una clase persistente "ficticia" y se salva esa clase persistente.
  * Para esto se llama a "getInheritanceStructurePersistentObject".
  * CAMBIO: NO ESTO NO VA ACA!!! (creo que está en PM)

Comentario general:
  * Se puede usar el call para simular metodos findAllByXXXAndYYY ... en el fondo es un constructor de Query... (xq hay que usar And u Or).

Método validate:
  * TODO: Verificar restricciones sobre asociaciones hasOne (p.ej. NotNull).
  * En la validación de hasOne: puedo pedirle los errores y adjuntarlos a los mios ($this->errors) puede ser con un prefijo del nombre del atributo, y estos errores pueden ir en cascada hasta el objeto original que se esta validando asi se puede saber si hubo un error en el objeto o en uno relacionado.
    * Creo que si se valida en cascada, los errores de cada clase los debería tener cada clase, y los errores sobre las relaciones deberían estar en la clase donde fue declarada la relación.


### Métodos removidos ###

/
  * Esta operacon es para cuando pido asociaciones por el nombre del atributo pero sin el nombre de asociacion,
  * si el nombre completo del atributo es roleassoc y ejecuto la accion obj->getRole() necesito obtener el
  * nombre completo a partir solo del role, para esto el rol no debe repetirse.
  * 
```
   public function getFullAttributename( $attrWithoutAssocName )
   {
      Logger::getInstance()->po_log("getFullAttributename attrWithoutAssocName = $attrWithoutAssocName");
      
      foreach ($this->hasMany as $attr => $clazz)
      {
         $pos = stripos($attr, $attrWithoutAssocName);
         if ($pos === 0) // veo si el nombre del atributo es prefijo del nombre real, me viene "role" y $attr es "role__assoc".
         {
            Logger::getInstance()->po_log("getFullAttributename return (hm) = $attr");
            return $attr;
         }
      }
      // TODO: creo que no lo necesito para hasOne... verificar.
      // SI, porque cuando se hace setAttr() necesito el nombre del atributo con asociacion.
      foreach ($this->hasOne as $attr => $clazz)
      {
         $pos = stripos($attr, $attrWithoutAssocName);
         if ($pos === 0) // veo si el nombre del atributo es prefijo del nombre real, me viene "role" y $attr es "role__assoc".
         {
            Logger::getInstance()->po_log("getFullAttributename return (ho) = $attr");
            return $attr;
         }
      }
   }
```

/
  * Dado el nombre de un atributo, que potencialmente podria tener codificado el nombre de la relacion, por ejemplo:
  * roleassoc, devuelve solo el nombre del role, si no tiene el nombre de la asociacion, simplemente devuelve el mismo valor.
  * 
```
   public static function getAssocRoleName( $attributeRawName )
   {
      Logger::getInstance()->po_log("getAssocRoleName attributeRawName = $attributeRawName");
      
      $pos = strrpos($attributeRawName, "__");
      if ( $pos === false )
      {
         Logger::getInstance()->po_log("getAssocRoleName return1 = $attributeRawName");
         return $attributeRawName;
      }
      
      Logger::getInstance()->po_log("getAssocRoleName return2 = ". substr( $attributeRawName, -$pos));
      return substr( $attributeRawName, -$pos);
   }
```


### Comentarios en getHasOneAttributeNameByAssocAttribute ###

```
      /* Esto esta mal porque no solo tengo que preguntar si tengo un atributo de esta clase,
       * sino que si ese atributo es de esa clase y es de la misma relacion que el atributo
       * assocAttribute.
       * Tener en cuenta que attributesOfSameRelationship devuelve true si ninguno de los 2
       * tiene el nombre de la relacion codificado en el nombre. O sea, no verifica que los
       * atributos pertenezcan a las clases ni que esas clases tengan relaciones declaradas
       * entre ellas. Esa verificacion deberia hacerse en algun lado.
       * 
      $hoattrs = $this->hasOneAttributesOfClass( $assocClass );
      $tam = sizeof($hoattrs);
      if ( $tam == 0 ) return NULL; // throw new Exception("PO.getHasManyAttributeNameByAssocAttribute: no tiene un atributo hasMany a " . $assocClass);
      if ( $tam == 1 ) return $hoattrs[0]; // Si hay uno, es ese!

      // Si hay muchos, tengo que ver por el nombre de asociacion codificado en el nombre de los atributos.
      foreach ($hoattrs as $attrName)
      {
         // attrName es un atributo hasMany que apunta a assocClass desde la clase de la instancia actual ($this)
         if ( self::attributesOfSameRelationship( $attrName, $assocAttribute ) ) return $attrName;
      }
      */
```

### Comentario en método isOwnerOf ###

Dentro del bloque: else if ( array\_key\_exists ( $attr, $this->hasMany ) )

Si tengo una relacion hasMany con migo mismo, tengo 1->**o**->**, para ambos casos debería devolver true.**

FIXME: Esto no se cumple completamente si tengo multiples relaciones entre las clases. Porque si tengo bidireccionalidad y cardinalidad **, deberia pedir un belongsTo declarado.
> El problema es que si A hasMany A, y le pregunto al hijo si hasMany el padre, me va a decir siempre true, lo que deberia hacer es linkear los roles de las relaciones en ambas clases (en este caso solo A), para saber cual es la contraparte de la relacion en la otra clase, por ejemplo: A->**(sub\_as)A, cuando le pregunto a A si tiene una relacion reversa con A, a traves de sub\_as, deberia decir false.
> En este caso, deberia decir true: A(parent)

&lt;-&gt;

**(sub\_as)A, y la relacion inversa por el rol "sub\_as" tiene el rol "parent". Para poder hacerlo, falta un constructor que me permita declarar una relacion con sus 2 roles en la clase origen y en la clase destino.**


### Comentario en método getHasManyAttributeNameByAssocAttribute ###

```
      /* Esto esta mal porque no solo tengo que preguntar si tengo un atributo de esta clase,
       * sino que si ese atributo es de esa clase y es de la misma relacion que el atributo
       * assocAttribute.
       * Tener en cuenta que attributesOfSameRelationship devuelve true si ninguno de los 2
       * tiene el nombre de la relacion codificado en el nombre. O sea, no verifica que los
       * atributos pertenezcan a las clases ni que esas clases tengan relaciones declaradas
       * entre ellas. Esa verificacion deberia hacerse en algun lado.
       * 
      // Se ejecuta sobre A y se pasa el atributo de B y quiero el nombre del atributo de A corespondiente a ese atributo de B en la relacion.
      // Es para salvar relaciones n-n bidireccionales y saber el tipo de la instancia, si es uni o bi direccional.
      $hmattrs = $this->hasManyAttributesOfClass( $assocClass );

      $tam = sizeof($hmattrs);
      if ( $tam == 0 ) return NULL; // throw new Exception("PO.getHasManyAttributeNameByAssocAttribute: no tiene un atributo hasMany a " . $assocClass);
      if ( $tam == 1 ) return $hmattrs[0]; // Si hay uno, es ese!

      // Si tengo declarada mas de un hasMany a la clase assocClass
      // Tengo que ver por el nombre de asociacion codificado en el nombre de los atributos
      // Si el rol de ambos lados de la relacion no tiene declarado el nombre de la relacion,
      // tengo que tirar una except porque lo debe declarar para no tener ambiguedad.
      foreach ($hmattrs as $attrName)
      {
         // attrName es un atributo hasMany que apunta a assocClass desde la clase de la instancia actual ($this)
         if ( self::attributesOfSameRelationship( $attrName, $assocAttribute ) )
         {
            //echo "<h1>OK attributesOfSameRelationship( $attrName $assocAttribute )</h1>";
            return $attrName;
         }
      }
      */
```


### Comentario en el método nullable ###

```
      // TODO: Si el atributo es una FK generada para relaciones hasOne o hasMany,
      // deberia verificar si la relacion puede ser nullable o no para saber si ese atributo puede ser null.
      // Por ejemplo el atributo "email" es nullable, aqui pregunto si el atrivuto "email_id" es nullable,
      // me tengo que fijar si hay un atributo "email" (me doy cuenta del nombre porque le saco el "_id"),
      // luego me fijo si ese atributo tiene una contraint nullable, y hago el resto como siempre...
      //
      // OJO! es "email_id" cuando es un hasOne, todavia no se como se va a llamar la FK a
      // hasMany o en que direccion van a ir las relaciones.

      //Logger::getInstance()->log("Nullable ATTR1? $attr");

      // Si el que me pasan es un atributo de referencia hasOne (inyectado) si es nullable o 
      // no depende de si el atributo hasOne correspondiente es nullable o no.
      // TODO:? Si es un atributo de referencia hasOne, hacerlo siempre nullable 
      // podria hacer mas faciles las cosas para PM.generateAll y PM.generate.
      // Y al resto del funcionamiento no le afectaria en nada.
```


### Comentario en clase ObjectReference ###

Campo attributeTypes, clave 'ord':

FIXME: si lo declaro aqui, y el tipo de la relacion no es lista, me genera la consulta con ORD y la consulta me tira el error de que el atributo no existe, en realidad se deberia enchufar dinamicamente el atributo si es que la coleccion es una lista o se genera siempre la tabla con el atributo ORD y se pone en null si no es lista.