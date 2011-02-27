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
   public static function toJSON( PersistentObject $po, $recursive = false, $loopDetection = NULL )
   {
      if (is_null($loopDetection)) $loopDetection = new ArrayObject();
      
      $loopDetection[] = $po->getClass().'_'.$po->getId(); // Marca como recorrido: TODO falta la marca de loop cuando se detecta.
      
      $json = "{";
      
      $i = 0;
      $n = count($po->getAttributeTypes())-1;
      foreach ( $po->getAttributeTypes() as $attr => $type )
      {
         $json .= '"' . $attr .'" : "' . $po->aGet($attr) . '"'; // TODO: si es numero, no poner comillas
         
         if ($i<$n) $json .= ", ";  
         $i++;
      }
      
      if ($recursive)
      {
         foreach ($po->getHasOne() as $attr => $clazz)
         {
            $relObj = $po->aGet($attr);
            if (!is_null($relObj))
            {
               // TODO: loop detection con referencia path al nodo loopeado
               if(!in_array($relObj->getClass().'_'.$relObj->getId(), (array)$loopDetection)) // si no esta marcado como recorrido
               {
                 // FIXME: las tags de los atributos hijos de la instancia raiz deberian
                 //        tener su nombre igual al nombre del atributo, no el nombre de
                 //        la clase. Con este codigo es el nombre de la clase.
                 $json .= ', "'. $attr .'": '. $relObj->toJSON( $recursive, $loopDetection );
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
               $json .= ', "'. $attr .'": [ ';
                
               foreach ($relObjs as $relObj)
               {
                  // TODO: loop detection con referencia path al nodo loopeado
                  if(!in_array($relObj->getClass().'_'.$relObj->getId(), (array)$loopDetection)) // si no esta marcado como recorrido
                  {
                     $json .= $relObj->toJSON( $recursive, $loopDetection ) .', ';
                  }
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