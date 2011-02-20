<?php

YuppLoader::load('core.http', 'HTTPResponse');

class HTTPRequest {

   const BUFF_SIZE = 512;

/*   private $code;
   private $date;
   private $server;
   private $powered;
   private $expires;
   private $cache;
   private $pragma;
   private $content_length;
   private $connection;
   */
   
   private $cookie;
   private $content_type;
   private $timeout = 10; // en segundos

   public function setTimeOut( $secs )
   {
      $this->timeout = $secs;
   }
   
   //Request Método Post
   // FIXME: cambiarle el nombre a post
   public function HttpRequestPost($url, $params = array())
   {
      // FIXME: si $url ya tiene params (?a=e), adjuntar los params de $params
    
      // Los espacios y otros caracteres de separacion no son admisibles en una url...
      $pattern="/[\t\r\n]*/s";
      $url = preg_replace($pattern, '', $url);
      
//      echo 'Query HTTPRequestPost B:<br/>';
//      echo '<textarea style="width: 800px; height: 400px;">'; 
//      print_r( $url );
//      echo '</textarea><br/>';

      //$r = $this->file_post_contents($url);
      //return $r;
      
      $parsedUrl = parse_url($url); // FIXME: no deberia hacer parse de la url con params, los params deberian venir aparte.

      if (!isset($parsedUrl['port']))
      {
         if ($parsedUrl['scheme'] == 'http')
         {
            $parsedUrl['port']=80;
         }
         elseif ($parsedUrl['scheme'] == 'https')
         {
            $parsedUrl['port']=443;
         }
      }

//      echo "QUERY:<br/>";
//      echo '<textarea style="width: 800px; height: 400px;">';
//      echo $url."\n\n"; 
//      print_r( $parsedUrl['query'] );
//      echo "</textarea><br/>";

   /*   $parsedQuery ='';
      if (isset($parsedUrl['query']))
      {
         $parsedQuery = $this->parseQuery($parsedUrl['query']);
         //print_r($parsedUrl['query']);
      }
   */

      //$parsedUrl['query'] = isset($parsedUrl['query'])?$parsedUrl['query']:'';
      
      // Adjunta los params de $params si ya tienen params en la url, si no,
      // los mete como nuevos.
      $moreParams = '';
      if (isset($parsedUrl['query']))
      {
         $moreParams = '&';
      }
      else
      {
         $parsedUrl['query'] = '';
      }
      foreach ($params as $name=>$value)
      {
         $moreParams .= $name.'='.$value.'&';
      }
      $parsedUrl['query'] .= substr($moreParams, 0, -1);
      
      
      $parsedUrl['protocol'] = $parsedUrl['scheme'].'://';
      
      $eol = "\r\n";
      
      if (!isset($parsedUrl['path'])) $parsedUrl['path'] = '';
      
      if (!empty($parsedUrl['port']))
      {
         $headers = "POST ".$parsedUrl['protocol'].$parsedUrl['host'].':'.$parsedUrl['port'].$parsedUrl['path']." HTTP/1.0".$eol;
      }
      else
      {
         $headers = "POST ".$parsedUrl['protocol'].$parsedUrl['host'].$parsedUrl['path']." HTTP/1.0".$eol;
      }
      
      $headers .= "Host: ".$parsedUrl['host'].$eol.
                  //"Referer: ".$parsedUrl['protocol'].$parsedUrl['host'].$parsedUrl['path'].$eol.
                  "Content-Type: application/x-www-form-urlencoded".$eol;
      
      //si tengo valor en cookie, lo agrego al header
      if (isset($this->cookie))
      {
         $headers .= "Cookie: ".$this->cookie.$eol;
      }
      $headers .= "Connection: close".$eol;
      $headers .= "Content-Length: ".strlen($parsedUrl['query']).$eol.$eol;
      $headers .= $parsedUrl['query'];

/*
      echo 'Query HTTPRequestPost D:<br/>';
      echo '<textarea style="width: 800px; height: 400px;">';
      echo $headers;
      echo '</textarea><br/>';
*/

      $response = new HTTPResponse();
      $result = '';
      try
      {
         // TODO: poder devolver numero de error y errstr.
         $fp = fsockopen($parsedUrl['host'], $parsedUrl['port'], $errno, $errstr, $this->timeout);
         if($fp && is_resource($fp))
         {
            fputs($fp, $headers);

            while (!feof($fp))
            {
               $result .= fread($fp, self::BUFF_SIZE);
            }
         }
         fclose($fp); // TODO: si hay keepalive, no cerrar el socket...
      }
      catch (Exception $e)
      {
         $response->createResponse( array('HTTP/1.1 404 Not Found') );
      }

      // Esto de que sirve? siempre da false y nunca entra porque headers siempre tiene algo.
      if (!isset($headers))
      {
         //removes headers
         $pattern="/^.*\r\n\r\n/s";
         $result=preg_replace($pattern,'',$result);
      }

      if ($result !== '')
      {
         $r = preg_split('/\r\n/', $result);
         $response->createResponse($r);
      }
      else // empty response
      {
         // http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
         $response->createResponse( array('HTTP/1.1 503 Service Unavailable') );
      }

      //echo "RESPONSE:<br/>";
      //print_r($response);
      
      $responseHeaders = $response->getHeaders();

      if (isset($responseHeaders['Set-Cookie']))
      {
         $this->setCookie($responseHeaders['Set-Cookie']);
      }
      
      return $response;
   }
   
   //Request Método Get
   // FIXME: cambiarle el nombre a get
   public function HttpRequestGet($url)
   {
      $parsedUrl = parse_url($url);

      if (!isset($parsedUrl['port']))
      {
         if ($parsedUrl['scheme'] == 'http')
         {
            $parsedUrl['port']=80;
         }
         elseif ($parsedUrl['scheme'] == 'https')
         {
            $parsedUrl['port']=443;
         }
      }
      
      $parsedUrl['query'] = ( isset($parsedUrl['query']) ? $parsedUrl['query'] : '' );
      $parsedUrl['protocol'] = $parsedUrl['scheme'].'://';
      
      $eol = "\r\n";
      
      $headers = "GET ".$url." HTTP/1.0".$eol.
                 "Host: ".$parsedUrl['host'].$eol;
                 // "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol.
      
      if (isset($this->cookie))
      {
         $headers .= "Cookie: ".$this->cookie.$eol;
      }
      $headers .= "Connection: close".$eol.$eol;
      
//      echo '<textarea style="width:900px; height:200px; border: 1px solid gold;">';
//      echo $headers;
//      echo '</textarea>';
//      
//      print_r( $parsedUrl );
//      
//      echo "<hr/>".gettype($parsedUrl['port'])."<hr/>";

      $response = new HTTPResponse();
      $result = '';
      try
      {
         //$r = file_get_contents($url);
         // TODO: poder devolver numero de error y errstr.
         $fp = fsockopen($parsedUrl['host'], $parsedUrl['port'], $errno, $errstr, $this->timeout);
         if ($fp && is_resource($fp))
         {
            fputs($fp, $headers); // Envia pedido
            
            while (!feof($fp))
            {
               //$result .= fgets($fp, self::BUFF_SIZE);
               $result .= fread($fp, self::BUFF_SIZE);
            }
         }
         fclose($fp); // TODO: si hay keepalive, no cerrar el socket...
      }
      catch (Exception $e)
      {
         $response->createResponse( array('HTTP/1.1 404 Not Found') );
      }

// TODO: probar con HTTPS (ssl)
//      $this->_fp = fsockopen(($this->_protocol == 'https' ? 'ssl://' : '') . $this->_host, $this->_port);
//      fwrite($this->_fp, $req);
//      while(is_resource($this->_fp) && $this->_fp && !feof($this->_fp)) $response .= fread($this->_fp, self::BUFF_SIZE);
//      
//      fclose($this->_fp);
      
      // Esto de que sirve? siempre da false y nunca entra porque headers siempre tiene algo.
      if (!isset($headers))
      {
         //removes headers
         $pattern="/^.*\r\n\r\n/s";
         $result=preg_replace($pattern,'',$result);
      }

      if ($result !== '')
      {
         $r = preg_split('/\r\n/', $result);
         $response->createResponse($r);
      }
      else // empty response
      {
         // http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
         $response->createResponse( array('HTTP/1.1 503 Service Unavailable') );
      }

//      echo '<textarea style="width:900px; height:300px; border: 1px solid green;">';
//      print_r( $response );
//      echo '</textarea>';

      $responseHeaders = $response->getHeaders();

      if (isset($responseHeaders['Set-Cookie']))
      {
         $this->setCookie($responseHeaders['Set-Cookie']);
      }
      
      return $response;
   }
   
   /*private function parseQuery($query)
   {
      $res = preg_split('/=/', $query);
      $parsedQuery = array();
      
      
   }*/
   
   // Ver si ya tiene cookies, como agregar (por ;)
   private function setCookie($cookie)
   {
      $this->cookie = $cookie;
   }
}
?>