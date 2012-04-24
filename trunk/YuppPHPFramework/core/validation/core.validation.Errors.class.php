<?php

//YuppLoader::load('core.validation','Constraints');
//YuppLoader::loadScript('core.validation','Messages');
//YuppLoader :: load('core.mvc', 'DisplayHelper');

/**
 * http://code.google.com/p/yupp/issues/detail?id=145
 */
class Errors implements IteratorAggregate {

   /*
    * Lista de errores de validacion por nombre del campo de un PersistentObject.
    */
   private $errors = array();

   /*
    * Implementar IteratorAggregate permite que se puedan iterar los errores de $this->errors.
    */
   public function getIterator()
   {
      return new ArrayIterator($this->errors);
   }
   
   /**
    * Agrega un error al campo $attr.
    */
   public function add($attr, $error)
   {
      if (!isset($this->errors[$attr])) $this->errors[$attr] = array();
      
      $this->errors[$attr][] = $error;
   }
   
   /**
    * Devuelve los errores para un atributo, los errores se generan luego de validar.
    * 
    * @param String attr nombre del atributo para el cual se quieren los errores de validacion.
    */
   public function getFieldErrors( $attr )
   {
      if (isset($this->errors[$attr]) || array_key_exists($attr, $this->errors))
         return $this->errors[$attr];
      
      return NULL;
   }
   
   /**
    * True si la instancia de la clase tiene algun error de validacion. False en otro caso.
    */
   public function hasErrors()
   {
      return count($this->errors) !== 0; // errors esta inicializado en un array
   }
   
   /**
    * True si el campo $attr tiene errores. False en otro caso.
    */
   public function hasFieldErrors($attr)
   {
      return count($this->errors) !== 0 && (isset($this->errors[$attr]) || array_key_exists($attr, $this->errors));
   }
   
   /**
    * Cantidad total de erorres.
    */
   public function countErrors()
   {
      return count($this->errors);
   }
}
?>