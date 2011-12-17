<?php

/**
 * Deserializa un XML a instancias de PersistentObject.
 * 
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 */
 
YuppLoader::load('core.persistent', 'PersistentObject');

class XML2PO {

/*
<Libro>
    <titulo>El ingenioso hidalgo don Quixote de la Mancha</titulo>
    <genero>prosa narrativa</genero>
    <fecha>1605-01-01 00:00:00</fecha>
    <idioma>es</idioma>
    <numeroPaginas>223</numeroPaginas>
    <class>Libro</class>
    <deleted>false</deleted>
    <autor_id>1</autor_id>
    <id>1</id>
    <autor type="Autor">
        <nombre>Miguel de Cervantes Saavedra</nombre>
        <fechaNacimiento>1547-09-29</fechaNacimiento>
        <class>Autor</class>
        <deleted>false</deleted>
        <id>1</id>
    </autor>
    <coautores type="collection" of="Autor">
        <Autor>
            <nombre>J. K. Rowling</nombre>
            <fechaNacimiento>1547-09-29</fechaNacimiento>
            <class>Autor</class>
            <deleted>false</deleted>
            <id>2</id>
        </Autor>
        <Autor>
            <nombre>Dan Brown</nombre>
            <fechaNacimiento>1547-09-29</fechaNacimiento>
            <class>Autor</class>
            <deleted>false</deleted>
            <id>3</id>
        </Autor>
    </coautores>
</Libro>
*/

   // TODO: si se le pasa un xslt, aplicarlo al formato de entrada por
   //       si el $xmlstr no tiene el formato generado por toXML(PO).
   public static function toPO( $xmlstr, XSLTProcessor $xslt = NULL )
   {
      if ($xslt != NULL)
      {
         //$doc = DOMDocument::loadXML($xmlstr);
         $doc = new DOMDocument();
         $doc->loadXML($xmlstr, LIBXML_NOCDATA);
         
         // TEST: hay 252
         $xpath = new DOMXPath($doc);
         $nodelist = $xpath->query('//cuerpo/personas/persona');
         echo 'Hay '. $nodelist->length . ' nodos persona<br/>';
         
         $xmlstr = $xslt->transformToXML( $doc );
      }

      if ($xmlstr === NULL)
      {
         echo 'la transformacion retorna NULL<br/>';
         return null;
      }
      
      
      // DEBUG
      //FileSystem::write('archivo_transformado_alta_pac.xml', $xmlstr);
      
    
      // Tengo que cargar todas las clases de la aplicacion actual porque
      // se como se llaman, pero no se donde estan.
      YuppLoader::forceReload();
      YuppLoader::loadModel();
      
      
      // FIXME: no carga las clases del imp!
      //print_r(YuppLoader::getLoadedClasses());
      
      
      // Parseo el XML (deberia tener el formato de toXML)
      $xml = simplexml_load_string($xmlstr);
      


      // TEST: hay 252!
      $arr = $xml->xpath('//IMPPersona ');
      echo "En el transformado hay ". count($arr) ." personas<br/>";
      
      
      
      
      // Referencias a paths con objetos para resolver referencias por loops
      $pathObj = new ArrayObject();
      
      
      // ***
      // TODO: ver si el nodo raiz es un objeto simple o una coleccion.
      // <personas type="collection" of="IMPPersona">
      if (!empty($xml['type']) && $xml['type'] == 'collection')
      {
         // TEST:
         // 252 nodos!
         echo count ( $xml->children() ) ."<br/>";
        
         // nodes, parentAttr, path, pathObj
         $list_po = self::toPOCollection($xml->children(), '/'.$xml->getName(), $pathObj);
         
         
         // TEST: error hay 381 personas!
         echo "Hay ". count($list_po) . " en la lista de PO<br/>";
         
         
         return $list_po;
      }

      // Para el primer nodo, la clase es el nombre del elemento
      $class = $xml->getName();
      
      
      $po = self::toPOSingle($class, $xml, '', -1, $pathObj);

      // TODO: no necesito loop detection para no entrar en loops infinitos,
      // lo necesito para resolver referencias a nodos, y reflejarlo en el PO que estoy creando.
      //$loopDetection = new ArrayObject();

      //self::toXMLSingle( $po, $xml_dom, $xml_dom, $recursive, $loopDetection );
      
      /*
      if ($xslt === NULL)
         return $xml_dom->saveXML();
      else
         return $xslt->transformToXML( $xml_dom );
      */
      
      //print_r($pathObj);
      
      return $po;
   }
   
   private static function toPOSingle($class, $xml, $path = '', $idx = -1, $pathObj)
   {
      $po = new $class();
      
      $path .= '/'.$xml->getName();
      if ($idx >= 0) $path .= "[$idx]";
      //echo "path: $path<br/>";
      
      //if (!isset($pathObj[$path]))
      $pathObj[$path] = $po;
      
      foreach ($xml->children() as $chName=>$child)
      {
         // Todavia no manejo referencias
         if (!empty($child['ref']))
         {
            //echo 'ref: '. $child['ref'] .'<br/>';
            
            // Si encuentra ref aca, es para atributo hasOne
            $hoObj = $pathObj[(string)$child['ref']]; // Si encuentra ref, deberia tener el objeto en pathObj.
            $po->aSet($chName, $hoObj);
            
            continue;
         }

         
         // TODO: si esta el ref, tendria que ir a buscar el objeto referenciado por su path.
         //       tengo que tener una coleccion de objetos cada uno con su path.
         
         
         //echo "parentClass: $class, chName: $chName<br/>";
         
         // Si son nodos simples, son atributos simples de PO
         // Se pregunta por type porque puede ser una coleccion vacia (no tiene hijos pero tiene type)
         if ($child->count()==0 && empty($child['type']))
         {
            $po->aSet($chName, (string)$child);
            continue;
         }
         
         
         // Es un atributo complejo
         // Puede ser hasOne o hasMany
         $type = (string)$child['type'];
         if ($type == 'collection') // Si es hasMany
         {
            // El of no es la clase concreta, es el tipo de la relacion
            // El nombre de la clase concreta es el nombre de cada tag hija
            //$class = (string)$child['of'];
            
            $values = array();
            $j = 0; // Para la path de hm
            foreach ($child->children() as $hmChName=>$hmXML)
            {
               // Todavia no manejo referencias
               if (!empty($hmXML['ref']))
               {
                  //echo 'ref hm: '. $hmXML['ref'] .'<br/>';
                  
                  // Si encuentra ref aca, es para atributo hasOne
                  $hoObj = $pathObj[(string)$hmXML['ref']]; // Si encuentra ref, deberia tener el objeto en pathObj.
                  $values[] = $hoObj;
                  
                  continue;
               }

               
               // TODO: si esta el ref, tendria que ir a buscar el objeto referenciado por su path.
               //       tengo que tener una coleccion de objetos cada uno con su path.

//echo "tag2: $hmChName<br/>";
               //$class = $hmChName; // $hmChName es la clase declarada
               $class = (string)$hmXML['type']; // type es la clase concreta
               
               //print_r($hmXML);
               //echo $k."<br/>";
               //$hmpath = $path.'/'.$chName.'/'.$hmChName.'['.$i.']';
               
               // FIXME: el i deberia ser por tag que se llama igual, pero si los elementos
               // son de distinta clase concreta, la tag se llama distinto.
               $hmpath = $path.'/'.$chName;
               $hmObj = self::toPOSingle($class, $hmXML, $hmpath, $j, $pathObj);
               
               // si hago addTo y el po tiene id, va a querer cargar
               // la coleccion de la base, y si hay elementos, van a 
               // aparecer doble.
               //$po->aAddTo($child->getName(), $hmObj);
               $values[] = $hmObj;
               $j++;
            }
            $po->aSet($chName, $values);
         }
         else // es hasOne
         {
//echo "tag3: $chName<br/>";
            //$path .= '/'.$xml->getName();
            $hoObj = self::toPOSingle($type, $child, $path, -1, $pathObj);
            $po->aSet($chName, $hoObj);
         }
      }
      
      return $po;
   }
   
   // ***
   /**
    * parentAttr es el nombre del atributo hasMany, si corresponde a una coleccion hm.
    */
   private static function toPOCollection($nodes, $colectionName, $pathObj)
   {
      $values = array();
      $i = 0; // Para la path de hm
      $h = 0;
      //foreach ($nodes as $hmChName=>$hmXML) // Con esto, si en nodes hay 252 elementos, me hace 381 vueltas.
      for ($i=0; $i<count($nodes); $i++) // Con esto hace 252 vuelta! OK!
      {
        $hmXML = $nodes[$i];
        
           // Todavia no manejo referencias
           if (!empty($hmXML['ref']))
           {
              //echo 'ref hm: '. $hmXML['ref'] .'<br/>';
              
              // Si encuentra ref aca, es para atributo hasOne
              $hoObj = $pathObj[(string)$hmXML['ref']]; // Si encuentra ref, deberia tener el objeto en pathObj.
              $values[] = $hoObj;
              
              continue;
           }

           // TODO: si esta el ref, tendria que ir a buscar el objeto referenciado por su path.
           //       tengo que tener una coleccion de objetos cada uno con su path.

//echo "tag: $hmChName<br/>";
           $class = (string)$hmXML['type']; // type es la clase concreta
           
           
           $hmObj = self::toPOSingle($class, $hmXML, $colectionName, $i, $pathObj);
           $values[] = $hmObj;
           //$i++;
           $h++;
      }
      
      // TEST:
      // i es 381 y count de nodes es 252!!!
      // FIXME: es toPOSingle que esta tocando los $i internamente!
      echo "Se hace loop de ". $i . " vueltas sobre una lista de ". count($nodes) ." elementos<br/>";
      echo $h."<br/>";
      
      return $values;
   }
}

?>