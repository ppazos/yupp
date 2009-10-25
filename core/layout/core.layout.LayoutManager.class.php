<?php

class LayoutManager {
	
   public static function renderWithLayout( $pagePath )
   {
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
         $head = $coincidencias[0];
         
         
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
         
         //$pos2 = strpos($layout, '"');
         //$layout = substr($layout, 0, $pos2); // Quiero lo que esta entre  '<layout name="' y '"', ese es el nombre del layout.
         
         // OJO! SI EL LAYOUT SE PONE EN EL HEAD, ESA TAG INVALIDA SE VA A MOSTRAR... TALVEZ SEA MEJOR PONERLA ARRIBA DEL TODO, ANTES DEL HTML, AUNQUE NO SEA UN XML valido...
         
//         echo "LAYOUT ";
//         echo '<textarea style="width:600px; height:400px;">';
//         echo $layout;
//         echo '</textarea>';

         $coincidencias = explode("<body>", $coincidencias[1]); // 0-> nada, 1-> body, /body /html
         $coincidencias = explode("</body>", $coincidencias[1]); // 0-> body, 1-> /html
         $body = $coincidencias[0];
         
         
         $ctx = YuppContext::getInstance();
         
         $path = "components/". $ctx->getComponent() ."/views/" . $layout . ".layout.php";
         
         if (!file_exists($path)) throw new Exception("El layout $layout no existe en la ruta: $path " . __FILE__ . " " . __LINE__);
         
         include_once( $path );
      }
      else
      {
         //echo "NO LAYOUT";
      	echo $view;
      }
   }
   
}

?>