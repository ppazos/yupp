<?php

YuppLoader::load('twitter.model', 'TUser');

class Message extends PersistentObject
{
   const TABLE = 'twitter_messages';
   
   public static function getFor($user)
   {
      if ($user == NULL) throw new Exception('user is null');
      
      $cond = Condition::EQ(self::TABLE, 'createdBy_id', $user->getId());
      $list = Message::findBy( $cond, new ArrayObject(array('dir'=>'desc')) );
      return $list;
   }
   
   public static function getTimeline($user, $offset = 0, $max = 20)
   {
      if ($user == NULL) throw new Exception('user is null');
      
      // Messages belonging to me or the users I follow
      $_or = Condition::_OR()
               ->add( Condition::EQ(self::TABLE, 'createdBy_id', $user->getId()) ); // I want to see my messages also
      
      $following = $user->getFollowing();
      foreach ($following as $otherUser)
      {
         $_or->add( Condition::EQ(self::TABLE, 'createdBy_id', $otherUser->getId()) );
      }
      
      // Paginated search
      // Messages ordered by createdOn, first the last messages
      $list = Message::findBy( $_or, new ArrayObject(array('sort'=>'createdOn', 'dir'=>'desc', 'offset'=>$offset, 'max'=>$max)) );
      
      return $list;
   }
   
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable(self::TABLE);
      
      // message data
      $this->addAttribute('text',       Datatypes :: TEXT);
      $this->addAttribute('createdOn',  Datatypes :: DATETIME);

      // associations
      $this->addHasOne('createdBy', 'TUser'); // the user that creates this twitter message
      
      // default values
      $this->setCreatedOn(date("Y-m-d H:i:s")); // Ya con formato de MySQL!
      
      // constraints
      $this->addConstraints('text' , array (
         Constraint :: maxLength(160), // 160 chars max
         Constraint :: nullable(false),
         Constraint :: blank(false)
      ));      
      

      parent :: __construct($args, $isSimpleInstance);
   }

   public static function listAll(ArrayObject $params)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: listAll($params);
   }

   public static function count()
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: count();
   }

   public static function get($id)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: get($id);
   }

   public static function findBy(Condition $condition, ArrayObject $params)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: findBy($condition, $params);
   }

   public static function countBy(Condition $condition)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: countBy($condition);
   }
}
?>