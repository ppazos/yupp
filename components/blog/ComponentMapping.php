<?php
/*
 * Created on 19/11/2009
 * ComponentMapping.php
 */

// Ex blog mapping
class ComponentMapping {
   
   public $mapping = "/blog(\/.*(\/.*)?)?/"; // conrtoller y action son opcionales! // TODO: hacer una expresion dividida por / que se esplitee por / y se chekeen las 3 regexps, o las N que sean, viendo cada pedazo de url. ASI ES MAS SENCILLA la regexp esta.
   
   // FIXME: sacar el & pasando ArrayObject
   //public function getLogicalRoute( & $field_list )
   public function getLogicalRoute( $field_list )
   {
      return array('component'  => $field_list[0], 
                   'controller' => (!isset($field_list[1])) ? 'entradaBlog' : $field_list[1], 
                   'action'     => (!isset($field_list[2])) ? 'list' : $field_list[2]);
   }
}
?>
