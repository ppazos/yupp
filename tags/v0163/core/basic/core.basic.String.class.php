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
   function filterCharacters($string)
   {
      // Si se ve raro es porque el visor esta mal configurado en su enconding type, este string tiene
      // primero las vocales acentuadas, luego las vocales con dieresis y por ultimo la enie.
      // OTRA FORMA DE ESPECIFICAR LOS CARACTERES EN ES NOTACION OCTAL. (CUIDADO, SI SE EDITA y SALVA PUEDE GUARDAR MAL ESTOS CARACTERES).
      // http://weblogtoolscollection.com/b2-img/convertaccents.phps
      return strtr($string,
                   "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ",
                   "AAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
   }
   
   function toUnderscore($string)
   {
     //return preg_replace("/[A-Z ]/", "/[a-z_]/", $string);
    return strtr($string,
                 "ABCDEFGHIJKLMNOPQRSTUVWXYZ ", 
                 "abcdefghikklmnopqrstuvwxyz_");
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

}
?>