<?php

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

      //return $this->render("usuario/list", & $this->params); // Id NULL para paginas de scaffolding
      return $this->render("list", & $this->params); // Id NULL para paginas de scaffolding
   }
   

   /**
    * Lo que quiero es que si ejecutan /blog/usuario/createUser, empiece a ejecutar el flow.
    */
   protected function createUserFlow()
   {
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
   	if ( array_key_exists('doit', $this->params) )
      {
         if ( $this->params['name'] === "" ) // TODO: validacion automatica desde las constraints de Usuario
         {
         	return "createUserFlow.fillName.error.nameRequired"; // Devolviendo un error no deja hacer la transicion.
         }
         
         $flow->addToModel("name", $this->params['name']); // Agrego el nombre a memoria de flow
         $flow->addToModel("edad", $this->params['edad']);
         
         return "move"; // Accion completada correctamente, le permito al flow moverse al proximo estado, es como un redirect a fillUserIdAndPass.
      }
      
      // Si no retorno nada, es que quiero que muestre la vista correspondiente a la accion.
   }

   /**
    * createUserFlow.fillUserAndPass
    */
   public function fillUserAndPassAction( &$flow )
   {
      if ( array_key_exists('doit', $this->params) )
      {
         if ( $this->params['email'] === "" || $this->params['pass'] === "" ) // TODO: validacion automatica desde las constraints de Usuario
         {
            return "createUserFlow.fillUserAndPass.error.userAndPassRequired"; // Devolviendo un error no deja hacer la transicion.
         }
         
         $flow->addToModel("email", $this->params['email']); // Agrego el nombre a memoria de flow
         $flow->addToModel("pass", $this->params['pass']);
         
         return "move"; // Accion completada correctamente, le permito al flow moverse al proximo estado, es como un redirect a fillUserIdAndPass.
      }
      
      // Si no retorno nada, es que quiero que muestre la vista correspondiente a la accion.
   }
   
   /**
    * createUserFlow.displayUser
    */
   public function displayUserAction( &$flow )
   {
      // ??? puedo redirigir a show directamente sin tener esta accion?
      $data = $flow->getModel();
      
      $user = new Usuario( array (
                                  "nombre" => $data["name"],
                                  "email" =>  $data["email"],
                                  "clave" => $data["pass"],
                                  //"fechaNacimiento" => "1981-10-24 09:59:00",
                                  "edad" =>  $data["edad"],
                                  //"gggf" => "2008-09-23 00:39:38"
                                ) );
                        
      if ( !$user->save() )
      {
         Logger::struct( $user->getErrors() );
         // FIXME: Si hay un error deberia regresar la maquina de estados al estado anterior.
      }
      
      // No devuelvo nada, es el ultimo estado y todo salio OK.
      // TODO: y si quiero devolver el usuario en modelo?
      $flow->addToModel("usuario", $user);
   }
   
   // ======================================================================================================================

   public function loginAction()
   {
       // OBS: si retorno NULL o modelo, desde la accion index, se intenta mostrar la vista index.view.php.
       if ( array_key_exists( 'doit', $this->params) )
       {
          if (!array_key_exists('email',$this->params) || !array_key_exists('clave', $this->params))
          {
          	 $this->flash['message'] = "Por favor ingrese email y clave";
             //return $this->render("/usuario/login", &$this->params);
             return $this->render("login", &$this->params);
          }
          
          // Login
       	 $tableName = YuppConventions::tableName( 'Usuario' ); // Se le pasa la clase, para saber la tabla donde se guardan sus instancias.
          $condition = Condition::_AND()
                          ->add( Condition::EQ($tableName, "email", $this->params['email']) )
                          ->add( Condition::EQ($tableName, "clave", $this->params['clave']) );
          
          $list = Usuario::findBy( $condition, &$this->params );
       
          //print_r( $list );
          
          if ( count($list) === 0 )
          {
          	 $this->flash['message'] = "El usuario no existe";
             //return $this->render("/usuario/login", &$this->params);
             return $this->render("login", &$this->params);
          }
          
          // Uusario logueado queda en session
          YuppSession::set("user", $list[0]);
          
          // TODO: crear cookie y verificar en lugar del login.

          $this->flash['message'] = "Usuario logueado con &eacute;xito!";
          return $this->redirect( array("controller" => "entradaBlog", "action" => "list") );
       }
       
    	 //return $this->render("/usuario/login", &$this->params);
       return $this->render("login", &$this->params);
   }

    public function logoutAction()
    {
       YuppSession::remove("user");
       $this->flash['message'] = "Vuelve a ingresar en otra ocasi&oacute;n!'";
    	 //return $this->render("/usuario/login", &$this->params);
       return $this->render("login", &$this->params);
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