<?php

// Referencias:
// http://www.php.net/manual/es/reserved.variables.get.php
// http://www.php.net/manual/es/reserved.variables.post.php
// http://www.php.net/manual/es/reserved.variables.cookies.php
// http://www.php.net/manual/es/function.setrawcookie.php

/**
 * 
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 * 
 * @todo Cambiarle el nombre a RequestFilter par aevitar confusion con ControllerFilter.
 */
class Filter {

    private $parsedUrl = "";

    function Filter( $url ) {
    	$this->parsedUrl = parse_url($url); // Puede dar falso o tirar error! url seria $_SERVER['REQUEST_URI']
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
    	 $paramsKeys = array_keys(array_merge( $_POST, $_GET));
       $current = current($paramsKeys);
       while ($current)
       {
       	 if ( String::startsWith($current, "_action_") ) return substr($current, 8);
          $current = next($paramsKeys);
       }
       return NULL;
    }
    
    public function getParams()
    {
    	 return array_merge( $_POST, $_GET);
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
}

/* Ejemplo de variables en post, get y request.


if(isset($_GET['posted']) == 1)
{
    echo "POST: ";
    print_R($_POST);
    echo "<br/>GET: ";
    print_R($_GET);
    echo "<br/>REQUEST: ";
    print_R($_REQUEST);
}
else
{
    ?>
    <form method="post" action="?posted=1&something=someotherval">
        <input type="text" value="someval" name="something"/>
        <input type="submit" value="Click"/>
    </form>
    <?
}
?>

The above form post will result in the following output:

POST: Array ( [something] => someval )
GET: Array ( [posted] => 1 [something] => someotherval )
REQUEST: Array ( [posted] => 1 [something] => someval )

*/

?>