<?php

YuppLoader::load('twitter.model', 'TUser');
YuppLoader::load('twitter.model', 'Message');

class UserController extends YuppController {

   public function indexAction()
   {
      return $this->redirect(array('action'=>'login'));
   }
   
   public function registerAction()
   {
      if (isset($this->params['doit']))
      {
         $user = new TUser($this->params);
         if (!$user->save())
         {
            $this->flash['error'] = 'twitter.user.register.error';
            return array('user'=>$user);
         }
         
         $this->flash['message'] = 'twitter.user.register.ok';
         return $this->redirect(array('action'=>'login'));
      }
   }
   
   public function loginAction()
   {
      if (isset($this->params['doit']))
      {
         $ret = TUser::login( ((isset($this->params['username']))? $this->params['username'] : NULL),
                             ((isset($this->params['password']))? $this->params['password'] : NULL));
         switch ($ret)
         {
            case TUser::LOGIN_ERR_INCOMPLETE:
               $this->flash['error'] = 'twitter.user.login.incomplete';
            break;
            case TUser::LOGIN_ERR_FAILED:
               $this->flash['error'] = 'twitter.user.login.failed';
            break;
            case TUser::LOGIN_ERR_SUCCESS:
               $this->flash['message'] = 'twitter.user.login.ok';
               return $this->redirect(array('action'=>'timeline'));
            break;
         }
      }
      // renders login.view.php
   }
   
   public function logoutAction()
   {
      $user = TUser::getLogged();
      $user->logout();
      return $this->redirect(array('action'=>'login'));
   }
   
   public function timelineAction()
   {
      if (isset($this->params['id']))
      {
         $user = TUser::get($this->params['id']);
      }
      else
      {
         $user = TUser::getLogged();
      }
      
      $messages = Message::getTimeline($user);
      
      // renders timeline.view.php
      return array('messages'=>$messages, 'user'=>$user);
   }
   
   // es necesaria? no puedo hacer todo desde timeline?
   /*
   public function profileAction()
   {
      if (isset($this->params['id']))
      {
         $user = TUser::get($this->params['id']);
      }
      else
      {
         $user = TUser::getLogged();
      }
      
      return array('user'=>$user);
   }
   */
   
   public function findAction()
   {
      $q = $this->params['q'];
      $cond = Condition::LIKE(TUser::TABLE, 'name', "%$q%");
      $users = TUser::findBy($cond, new ArrayObject());
      
      return array('users'=>$users);
   }

   public function followAction()
   {
      if (isset($this->params['id']))
      {
         // EXCEPT: debe seleccionar un usuario a seguir
      }
      
      $user = TUser::getLogged();
      $follow = TUser::get($this->params['id']);
      
      if (isset($this->params['follow']))
      {
         $this->flash['message'] = 'You started following '. $follow->getName();
         $user->addToFollowing($follow);
      }
      else if (isset($this->params['unfollow']))
      {
         $this->flash['message'] = 'You stoped following '. $follow->getName();
         $user->removeFromFollowing($follow);
      }
      
      $user->save();
      
      return $this->redirect(array('action'=>'timeline'));
   }
}

?>