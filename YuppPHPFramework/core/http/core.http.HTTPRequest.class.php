<?php


//include_once('../http/core.http.HTTPResponse.class.php');
YuppLoader::load('core.http', 'HTTPResponse');

class HTTPRequest{

/*	private $code;
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
	public function HttpRequestPost($url){
		
		$r = $this->file_post_contents($url);
		return $r;
	/*	$res = explode("\r\n", $r);

		return $res[count($res)-1];*/
	}
	
	//Request Método Get
	public function HttpRequestGet($url){
	
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
		$parsedUrl['query']=isset($parsedUrl['query'])?$parsedUrl['query']:'';

		$parsedUrl['protocol']=$parsedUrl['scheme'].'://';
		$eol="\r\n";
		$headers =  "GET ".$url." HTTP/1.0".$eol.
                "Host: ".$parsedUrl['host'];/*
                "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol.*/
				"Connection: close\n\n".$eol.
				$eol;
		if (isset($this->cookie))
		{
			$headers .= $eol.
				"Cookie: ".$this->cookie.$eol.
				"Connection: close\n\n".$eol.
				$eol;
		}
		else
		{
			$headers .= $eol.
				"Connection: close\n\n".$eol.
				$eol;
		}
		
		
		$timeout = 30;
		//echo $headers;
		//$r = file_get_contents($url);
		$fp = fsockopen($parsedUrl['host'], $parsedUrl['port'], $errno, $errstr, 30);
		if($fp) 
		{
			
			fputs($fp, $headers);
			$result='';
			
			while(!feof($fp)) 
			{ 
				$result .= fgets($fp, 128);
			}
			
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
		$responseHeaders = $response->getHeaders();
			
		if (isset($responseHeaders['Set-Cookie']))
		{
			$this->setCookie($responseHeaders['Set-Cookie']);
		}
		
		return $response;
		
	}
	

	//auxiliar para envío post
	private function file_post_contents($url,$headers=false) {
		$parsedUrl = parse_url($url);
	//	print_r($parsedUrl['query']);

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
	/*	$parsedQuery ='';
		if (isset($parsedUrl['query']))
		{
			$parsedQuery = $this->parseQuery($parsedUrl['query']);
			//print_r($parsedUrl['query']);	
		}
		
	*/			
		$parsedUrl['query']=isset($parsedUrl['query'])?$parsedUrl['query']:'';

		$parsedUrl['protocol']=$parsedUrl['scheme'].'://';
		$eol="\r\n";
		$headers =  "POST ".$parsedUrl['protocol'].$parsedUrl['host'].$parsedUrl['path']." HTTP/1.0".$eol.
                "Host: ".$parsedUrl['host'].$eol.
                "Referer: ".$parsedUrl['protocol'].$parsedUrl['host'].$parsedUrl['path'].$eol.
                "Content-Type: application/x-www-form-urlencoded";
		
		//si tengo valor en cookie, lo agrego al header
		if (isset($this->cookie))
		{
			$headers .= $eol."Cookie: ".$this->cookie.$eol.
                "Content-Length: ".strlen($parsedUrl['query']).$eol.
				$eol.$parsedUrl['query'];
		}
		else
		{
			$headers .= $eol.
                "Content-Length: ".strlen($parsedUrl['query']).$eol.
				$eol.$parsedUrl['query'];
		}
	/*	$headers =  "POST ".$parsedUrl['protocol'].$parsedUrl['host'].$parsedUrl['path']." HTTP/1.0".$eol.
                "Host: ".$parsedUrl['host'].$eol.
                "Referer: ".$parsedUrl['protocol'].$parsedUrl['host'].$parsedUrl['path'].$eol.
                "Content-Type: application/x-www-form-urlencoded".$eol.
                "Content-Length: ".strlen($parsedQuery).$eol.
				$eol.$parsedQuery;*/
		//echo $headers;
		$timeout = 30;
		$fp = fsockopen($parsedUrl['host'], $parsedUrl['port'], $errno, $errstr, $timeout);
		if($fp) 
		{
			fputs($fp, $headers);
			
			$result = '';
		
			while(!feof($fp)) 
			{ 
				$result .= fgets($fp, 128);
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