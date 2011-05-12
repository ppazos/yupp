<?php

class LayoutManager {
	
   private function __construct() {}
   
   private static $instance = NULL;
   
   public static function getInstance()
   {
      if (is_null(self::$instance)) self::$instance = new LayoutManager();
      return self::$instance;
   }
   
   
   // Registro de las librerias javscript referenciadas y que deben ser incluidas en el header de la vista.
   // El helper js es quien hace el registro.
   private $referencedJSLibs = array();
   
   /**
    * @param params array asociativo con los valores
    *  - app (opcional) nombre de la app donde esta la libreria
    *  - name (obligatorio) nombre de la libreria JS 
    */
   public function addJSLibReference( $params )
   {
      global $_base_dir;
      
      // TODO: verificar que existe el directorio referenciado
      
      $path = $_base_dir;
      
      // Busca la ubicacion en una app particular
      if ( array_key_exists('app', $params) ) 
         $path .= '/apps/'. $params['app'] .'/javascript/'. $params['name'] .'.js';
      else // Ubicacion por defecto de todos los javascripts de todos los modulos
         $path .= '/js/'. $params['name'] .'.js';
      
      /*
      if (!in_array($path, $this->referencedJSLibs))
         $this->referencedJSLibs[] = $path;
      
      if (isset($params['directInclude'])) echo '<script type="text/javascript" src="'. $path .'"></script>';
      */
      
      // Cambio que permite incluir JS desde layout (imprime directamente la referencia).
      //if (!in_array($path, $this->referencedJSLibs)) // no importa la path, puede ser la misma lib y estar en paths distintas, el id es por el nombre
      // FIXME: el nombre puede incluir una path, por lo que la lib puede ser la misma, pero si esta en distintas paths, dar distinto.
      // p.e. si por un lado se incluye "jquery/jquery-1.5.1.min" y por otro solo "jquery-1.5.1.min" 
      if (!array_key_exists($params['name'], $this->referencedJSLibs))
      {
         $this->referencedJSLibs[$params['name']] = $path;
         echo '<script type="text/javascript" src="'. $path .'"></script>';
      }
   }
   
   /**
    * Obtiene el nombre de la principal libreria javscript incluida, null si no se ha incluido una libreria.
    * Sirve para obtener la implementacion correcta de los helpers javascript que dependen de la libreria js.
    * TODO: tal vez seria bueno retornar un nombre por defecto si es que no hay una lib incluida.
    */
   public function getJSLib()
   {
      //print_r($this->referencedJSLibs);
      // Array ( [0] => /YuppPHPFramework/js/jquery/jquery-1.3.1.min.js )
    
      $jslibs = array('prototype','jquery'); //,'mootools','dojo','yui');
      foreach ($this->referencedJSLibs as $jsname => $jslibpath)
      {
         foreach ($jslibs as $lib)
         {
            //if ( String::startsWith($jslib, $lib) )
            //echo $lib.' '.$jslibpath.'<br/>';
            if (strpos($jslibpath, $lib) !== false) // Si encuentra la libreria en algun lado de la ruta de inclusion.
            {
               return $lib;
            }
         }
      }
      
      return NULL;
   }
   
   
   public function renderWithLayout( $pagePath )
   {
      // TODO: utilizar $referencedJSLibs para meter en el header las librerias seleccionadas.
      // reglas:
      //  - la vista puede no tener header, si hay js y no hay header, agregar tambien el header.
      //  - se inlcuiran todos los archivos en el directorio referenciado, 
      //    con el potencial problema que un js deba ser incluido antes que
      //    otro y esa informacion no la maneja el framework. Una solucion es
      //    poner los Js en directorios distintos, en ese caso el directorio que
      //    sea referenciado primero sera el primero que se incluya, porque si se 
      //    mantienen el orden de inclusion de las libs en $referencedJSLibs.
      //  - ...
      //
      
      ob_start();
      
      // Precondicion: la pagina con esta path existe.
      // importa derecho la pagina...
      include_once( $pagePath );
      
      $view = ob_get_clean();
      
      //echo '<textarea style="width:600px;">';
      //echo $view;
      //echo '</textarea>';
      
   	//$coincidencias = explode("<head>", $view); // 0 -> html+layout, 1-> resto
      
      $layout = NULL;
      
      // Busco tag de layout... muuuyyyy lentoooo!!!!! lento con stripos...
      $pos = strpos($view, '<layout name="'); // <layout name="papichulo" />
      if ( $pos !== false) // tengo layout
      {
         $coincidencias = explode('<layout name="', $view); // 0-> html, 1-> " />+resto
         
         //echo '<textarea style="width:600px; height:400px;">';
         //print_r( $coincidencias );
         //echo '</textarea>';
         
         //$coincidencias = explode('" />', $coincidencias[1]); // MAL! puede haber mas /> que el que cierra el layout!
         //$layout = $coincidencias[0];
         
         $end_layout_pos = strpos($coincidencias[1], '" />');
         $layout = substr($view, $pos+14, $end_layout_pos);
         
         //$layout = substr($view, $pos+14); // 14 es el largo de <layout name="
         
//         echo '<textarea style="width:600px; height:400px;">';
//         print_r( $coincidencias );
//         echo '</textarea>';
         
         /*
          * coincidencias[0] => 
            <html>
          * coincidencias[1] => blog" />
            <head>
               <link type="text/css" rel="stylesheet" href="/Persistent/css/main.css"/>   </head>
            <body>
            ...
          */
         
         // substr($coincidencias[1], $end_layout_pos+4) // +4 para evitar el '" />', y cuenta a partir del " en coincidencias[1]
         $coincidencias = explode("</head>", substr($coincidencias[1], $end_layout_pos+4)); // 0-> head, 1->resto
         
         // coincidencias[0] tiene <head>
         $dirtyhead = explode("<head>", $coincidencias[0]);
         $head = $dirtyhead[1]; // saca el <head>
         
         
         //echo '<textarea style="height:800px; width:940px;">';
         //print_r( $dirtyhead );
         //print_r( $coincidencias );
         //echo '</textarea>';
         
         
         /*
          * coincidencias[0] => 
            <head>
               <link type="text/css" rel="stylesheet" href="/Persistent/css/main.css"/>   
            coincidencias[1] => 
            <body>
               <h1>Ingreso</h1>
               ...
          */
//         echo '<textarea style="width:600px; height:400px;">';
//         print_r( $coincidencias );
//         echo '</textarea>';
         


         // Inclusion de JS bajo demanda
         // http://code.google.com/p/yupp/issues/detail?id=32
         /* La inclusion se hace directamente en addJSLibReference
         foreach ( $this->referencedJSLibs as $path )
         {
            $head = '<script type="text/javascript" src="'. $path .'"></script>' . $head;
         }
         */
         
         
         // OJO! SI EL LAYOUT SE PONE EN EL HEAD, ESA TAG INVALIDA SE VA A MOSTRAR... TALVEZ SEA MEJOR PONERLA ARRIBA DEL TODO, ANTES DEL HTML, AUNQUE NO SEA UN XML valido...
         
//         echo "LAYOUT ";
//         echo '<textarea style="width:600px; height:400px;">';
//         echo $layout;
//         echo '</textarea>';

         $coincidencias = explode("<body>", $coincidencias[1]); // 0-> nada, 1-> body, /body /html
         $coincidencias = explode("</body>", $coincidencias[1]); // 0-> body, 1-> /html
         $body = $coincidencias[0];
         
         
         $ctx = YuppContext::getInstance();
         
         $path = 'apps/'. $ctx->getApp() .'/views/' . $layout . '.layout.php';
         if (!file_exists($path)) throw new Exception("El layout $layout no existe en la ruta: $path " . __FILE__ . " " . __LINE__);
         
         include_once( $path );
      }
      else
      {
         //echo "NO LAYOUT";
         //echo $view;
         
         // Inclusion de JS bajo demanda
         // http://code.google.com/p/yupp/issues/detail?id=32
         $partes = explode("<head>", $view);

         if (count($partes)>1)
            $partes[0] .= '<head>';// explode elimina el <head>, lo agrego.
         
         //$head = $coincidencias[0];
         
         // Inclusion de JS bajo demanda
         // http://code.google.com/p/yupp/issues/detail?id=32
         /* La inclusion se hace directamente en addJSLibReference
         foreach ( $this->referencedJSLibs as $path )
         {
            $partes[0] .= '<script type="text/javascript" src="'. $path .'"></script>';
         }
         */
         
         // FIXME: Si la pagina no esta bien formada aqui dara un error
         // p.e. si no se tiene html/head/body
         if (isset($partes[0]))
            echo $partes[0];
            
         if (isset($partes[1]))
            echo $partes[1];
         
         //echo '<textarea style="height:800px; width:940px;">';
         //print_r( $partes );
         //echo '</textarea>';
      }
   }
}

?>