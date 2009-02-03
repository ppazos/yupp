<?php

/*
 * Procesa urls, sacando parametros y que controller y accion debe ejecutarse.
 * Implementa estandares (convensiones) de ubicacion del controller y action y parametros en la url.
 */

class UrlProcessing {

    /*
     * Desde el directorio del punto de acceso se busca: {dir_pa}/component/controller/action/id/params
     * params puede tener la forma: par1/par2/par3 (donde son solo valores y la accion debe saber cual es cual)
     * o la forma clasica: ?par1=val1&par2=val2&par3=val3
     *
     * Las urls quedan mas lindas de la primer forma.
     */

    //private $urlMatch = "/\/(.*)\/(.*)\/(.*)/i";
    //private $urlMatch = "/\/([^\/]*)\/([^\/\?]*)(\/(.*))?/i"; // En controller, action, matchea cualquier cosa menos '/'.
    private $urlMatch = "/\/([^\/]*)\/([^\/]*)(\/([^\/\?]*))?[\/\?]?(.*)/i"; // action opcional con la barra opcional tambien! (\/([^\/\?]*))?
    //private $urlMatch = "/(.*)/i";
    private $url;
    private $matches;
    private $params;

    //preg_match("/(.*)\.(.*)\.(.*)\.php$/i", $filename, $matches)

    function __construct( $url )
    {
       $this->url = $url; // SE ESPERA QUE EMPIECE CON '/', ver urlMatch. (esto es asi como lo devuelve $_SERVER['REQUEST_URI'])
       preg_match($this->urlMatch, $url, $this->matches);

//echo "<pre>";
//print_r( $this->matches );
//echo "</pre>";

       // ------------------------------------------------------------------------------
       // procesado de args de url (get) o tambien de post y files.
       // url: http://localhost:8081/Persistent/test/UrlProcessing.php/aaa/bbb/ccc
       if ( strlen( $_SERVER['QUERY_STRING'] ) == 0 )
       {
          // Si termina con / se lo saco.
          if ( $this->matches[4][strlen($this->matches[4])-1] == '/' ) $this->matches[4] = substr($this->matches[4], 0, -1);

          $_params = explode("/", $this->matches[4]); // matches[4] = par1/par2/par3
          $paramBaseName = "_param_";
          $i = 1;
          foreach ($_params as $value)
          {
             $this->params[$paramBaseName.$i] = $value;
             $i++;
          }
       }
       else // url: http://localhost:8081/Persistent/test/UrlProcessing.php?aaa=123&bbb=345&ccc=456 o vienen por post o file.
       {
          // ME PARECE QUE ESTO SE DEBERIA EJECUTAR AUN SI SE PASAN PARAMS POR LA URL...

          // si caigo aca, tengo los params en $_GET
          if ( sizeof($_GET) > 0)
          {
             foreach ($_GET as $key => $value)
             {
                $this->params[$key] = $value;
             }
          }

          if ( sizeof($_POST) > 0)
          {
             foreach ($_POST as $key => $value)
             {
                $this->params[$key] = $value;
             }
          }

          if ( sizeof($_FILES) > 0)
          {
             foreach ($_FILES as $key => $value)
             {
                $this->params[$key] = $value;
             }
          }
       }
       // ------------------------------------------------------------------------------
    }

    function component()
    {
       return $this->matches[1];
    }

    function controller()
    {
    	 return $this->matches[2];
    }

    function action()
    {
       return $this->matches[4]; // se cambiaron los grupos, agregue uno nuevo para que la action sea opcional.
    }

    function params()
    {
       return $this->params;
    }

    // OJO SE AGREGO EL COMPONENT  A LA URL !!!!

    // Que obtengo llamando al test de esta clase con urls que pasan los params de distinta forma.
    /*
     * http://localhost:8081/Persistent/test/user/create?name=pepe&age=23&height=180
     (
       [urlMatch:private] => /\/([^\/]*)\/([^\/\?]*)[\/\?](.*)/i
       [url:private] => /user/create?name=pepe&age=23&height=180
       [matches:private] => Array
           (
               [0] => /user/create?name=pepe&age=23&height=180
               [1] => user
               [2] => create
               [3] => name=pepe&age=23&height=180
           )
   )
     *
     */

     /*
      * http://localhost:8081/Persistent/test/user/create/pepe/23/180
   (
       [urlMatch:private] => /\/([^\/]*)\/([^\/\?]*)[\/\?](.*)/i
       [url:private] => /user/create/pepe/23/180
       [matches:private] => Array
           (
               [0] => /user/create/pepe/23/180
               [1] => user
               [2] => create
               [3] => pepe/23/180    // el que recibe los parametros debe saber que son por como los recibe,
                                     // ya que fue el mismo modulo quien creo la url, es decir, si para acceder
                                     // a algo el link se hace de determinada forma, cuando ejecuto la accion para
                                     // mostrar eso, espera que el link tenga esa forma y procesa los params segun vienen.
                                     // Como lo que necesito es un map (el caos general) le voy a poner nombres generados
                                     // a los params que vengan en la url, y estos nombres seran conocidos por las acciones,
                                     // por ejemplo: _param_1, _param_2, etc, esto prohibe el uso de estas palabras como
                                     // nombres de parametros!!!!
           )
   )
      */
}
?>