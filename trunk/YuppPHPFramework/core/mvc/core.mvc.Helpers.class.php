<?php

/**
 * Helpers para el view.
 */

/**
 * Funcion global para llamadas cortas.
 * TODO: mover a un archivo de script 'shortcuts.script.php' y que el usuario deba incuirlo si quiere usar los shortcuts.
 */
function h( $name, $paramsMap = array() )
{
   // TODO: agregar un tercer parametro para indicar si se hace o no echo del resultado.
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

        if ( array_key_exists('app', $paramsMap) ) // Si no me lo pasan, tengo que poner el actual.
           $app = $paramsMap['app'];
        else
           $app = $ctx->getApp();
           
        if ( array_key_exists('controller', $paramsMap) ) // Si no me lo pasan, tengo que poner el actual.
           $controller = $paramsMap['controller'];
        else
           $controller = $ctx->getController();
        
        if ( array_key_exists('action', $paramsMap) ) // Si no me lo pasan, tengo que tirar una except. (es obligatorio)
           $action = $paramsMap['action']; 
        else
           throw new Exception("El parametro 'action' es obligatorio y no esta presente. " . __FILE__ . " " . __LINE__);

        // Saco los que ya use...
        $paramsMap['app']  = NULL;
        $paramsMap['controller'] = NULL;
        $paramsMap['action']     = NULL;
        
        // Parametros para la url
        $params_url = "";
        
        // Si viene un array de params explicito, tambien va para la url
        if (isset($paramsMap['params']) && is_array($paramsMap['params']))
        {
           foreach ($paramsMap['params'] as $key => $value) // FIXME: hay una funcion de PHP que ya hace esto...
           {
              $params_url .= $key ."=". $value ."&";
           }
           
           $paramsMap['params'] = NULL; // Para que filtre
        }
        
        
        $params = array_filter($paramsMap, "notNull"); // Saca nulls // ['params']; // opcional, es un mapa.
                                                       // FIXED: si tengo un 0 que es un valor valido par un param, me lo saca tambien!
                                                       // Ahora con callback notNull, el valor 0 se queda en el array. 

        // debe ser un array!
        $params_in_url = array();
        foreach ($params as $key => $value) // FIXME: hay una funcion de PHP que ya hace esto...
        {
           // armo: key=val&key=val&...=val
           // FIXME: php tiene una funcion para hacer esto. (poner params en una url)
           if ( String::startsWith($key, "_param_") ) $params_in_url[ substr($key, 7) ] = $value; // agrega los _param_x en orden.
           else $params_url .= $key ."=". $value ."&";
        }
        
        // Saco el & sobrante de los params
        $params_url = substr($params_url, 0, -1);

        $params_in_url_str = "";
        foreach ($params_in_url as $value) // FIXME: hay una funcion de PHP que ya hace esto...
        {
           $params_in_url_str .= "/" . $value;
        }

        return $_base_dir ."/". $app ."/". $controller ."/". $action . $params_in_url_str . ((strcmp($params_url,"") != 0)? ("?". $params_url) : "");
    }

    /**
     * Devuelve una tag anchor.
     */
    public static function link($paramsMap)
    {
       // Deberia chekear nombre de la app, controller, action. (se hace en url)
       if (!isset($paramsMap['body'])) throw new Exception('El array de parametros debe contener la clave "body"');
       
       $body = $paramsMap['body'];
       $paramsMap['body'] = NULL;
       
       // Soporte para attrs en el link
       $attrs = ((isset($paramsMap['attrs']))?$paramsMap['attrs']:array());
       $paramsMap['attrs'] = NULL;
       
       $strattrs = '';
       foreach ($attrs as $name=>$val)
       {
          $strattrs .= ' '. $name .'="'. $val .'"';
       }
       // /Soporte para attrs en el link
       
       return '<a href="'. self::url(array_filter($paramsMap, "notNull")) .'"'. $strattrs .'>'. $body .'</a>';
    }
    
    /**
     * Paginador para listados.
     */
    public static function pager($paramsMap)
    {
       // Agrega params de ordenamiento al paginador.
       $model = Model::getInstance();
       $paramsMap['dir']  = $model->get('dir'); // puede no estar
       $paramsMap['sort'] = $model->get('sort'); // puede no estar
      
       $bodyPrev = (isset($paramsMap['bodyPrev'])) ? $paramsMap['bodyPrev'] : "Previo";
       $paramsMap['bodyPrev'] = NULL;
       $bodyNext = (isset($paramsMap['bodyNext'])) ? $paramsMap['bodyNext'] : "Siguiente";
       $paramsMap['bodyNext'] = NULL;
       
       // Las reglas para filtrar offset y max estan en PersistentObject::filtrarParams
       //$offset = (isset($paramsMap['offset'])) ? $paramsMap['offset'] : '0';
       //$max    = (isset($paramsMap['max']))    ? $paramsMap['max'] : '50';
       $filteredParams = PersistentObject::filtrarParams(new ArrayObject($paramsMap));
       $offset = $filteredParams['offset'];
       $max = $filteredParams['max'];
       
       if (!isset($paramsMap['count'])) throw new Exception("El parametro 'count' es obligatorio y no aparece en la lista de parametros");
       $count  = $paramsMap['count'];
       $paramsMap['count'] = NULL;
       
       // El helper link necesita la accion, le paso la accion actual.
       $ctx = YuppContext::getInstance();
       $paramsMap['action'] = $ctx->getAction();
       
       // Agregado de params de filtrado a links (http://code.google.com/p/yupp/issues/detail?id=49)
       $_params = $model->getAll(); // params + lo que metio el controller como model para la view
       foreach ($_params as $k=>$v)
       {
          if (String::startsWith($k, 'filter_'))
          {
             $paramsMap[$k] = $v;
          }
       }
       
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
    
    /**
     * Operacion auxiliar para generar el HTML y JS del helper ajax_link para prototype.
     */
    private static function ajax_link_prototype($paramsMap, $body, $before, $callback)
    {
       // Soporte para attrs en el link
       $attrs = ((isset($paramsMap['attrs']))?$paramsMap['attrs']:array());
       $paramsMap['attrs'] = NULL;
       
       $strattrs = '';
       foreach ($attrs as $name=>$val)
       {
          $strattrs .= ' '. $name .'="'. $val .'"';
       }
       // /Soporte para attrs en el link
       
       /**
        * Depende de prototype, con esto me aseguro que se incluye en LayoutManager.
        */
       self::js( array("name" => "prototype_170") ); // FIXME: en 1.7 no parsea bien el JSON string, lo dejo en 1.6.1.
       
       $func = "ajax_link_". self::getCounter()."()";
        
       $script = "<script type=\"text/javascript\">
function $func {
   new Ajax.Request('". self::url(array_filter($paramsMap)) ."', {";
       if ($before != NULL) $script .= "onLoading: $before,";   
       if ($callback != NULL) $script .= "onSuccess: $callback";
       $script .= "
   });
}
</script>";
       
       return $script .'<a href="javascript:'. $func .'" target="_self" '. $strattrs .'">'. $body .'</a>'; // Tengo que pegar el script para que quede disponible.
    }
    
    private static function ajax_link_jquery($paramsMap, $body, $before, $callback)
    {
       // Soporte para attrs en el link
       $attrs = ((isset($paramsMap['attrs']))?$paramsMap['attrs']:array());
       $paramsMap['attrs'] = NULL;
       
       $strattrs = '';
       foreach ($attrs as $name=>$val)
       {
          $strattrs .= ' '. $name .'="'. $val .'"';
       }
       // /Soporte para attrs en el link
        
       /**
        * Depende de prototype, con esto me aseguro que se incluye en LayoutManager.
        */
       self::js( array("name" => "jquery/jquery-1.7.1.min") );
       
       $func = "ajax_link_". self::getCounter()."()";
        
       $script = "<script type=\"text/javascript\">
function $func {
   $.ajax({
      url: '". self::url(array_filter($paramsMap)) ."',";
       if ($before != NULL) $script .= "beforeSend: $before,";
       if ($callback != NULL) $script .= "success: $callback";
       $script .= "
   });
}
</script>\n\n";
       
       return $script .'<a href="javascript:'. $func .'" target="_self" '. $strattrs .'">'. $body .'</a>'; // Tengo que pegar el script para que quede disponible.
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
       
       $lm = LayoutManager::getInstance();
       $jslib = $lm->getJSLib();
       if ($jslib === NULL) $jslib = 'prototype'; // Libreria por defecto.
       
       eval('$ret = self::ajax_link_'.$jslib.'($paramsMap, $body, $before, $callback);');
       
       return $ret;
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
       
       $url = '';
       $path = '';
       
       if ( array_key_exists('url', $params) )
       {
          $url = $params['url'];
          $params['url'] = NULL;
          $params = array_filter($params);
       }
       else
       {
          $ctx = YuppContext::getInstance();

          if ( array_key_exists('app', $params) ) // Si no me lo pasan, tengo que poner el actual.
             $app  = $params['app'];
          else
             $app = $ctx->getApp();
              
          if ( array_key_exists('controller', $params) ) // Si no me lo pasan, tengo que poner el actual.
             $controller = $params['controller'];
          else
             $controller = $ctx->getController();

          // Nuevo: path entre el directorio de vistas y donde se ubica el template
          if ( array_key_exists('path', $params) )
             $path = $params['path'] . '/';

          // Saco los que ya use...
          $params['app']  = NULL;
          $params['controller'] = NULL;
          $params = array_filter($params); // Saca nulls // ['params']; // opcional, es un mapa.

          //$url = $_base_dir .'/apps/'. $app .'/views/'. $controller;
          $url = './apps/'. $app .'/views/'. $controller;
          
       } // template
       
       $params_url = "";
       if (isset($params['args'])) // si viene un array vacio, array_filter lo saca.
       {
          foreach ($params['args'] as $argname => $argvalue)
          {
             $$argname = $argvalue; // Declaro variables con los nombres pasados en los args.
          }
       }
       
       include($url .'/'. $path . $params['name'] .'.template.php');
    }
 
    
    /**
     * Se utiliza para mostrar la tag img para una imagen local, resolviendo su src de forma automatica.
     * 
     * @param params array de argumentos:
     *   app: nombre de la app donde se encuentra la imagen.
     *   src: path de la imagen a partir del directorio del imagenes de la app (si viene "app")
     *        o desde el directorio /images, incluye el nombre de la imagen. Es obligatorio.
     *   w: ancho de la imagen en pixels.
     *   h: alto de la imagen en pixels.
     *   text: texto alternativo a la imagen.
     *   
     */
    public static function img( $params )
    {
       global $_base_dir;
       
       if ( !array_key_exists('src', $params) ) throw new Exception( __FILE__ . '('.__LINE__.') : parametro "src" es obligatorio y no esta presente.');
       
       $src = NULL;
       
       // Busca la ubicacion en una app particular
       if ( array_key_exists('app', $params) ) 
          $src = '/apps/'. $params['app'] .'/images/'. $params['src'];
       else // Ubicacion por defecto de todos los javascripts de todos los modulos
          $src = '/images/'. $params['src'];
       
       unset($params['app']);
       unset($params['src']);
       
       // FIXME: retornar una imagen por defecto
       if (!file_exists('./'.$src)) throw new Exception('La imagen '. $src .' no existe');
       
       $res = '<img src="'. $_base_dir . $src .'"';
       if ( isset($params['w']) )
       {
          $res .= ' width="'. $params['w'] .'"';
          unset($params['w']);
       }
       if ( isset($params['h']) )
       {
          $res .= ' height="'. $params['h'] .'"';
          unset($params['h']);
       }
       if ( isset($params['text']) )
       {
          $res .= ' alt="'. $params['text'] .'"';
          unset($params['text']);
       }
       
       // No usa attrs explicito, todo lo extra en los params lo pone como attrs.
       foreach ($params as $name=>$value)
       {
          $res .= ' '. $name .'="'. $value .'"';
       }
       
       return $res . "/>";
    }
    
    
    /**
     * @param params array asociativo con los valores
     *  - app (opcional) nombre de la app donde esta la libreria
     *  - name (obligatorio) nombre de la libreria JS 
     */
    public static function js( $params )
    {
       // Registra la libreria en LayoutManager en lugar de retornar un string...
       LayoutManager::getInstance()->addJSLibReference( $params );
       
       /*
       // Busca la ubicacion en una app particular
       if ( array_key_exists('app', $params) ) 
          $res = '<script type="text/javascript" src="'. $_base_dir .'/apps/'. $params['app'] .'/javascript/'. $params['name'] .'.js"></script>';
       else // Ubicacion por defecto de todos los javascripts de todos los modulos
          $res = '<script type="text/javascript" src="' . $_base_dir . '/js/' . $params['name'] . '.js"></script>';
       
       return $res;
       */
       return ''; // FIXME: no retornar nada
    }
    
    public static function css( $params )
    {
       global $_base_dir;
       
       if ( array_key_exists('app', $params) ) 
          $res = '<link type="text/css" rel="stylesheet" href="'. $_base_dir .'/apps/'. $params['app'] .'/css/'. $params['name'] .'.css" />';
       else
          $res = '<link type="text/css" rel="stylesheet" href="'. $_base_dir .'/css/'. $params['name'] .'.css" />';
       
       return $res;
    }
    
    public static function locale_chooser($params = array())
    {
       $ctx = YuppContext::getInstance();
       
       $url = self::url( array('app'=>'core', 'controller'=>'core', 'action'=>'changeLocale') );
       $res = '<form action="'. $url .'" class="locale_chooser">';
       $res .= '<select name="locale">';
       
       if (isset($params['langs']))
       {
          $langs = $params['langs'];
       }
       else
       {
          $config = YuppConfig::getInstance();
          $langs = $config->getAvailableLocales();
       }
       
       foreach ($langs as $locale)
       {
          $res .= '<option value="' . $locale . '" '. (($locale === $ctx->getLocale())?'selected="true"':'') .'>'. $locale . '</option>';
       }
       
       $res .= '</select>';
       $res .= '<input type="hidden" name="back_app" value="'. $ctx->getApp() .'" />';
       $res .= '<input type="hidden" name="back_controller" value="'. $ctx->getController() .'" />';
       $res .= '<input type="hidden" name="back_action" value="'. $ctx->getAction() .'" />';
       $res .= '<input type="submit" value="Cambiar" />'; // FIXME: i18n
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
      
       $url = self::url( array('app' => 'core', 'controller' => 'core', 'action' => 'changeMode') );
       $res = '<form action="'. $url .'" style="width:270px; margin:0px; padding:0px;">';
       $res .= '<select name="mode">';
       
       foreach ( $config->getAvailableModes() as $mode )
       {
          $res .= '<option value="' . $mode . '" '. (($mode === $ctx->getMode())?'selected="true"':'') .'>'. $mode . '</option>';
       }
       
       $res .= '<input type="hidden" name="back_app"  value="'. $ctx->getApp() .'" />';
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
       
       if (!isset($params['attrs'])) $params['attrs'] = array();
       
       $dir = 'asc';
       if ( $sort === $params['attr'] )
       {
          if ( $current_dir === 'asc' )
          {
             $dir = 'desc';
             $params['attrs']['class'] = 'order_desc'; // FIXED> para CSS FIXME: me lo pone como params de la url, no en la tag.
          }
          else
          {
             $params['attrs']['class'] = 'order_asc'; // FIXED> para CSS FIXME: me lo pone como params de la url, no en la tag.
          }
       }

       //$res .= '<a href="'. Helpers::params2url( array('sort'=>$attr, 'dir'=>$dir) ) .'">'; // TODO: no tengo acceso a los params desde helpers.
       //$res .= $attr; // TODO: Habria que ver si esto es i18n, deberia haber algun "display name" asociado al nombre del campo.
       //$res .= '</a>';
       
       // Agregado de params de filtrado a links (http://code.google.com/p/yupp/issues/detail?id=49)
       $_params = $model->getAll(); // params + lo que metio el controller como model para la view
       foreach ($_params as $k=>$v)
       {
          if (String::startsWith($k, 'filter_'))
          {
             $params[$k] = $v;
          }
       }
       
       // Soporte para addParams, la vista puede decirle que otros 
       // params quiere poner en la url (p.e. 'query' si se hizo
       // una busqueda y se quiere tener ordenamiento por los resultados).
       if (isset($params['addParams'])) // es un array
       {
          foreach ($params['addParams'] as $paramName)
          {
             $params[$paramName] = $model->get($paramName); // los params se sacan del modelo actual, la vista sabe cuales params quiere
          }
          $params['addParams'] = NULL;
       }
       
       // Para mantener el paginado.
       $params['offset'] = $model->get('offset'); // puede no estar
       $params['max'] = $model->get('max'); // puede no estar
       $params['dir'] = $dir;
       $params['sort'] = $params['attr'];
       
       if (!isset($params['body'])) $params['body'] = $params['attr'];
       
       $res = self::link( $params );
       
       return $res;
    }
}
?>