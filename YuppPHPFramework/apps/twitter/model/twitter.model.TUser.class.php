<?php

class TUser extends PersistentObject
{
   const TABLE = 'twitter_users';

   const LOGIN_ERR_INCOMPLETE = 1; // Falta username o password
   const LOGIN_ERR_FAILED     = 2; // Login fallido, usuario no existe
   //const LOGIN_ERR_PENDING    = 3; // Usuario aun pendiente de aprobacion
   const LOGIN_ERR_SUCCESS    = 4; // Login exitoso

   /**
    * @param string username
    * @param string password
    * @param boolean remember
    */
   static public function login($username, $password)
   {
      if (empty($username) || empty($password))
      {
         return self::LOGIN_ERR_INCOMPLETE;
      }
      
      $cond = Condition::_AND()
                ->add( Condition::EQ(self::TABLE, 'username', $username) )
                ->add( Condition::EQ(self::TABLE, 'password',  $password) );
    
      $list = TUser::findBy( $cond, new ArrayObject() );
      
      if ( count($list) == 0 )
      {
         return self::LOGIN_ERR_FAILED;
      }
      
      $user = $list[0];
      
      // Uusario logueado queda en session
      YuppSession::set('_twitter_user', $user);
      
      
      $user->save(); // TODO: check 4 errors
      
      // TODO: se deberia llevar log de la IP+userid+fecha
      // Se podria hacer un archivo de log en disco por cada user id y poner fechas con ips nomas
      
      return self::LOGIN_ERR_SUCCESS;
   }
   
   /**
    * Actualiza al usuario en sesion.
    * Se usa para cuando se actualizan datos en la base pero que
    * usuario esta logueado y la sesion queda desactualizada.
    */
   public function refresh()
   {
      $user = YuppSession::get('_twitter_user');
      $user = TUser::get($user->getId()); // Recarga de la base
      YuppSession::set('_twitter_user', $user);
   }
   
   static public function logout()
   {
      //$user = YuppSession::get('_twitter_user');
      return YuppSession::remove('_twitter_user');
   }
   
   /**
    * @return TUser el usuario logueado o null.
    */
   static public function getLogged()
   {
      return YuppSession::get('_twitter_user');
   }

   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable(self::TABLE);
      
      // user data
      $this->addAttribute('name',     Datatypes :: TEXT);
      $this->addAttribute('email',    Datatypes :: TEXT);
      
      // auth
      $this->addAttribute('username', Datatypes :: TEXT);
      $this->addAttribute('password', Datatypes :: TEXT);
      
      // associations
      $this->addHasMany('following', 'TUser', PersistentObject::HASMANY_SET); // users I follow, is a set

      // constraints
      $this->addConstraints('name' , array (
         Constraint :: minLength(1),
         Constraint :: maxLength(255),
         Constraint :: nullable(false),
         Constraint :: blank(false)
      ));
      $this->addConstraints('username' , array (
         Constraint :: blank(false)
      ));
      $this->addConstraints('password', array (
         Constraint :: minLength(4)
      ));
      $this->addConstraints('email', array (
         Constraint :: email()
      ));
      
      parent :: __construct($args, $isSimpleInstance);
   }
   
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}
?>