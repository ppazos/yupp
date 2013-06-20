<?php

class THelpers {
   
   /**
    * Muestra una tag img con la direccion de un gravatar para el usuario logueado.
    * https://es.gravatar.com/site/implement/images/
    * 
    * @param $size indica las dimensiones de la imagen 1..512
    * @param $default indica el codigo gravatar o la url de la imagen por defecto sino se encuentra el gravatar para el email del usuario logueado.
    */
   public static function gravatar($size = 40, $user = NULL, $default = 'mm')
   {
       if ($user == NULL)
       {
          $user = TUser::getLogged();
       }
       
       // Si no le paso usuario y ademas no hay usuario logueado
       if ($user == NULL)
       {
          throw new Exception('Deberia haber un usuario logueado o pasar un usuario por parametro');
       }
       
       $hash = md5( strtolower( trim( $user->getEmail() ) ) );
       
       // $size <= 512 && $size > 0
       if ($size > 512) $size = 512;
       if ($size <= 0) $size = 40;
       
       echo '<img src="http://www.gravatar.com/avatar/'.$hash.'.jpg?s='.$size.'&d='.$default.'" />';
   }

}
?>