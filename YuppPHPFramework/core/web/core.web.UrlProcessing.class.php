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
 //      preg_match($this->urlMatch, $url, $this->matches);
       
       $this->matches = explode("/", $url);
       // OJO, el ultimo lugar puede tener params que estan en la URL: 
       // /YuppPHPFramework/portal/page/display/mi_pagina_bbb/sdfda/asdf?asdfa=sdf&gg=ee

echo "<pre>";
print_r( $url );
print_r( $this->matches );
echo "</pre>";

       // ------------------------------------------------------------------------------
       // procesado de args de url (get) o tambien de post y files.
       // url: http://localhost:8081/Persistent/test/UrlProcessing.php/aaa/bbb/ccc

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
       
       // ------------------------------------------------------------------------------
    }

    function component()
    {
       return $this->matches[2];
    }

    function controller()
    {
    	 return $this->matches[3];
    }

    function action()
    {
       return $this->matches[4]; // se cambiaron los grupos, agregue uno nuevo para que la action sea opcional.
    }

    function params()
    {
       return $this->params;
    }

}
?>