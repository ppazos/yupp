<?php

class HTTPResponse {

   protected $version;
   protected $code;
   protected $message;
   protected $headers = array();
   protected $body;
   
   public function __construct()
   {
   }
   
   public function createResponse($res = array())
   {
      //version, code y message vienen en la primer posición del array
      $this->headers[0] = $res[0]; // HTTP/1.1 200 OK
      
      if (empty($this->headers[0])) throw new Exception('La respuesta es vacia '. __FILE__ .' '.__LINE__);
      
      // Para obtener version de HTTP, codigo y mensaje: HTTP/1.1 200 OK
      $headerSplit = preg_split('/ /', $this->headers[0]);
      $this->version = $headerSplit[0];
      
      if (isset($headerSplit[1]))
      {
         $this->code = $headerSplit[1];
         $this->message = '';
         for ($i = 2; $i <= (count($headerSplit) - 1); $i++)
         {
            $this->message .= $headerSplit[$i].' ';
         }

         //guardo el resto de los headers
         //para cada posicion del array, y mientras la posicion no sea vacio, 
         // guardo la primer parte como key (antes de ': '), y la segunda posicion como value 
         $j = 1;
         while (($j <= (count($res)-1)) && $res[$j] != '')
         {
            $header = preg_split('/: /', $res[$j]);
            $this->headers[$header[0]] = $header[1];
            $j++;
         }

         //avanzo una posicion en el array, para evitar la posicion que no tiene value
         $j++;
         while (($j <= (count($res)-1)) )
         {
            $this->body .= $res[$j++];
            $j++;
         }
      }

      return $this;
   }

   public function getVersion()
   {
      return $this->version;
   }

   public function getStatus()
   {
      return $this->code;
   }
   
   public function getMessage()
   {
      return $this->message;
   }
   
   public function getHeaders()
   {
      return $this->headers;
   }
   
   public function getBody()
   {
      return $this->body;
   }
   
   public function setVersion($version)
   {
      $this->version = $version;
   }
   
   public function setCode($code)
   {
      $this->code = $code;
   }
   
   public function setMessage($message)
   {
      $this->message = $message;
   }
   
   public function setHeaders($headers)
   {
      foreach ($headers as $name => $value) 
      {
         if (is_int($name))
            list($name, $value) = explode(": ", $value, 1);

         $this->headers[ucwords(strtolower($name))] = $value;
      }
   }
   
   public function setBody($body)
   {
      $this->body = $body;
   }
   
   public function isRedirect()
   {
      $restype = floor($this->code / 100);
      if ($restype == 3)
      {
         return true;
      }
      return false;
   }
    
   //retorna el header pasado como string por parametro 
   public function getHeader($header)
   {
      $header = ucwords(strtolower($header));
      if (! is_string($header) || ! isset($this->headers[$header])) 
         return null;
         
      return $this->headers[$header];
   }
}
?>