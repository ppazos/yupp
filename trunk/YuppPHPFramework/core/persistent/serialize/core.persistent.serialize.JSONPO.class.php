<?php

/**
 * Serializa a XML instancias de PersistentObject.
 * 
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 */

YuppLoader::load('core.persistent', 'PersistentObject');

class JSONPO {

   /**
    * Si $resursive es true, se cargan las asociaciones de la clase y tambien se pasan a json.
    */
   public static function toJSON( PersistentObject $po, $recursive = false, $loopDetection = NULL, $currentPath = '' )
   {
      if (is_null($loopDetection)) $loopDetection = new ArrayObject();
      
      // Necesito hacer que cada nodo tenga una path para poder expresar las referencias por loops detectados.
      // La idea es simple: (ver http://goessner.net/articles/JsonPath/)
      // - vacio es la path que referencia al nodo raiz
      // - el nombre del atributo referencia a un objeto hasOne relacionado
      // - el nombre del atributo con notacion de array referencia a un objeto hasMany relacionado
      // p.e. x.store.book[0].title donde x es el objeto raiz, entonces una path valida es: .store.book[0].title
      $loopDetection[$currentPath] = $po->getClass().'_'.$po->getId(); // Marca como recorrido: TODO falta la marca de loop cuando se detecta.
      
      $json = "{";
      $i = 0;
      $n = count($po->getAttributeTypes())-1;
      foreach ( $po->getAttributeTypes() as $attr => $type )
      {
         $value = $po->aGet($attr);
         if (is_bool($value)) (($value)?$value='true':$value='false'); // Si no esta esto, aparece 1 para true y nada para false.
         
         $json .= '"'. $attr .'" : "'. $value .'"'; // TODO: si es numero, no poner comillas
         
         if ($i<$n) $json .= ", ";  
         $i++;
      }
      
      // Agrega errores de validacion si los hay
      // http://code.google.com/p/yupp/issues/detail?id=86
      if ($po->hasErrors())
      {
         $errors = $po->getErrors();
         $json .= ', "errors": {';
         
         foreach ($errors as $attr => $theErrors)
         {
            $json .= '"'. $attr .'": [';
            foreach ($theErrors as $theError)
            {
               $json .= '"'. $theError .'", ';
            }
            $json = substr($json, 0, -2); // Saco ', ' del final
            $json .= '], ';
         }
            
         $json = substr($json, 0, -2); // Saco ', ' del final
         $json .= '}';
      }
      
      if ($recursive)
      {
         foreach ($po->getHasOne() as $attr => $clazz)
         {
            $relObj = $po->aGet($attr);
            if (!is_null($relObj))
            {
               if(!in_array($relObj->getClass().'_'.$relObj->getId(), (array)$loopDetection)) // si no esta marcado como recorrido
               {
                 // FIXME: las tags de los atributos hijos de la instancia raiz deberian
                 //        tener su nombre igual al nombre del atributo, no el nombre de
                 //        la clase. Con este codigo es el nombre de la clase.
                 $json .= ', "'. $attr .'": '. self::toJSON( $relObj, $recursive, $loopDetection, $currentPath.'.'.$attr );
               }
               else // referencia por loop
               {
                  // Agrego un objeto referencia
                  $keys = array_keys((array)$loopDetection, $relObj->getClass().'_'.$relObj->getId());
                  $path = $keys[0];
                  $json .= ', "'.$attr.'": "'. $path .'"';
               }
            }
         }
         
         foreach ($po->getHasMany() as $attr => $clazz)
         {
            // TODO: type de la coleccion, en el de XML tengo:
            //$hm_node->setAttribute( 'type', $obj->getHasManyType($attr) ); // list, colection, set
            //$hm_node->setAttribute( 'of', $obj->getType($attr) ); // clase de las instancias que contiene la coleccion
               
            $relObjs = $po->aGet($attr);
            
            if ( count($relObjs) > 0 )
            {
               $json .= ', "'. $attr .'": [';
               
               $idx = 0; // Se usa para la referencia por loop en la JSON path
               foreach ($relObjs as $relObj)
               {
                  if(!in_array($relObj->getClass().'_'.$relObj->getId(), (array)$loopDetection)) // si no esta marcado como recorrido
                  {
                     $json .= self::toJSON( $relObj, $recursive, $loopDetection, $currentPath.'.'.$attr.'['.$idx.']' ) .', ';
                  }
                  else // referencia por loop
                  {
                     // Agrego un objeto referencia
                     $keys = array_keys((array)$loopDetection, $relObj->getClass().'_'.$relObj->getId());
                     $path = $keys[0];
                     $json .= '"'.$attr.'": "'. $path .'", ';
                  }
                  
                  $idx++;
               }
                
               $json = substr($json, 0, -2); // Saco ', ' del final
                
               $json .= ']';
            }
         }
      }
      
      $json .= '}';
      
      return $json;
   }
   
   /**
   private static function toJSONSingle( PersistentObject $obj, $xml_dom_doc, $xml_parent_node, $recursive, $loopDetection, $attrName = null )
   {
      if(!in_array(get_class($obj).'_'.$obj->getId(), (array)$loopDetection)) // si no esta marcado como recorrido
      {
         $loopDetection[] = get_class($obj).'_'.$obj->getId(); // Marca como recorrido
         
         // Nodo actual
         $node = NULL;
         
         // El nombre de la tag es el nombre del atributo, a no ser que sea el nodo raiz.
         if ( is_null($attrName) )
         {
            $node = $xml_dom_doc->createElement( get_class($obj) );
         }
         else
         {
            $node = $xml_dom_doc->createElement( $attrName );
            $node->setAttribute( 'type', get_class($obj) ); // setAttribute ( string $name , string $value )
         }
         
         // Para la primer llamada, este nodo es el dom_document
         $xml_parent_node->appendChild( $node );
         
         foreach ( $obj->attributeTypes as $attr => $type )
         {
            $attr_node = $xml_dom_doc->createElement( $attr, $obj->aGet($attr) );
            $node->appendChild( $attr_node );
         }
         
         if ($recursive)
         {
            foreach ($obj->getHasOne() as $attr => $clazz)
            {
               $relObj = $obj->aGet($attr);
               if (!is_null($relObj))
               {
                  if(!in_array(get_class($relObj).'_'.$relObj->getId(), (array)$loopDetection)) // si no esta marcado como recorrido
                  {
                     // FIXME: las tags de los atributos hijos de la instancia raiz deberian
                     //        tener su nombre igual al nombre del atributo, no el nombre de
                     //        la clase. Con este codigo es el nombre de la clase.
                     $this->toJSONSingle( $relObj, $xml_dom_doc, $node, $recursive, $loopDetection, $attr );
                  }
               }
            }
            
            foreach ($obj->getHasMany() as $attr => $clazz)
            {
               $hm_node = $xml_dom_doc->createElement( $attr );
               $hm_node->setAttribute( 'type', $obj->getHasManyType($attr) ); // list, colection, set
               
               $relObjs = $obj->aGet($attr);
               
               foreach ($relObjs as $relObj)
               {
                  if(!in_array(get_class($relObj).'_'.$relObj->getId(), (array)$loopDetection)) // si no esta marcado como recorrido
                  {
                     $this->toJSONSingle($relObj, $xml_dom_doc, $hm_node, $recursive, $loopDetection);
                  }
               }
               
               $node->appendChild( $hm_node );
            }
         } // si es recursiva
      } // si no hay loop
   }
   */
   
}
?>