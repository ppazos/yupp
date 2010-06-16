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
		//print_r($res);
      
      //version, code y message vienen en la primer posición del array
      $header = $res[0]; // HTTP/1.1 200 OK
      
      $headerSplit = preg_split('/ /', $header);
      
      $this->version = $headerSplit[0];
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
      while ( ($j <= (count($res)-1)) && $res[$j]!='' )
      {
         $header = preg_split('/: /', $res[$j]);
         $this->headers[$header[0]] = $header[1];
         $j++;
         //echo "<b>$j</b><br/>";
      }
      
      //print_r( $this->headers );
      
      //avanzo una posicion en el array, para evitar la posicion que no tiene value
      //$j++;
      //echo "<b>$j</b><br/>";
      while (($j+1 <= (count($res)-1)) )
      {
         $this->body .= $res[$j+1];
         $j++;
         //echo "$j<br/>";
      }
      
		return $this;
	}


	//get
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
	
	//set
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