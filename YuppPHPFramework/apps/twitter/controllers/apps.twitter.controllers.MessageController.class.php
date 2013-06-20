<?php

YuppLoader::load('twitter.model', 'TUser');
YuppLoader::load('twitter.model', 'Message');

class MessageController extends YuppController {

   /*
   public function indexAction()
   {
      return $this->renderString("Bienvenido a su nueva aplicacion!");
   }
   */
   
   /*
    * FIXME: deberia ser ajax...
    */
   public function sendMessageAction()
   {
      if (isset($this->params['doit']))
      {
         $user = TUser::getLogged();
         $message = new Message($this->params);
         $message->setCreatedBy($user);
         if (!$message->save())
         {
            $this->flash['error'] = 'twitter.message.sendMessage.error';
            
            $messages = Message::getTimeline($user);
            $this->params['messages'] = $messages;
            $this->params['user'] = $user;
            
            return $this->render('../user/timeline');
         }
         
         // go back to my timeline
         return $this->redirect(array('controller'=>'user', 'action'=>'timeline'));
      }
      
      // renders sendMessage.view.php
   }
}

?>