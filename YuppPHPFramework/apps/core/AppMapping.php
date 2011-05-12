<?php

// Ex DefaultMapping
class AppMapping {
   
   //public $mapping = array('app' => '/.*/', 'controller' => '/.*/', 'action' => '/.*/');
   
   // Matchea xxx/yyy/zzz con /yyy/zzz y /zzz opcionales.
   public $mapping = "/.*(\/.*(\/.*)?)?/"; // TODO: hacer una expresion dividida por / que se esplitee por / y se chekeen las 3 regexps, o las N que sean, viendo cada pedazo de url. ASI ES MAS SENCILLA la regexp esta.
   
   // FIXME: sacar el & pasando ArrayObject
   //public function getLogicalRoute( & $field_list )
   public function getLogicalRoute( $field_list )
   {
      return array('app'  => (!isset($field_list[0])) ? NULL : $field_list[0], 
                   'controller' => (!isset($field_list[1])) ? NULL : $field_list[1],  // Si dejo que el campo sea null y uso su valor, en PHP 5.2.8 me tira un notice: index 1 not defined. 
                   'action'     => (!isset($field_list[2])) ? NULL : $field_list[2]); // Si dejo que el campo sea null y uso su valor, en PHP 5.2.8 me tira un notice: index 2 not defined.
   
                  // 'controller' => (array_key_exists(1, $field_list)) ? NULL : $field_list[1],  // Si dejo que el campo sea null y uso su valor, en PHP 5.2.8 me tira un notice: index 1 not defined. 
                  // 'action'     => (array_key_exists(2, $field_list)) ? NULL : $field_list[2]); // Si dejo que el campo sea null y uso su valor, en PHP 5.2.8 me tira un notice: index 2 not defined.
   }
}

?>