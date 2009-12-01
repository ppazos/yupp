<?php

/*
 * Helpers para el view.
 */

function h( $name, $paramsMap = array() )
{
   return Helpers::$name($paramsMap);
}

/**
 * Se utiliza como callback de array_filter, para sacar NULLs de un array, pero dejar valores validos como 0 (cero).
 */
function notNull( $value )
{
   return $value !== NULL;
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
        
        if ( array_key_exists('action', $paramsMap) ) // Si no me lo pasan, tengo que tirar una except. (es obligatorio)
           $action = $paramsMap['action']; 
        else
           throw new Exception("El parametro 'action' es obligatorio y no esta presente. " . __FILE__ . " " . __LINE__);

        // Saco los que ya use...
        $paramsMap['component']  = NULL;
        $paramsMap['controller'] = NULL;
        $paramsMap['action']     = NULL;
        
        $params = array_filter($paramsMap, "notNull"); // Saca nulls // ['params']; // opcional, es un mapa.
                                                       // FIXED: si tengo un 0 que es un valor valido par un param, me lo saca tambien!
                                                       // Ahora con callback notNull, el valor 0 se queda en el array. 

        // debe ser un array!
        $params_url = "";
        $params_in_url = array();
        foreach ($params as $key => $value) // FIXME: hay una funcion de PHP que ya hace esto...
        {
           // armo: key=val&key=val&...=val
           // FIXME: php tiene una funcion para hacer esto. (poner params en una url)
           if ( String::startsWith($key, "_param_") ) $params_in_url[ substr($key, 7) ] = $value; // agrega los _param_x en orden.
           else $params_url .= $key ."=". $value ."&";
        }
        $params_url = substr($params_url, 0, -1);

        $params_in_url_str = "";
        foreach ($params_in_url as $value) // FIXME: hay una funcion de PHP que ya hace esto...
        {
           $params_in_url_str .= "/" . $value;
        }

        return $_base_dir ."/". $component ."/". $controller ."/". $action . $params_in_url_str . ((strcmp($params_url,"") != 0)? ("?". $params_url) : "");
    }

    /**
     * Este no se usa, esta solo como prueba.
     */
    public static function link($paramsMap)
    {
       // Deberia chekear nomnbre de componente, controller, action. (se hace en url)
       $body = $paramsMap['body'];
       $paramsMap['body'] = NULL;
       
    	 return '<a href="'. self::url(array_filter($paramsMap, "notNull")) .'">'. $body .'</a>';
    }
    
    /**
     * Paginador para listados.
     */
    public static function pager($paramsMap)
    {
       //print_r($paramsMap);
       
       // Agrega params de ordenamiento al paginador.
       $model = Model::getInstance();
       $paramsMap['dir']  = $model->get('dir'); // puede no estar
       $paramsMap['sort'] = $model->get('sort'); // puede no estar
      
      
       $bodyPrev = (isset($paramsMap['bodyPrev'])) ? $paramsMap['bodyPrev'] : "Previo";
       $paramsMap['bodyPrev'] = NULL;
       $bodyNext = (isset($paramsMap['bodyNext'])) ? $paramsMap['bodyNext'] : "Siguiente";
       $paramsMap['bodyNext'] = NULL;
       
       $offset = (isset($paramsMap['offset'])) ? $paramsMap['offset'] : '0';
       $max    = (isset($paramsMap['max']))    ? $paramsMap['max'] : '10';
       
       if (!isset($paramsMap['count'])) throw new Exception("El parametro 'count' es obligatorio y no aparece en la lista de parametros");
       $count  = $paramsMap['count'];
       $paramsMap['count'] = NULL;
       
       // El helper link necesita la accion, le paso la accion actual.
       $ctx = YuppContext::getInstance();
       $paramsMap['action'] = $ctx->getAction();
       
       $linkPrev = "";
       if ( $offset - $max >= 0 ) // Si no esta en la primer pagina, puedo volver para atras.
       {
          // Link previo
          $paramsMap['body'] = $bodyPrev;
          $paramsMap['offset'] = $offset-$max;
          $linkPrev = '[ '. self::link($paramsMap) . ' ] ';
       }
       
       // pagina actual / cantidad de paginas
       $pagerState = (floor($offset/$max) + 1) .' / '. (($count==0) ? '1' : ceil($count/$max));
       
       $linkNext = "";
       if ( $offset + $max < $count ) // Si no esta en la ultima pagina, puede ir para adelante.
       {
          // Link Siguiente
          $paramsMap['body'] = $bodyNext;
          $paramsMap['offset'] = $offset+$max;
          $linkNext = ' [ '. self::link($paramsMap).' ] ';
       }
       
       return $linkPrev . $pagerState . $linkNext;
    }
    
    public static function ajax_link($paramsMap)
    {
       $before = (array_key_exists('before',$paramsMap)) ? $paramsMap['before'] : NULL;
       unset($paramsMap['before']);
       
       $update = (array_key_exists('update',$paramsMap)) ? $paramsMap['update'] : NULL;
       unset($paramsMap['update']);
       
       // callback no es necesario, usando updater y sobreescribiendolo funciona bien.
       // Igual si no usa updater y quiere usar request, el callback se puede hacer.
       $callback = (array_key_exists('after',$paramsMap)) ? $paramsMap['after'] : NULL;
       unset($paramsMap['after']);

       $body = (array_key_exists('body',$paramsMap)) ? $paramsMap['body'] : NULL;
       unset($paramsMap['body']);
       
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

          // Saco los que ya use...
          $params['component']  = NULL;
          $params['controller'] = NULL;
          $params = array_filter($params); // Saca nulls // ['params']; // opcional, es un mapa.

          // /Persistent/components/blog/views/entradaBlog/details.template.php
          //
          //$url = $_base_dir ."/components/". $component ."/views/". $controller;
          $url = "./components/". $component ."/views/". $controller;
          
       } // template

       $params_url = "";
       foreach ($params['args'] as $argname => $argvalue)
       {
          $$argname = $argvalue; // Declaro variables con los nombres pasados en los args.
       }
       
       include($url . "/" . $params['name'] . ".template.php");
    }
 
    
    /**
     * @param params array de argumentos:
     *   component: nombre del componente donde se encuentra la imagen.
     *   src: path de la imagen a partir del directorio del imagenes del componente (si viene "component")
     *        o desde el directorio /images, incluye el nombre de la imagen. Es obligatorio.
     *   w: ancho de la imagen en pixels.
     *   h: alto de la imagen en pixels.
     *   text: texto alternativo a la imagen.
     *   
     */
    public static function img( $params )
    {
       global $_base_dir;
       
       if ( !array_key_exists('src', $params) ) throw new Exception( __FILE__ . "(".__LINE__.") : parametro 'src' es obligatorio y no esta presente.");
       
       // Busca la ubicacion en un componente particular
       if ( array_key_exists('component', $params) ) 
          $res = '<img src="'. $_base_dir .'/components/'. $params['component'] .'/images/'. $params['src'] .'"';
       else // Ubicacion por defecto de todos los javascripts de todos los modulos
          $res = '<img src="'. $_base_dir .'/images/'. $params['src'] .'"';
       
       if ( isset($params['w']) )    $res .= ' width="'.  $params['w'] .'"';
       if ( isset($params['h']) )    $res .= ' height="'. $params['h'] .'"';
       if ( isset($params['text']) ) $res .= ' alt="'.    $params['text'] .'"';
       
       return $res . "/>";
    }
    
    
    public static function js( $params )
    {
       global $_base_dir;
       
       // Busca la ubicacion en un componente particular
       if ( array_key_exists('component', $params) ) 
          $res = '<script type="text/javascript" src="'. $_base_dir .'/components/'. $params['component'] .'/javascript/'. $params['name'] .'.js"></script>';
       else // Ubicacion por defecto de todos los javascripts de todos los modulos
          $res = '<script type="text/javascript" src="' . $_base_dir . '/js/' . $params['name'] . '.js"></script>';
       
       return $res;
    }
    
    public static function css( $params )
    {
       global $_base_dir;
       
       if ( array_key_exists('component', $params) ) 
          $res = '<link type="text/css" rel="stylesheet" href="'. $_base_dir .'/components/'. $params['component'] .'/css/'. $params['name'] .'.css" />';
       else
          $res = '<link type="text/css" rel="stylesheet" href="'. $_base_dir .'/css/'. $params['name'] .'.css" />';
       
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
       
       $res .= '</select>';
       $res .= '<input type="hidden" name="back_component"  value="'. $ctx->getComponent() .'" />';
       $res .= '<input type="hidden" name="back_controller" value="'. $ctx->getController() .'" />';
       $res .= '<input type="hidden" name="back_action"     value="'. $ctx->getAction() .'" />';
       
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
    
    /**
     * Helper para crear titulos de columnas ordenables en los listados.
     * 
     * attr es el nombre del atributo por la que se va a verficar el orden.
     * sort es el nombre del atributo por el que se esta ordenando actualmente.
     * 
     */
    public static function orderBy($params)
    {
       $model = Model::getInstance();
       $sort = $model->get('sort'); // puede ser vacio //$params['sort']; // sort actual
       $current_dir = $model->get('dir');
       
       // TODO> si hay max y offset, conservarlos.
       
       $dir = 'asc';
       if ( $sort === $params['attr'] )
       {
          if ( $current_dir === 'asc' )
          {
             $dir = 'desc';
             //$params['class'] = 'order_desc'; // para CSS FIXME: me lo pone como params de la url, no en la tag.
          }
          else
          {
             //$params['class'] = 'order_asc'; // para CSS FIXME: me lo pone como params de la url, no en la tag.
          }
       }

       //$res .= '<a href="'. Helpers::params2url( array('sort'=>$attr, 'dir'=>$dir) ) .'">'; // TODO: no tengo acceso a los params desde helpers.
       //$res .= $attr; // TODO: Habria que ver si esto es i18n, deberia haber algun "display name" asociado al nombre del campo.
       //$res .= '</a>';
       
       // Para mantener el paginado.
       $params['offset'] = $model->get('offset'); // puede no estar
       $params['max'] = $model->get('max'); // puede no estar
       $params['dir'] = $dir;
       $params['sort'] = $params['attr'];
       $res = self::link( $params );
       
       return $res;
    }
}
?>