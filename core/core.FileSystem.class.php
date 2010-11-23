<?php


/*
 * Created on 25/02/2008
 *
 * Clase auxiliar para manejar el filesystem.
 */

class FileSystem
{

   /**
    * Lista de nombres de archivos en el directorio dado procesados por match y groups.
    * Match es una expresion regular con la cual deben matchear los nombres de los archivos, por ejemplo si quiero los php, le paso "/\.php$/i"
    * Groups es un array con el numero de grupo que se crea al matchear el nombre de una entrada con $match. Si se pasa $groups se debe pasar $match, si no no tiene sentido.
    *
    * Los archivos que se devuelven matchean con $match y son la concatenacion de los grupos seleccionados por $groups desde el $matches del matcheo.
    * $groups sirve para procesar los nombres de los archivos, por ejemplo si se quieren entregar los .php sin la extension: getFileNames(".", "/(.*)\.php$/i", array(1))
    * El grupo 0 es igual al nombre del archivo, y los demas grupos dependen de como se defina la expreg $match.
    * Se siguen las reglas comunes para definicion de grupos en expresiones regulares (para escribir $match).
    *
    * Ver el archivo de tests por mas info.
    */
   public static function getFileNames($path, $match = null, $groups = null)
   {
      if (is_dir($path))
      {
         $res = array();
         $d = dir($path);

         while (false !== ($entry = $d->read()))
         {
            if (is_file($path . "/" . $entry))
            {
               //echo "FILE<br/>";
               $matches = null;
               if ($match)
               {
                   //echo "MATCH<br/>";
                   if (preg_match($match, $entry, $matches))
                   {
                       //echo "MATCHES<br/>";
                       if (!$groups) $res[] = $entry;
                       else
                       {
                          $gentry = "";
                          foreach($groups as $i)
                          {
                           $gentry .= $matches[$i];
                          }
                          $res[] = $gentry;
                       }
                   }
               }
               else // Si no paso match, le entrego derecho la entrada.
               {
                  //echo "ELSE<br/>";
                  $res[] = $entry;
               }
            }
         }
         $d->close();
         return $res;
      }
      else
      {
         throw new Exception("FileSystem::getFileNames - El directorio: $path no existe.");
      }
   }

   /**
    * Idem anterior pero con lso nombres de los subdirectorios.
    */
   public static function getSubdirNames($path, $match = null, $groups = null)
   {
      if (is_dir($path))
      {
         $res = array();
         $d = dir($path);

         while (false !== ($entry = $d->read()))
         {
            if ($entry !== "." && $entry !== ".." && is_dir($path . "/" . $entry))
            {
               //echo "FILE<br/>";
               $matches = null;
               if ($match)
               {
                   //echo "MATCH<br/>";
                   if (preg_match($match, $entry, $matches))
                   {
                       //echo "MATCHES<br/>";
                       if (!$groups) $res[] = $entry;
                       else
                       {
                          $gentry = "";
                          foreach($groups as $i)
                          {
                           $gentry .= $matches[$i];
                          }
                          $res[] = $gentry;
                       }
                   }
               }
               else // Si no paso match, le entrego derecho la entrada.
               {
                  //echo "ELSE<br/>";
                  $res[] = $entry;
               }
            }
         }
         $d->close();
         return $res;
      }
      else
      {
         throw new Exception("FileSystem::getFileNames - El directorio: $path no existe.");
      }
   }

   /**
    * True si pudo crear exitosamente.
    */
   public static function createEmptyFile($path)
   {
      // TODO: chekeo de que no existe el archivo.
      if (is_file($path)) throw new Exception("FileSystem::createEmptyFile - El archivo: $path ya existe.");
      if ($file = fopen($path, 'w+'))
      {
         fwrite($file, "");
         fclose($file);
         return true;
      }
      fclose($file);
      return false;
   }

   public static function write($filepath, $text)
   {
      if ($file = fopen($filepath, 'w+'))
      {
         fwrite($file, $text);
      }
      fclose($file);
   }

   public static function append($filepath, $text)
   {
      $file = -1;
      if (!file_exists($filepath)) // si no existe lo crea!
      {
         $file = fopen($filepath, 'w+');
         fclose($file);
      }

      if ($file = fopen($filepath, 'a+'))
      {
         fputs($file, $text);
         fclose($file);
      }
   }

   public static function appendLine($filepath, $line)
   {
      FileSystem::append($filepath, $line . "
");
   }

   // TODO: Mejorar! leer con un buffer grande en lugar de linea por linea!
   public static function read ($fileName)
   {
      if (!file_exists($fileName)) return null;

      $lineasEOL = file($fileName);

      $s = "";
      for ($i = 0; $i < sizeof($lineasEOL); $i++)
      {
         $s .= $lineasEOL[$i];
      }

      return $s;
   }


   /*
      function loadFileContent($fileName)
      {

         if (!file_exists($fileName)) return null;

         $lineasEOL = file($fileName);

         for ($i = 0; $i < sizeof($lineasEOL); $i++)
         {
            $lineasEOL[$i] = chop($lineasEOL[$i]);
         }

         return $lineasEOL;
      }

      function std_loadFileContent($fileName)
      {

         if (!file_exists($fileName)) return null;

         $lineasEOL = file($fileName);

         $s = "";
         for ($i = 0; $i < sizeof($lineasEOL); $i++)
         {
            $s .= $lineasEOL[$i];
         }

         return $s;
      }

      function std_saveFileContent($string, $filename)
      {
         if ($file = fopen($filename, 'w+'))
         {
            fwrite($file, $string);
         }
         fclose($file);
      }

      function saveFileContent($lines, $filename)
      {
         if ($file = fopen($filename, 'w+'))
         {
            for ($i = 0; $i < sizeof($lines); $i++)
            {
               $data = $lines[$i];
               fwrite($file, $data);
               fwrite($file, "
                           ");
            }
         }
         fclose($file);
      }

      function saveFileContentKV($data, $filename)
      {
         if ($file = fopen($filename, 'w+'))
         {
            for ($i = 0; $i < sizeof($data); $i++)
            {
               $dataKV = each($data);
               fputs($file, $dataKV['key'] . "\n");
               fputs($file, $dataKV['value'] . "\n");
            }
         }
         fclose($file);
      }

      function fileLineCount($fileName)
      {

         $lines = $this->loadFileContent($fileName);
         return count($lines);
      }

      function appendStringToFile($string, $fileName)
      {

         $file = -1;
         if (!file_exists($fileName))
         {
            $file = fopen($fileName, 'w+');
            fclose($file);
         }

         if ($file = fopen($fileName, 'a+'))
         {
            fputs($file, $string);
            fclose($file);
         }
      }

      function appendLineToFile($line, $fileName)
      {

         $this->appendStringToFile($line . "
                  ", $fileName);
      }

      function downloadFile($file, $mimetype, $downfilename)
      {
         $status = 0;
         if (($file != NULL) && file_exists($file))
         {
            if (isset ($_SERVER['HTTP_USER_AGENT']) && preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT']))
            {

               ini_set('zlib.output_compression', 'Off');
            }

            header('Content-type: ' . $mimetype);

            header('Content-Disposition: attachment; filename="' . $downfilename . '"');
            header('Expires: ' . gmdate("D, d M Y H:i:s", mktime(date("H") + 2, date("i"), date("s"), date("m"), date("d"), date("Y"))) . ' GMT');
            header('Accept-Ranges: bytes');

            header("Cache-control: private");
            header('Pragma: private');

            $size = filesize($file);
            if (isset ($_SERVER['HTTP_RANGE']))
            {
               list ($a, $range) = explode("=", $_SERVER['HTTP_RANGE']);

               str_replace($range, "-", $range);
               $size2 = $size -1;
               $new_length = $size2 - $range;
               header("HTTP/1.1 206 Partial Content");
               header("Content-Length: $new_length");
               header("Content-Range: bytes $range$size2/$size");
            }
            else
            {
               $size2 = $size -1;
               header("Content-Range: bytes 0-$size2/$size");
               header("Content-Length: " . $size);
            }

            if ($file = fopen($file, 'r'))
            {
               while (!feof($file) and (connection_status() == 0))
               {
                  print (fread($file, 1024 * 8));
                  flush();
               }
               $status = (connection_status() == 0);
               fclose($file);
            }
         }
         return ($status);
      }

    */

}
?>
