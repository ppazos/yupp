<?php

/**
 * Serializa a XML instancias de PersistentObject.
 * 
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 */

YuppLoader::load('core.persistent', 'PersistentObject');

class XMLPO {

   /*
    * para obtener el xslt, hacer:
    * 
    * $xsl_dom = new DOMDocument();
    * $xsl_dom->load( 'yuppxml2impxml.xsl', LIBXML_NOCDATA);
    * 
    * $xslt = new XSLTProcessor();
    * $xslt->importStylesheet( $xsl_dom );
    */ 
   public static function toXML( PersistentObject $po, $recursive = false, $pretty = false, XSLTProcessor $xslt = NULL )
   {
      //$xml_dom = new DOMDocument();
      //$xml_dom = new DOMDocument('1.0', 'utf-8');
      $xml_dom = new DOMDocument('1.0', 'iso-8859-1');
      
      if ($pretty)
      {
         $xml_dom->preserveWhiteSpace = false;
         $xml_dom->formatOutput   = true;
      }
      
      $loopDetection = new ArrayObject();

      self::toXMLSingle( $po, $xml_dom, $xml_dom, $recursive, $loopDetection );
      
      if ($xslt === NULL)
         return $xml_dom->saveXML();
      else
         return $xslt->transformToXML( $xml_dom ); 
   }
   
   // PersistentObject, DomDocument, DomNode, Boolean, ArrayObject, String
   private static function toXMLSingle( PersistentObject $obj, $xml_dom_doc, $xml_parent_node, $recursive, $loopDetection, $attrName = null )
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
            //$node = $xml_dom_doc->createElement( get_class($obj) );
            $node = $xml_dom_doc->createElement( $attrName );
            $node->setAttribute( 'type', get_class($obj) ); // setAttribute ( string $name , string $value )
         }
         
         // Para la primer llamada, este nodo es el dom_document
         $xml_parent_node->appendChild( $node );
         
         foreach ( $obj->getAttributeTypes() as $attr => $type )
         {
            // Sin iconv da un error en el XML si encuentra un tilde o una html entity
            // Funciona ok, y utf8_decode( utf8_encode() ) NO FUNCIONA
            $attr_node = $xml_dom_doc->createElement( $attr, iconv( "ISO-8859-1", "UTF-8//TRANSLIT", $obj->aGet($attr) ) ); // string iconv ( string $in_charset , string $out_charset , string $str )
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
                     self::toXMLSingle( $relObj, $xml_dom_doc, $node, $recursive, $loopDetection, $attr );
                  }
               }
            }
            
            foreach ($obj->getHasMany() as $attr => $clazz)
            {
               $hm_node = $xml_dom_doc->createElement( $attr );
               $hm_node->setAttribute( 'type', $obj->getHasManyType($attr) ); // list, colection, set
               $hm_node->setAttribute( 'of', $obj->getType($attr) ); // clase de las instancias que contiene la coleccion
               
               $relObjs = $obj->aGet($attr);
               
               foreach ($relObjs as $relObj)
               {
                  if(!in_array(get_class($relObj).'_'.$relObj->getId(), (array)$loopDetection)) // si no esta marcado como recorrido
                  {
                     self::toXMLSingle($relObj, $xml_dom_doc, $hm_node, $recursive, $loopDetection);
                  }
               }
               
               $node->appendChild( $hm_node );
            }
         } // si es recursiva
      } // si no hay loop
   }
   
   /**
    * En la primera vuelta se le pasa el xmlstr
    * Si debe parsear hasMany y hasOne, usa el segundo parametro 
    * para seguir la recursion, y el primero queda en NULL.
    */
   public static function fromXML($xmlstr, $xmlnode = NULL)
   {
      // TODO: falta resolver loops.
    
      // Debo tener todas las clases cargadas porque no se de donde cargar la clase solo por el nombre (ni siquiera se de que aplicacion es).
      // TODO: Podria: agregar informacion al XML para saber en que paquete esta la clase, o indicarle por parametro el nombre de la aplicacion donde buscar la clase.  
      YuppLoader::loadModel();
      
      $xml = NULL;
      $clazz = NULL;
      
      // Si es la primer llamada, parsea, si no, usa el xml parseado en el paso previo de la recursion.
      if ($xmlnode == NULL)
      {
         $xml = simplexml_load_string($xmlstr);
         $clazz = $xml->getName(); // Nombre de la etiqueta
      }
      else
      {
         // En la recursion, el nombre de la clase viene en:
         // - atributo type: si es hasOne
         // - nombre de la etiqueta: si es hasMany
         $type = (string)$xmlnode['type'];
         if (!empty($type)) // Es hasOne
         {
            $clazz = $type;
         }
         else
         {
            $clazz = $xmlnode->getName(); // Nombre de la etiqueta
         }
         
         $xml = $xmlnode;
      }
      
      
      $po_ins = new $clazz();
      
      
      // Obtengo los atributos: son las tags que no tienen el atributo type.
      $attributes = array();
      foreach( $xml->children() as $name => $xml_node )
      {
         $type = (string) $xml_node['type'];
         if (!empty($type)) // Es hasOne o hasMany
         {
            // Hacer el parse recursivo, ver si es HO o HM
            $of = (string)$xml_node['of'];
            if (!empty($of)) // Es hasMany
            {
               //echo "$name hasMany\n";
               $value = array(); // El valor de hasMany es una coleccion
               foreach($xml_node->children() as $hm_xml_node)
               {
                  $value[] = self::fromXML(NULL, $hm_xml_node);
               }
            }
            else // Es hasOne
            {
               //echo "$name hasOne\n";
               $value = self::fromXML(NULL, $xml_node);
            }
         }
         else // Setea atributos simples
         {
            //echo "$name simple\n";
            $value = (string)$xml_node;
         }
         
         // Setea el valor del atributo
         $po_ins->aSet($name, $value);
      }
            
      return $po_ins;
   }
}

?>