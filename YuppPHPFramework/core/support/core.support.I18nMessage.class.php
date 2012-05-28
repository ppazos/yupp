<?php

// TODO: sera mas rapido ponerle los valores mediante el metodo a() o escribir los mensajes directamente en el campo messages 
// (haciendolo publico o protected y que los mensajes de las aplicaciones hereden de el).

class I18nMessage
{
   /**
    * Mapping: key -> lang -> mensaje
    */
   protected $messages = array();
   
   // Singleton
   protected function I18nMessage() {}

   private static $instance = NULL;
   public static function getInstance()
   {
      if (self::$instance === NULL) self::$instance = new I18nMessage();
      return self::$instance;
   }
   // /Singleton
   
   // add
   public function a( $key, $lang, $message )
   {
      $a1 = &$this->messages[ $key ];
      if ( $a1 === NULL ) // Es raro, sin poner esto y solo haciendo $a1[lang] = message ya me crea el array en $a1[lang].
      {
         $a1 = array();
      }
      $a1[ $lang ] = $message;
   }

   // get
   public function g( $key, $lang, $default = NULL )
   {
      $a1 = &$this->messages[$key];
      if ( $a1 !== NULL ) // Es raro, sin poner esto y solo haciendo $a1[lang] = message ya me crea el array en $a1[lang].
      {
         $a2 = &$a1[$lang];
         if ( $a2 !== NULL ) return $a2;
          
         // No encuentra el lang asi como me lo pasan, si me pasan uno con forma de locale tipo es_MX_AAA
         // Tengo que desglozarlo y buscar por "es_MX" y si no encuentro, por "es".
          
         //print_r(explode("_", $lang ));
          
         //list($locale_lang, $locale_country, $locale_variant) = explode("_", $lang );
         //list($locale_lang, $locale_country) = explode("_", $lang );
          
         $arr_locale = explode("_", $lang ); // [0]=>lang, [1]=>country, [2]=>variant
          
         //echo "LOCALE LANG: " . $locale_lang . "<br/>";
         //echo "LOCALE COUNTRY: " . $locale_country . "<br/>";
         //echo "LOCALE VARIANT: " . $locale_variant . "<br/>";
          
         //if ( $locale_variant === NULL ) echo "VARIANT NULL<br/>"; // Variant es null si no lo pasan.
          
         // Si vino solo el lenguaje, y no esta el lenguaje retorno segun criterio comun, default si esta y si no la misma key. 
         if ( !isset($arr_locale[1]) && !isset($arr_locale[2]))
         //if ( $locale_country === NULL && $locale_variant === NULL)
         {
            if ( !empty($default) ) return $default;
            return $key;
         }
         else if ( isset($arr_locale[1]) && !isset($arr_locale[2])) // vino locale_lang+locale_country => pruebo solo con locale_lang.
         {
             // FIXME: no usa country
             //echo "VIENE $lang, PRUEBA $locale_lang<br/>";
             $a2 = &$a1[$arr_locale[0]];
             if ( $a2 !== NULL ) return $a2;
             
             if ( !empty($default) ) return $default;
             return $key;
         }
         else // vinieron lang, coutnry y variant, tengo que probar lang+country y lang solo.
         {
             // FIXME: no usa variant
             //echo "VIENE $lang, PRUEBA $locale_lang _ $locale_country<br/>";
             $a2 = &$a1[$arr_locale[0]."_".$arr_locale[1]];
             if ( $a2 !== NULL ) return $a2;
             
             //echo "VIENE $lang, PRUEBA $locale_lang<br/>";
             $a2 = &$a1[$arr_locale[0]];
             if ( $a2 !== NULL ) return $a2;
             
             if ( !empty($default) ) return $default;
             return $key;
         }          
      }
      
      if ( !empty($default) ) return $default;
      return $key;
   }
}


/* TEST

$m = new I18nMessage();

$m->a( "blog.entrada.list.title", "es", "Listado de entradas" );
$m->a( "blog.entrada.list.label.entrada", "es", "Entrada" );
$m->a( "blog.entrada.list.action.agregar", "es", "Agregar entrada" );

$m->a( "blog.entrada.list.title", "en", "Entry list" );
$m->a( "blog.entrada.list.label.entrada", "en", "Entry" );
$m->a( "blog.entrada.list.action.agregar", "en", "Add entry" );

echo "<pre>";
print_r( $m );
echo "</pre>";

$time_start = microtime(true);
echo $m->g("blog.entrada.list.title", "es"); // Clave existe en el idioma
echo "<br/>";
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Tarda $time seconds<br/><br/>";

$time_start = microtime(true);
echo $m->g("blog.entrada.list.title", "es_MX"); // Clave existe, idioma no, pero deberia buscar tambien por "es"
echo "<br/>";
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Tarda $time seconds<br/><br/>";

$time_start = microtime(true);
echo $m->g("blog.entrada.list.title", "es_MX_kaka"); // Clave existe, idioma no, pero deberia buscar tambien por "es" y por "es_MX"
echo "<br/>";
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Tarda $time seconds<br/><br/>";

$time_start = microtime(true);
echo $m->g("pepe", "es"); // Clave no existe y no paso mensaje por defecto, entonces devuelve la clave
echo "<br/>";
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Tarda $time seconds<br/><br/>";

$time_start = microtime(true);
echo $m->g("carlox", "es", "Mensaje pro defecto"); // Clave no existe y paso mensaje por defecto, devuelve mensaje por defecto.
echo "<br/>";
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Tarda $time seconds<br/><br/>";

*/

?>