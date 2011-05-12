<?php

class Router {

    // Ex Filter
    private $parsedUrl = ""; // resultado de parse_url()
    private $urlParams;
    
    private $requested_route; // [app=>xxx, controller=>yyy, action=>zzz]
    
    // Ex Mapping
    private $relative_logic_url; // algo como: blog/entradaBlog/show
    private $field_list; // Lista de campos de la url logica
    
    function __construct( $url )
    {
        global $_base_dir;
      
        $this->parsedUrl = parse_url($url); // FIXME: hacer un url encode por si viene una url con http: como parametro, si no no parsea. Luego hacer url decode. 
        
        // Saca los params de la url sin importar en que subdirectorio esta instalado Yupp.
        $lp = strrpos( $_SERVER["SCRIPT_NAME"], "/" );
        $soloUrl = substr( $this->parsedUrl['path'], $lp+1 );
        $preUrlParams = explode( "/", $soloUrl );
        
        /*
         * print_r( $preUrlParams );
         * 0 app
         * 1 controller
         * 2 action (si hay)
         * 3 ... params.
         */
        $this->requested_route = array( 'app'       =>((!empty($preUrlParams[0])) ? $preUrlParams[0] : 'core'),
                                        'controller'=>((!empty($preUrlParams[1])) ? $preUrlParams[1] : 'core'),
                                        'action'    =>((!empty($preUrlParams[2])) ? $preUrlParams[2] : 'index') );
      
        // Recorro desde el param 3 para arriba.
        $szPUP = count($preUrlParams)-2;
        for ($i=1; $i<$szPUP; $i++)
        {
            // FIXME: el 3 depende de donde este instalado el framework en el servidor,
            // si esta en el root es 3, si esta en un subdirectorio es 4, etc, etc, etc.
            $this->urlParams["_param_".$i] = $preUrlParams[$i+2];
        }
        
        //print_r( $this->requested_route );
        //print_r( $this->urlParams );
        
      
        // Url relativa, o sea, sin el base dir.
        $this->relative_logic_url = substr( $this->parsedUrl['path'], strlen( $_base_dir ) + 1); // +1 para sacarle el '/' en el inicio.
        $this->field_list = array_filter( explode("/", $this->relative_logic_url) );
    }
    
    // ============ OPERACIONES DE ACCESO A LOS PARAMS ============== //
    
    /**
     * Agrega parametros por nombre bajo demanda, no es necesario que sean pasados por get o post, 
     * pero luego de procesados son tomados como cualquier parametro.
     */
    public function addCustomParams( $paramArray )
    {
        foreach ($paramArray as $name => $value) $this->urlParams[$name] = $value;
    }
    
    public function getPath() { return $this->parsedUrl['path']; }
    public function getQuery() { return $this->parsedUrl['query']; }
    public function getAnchor() { return $this->parsedUrl['fragment']; }
    
    /**
     * Cuando la accion viene codificada como param (es decir '_action_laAccion'), 
     * esta funcion devuelve 'laAccion', si no existe este parametro, devuelve NULL.
     */
    public function getActionParam()
    {
       $paramsKeys = array_keys(array_merge($_POST, $_GET));
       $current = current($paramsKeys);
       while ($current)
       {
          if ( String::startsWith($current, "_action_") ) return substr($current, 8);
          $current = next($paramsKeys);
       }
       return NULL;
    }
    
    /**
     * Si se hizo un redirect y se puso algo en flash, esos valores se pasan por GET.
     * Este metodo retorna todos los valors de GET que se correspondan con flash. 
     */
    public function getFlashParams()
    {
       $res = array();
       $paramsKeys = array_keys($_GET);
       $current = current($paramsKeys);
       while ($current)
       {
          if ( String::startsWith($current, "flash_") )
          {
             $res[ substr($current, 6)  ] = $_GET[ $current ];
          }
          $current = next($paramsKeys);
       }
       
       return $res;
    }
    
    public function getParams()
    {
       $tempArr = array();
       if ($this->urlParams !== NULL && count($this->urlParams)>0) // Merge de POST, GET y urlParams.
       {
          $tempArr = array_merge( $this->urlParams, $_GET);
          $tempArr = array_merge( $_POST, $tempArr);
          return array_merge( $_FILES, $tempArr);
       }
       
       // Merge POST, GET y FILES
       $tempArr = array_merge( $_POST, $_GET);
       return array_merge( $_FILES, $tempArr);
    }
    
    public function getGetParams()
    {
       return $_GET;
    }
    public function getGetParam( $name )
    {
       if ( array_key_exists($name, $_GET) ) return $_GET[$name];
       return NULL;
    }
    
    public function getPostParams()
    {
       return $_POST;
    }
    public function getPostParam( $name )
    {
       if ( array_key_exists($name, $_POST) ) return $_POST[$name];
       return NULL;
    }
    
    /**
     * Busca el parametro tanto en post como en get. 
     * Busca primero en post porque tiene preferencia sobre get.
     * @param string name nombre del parametro buscado.
     */
    public function getParam( $name )
    {
       $value = $this->getPostParam($name);
       if ( $value !== NULL ) return $value;
       return $this->getGetParam($name);
    }
    
    public function getFiles()
    {
       // Ver: http://www.php.net/manual/es/features.file-upload.php
       return $_FILES;
    }
    
    public function getCookies()
    {
       return $_COOKIE;
    }
    
    public function getCookie( $name )
    {
       if ( array_key_exists($name, $_COOKIE) ) return $_COOKIE[$name];
       return NULL;
    }
    
    // ============ OPERACIONES DE ACCESO A LOS PARAMS ============== //
    
    // ex Mapping
    public function getLogicalRoute()
    {
      // si no hay mapping para la app, o si el que hay no matchea,
      // busca el Mapping de Core.
      $mapping = NULL;
      $mappingPath = "apps/".$this->requested_route['app']."/AppMapping.php";
      if ( file_exists($mappingPath) )
      {
         include_once( $mappingPath );
         $mapping = new AppMapping();
         if ( preg_match($mapping->mapping, $this->relative_logic_url ) )
         {
            return $mapping->getLogicalRoute( $this->field_list );
         }
         else
         {
            // Va al mapping por defecto
            // Siempre existe
            $mappingPath = "apps/core/AppMapping.php";
            include_once( $mappingPath );
            $mapping = new AppMapping();
            
            // Siempre matchea, ni pregunto...
            return $mapping->getLogicalRoute( $this->field_list );
         }
      }
      else
      {
         // Va al mapping por defecto
         // Siempre existe
         $mappingPath = "apps/core/AppMapping.php";
         include_once( $mappingPath );
         $mapping = new AppMapping();
         
         // Siempre matchea, ni pregunto...
         return $mapping->getLogicalRoute( $this->field_list );
      }
    }
}
?>