<?php

/**
Tipos de datos definidos para los tipos de los atributos de las clases persistentes.
*/
class Datatypes {

   // Si tiene un atributo de tipo TEXT, la restriccion maxLength determina el tipo en la bd.
   const TEXT = "type_string"; // esto seria hasta 4 gb, necesito uno que sea mas chico, como hasta 255 chars.

   const INT_NUMBER = "type_int32";
   const LONG_NUMBER = "type_int64";
   const FLOAT_NUMBER = "type_float";

   const BOOLEAN = "type_bit";

   const DATE = "type_date";
   const TIME = "type_time";
   const DATETIME = "type_datetime"; // mysql usa: 2007-11-15 20:11:01 "Y-m-d H:i:s"


   public static function isText( $type )
   {
      return ($type == self::TEXT);
   }

   public static function isNumber( $type )
   {
      return ($type == self::INT_NUMBER || $type == self::LONG_NUMBER || $type == self::FLOAT_NUMBER || $type == self::BOOLEAN);
   }

   public static function isDateTime( $type )
   {
      return ($type == self::DATE || $type == self::TIME || $type == self::DATETIME);
   }


}

?>