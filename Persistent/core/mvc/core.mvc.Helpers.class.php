<?php

/*
 * Helpers para el view.
 */

function h( $name, $paramsMap = array() )
{
   return Helpers::$name(&$paramsMap);
}

class Helpers {

    function __construct() {}
    
    private static $counter = 0;
    
    private static function getCounter()
    {
    	 $res = self::$counter;
       self::$counter++;
       return $res;
    }

    public static function url($paramsMap)
    {
        global $_base_dir;

        $ctx = YuppContext::getInstance();

        if ( array_key_exists('component', $paramsMap) ) // Si no me lo pasan, tengo que poner el actual.
           $component  = $paramsMap['component'];
        else
           $component = $ctx->getComponent();
           
        if ( array_key_exists('controller', $paramsMap) ) // Si no me lo pasan, tengo que poner el actual.
           $controller = $paramsMap['controller'];
        else
           $controller = $ctx->getController();
        
        
        $action = $paramsMap['action'];     // Obligatorio, si no, no se que hacer, o voy a la accion por defecto.

        // Saco los que ya use...
        $paramsMap['component']  = NULL;
        $paramsMap['controller'] = NULL;
        $paramsMap['action']     = NULL;
        
        
        //if ( array_key_exists('params', $paramsMap) )
        //{
           $params     = array_filter($paramsMap); // Saca nulls // ['params']; // opcional, es un mapa.
           
           //Logger::struct( $params, "Helpers.url" );
           
           // debe ser un array!
           $params_url = "";
           foreach ($params as $key => $value) // FIXME: hay una funcion de PHP que ya hace esto...
           {
              // armo: key=val&key=val&...=val
              $params_url .= $key ."=". $value ."&";
           }
           $params_url = substr($params_url, 0, -1);
        //}

        return $_base_dir ."/". $component ."/". $controller ."/". $action . ((strcmp($params_url,"") != 0)? ("?". $params_url) : "");
    }

    /**
     * Este no se usa, esta solo como prueba.
     */
    public static function link($paramsMap)
    {
       // Deberia chekear nomnbre de componente, controller, action. (se hace en url)
       $body = $paramsMap['body'];
       $paramsMap['body'] = NULL;
       
    	 return '<a href="'. self::url(array_filter($paramsMap)) .'">'. $body .'</a>';
    }
    
    public static function ajax_link($paramsMap)
    {
       $before = $paramsMap['before'];
       $paramsMap['before'] = NULL;
       
       $update = $paramsMap['update'];
       $paramsMap['update'] = NULL;
       
       // callback no es necesario, usando updater y sobreescribiendolo funciona bien.
       // Igual si no usa updater y quiere usar request, el callback se puede hacer.
       $callback = $paramsMap['after'];
       $paramsMap['after'] = NULL;

       $body = $paramsMap['body'];
       $paramsMap['body'] = NULL;
       
       /*
        new Ajax.Updater({ success: 'items', failure: 'notice' }, '/items', {
           parameters: { text: $F('text') },
           insertion: Insertion.Bottom
         });
        */
       // Prototype
//       $script = "new Ajax.Updater({ success: '".$update."' }, " .
//                     "'". self::url(array_filter($paramsMap)) ."', { " .
//                     "onLoading: $before ," .
//                     //"insertion: Insertion.Bottom" .
//                     //" onComplete: $callback " .
//                  "});";
        
       /*
        * new Ajax.Request(url, {
              method: 'get',
              onSuccess: function(transport) {
                var notice = $('notice');
                if (transport.responseText.match(/href="http:\/\/prototypejs.org/))
                  notice.update('Yeah! You are in the Top 10!').setStyle({ background: '#dfd' });
                else
                  notice.update('Damn! You are beyond #10...').setStyle({ background: '#fdd' });
              }
            });
        */
        
      // OK!!!
      $func = "ajax_link_". self::getCounter()."()";
        
      $script = "<script type=\"text/javascript\">
function $func {
   new Ajax.Request('". self::url(array_filter($paramsMap)) ."', {
                     onLoading: $before,   
                     onSuccess: $callback
   });
}
</script>";
       
       //$paramsMap['onClick'] = $script;
       
       //return '<a href="#" onClick="'. $script .'">'. $body .'</a>';
       return $script . '<a href="javascript:'. $func .'" target="_self">'. $body .'</a>'; // Tengo que pegar el script para que quede disponible.

       //return self::link(array_filter($paramsMap));
    }

    public static function params2url( $params )
    {
    	 if (!is_array( $params )) throw new Exception("Helpers.params2url: params debe ser un array y es un " . gettype($params));

       $pars = "?";
       foreach ($params as $key => $value)
       {
       	 $pars .= $key . "=". $value . "&";
       }

       return $pars;
    }
    
    
    /**
     * Muestra un template.
     */
    public static function template( $params )
    {
    	 global $_base_dir;
       
       if ( !array_key_exists('name', $params) ) throw new Exception('Helpers::template: parametro "name" es obligatorio y no esta presente.');
       if ( !array_key_exists('args', $params) || !is_array($params['args']) )  throw new Exception('Helpers::template: parametro "args" no puede ser null y ser un array.');
       
       $url = "";
       
       if ( array_key_exists('url', $params) )
       {
       	 $url = $params['url'];
          
          $params['url']     = NULL;
          $params = array_filter($params);
       }
       else
       {
          $ctx = YuppContext::getInstance();

          if ( array_key_exists('component', $params) ) // Si no me lo pasan, tengo que poner el actual.
             $component  = $params['component'];
          else
             $component = $ctx->getComponent();
              
          if ( array_key_exists('controller', $params) ) // Si no me lo pasan, tengo que poner el actual.
             $controller = $params['controller'];
          else
             $controller = $ctx->getController();
           
           
          //$action = $params['action'];     // Obligatorio, si no, no se que hacer, o voy a la accion por defecto.


          // Saco los que ya use...
          $params['component']  = NULL;
          $params['controller'] = NULL;
          $params = array_filter($params); // Saca nulls // ['params']; // opcional, es un mapa.

          // /Persistent/components/blog/views/entradaBlog/details.template.php
          //
          //$url = $_base_dir ."/components/". $component ."/views/". $controller;
          $url = "./components/". $component ."/views/". $controller;
       }

       
       $params_url = "";
       foreach ($params['args'] as $argname => $argvalue)
       {
          $$argname = $argvalue; // Declaro variables con los nombres pasados en los args.
       }
       
       //echo "URL: " . $url;
          
       include($url . "/" . $params['name'] . ".template.php");
       
    }
    
    
    public static function js( $params )
    {
       global $_base_dir;
       
       // Busca la ubicacion en un componente particular
       if ( $params['component'] !== NULL ) 
          $res = '<script type="text/javascript" src="' . $_base_dir . '/components/' . $params['component'] . '/javascript/' . $params['name'] . '.js"></script>';
       else // Ubicacion por defecto de todos los javascripts de todos los modulos
          $res = '<script type="text/javascript" src="' . $_base_dir . '/js/' . $params['name'] . '.js"></script>';
       
       return $res;
    }
    
    public static function css( $params )
    {
       global $_base_dir;
       
       $res = '<link type="text/css" rel="stylesheet" href="' . $_base_dir . '/css/' . $params['name'] . '.css"/>';
       
       return $res;
    }
    
    public static function locale_chooser( $params )
    {
       $ctx = YuppContext::getInstance();
       
       $config = YuppConfig::getInstance();
      
       $url = self::url( array('component' => 'core', 'controller' => 'core', 'action' => 'changeLocale') );
       $res = '<form action="'. $url .'" style="width:270px; margin:0px; padding:0px;">';
    	 $res .= '<select name="locale">';
       
       foreach ( $config->getAvailableLocales() as $locale )
       {
       	 $res .= '<option value="' . $locale . '" '. (($locale === $ctx->getLocale())?'selected="true"':'') .'>'. $locale . '</option>';
       }
       
       $res .= '<input type="hidden" name="back_component"  value="'. $ctx->getComponent() .'" />';
       $res .= '<input type="hidden" name="back_controller" value="'. $ctx->getController() .'" />';
       $res .= '<input type="hidden" name="back_action"     value="'. $ctx->getAction() .'" />';
       
       $res .= '</select>';
       
       $res .= '<input type="submit" value="Cambiar" />';
       $res .= '</form>';
       
       return $res;
    }
    
    
    /**
     * Select para modificar el modo de ejecucion.
     */
    public static function mode_chooser( $params )
    {
       $ctx    = YuppContext::getInstance();
       $config = YuppConfig::getInstance();
      
       $url = self::url( array('component' => 'core', 'controller' => 'core', 'action' => 'changeMode') );
       $res = '<form action="'. $url .'" style="width:270px; margin:0px; padding:0px;">';
       $res .= '<select name="mode">';
       
       foreach ( $config->getAvailableModes() as $mode )
       {
          $res .= '<option value="' . $mode . '" '. (($mode === $ctx->getMode())?'selected="true"':'') .'>'. $mode . '</option>';
       }
       
       $res .= '<input type="hidden" name="back_component"  value="'. $ctx->getComponent() .'" />';
       $res .= '<input type="hidden" name="back_controller" value="'. $ctx->getController() .'" />';
       $res .= '<input type="hidden" name="back_action"     value="'. $ctx->getAction() .'" />';
       
       $res .= '</select>';
       
       $res .= '<input type="submit" name="Cambiar" />';
       $res .= '</form>';
       
       return $res;
    }
}
?>