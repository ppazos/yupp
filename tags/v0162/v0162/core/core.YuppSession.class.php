<?php

/**
 * Unica forma de acceder a la session es a traves de esta clase.
 */

// TODO: usar serialize/unserialize para poner y sacar las cosas de la session....

// Adminsitra session por sitio del sistema //
class YuppSession {

   public static function set( $key, $obj )
   {
      $_SESSION[ $key ] = serialize( $obj );

      //echo "SERIALIZE<br/>";
      //print_r( $_SESSION[ $key ] );
   }

   public static function &get( $key )
   {
      if ( !isset($_SESSION[$key]) )
      {
         return NULL;
      }

      $obj = unserialize( $_SESSION[ $key ] );

      //echo "UNSERIALIZE<br/>";
      //print_r( $obj );

      return $obj;
   }

   // ahora set no pregunta si ya esta seteado, se puede usar esa para el refresh...
   //public static function refresh() {}

   public static function remove( $key )
   {
      unset($_SESSION[ $key ]);
   }

   public static function contains( $key )
   {
      return isset($_SESSION[ $key ]);
   }
}

?>
