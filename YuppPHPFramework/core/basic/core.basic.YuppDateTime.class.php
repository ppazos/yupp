<?php
class YuppDateTime
{
   const MySQL_DATETIME_FORMAT  = "Y-m-d H:i:s";
   const MySQL_DATE_FORMAT      = "Y-m-d";
   const MySQL_TIME_FORMAT      = "H:i:s";

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