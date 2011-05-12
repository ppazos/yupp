<?php

/**
 * Adminsitra session por sitio del sistema. La unica forma
 * de acceder a la session es a traves de esta clase.
 */
class YuppSession {

   /**
    * Agrega un objeto a la sesion, o lo sobreescribe si ya existe. 
    */
   public static function set( $key, $obj )
   {
      $_SESSION[ $key ] = serialize( $obj );
   }

   /**
    * Obtiene un objeto de la sesion usando una clave.
    */
   public static function get( $key ) // Da problemas con el & si retorna NULL (no quiero la referencia al objeto en session, porque en realidad obtengo la referencia a su desserializacion).
   {
      if ( !isset($_SESSION[$key]) ) return NULL;

      $obj = unserialize( $_SESSION[ $key ] );

      return $obj;
   }

   // ahora set no pregunta si ya esta seteado, se puede usar esa para el refresh...
   //public static function refresh() {}

   /**
    * Elimina un objeto con clave $key de la sesion.
    */
   public static function remove( $key )
   {
      unset($_SESSION[ $key ]);
   }

   /**
    * Retorna true si el objeto con clave $key esta en sesion, false en caso contrario.
    */
   public static function contains( $key )
   {
      return isset($_SESSION[ $key ]);
   }
   
   /**
    * Para testing, muestra el contenido de la sesion actual.
    */
   public static function dump()
   {
      echo '<pre>';
      print_r( $_SESSION );
      echo '</pre>';
   }
}

?>