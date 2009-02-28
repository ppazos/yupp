<?php

/**
 * 
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
class Mapping {

   private $mappings = array( "BlogMapping", "DefaultMapping" ); // TODO: BlogMapping deberia registrarse desde el componente blog.
   private $relative_logic_url; // algo como: blog/entradaBlog/show
   private $field_list;
   
   /**
    * @param string path valor devuelto por Filter.getPath(), tiene base_dir incluido.
    */
   function Mapping( $path ) {
      
      global $_base_dir;
      
      // Url relativa, o sea, sin el base dir.
      $this->relative_logic_url = substr( $path, strlen( $_base_dir ) + 1); // +1 para sacarle el '/' en el inicio.
      
      //blog/entradaBlog/show
      //echo "REL LOGIC URL: " . $this->relative_logic_url. "<br/>";
      
      // Lista de campos de la url logica.
      $this->field_list = explode("/", $this->relative_logic_url);
   }
   
   public function getLogicalRoute()
   {
      foreach ( $this->mappings as $mappingClass )
      {
         // OBS: $this->field_list[i] puede ser null...
         $ins = new $mappingClass;
         //if ( preg_match($ins->mapping['component'],  $this->field_list[0] ) &&
         //     preg_match($ins->mapping['controller'], $this->field_list[1] ) &&
         //     preg_match($ins->mapping['action'],     $this->field_list[2] ) )
         if ( preg_match($ins->mapping,  $this->relative_logic_url ) )
         {
//         	echo "MATCHEA: $mappingClass<br/>";
//            print_r( $this->field_list );
//            echo "<br/>";
            
            return $ins->getLogicalRoute( $this->field_list );
         }
      }
   }
}

// TODO: redefinir la forma de la expresion regular para que en realidad sean 3 
//       expresiones divididas por '/' que se chekeen independietemente, asi es
//       mas facil declararlas, si no hay que poner '?' (opcional) por todos lados.

class DefaultMapping {
	
   //public $mapping = array('component' => '/.*/', 'controller' => '/.*/', 'action' => '/.*/');
   
   // Matchea xxx/yyy/zzz con /yyy/zzz y /zzz opcionales.
   public $mapping = "/.*(\/.*(\/.*)?)?/"; // TODO: hacer una expresion dividida por / que se esplitee por / y se chekeen las 3 regexps, o las N que sean, viendo cada pedazo de url. ASI ES MAS SENCILLA la regexp esta.
   
   public function getLogicalRoute( & $field_list )
   {
   	return array('component'  => $field_list[0], 
                   'controller' => (!array_key_exists(1, $field_list)) ? NULL : $field_list[1],  // Si dejo que el campo sea null y uso su valor, en PHP 5.2.8 me tira un notice: index 1 not defined. 
                   'action'     => (!array_key_exists(2, $field_list)) ? NULL : $field_list[2]); // Si dejo que el campo sea null y uso su valor, en PHP 5.2.8 me tira un notice: index 2 not defined.
   
                  // 'controller' => (array_key_exists(1, $field_list)) ? NULL : $field_list[1],  // Si dejo que el campo sea null y uso su valor, en PHP 5.2.8 me tira un notice: index 1 not defined. 
                  // 'action'     => (array_key_exists(2, $field_list)) ? NULL : $field_list[2]); // Si dejo que el campo sea null y uso su valor, en PHP 5.2.8 me tira un notice: index 2 not defined.
   }
}


// TODO: Deberia estar definida dentro del componente. 
class BlogMapping {
   
   public $mapping = "/blog(\/.*(\/.*)?)?/"; // conrtoller y action son opcionales! // TODO: hacer una expresion dividida por / que se esplitee por / y se chekeen las 3 regexps, o las N que sean, viendo cada pedazo de url. ASI ES MAS SENCILLA la regexp esta.
   
   public function getLogicalRoute( & $field_list )
   {
      return array('component'  => $field_list[0], 
                   'controller' => ($field_list[1] === NULL) ? 'entradaBlog' : $field_list[1], 
                   'action'     => $field_list[2]);
   }
}

/* Quiero hacer un mapeo para el listado de entradas que permita mostrar las entradas de una fecha.
class EntradasPorFechaMapping {
   
   // FIXME: De esta forma si quiero hacer un mapeo por mas parametros que com, cont y act no puedo. Deberia ser uan sola regexp. 
   public $mapping = array('component' => '/blog/', 'controller' => '/\d\d\d\d/', 'action' => '/\d\d?/');
   
   public function getLogicalRoute( & $field_list )
   {
      return array('component'  => $field_list[0], 
                   'controller' => 'entradaBlog', 
                   'action'     => $field_list[2]);
   }
}
*/

?>