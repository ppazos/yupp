<?php


YuppLoader::load('core.http', 'HTTPResponse');

class HTTPRequest{

/*   private $code;
   private $date;
   private $server;
   private $powered;
   private $cookie;
   private $expires;
   private $cache;
   private $pragma;
   private $content_length;
   private $connection;
   private $content_type;
   */
   private $cookie;
   
   //Request Método Post
   public function HttpRequestPost($url)
   {
      $r = $this->file_post_contents($url);
      return $r;

   }
   
   //Request Método Get
   public function HttpRequestGet($url)
   {
      $parsedUrl = parse_url($url);

      if (!isset($parsedUrl['port'])) {
         if ($parsedUrl['scheme'] == 'http') 
         {
            $parsedUrl['port']=80; 
         }
         elseif ($parsedUrl['scheme'] == 'https') 
         { 
            $parsedUrl['port']=443; 
         }
      }
      
      //print_r( $parsedUrl );
      
      $parsedUrl['query'] = ( isset( $parsedUrl['query'] ) ? $parsedUrl['query'] : '');
      $parsedUrl['protocol'] = $parsedUrl['scheme'].'://';
      
      $eol = "\r\n";
      
      $headers =  "GET ".$url." HTTP/1.0".$eol.
                  "Host: ".$parsedUrl['host'].$eol;
                  //"Referer: ".$url['protocol'].$url['host'].$url['path'].$eol.
      
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
      
      $result = '';
      $timeout = 30;
      //echo $headers;
      //$r = file_get_contents($url);
      $fp = fsockopen($parsedUrl['host'], $parsedUrl['port'], $errno, $errstr, 30);
      if($fp) 
      {
         /*
         fputs($fp, $headers); // Mandar headers
         while(!feof($fp)) 
         { 
            $result .= fgets($fp, 128);
         }
         */
         fwrite($fp, $headers);
         while(is_resource($fp) && $fp && !feof($fp)) $result .= fread($fp, 1024);
      }
      fclose($fp);
      
      
//      $this->_fp = fsockopen(($this->_protocol == 'https' ? 'ssl://' : '') . $this->_host, $this->_port);
//      fwrite($this->_fp, $req);
//      while(is_resource($this->_fp) && $this->_fp && !feof($this->_fp)) $response .= fread($this->_fp, 1024);
//      
//      fclose($this->_fp);
      
      
//      echo '<textarea style="width:900px; height:300px; border: 1px solid blue;">';
//      echo $result;
//      echo '</textarea>';
      
      if (!isset($headers)) 
      {
         //removes headers
         $pattern="/^.*\r\n\r\n/s";
         $result=preg_replace($pattern,'',$result);

      }

      $r = preg_split('/\r\n/', $result);
      
//      echo '<textarea style="width:900px; height:300px; border: 1px solid red;">';
//      print_r( $r );
//      echo '</textarea>';
         
      $response = new HTTPResponse();
      $response->createResponse($r);
      
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
   

   //auxiliar para envío post
   private function file_post_contents($url, $headers = false)
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
   /*   $parsedQuery ='';
      if (isset($parsedUrl['query']))
      {
         $parsedQuery = $this->parseQuery($parsedUrl['query']);
         //print_r($parsedUrl['query']);   
      }
      
   */         
      
      $parsedUrl['query']=isset($parsedUrl['query'])?$parsedUrl['query']:'';
      $parsedUrl['protocol']=$parsedUrl['scheme'].'://';
      
      $eol="\r\n";
      
      $headers = "POST ".$parsedUrl['protocol'].$parsedUrl['host'].':'.$parsedUrl['port'].$parsedUrl['path']." HTTP/1.0".$eol.
                 "Host: ".$parsedUrl['host'].$eol.
                 "Referer: ".$parsedUrl['protocol'].$parsedUrl['host'].$parsedUrl['path'].$eol.
                 "Content-Type: application/x-www-form-urlencoded".$eol;
      
      //si tengo valor en cookie, lo agrego al header
      if (isset($this->cookie))
      {
         $headers .= "Cookie: ".$this->cookie.$eol;
      }
      $headers .= "Connection: close".$eol;
      $headers .= "Content-Length: ".strlen($parsedUrl['query']).$eol.$eol;
      $headers .= $parsedUrl['query'];
      
      /*   $headers =  "POST ".$parsedUrl['protocol'].$parsedUrl['host'].$parsedUrl['path']." HTTP/1.0".$eol.
                "Host: ".$parsedUrl['host'].$eol.
                "Referer: ".$parsedUrl['protocol'].$parsedUrl['host'].$parsedUrl['path'].$eol.
                "Content-Type: application/x-www-form-urlencoded".$eol.
                "Content-Length: ".strlen($parsedQuery).$eol.
            $eol.$parsedQuery;*/
      //echo $headers;
      
//      echo '<textarea style="width:900px; height:200px; border: 1px solid gold;">';
//      echo $headers;
//      echo '</textarea>';
      
      $result = '';
      $timeout = 30;
      $fp = fsockopen($parsedUrl['host'], $parsedUrl['port'], $errno, $errstr, $timeout);
      if($fp) 
      {
         /*
         fputs($fp, $headers);

         while(!feof($fp)) 
         { 
            $result .= fgets($fp, 128);
         }
         */
         fwrite($fp, $headers);
         while(is_resource($fp) && $fp && !feof($fp)) $result .= fread($fp, 1024);
      }
      fclose($fp);
      
      if (!$headers) 
      {
         //removes headers
         $pattern="/^.*\r\n\r\n/s";
         $result=preg_replace($pattern,'',$result);
      }
      
      $r = preg_split('/\r\n/', $result);
      
      $response = new HTTPResponse();
      $response->createResponse($r);
      //echo "request";
      //print_r($response);
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