<?php

YuppLoader::load('core.http', 'HTTPRequest');

class MovieController extends YuppController {

   public function indexAction()
   {
      return $this->redirect(array('action' => 'list'));
   }
   
   public function listAction()
   {
      if ( !isset($this->params['max']) ) // paginacion
      {
         $this->params['max'] = 10;
         $this->params['offset'] = 0;
      }

      if (!empty($this->params['filter_genre']))
      {
         $tableName = YuppConventions::tableName( 'Movie' );
         /*
         $condition = Condition::_AND()
                        ->add( Condition::LIKE($tableName, 'genres',    '%'.$this->params['filter_genre'].'%') )
                        ->add( Condition::EQ($tableName, "password",    $password) )
                        ->add( Condition::EQ($tableName, "institucion", $codInstitucion) );
         */
         $condition = Condition::LIKE($tableName, 'genres', '%'.$this->params['filter_genre'].'%');
         $list = Movie::findBy( $condition, $this->params );
         $count = Movie::countBy( $condition );
      }
      else
      {
         $list  = Movie::listAll( $this->params );
         $count = Movie::count();
      }

      $this->params['class'] = 'Movie';
      $this->params['list']  = $list;
      $this->params['count'] = $count;

      return $this->render("list");
   }
   
   public function createAction()
   {
      if (isset($this->params['doit']))
      {
         $movie = new Movie($this->params);
         if (!$movie->save())
         {
            $this->flash['message'] = 'Ingrese el nombre';
            $this->params['movie'] = $movie;
         }
         else
         {
            return $this->redirect(array('action'=>'getMovieData', 'params'=>array('id'=>$movie->getId())));
         }
      }
   }
   
   public function getMovieDataAction()
   {
      //print_r($this->params);
      
      $movie = Movie::get($this->params['id']);
      $this->params['movie'] = $movie;
      
      if (isset($this->params['doit']))
      {
         if (isset($this->params['url']))
            $this->params['url'] = stripslashes($this->params['url']);
         
         $movie->setProperties($this->params);
         if (!$movie->save())
         {
            $this->flash['message'] = 'Error';     
         }
         else
         {
            return $this->redirect(array('action'=>'list'));
         }
      }
   }
   
   public function getMovieDataJSONAction()
   {
      set_time_limit(10);
      
      $req = new HTTPRequest();
      $req->setTimeOut( 5 );
      
      // DOC: http://www.deanclatworthy.com/imdb/
      
      $movie = Movie::get($this->params['id']);
      
      $url = 'http://www.deanclatworthy.com/imdb/?q='. str_replace(' ','+',$movie->getName());

      $res = $req->HTTPRequestGet( $url );
      $json = $res->getBody();
      
      //echo $res->getStatus();
      
      header('Content-Type: application/json');
      return $this->renderString( $json );
   }
   

   /*
   public function editAction()
   {
      $this->params['config'] = Config::get( 1 ); // Hay solo una sola config.
      return;
   }
   
   public function saveAction()
   {
      if (isset($this->params['cancel']))
      {
         return $this->redirect( array("controller" => "page",
                                       "action"     => "display",
                                       "params"     => array("_param_1" => "index")) );
      }
      
   	$config = Config::get( 1 );
      
      //
      // TODO:
      // Validar que> approveEmailText tiene USER_EMAIL, USER_PASSWORD, DOMAIN y ADMIN_EMAIL.
      // Validar que> sendPasswordEmailText tiene USER_PASSWORD, DOMAIN y ADMIN_EMAIL.
      //
      // Esto deberia hacerse en un custom validator.
      $spet = $this->params['sendPasswordEmailText'];
      $pos1 = strpos($spet, '[USER_PASSWORD]');
      $pos2 = strpos($spet, '[DOMAIN]');
      $pos3 = strpos($spet, '[ADMIN_EMAIL]');
      $errorSpet = ($pos1 === false || $pos2 === false || $pos3 === false);
  
      $aet = $this->params['approveEmailText'];
      $pos4 = strpos($aet, '[USER_EMAIL]');
      $pos5 = strpos($aet, '[USER_PASSWORD]');
      $pos6 = strpos($aet, '[DOMAIN]');
      $pos7 = strpos($aet, '[ADMIN_EMAIL]');
      $errorAet = ($pos4 === false || $pos5 === false || $pos6 === false || $pos7 == false);
      
      $config->setProperties( $this->params );
      
      if (!$config->save() || $errorSpet || $errorAet)
      {
         $this->params['config'] = $config;
         
         // TODO: cual error lo saco del objeto config
      	$this->flash['message'] = "Ocurrio un error al guardar la configuracion";
         return $this->render("edit", $this->params);
      }
      
      $this->flash['message'] = "Configuracion guardada";
      return $this->redirect( array("controller" => "page",
                                    "action"     => "display",
                                    "params"     => array("_param_1" => "index")) );
   }
   */
}
?>