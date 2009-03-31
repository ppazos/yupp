<?php

// TODO: hacer un contador de TODOs, FIXMEs y VERIFY en los archivos del framework.

class YuppStats
{

	function YuppStats()
	{
	}

	/**
	* lineStatisticsByFile
	* Creates a list with all lines of the given file and their occurrences.
	*
	* @param     string
	* @param     bool
	* @return    string
	*/
	function lineStatisticsByFile($Filepath, $IgnoreCase = false, $NewLine = "\n")
	{
		if (!file_exists($Filepath))
		{
			$ErrorMsg = 'LineStatisticsByFile error: ';
			$ErrorMsg .= 'The given file ' . $Filepath . ' does not exist!';
			die($ErrorMsg);
		}

		return $this->lineStatisticsByString(file_get_contents($Filepath), $IgnoreCase, $NewLine);
	}

	/**
	 * lineStatisticsByString
	 * Creates a list with all lines of the given string and their occurrences.
	 *
	 * @param     string
	 * @param     bool
	 * @return    string
	 */
	function lineStatisticsByString($Lines, $IgnoreCase = false, $NewLine = "\n")
	{
		if (is_array($Lines))
			$Lines = implode($NewLine, $Lines);

		$Lines = explode($NewLine, $Lines);

		$LineArray = array ();

		// Go trough all lines of the given file
		for ($Line = 0; $Line < count($Lines); $Line++)
		{
			// Trim whitespace for the current line
			$CurrentLine = trim($Lines[$Line]);

			// Skip empty lines
			if ($CurrentLine == '')
				continue;

			// Use the line contents as array key
			$LineKey = $CurrentLine;

			if ($IgnoreCase)
				$LineKey = strtolower($LineKey);

			// Check if the array key already exists,
			// and increase the counters
			if (isset ($LineArray[$LineKey]))
				$LineArray[$LineKey] += 1;
			else
				$LineArray[$LineKey] = 1;
		}

      /*
		// Sort the array
		arsort($LineArray);

		// Create a new readable array for the output file
		$NewLineArray = array ();
		while (list ($LineKey, $LineValue) = each($LineArray))
		{
			$NewLineArray[] = $LineKey . ': ' . $LineValue;
		}

		// Return how many lines were counted
		return implode("\n", $NewLineArray);
      */
      
      return $LineArray;
	}
   
   
   /**
    * lineCount
    * Numero de lineas con comentarios.
    * 
    * @params array $stats resultado de lineStatisticsByFile.
    */
   function lineCount( $stats )
   {
      $counter = 0;
   	foreach( $stats as $line => $ocurs )
      {
      	$counter += $ocurs;
      }
      return $counter;
   }
   
   /**
    * showStats
    * Muestra cantidad de lineas (con comentarios) para el core de Yupp, totales y por archivo.
    * 
    * @todo mostrar LOCs por modulo de la core.
    */
   function showStats()
   {
      ob_start(); // agarro el output y devuelvo el string
      
      // Line Count...
      
      $dirs = array (
                "./core",
                "./core/basic",
                "./core/config",
                "./core/db", "./core/db/criteria2",
                "./core/layout",
                "./core/mvc",
                "./core/persistent",
                "./core/routing",
                "./core/support",
                "./core/utils",
                "./core/web",
                "./components/core/controllers",
                //"./model", // es de usuario!
                //"./components/blog", // es de usuario!
                //"./components/blog/controllers", // es de usuario!
                //"./components/blog/views", // es de usuario!
                //"./components/blog/views/comentario", // es de usuario!
                //"./components/blog/views/entradaBlog", // es de usuario!
                //"./components/blog/views/usuario" // es de usuario!
              );
              
      $stats = new YuppStats();
      $totalLines = 0;
      $fileCounter = 0;
      foreach ( $dirs as $dir )
      {
         $files = FileSystem::getFileNames($dir, "/(.*\.php)$/i", array(1));
         //print_r( $files );
         
         foreach ( $files as $file )
         {
            $filePath = $dir . "/" . $file;
            $fileCounter++;
            //echo "FILE: $filePath<br/>";
            
            //$res = $stats->lineStatisticsByFile("./core/core.Constraints.class.php");
            $res = $stats->lineStatisticsByFile( $filePath );
            //$res = $stats->lineStatisticsByFile("./index.php");
            //print_r($res);
            $lineCount = $stats->lineCount( $res );
            
            echo $fileCounter .") ". $filePath .": ".$lineCount . "<br/>";
            
            $totalLines += $lineCount;
            
         }
      }
      
      echo "<hr/>";
      echo "TOTAL FILES: $fileCounter<br/>";
      echo "TOTAL LINES: $totalLines<br/>";
      echo "AVG LINES: ". ($totalLines/$fileCounter) ."<br/>";
      echo "<hr/>";
      
      return ob_get_clean(); // devuelve el output 
   }
}
?>