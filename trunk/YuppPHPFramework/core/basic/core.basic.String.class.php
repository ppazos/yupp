<?php

class String {

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

   public static function firstToUpper( $str )
   {
      if(false === function_exists('ucfirst')) // Podria no existir la funcion, segun la doc.
      {
         return strtoupper( substr($str, 0, 1) ) . substr($str, 1);
      }
      return ucfirst($str);
   }
   
   /**
    * Quita los caracteres que tengan tildes o las enies o dieresis, 
    * transformandolas en las mismas letras sin tildes o enies.
    */
   public static function filterCharacters($string)
   {
      // Si se ve raro es porque el visor esta mal configurado en su enconding type, este string tiene
      // primero las vocales acentuadas, luego las vocales con dieresis y por ultimo la enie.
      // OTRA FORMA DE ESPECIFICAR LOS CARACTERES EN ES NOTACION OCTAL. (CUIDADO, SI SE EDITA y SALVA PUEDE GUARDAR MAL ESTOS CARACTERES).
      // http://weblogtoolscollection.com/b2-img/convertaccents.phps
      //return strtr($string,
      //             "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ",
      //             "AAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
	   
	   $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                               'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                               'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                               'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                               'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
       return strtr( $string, $unwanted_array );
   }
   
   public static function removeNonLetterChars( $string )
   {
     return str_replace( array("?","¿"), array("",""), $string );
   }
   
   public static function toUnderscore($string)
   {
     //return preg_replace("/[A-Z ]/", "/[a-z_]/", $string);
     return strtr($string,
                 "ABCDEFGHIJKLMNOPQRSTUVWXYZ ", 
                 "abcdefghijklmnopqrstuvwxyz_");
     /*
     $pattern[0] = '/\&/';
     $pattern[1] = '/</';
     $pattern[2] = "/>/";
     $pattern[3] = '/\n/';
     $pattern[4] = '/"/';
     $pattern[5] = "/'/";
     $pattern[6] = "/%/";
     $pattern[7] = '/\(/';
     $pattern[8] = '/\)/';
     $pattern[9] = '/\+/';
     $pattern[10] = '/-/';
     $replacement[0] = '&amp;';
     $replacement[1] = '&lt;';
     $replacement[2] = '&gt;';
     $replacement[3] = '<br>';
     $replacement[4] = '&quot;';
     $replacement[5] = '&#39;';
     $replacement[6] = '&#37;';
     $replacement[7] = '&#40;';
     $replacement[8] = '&#41;';
     $replacement[9] = '&#43;';
     $replacement[10] = '&#45;';
     return preg_replace($pattern, $replacement, $string);
     */
   }
   
   // Si recibe un underscore notation, lo vuelve a camel case, o sea se puede hacer camel -> underscore -> camel y quedar el mismo.
   public static function toCamelCase( $string )
   {
      //$string = preg_replace('/_([a-z])/', strtoupper('$1'), $string);  // No funka

      // El problema es como hacer para saber si la primer letra es mayuscula o minuscula!!!!
      // Podemos usar conversiones e nlo que refiere a los atributos por ejemplo empiezan con minusculas!!!

      $busca = array("_a", "_b", "_c", "_d", "_e", "_f", "_g", "_h", "_i", "_j", "_k", "_l", "_m", "_n", "_o", "_p", "_q", "_r", "_s", "_t", "_u", "_v", "_w", "_x", "_y", "_z");
      $cambia = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");

      $string = str_replace($busca, $cambia, $string);

      return $string;
   }
   
   /*
    * public static function toUnderscoreNotation($string)
      {
          $string = preg_replace('/[\'"]/', '', $string); // Saca comillas
          //$string = preg_replace('/[^a-zA-Z0-9]+/', '_', $string); // Saca primer caracter a _ ???
    
          // Kiero tambien que reemplace las mayusculas por _minusculas... ESTO ES CONVERSIoN DE CAMEL CASE...
          $string = preg_replace('/([A-Z])/', '_$1', $string);
    
          $string = trim($string, '_'); // Si la primera era mayuscula, queda con un _ al principio.
          $string = strtolower($string);
    
          return $string;
      }
    */
   
   /**
    * Verifica si el string tiene formato de fecha.
	* FIXME: cuidado que tambien matchea datetimes.
    */
   public static function isDate( $string )
   {
      $pattern = '/\d\d\d\d-\d\d-\d\d/';
      return preg_match($pattern, $string, $matches);
   }
   
   /**
    * Verifica si el string tiene formato de fecha con tiempo.
    */
   public static function isDateTime( $string )
   {
      $pattern = '/\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d/';
      return preg_match($pattern, $string, $matches);
   }
}
?>