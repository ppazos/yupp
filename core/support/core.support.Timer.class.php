<?php

/**
 * Esta clase sirve para calcular tiempos y medir performance.
 */
class Timer
{
    private $start;
    private $end;
    private $acum = 0; // Acumulado de varios start/stop
    
    /*
     * http://www.tonymarston.net/php-mysql/elapsed-time.html
The first step is to capture the start time for the event. The microtime() function provides two values - one in seconds and another in microseconds.

   list($usec, $sec) = explode(' ', microtime());
   $script_start = (float) $sec + (float) $usec;

When the event has completed you need similar code to capture the end time.

   list($usec, $sec) = explode(' ', microtime());
   $script_end = (float) $sec + (float) $usec;

The final step is to calculate the difference between the two times. Here I am rounding the result to 5 decimal places.

   $elapsed_time = round($script_end - $script_start, 5);

     */
    
    public function start()
    {
        list($usec, $sec) = explode(' ', microtime());
        $this->start = (float) $sec + (float) $usec;
        //$this->start = microtime(true);
    }
    
    public function stop()
    {
       //$this->end = microtime(true);
       //$this->acum += $this->end - $this->start;
       
       list($usec, $sec) = explode(' ', microtime());
       $this->end = (float) $sec + (float) $usec;
       
       $this->acum += round($this->end - $this->start, 5);
    }
    
    /**
     * Vuelve el tiempo acumulado a cero.
     */
    public function reset()
    {
        $this->acum = 0;
    }
    
    /**
     * Tiempo total transcurrido
     */
    public function getElapsedTime()
    {
        return $this->acum;
    }
}

?>