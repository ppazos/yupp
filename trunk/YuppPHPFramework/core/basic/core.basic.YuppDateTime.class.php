<?php

class YuppDateTime {
   
   const MySQL_DATETIME_FORMAT  = "Y-m-d H:i:s";
   const MySQL_DATE_FORMAT     = "Y-m-d";
   const MySQL_TIME_FORMAT     = "H:i:s";
   
   public static function dateParts( $date )
   {
      // FIXME: match con MySQL_DATE_FORMAT
      $arr = explode( "-", $date );
      $arr['year']  = (int)$arr[0];
      $arr['month'] = (int)$arr[1];
      $arr['day']   = (int)$arr[2];
     
      return $arr;
   }
   
   // Verifica que $mysql_date tenga formato aaaa-mm-dd y que sea valida.
   public static function checkMySQLDate($mysql_date)
   {
      // Verifica formato
      if (preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $mysql_date, $parts))
      {
         // Verifica validez de la fecha
         if(checkdate($parts[2],$parts[3],$parts[1])) return true;
      }
    
      return false;
   }
   
   public static function checkMySQLDateTime($mysql_date_time)
   {
      $arr = explode( " ", $mysql_date_time );
     
      // Para ser datetime debe tener date y time
      if (count($arr)!=2) return false;
     
      $date = $arr[0];
      $time = $arr[1];
     
      // Verifica la parte date
      if (self::checkMySQLDate($date))
      {
         // Verifica la parte time
         $arr = explode( ":", $time );
       
         // Horas y minutos obligatorios, segundos opcionales
         if (count($arr)<2) return false;
       
         $hour = (int)$arr[0];
         $mins = (int)$arr[1];
       
         $segs = 0;
         if (isset($arr[2])) $segs = (int)$arr[2];

         return ($hour<24 && $hour>=0 && $mins<60 && $mins>=0 && $segs<60 && $segs>=0);
      }
   }

   function checktime($hour, $minute)
   {
      if ($hour > -1 && $hour < 24 && $minute > -1 && $minute < 60)
      {
         return true;
      }
   } 

   public static function timeToMySQLDate($timestamp)
   {
      return date(self::MySQL_DATE_FORMAT, $timestamp);
   }
   
   public static function timeToMySQLDateTime($timestamp)
   {
      return date(self::MySQL_DATETIME_FORMAT, $timestamp);
   }
   
   public static function timeToMySQLTime($timestamp)
   {
      return date(self::MySQL_TIME_FORMAT, $timestamp);
   }
   
   public static function mySQLDateToTime( $mysql_date ) 
   {
     list($year, $month, $day) = split('-', $mysql_date);
     
     $diasMes = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
     
     echo "YEAR $year<br/>";
     echo "MONTH $month<br/>";
     echo "DAY $day<br/>";
     
     $segundosPorDiasDeMeses = 0;
     for($i=0; $i<$month; $i++) $segundosPorDiasDeMeses += $diasMes[$i] * 24 * 60 * 60;
     
     // FIXME: dias del mes! dependen del mes! le puse 30!
     // Esto da parecido pero esta por debajo del tiempo real! es porque le falta el tiempo! (horas, minutos, segundos)
     return (($year-1970) * 365 * 24 * 60 * 60) +
             $segundosPorDiasDeMeses + 
             //(($month-1) * 30 * 24 * 60 * 60) + // -1 porque el mes actual no termina todavia! LOS DIAS DEBERIAN SER DISTINTOS PARA CADA MES!
             (($day-1) * 24 * 60 * 60); // si es solo date, claramente van a faltar minutos y segundos para la cuenta!
   }
}
?>