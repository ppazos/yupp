<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
 
class UsuarioController extends YuppController {

    /**
     * Accion por defecto.
     */
    public function indexAction()
    {
       $loguedUser = YuppSession::get("user"); // Lo pone en session en el login.
       if ($loguedUser !== NULL)
          return $this->listAction();
       else
          return $this->loginAction();
    }

   public function listAction()
   {
      // paginacion
      if (!array_key_exists('max', $this->params))
      {
         $this->params['max'] = 10;
         $this->params['offset'] = 0;
      }

      $this->params['list']  = Usuario::listAll( $this->params );
      $this->params['count'] = Usuario::count(); // Maximo valor para el paginador.

      return $this->render("list");
   }
   

   /**
    * Lo que quiero es que si ejecutan /blog/usuario/createUser, empiece a ejecutar el flow.
    */
   protected function createUserFlow()
   {
      //echo "<h1>CREATE USER FLOW</h1>";
      //Logger::struct( $this );
      
      $flow = WebFlow::create("createUser")
                ->add( // El primero que se agrega, por defecto, es el inicial!!!
                  State::create( "fillName" ) // Se asume que hay una vista "fillName" para el flow.
                    ->add( Transition::create( "nameFilled", "fillUserAndPass" ) )
                    //->add( Transition::create( "logout", "displayLogin" ) )
                )
                ->add(
                  State::create( "fillUserAndPass" )
                    ->add( Transition::create( "userIdFilled", "displayUser" ) )
                    //->add( Transition::create( "funishShopping", "displayInvoice" ) )
                )
                ->add(
                  State::create( "displayUser" ) // Un estado sin transiciones de salida es un estado final.
                );                               // El nombre del estado final seria la accion a la que redirijo para que muestre una pagina o tambine podria ella misma mostrar una pagina con el modelo del flow, hay que ver que es lo mejor.
                
      // Debe retornar el flow y el framework se encargua de ponerlo en CurrentFlows, asi el usuario no lo tiene que hacer.
      return $flow;
   }

   /**
    * createUserFlow.fillName
    */
   public function fillNameAction( &$flow )
   {
      //echo "<h1>FILL NAME FLOW</h1>";
      //Logger::struct( $this );
      
   	if ( isset($this->params['doit']) )
      {
         $user = new Usuario( array("nombre" => $this->params["name"], "edad" => $this->params["edad"]) );
         $flow->addToModel("usuario", $user);
         
         if ( !$user->validateOnly( array("nombre", "edad") ) ) // TODO: validacion automatica desde las constraints de Usuario
         {
         	return "createUserFlow.fillName.error.pleaseVerifyEnteredData"; // Devolviendo un error no deja hacer la transicion.
         }
         
         return "move"; // Accion completada correctamente, le permito al flow moverse al proximo estado, es como un redirect a fillUserIdAndPass.
      }
      
      // Si no retorno nada, es que quiero que muestre la vista correspondiente a la accion.
   }

   /**
    * createUserFlow.fillUserAndPass
    */
   public function fillUserAndPassAction( &$flow )
   {
      //echo "<h1>FILL USER AND PASS FLOW</h1>";
      //Logger::struct( $this );
      
      if ( isset($this->params['doit']) )
      {
         $user = $flow->getFromModel("usuario");
         $user->setEmail( $this->params['email'] );
         $user->setClave( $this->params['pass'] );
         $flow->addToModel("usuario", $user);
         
         if ( !$user->validateOnly( array("email", "clave") ) ) // TODO: validacion automatica desde las constraints de Usuario
         {
            return "createUserFlow.fillUserAndPass.error.pleaseVerifyEnteredData"; // Devolviendo un error no deja hacer la transicion.
         }
                           
         if ( !$user->save() )
         {
            //Logger::struct( $user->getErrors() );
            return "createUserFlow.fillUserAndPass.error.errorSavingUser"; // Devolviendo un error no deja hacer la transicion.
         }

         return "move"; // Accion completada correctamente, le permito al flow moverse al proximo estado, es como un redirect a fillUserIdAndPass.
      }
      
      // Si no retorno nada, es que quiero que muestre la vista correspondiente a la accion.
   }
   
   /**
    * createUserFlow.displayUser
    */
   public function displayUserAction( &$flow )
   {
      // No devuelvo nada, es el ultimo estado y todo salio OK.
   }
   
   // ======================================================================================================================

   public function loginAction()
   {
       // OBS: si retorno NULL o modelo, desde la accion index, se intenta mostrar la vista index.view.php.
       if ( isset($this->params['doit']) )
       {
          if (!isset($this->params['email']) || !isset($this->params['clave']))
          {
          	 $this->flash['message'] = "Por favor ingrese email y clave";
             return $this->render("login");
          }
          
          // Login
       	 $tableName = YuppConventions::tableName( 'Usuario' ); // Se le pasa la clase, para saber la tabla donde se guardan sus instancias.
          /*
          $condition = Condition::_AND()
                          ->add( Condition::EQ($tableName, "email", $this->params['email']) )
                          ->add( Condition::EQ($tableName, "clave", $this->params['clave']) );
          */
          $condition = Condition::_AND()
                          ->add( Condition::EQ($tableName, "email", $this->params['email']) )
                          ->add( Condition::STREQ($tableName, "clave", $this->params['clave']) ); // Nueva solucion: se usa STREQ
          
          $list = Usuario::findBy( $condition, $this->params );
       
          if ( count($list) === 0 )
          {
          	 $this->flash['message'] = "El usuario no existe";
             return $this->render("login");
          }
          
          /**
           * Problema con comparacion de Srtings en MySQL:
           * - no distingue entre mayusculas y minusculas.
           * => se verifica por mayusculas y minusculas.
           * Gracias Shadow!
           */
          //SOLUCION AL PROBLEMA
          /*
          if ( strcmp( $list[0]->getClave(), $this->params['clave'] ) != 0 )
          {
             $this->flash['message'] = "La contrase&ntilde;a es incorrecta";
             return $this->render("login");
          }
          */
          //FIN DE LA SOLUCION
          
          // Uusario logueado queda en session
          YuppSession::set("user", $list[0]);
          
          // TODO: crear cookie y verificar en lugar del login.

          $this->flash['message'] = "Usuario logueado con &eacute;xito!";
          return $this->redirect( array("controller" => "entradaBlog", "action" => "list") );
       }
       
       return $this->render("login");
   }

    public function logoutAction()
    {
       YuppSession::remove("user");
       $this->flash['message'] = "Vuelve a ingresar en otra ocasi&oacute;n!'";
       return $this->render("login");
    }
    
    public function deleteAction()
    {
       $id  = $this->params['id'];
       $ins = Usuario::get( $id );
       $ins->delete(true); // Eliminacion logica, si fuera fisica tendria que actualizar los links a las entradas, o borrar tambien las entradas del usuario.
       $this->flash['message'] = "Elemento [Usuario:$id] eliminado.";
       return $this->redirect( array("action" => "list") ); // FIXME: el redirect mata el flash!
    }

}
?>