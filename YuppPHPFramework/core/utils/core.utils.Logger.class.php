<?php

YuppLoader :: load("core", "FileSystem");

class Logger {

   // TODO: En lugar de solo poder apagar y prender el log, quiero poder mostrar por niveles, o sea
   //       apagar los de menos nivel y poder ver los de mas nivel, asi puedo debugear por capa o por
   //       importancia del modulo.

   private $active = true;
   private static $instance = NULL;

   // Para guardar logs en archivo.
   private $file = NULL;
   
   public function setFile($filename)
   {
      $this->file = $filename;
   }

   // Para guardar arbol de logs =======
   
   // Matrix cant. llamadas / nivel
   private $buffer = array();
   private $currentIndex = 0;
   
   const LEVEL_KEY_PO  = "persistent_object";  // Index 0
   const LEVEL_KEY_PM  = "persistent_manager"; // Index 1
   const LEVEL_KEY_DAL = "persistent_dal";     // Index 2
   const LEVEL_KEY_DB  = "persistent_db";      // Index 3
   
   // $index que se le pasa a add.
   const LEVEL_PO  = 0;  // Index 0
   const LEVEL_PM  = 1;  // Index 1
   const LEVEL_DAL = 2;  // Index 2
   const LEVEL_DB  = 3;  // Index 3
   
   //private $level = 0; // Nivel en el que se esta actualmente, para el indice $currentIndex.

   public static function add( $index, $message = "" )
   {
      $log = Logger::getInstance();
      $log->_add($index, $message);
   }
   
   public static function printTree()
   {
      $log = Logger::getInstance();
      $log->_printTree();
   }
   
   public function _add( $index, $message = "", $customData= NULL )
   {
      // TODO: ver como acomodar la customData.

      if ( !array_key_exists($this->currentIndex, $this->buffer) || $this->buffer[$this->currentIndex] === NULL )
         $this->buffer[$this->currentIndex] = array(); // un lugar para cada posible indice.
         
      $this->buffer[$this->currentIndex][$index] = $message;
      $this->currentIndex ++;
   }
   
   public function _printTree()
   {
      $string = "";
      foreach ($this->buffer as $levels)
      {
         for ($i=0; $i<4; $i++)
         {
            if ($levels[$i] !== NULL)
            {
               for ($j=0; $j<$i; $j++) $string .= "   "; // tantos tabs como el nivel
               
               switch ( $i )
               {
                  case 0: $string .= "* " . self::LEVEL_KEY_PO  . " *: "; break;
                  case 1: $string .= "* " . self::LEVEL_KEY_PM  . " *: "; break;
                  case 2: $string .= "* " . self::LEVEL_KEY_DAL . " *: "; break;
                  case 3: $string .= "* " . self::LEVEL_KEY_DB  . " *: "; break;
               }
               
               $string .= $levels[$i] . "\n"; // concatena el mensaje + new line
            }
         }
      }
      
      echo $string;
   }
   
   // / Para guardar arbol de logs =======

   public static function getInstance()
   {
      if (!self::$instance) self::$instance = new Logger();
      return self::$instance;
   }

   private function __construct() {}
   
   public static function struct( $obj, $msg = "" )
   {
      $log = self::getInstance();
      if ($log->file !== NULL)
      {
         $txt = "<pre> $msg<br/>";
         $txt .= print_r( $obj, true );
         $txt .= "</pre>";
         FileSystem::appendLine($log->file, $txt);
         
         return;
      }
      
      echo "<pre> $msg<br/>";
      print_r( $obj );
      echo "</pre>";
   }
   
   public static function show( $msg, $tag = NULL )
   {
      $log = self::getInstance();
      if ($log->active)
      {
         if ($log->file !== NULL)
         {
            $txt = (($tag)?"<$tag>":"") . $msg . (($tag)?"</$tag>":"");
            FileSystem::appendLine($log->file, $txt);
            return;
         }
          
         echo (($tag)?"<$tag>":"") . $msg . (($tag)?"</$tag>":"");
      }
   }

   public function off()
   {
      $this->active = false;
   }
   
   public function on()
   {
      $this->active = true;
   }

   public function log( $msg )
   {
      if ($this->active)
      {
         if ($this->file !== NULL)
         {
            $txt = "[" . $msg . "]";
            FileSystem::appendLine($this->file, $txt);
            return;
         }
         echo "[" . $msg . "]<br />";
      }
   }

   public function info( $msg )
   {
      if ($this->active)
      {
         echo '<div style="background: #6af; border: 1px solid #036;">';
         echo $msg;
         echo '</div>';
      }
   }

   public function warn( $msg )
   {
      if ($this->active)
      {
         echo '<div style="background: #ffff80; border: 1px solid #aaaa00;">';
         echo $msg;
         echo '</div>';
      }
   }

   public function error( $msg )
   {
      if ($this->active)
      {
         echo '<div style="background: #f66; border: 1px solid #f00;">';
         echo $msg;
         echo '</div>';
      }
   }

   // Dal tiene fondo rojo y letras blancas... y dos niveles de tab.
   public function dal_log($msg)
   {
      if ($this->active)
      {
         if ($this->file !== NULL)
         {
            $txt = "[" . $msg . "]";
            FileSystem::appendLine($this->file, $txt);
            return;
         }
         echo '<div style="background: #f00; border: 1px solid #000; color: #fff;">';
         echo "\t\t" . $msg;
         echo '</div>';
      }
   }

   // Persistent Manager tiene fondo azul y letras blancas... y un nivel de tab.
   public function pm_log($msg)
   {
      if ($this->active)
      {
         if ($this->file !== NULL)
         {
            $txt = "[" . $msg . "]";
            FileSystem::appendLine($this->file, $txt);
            return;
         }
         echo '<div style="background: #00f; border: 1px solid #000; color: #fff;">';
         echo "\t" . $msg;
         echo '</div>';
      }
   }

   // DatabaseMySQL tiene fondo anaranjado y letras negras... y tres niveles de tab.
   public function dbmysql_log($msg)
   {
      if ($this->active)
      {
         if ($this->file !== NULL)
         {
            $txt = "[" . $msg . "]";
            FileSystem::appendLine($this->file, $txt);
            return;
         }
         echo '<div style="background: #ff0; border: 1px solid #000; color: #000;">';
         echo "\t\t\t" . $msg;
         echo '</div>';
      }
   }

   public function po_log($msg)
   {
      if ($this->active)
      {
         if ($this->file !== NULL)
         {
            $txt = "[" . $msg . "]";
            FileSystem::appendLine($this->file, $txt);
            return;
         }
         echo '<div style="background: #0f0; border: 1px solid #000; color: #000;">';
         echo $msg;
         echo '</div>';
      }
   }

   public function artholder_log($msg)
   {
      if ($this->active)
      {
         if ($this->file !== NULL)
         {
            $txt = "[" . $msg . "]";
            FileSystem::appendLine($this->file, $txt);
            return;
         }
         echo '<div style="background: #fa0; border: 1px solid #000; color: #000;">';
         echo $msg;
         echo '</div>';
      }
   }

}

?>