<?php

class String {

   function String() {
   }
    
   public static function endsWith($str, $suffix)
   {
      //echo "'" . substr( $str, -strlen($suffix), strlen($str) ) . "'";
   	 return substr( $str, -strlen($suffix), strlen($str) ) === $suffix;
   }
    
   public static function startsWith($str, $prefix)
   {
      return strncmp($str, $prefix, strlen($prefix)) == 0;
   }
    
   /**
    * {@link http://www.php.net/manual/es/function.lcfirst.php documentacion de la funcion utilizada.}
    */
   public static function firstToLower( $str )
   {
      if(false === function_exists('lcfirst')) // Podria no existir la funcion, segun la doc.
      {
         return strtolower( substr($str, 0, 1) ) . substr($str, 1);
      }
      return lcfirst($str);
   }
}
?>