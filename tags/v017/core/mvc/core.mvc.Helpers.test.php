<?php
/*
 * Created on 22/03/2008
 * core.mvc.Helpers.test.php
 */

include_once('core.mvc.Helpers.class.php');

echo h('url', array(//falta component!
               'controller' => 'user',
               'action'     => 'create',
               'params'      => array(
                                      'name'   => 'Pablo',
                                      'edad'   => 23,
                                      'altura' => 180
                                     )
              ));


/* notacion alternativa de parametros sin mapas:
echo h('url', array(
               'controller_user',
               'action_create',
               'p_name'   => 'Pablo',
               'p_edad'   => 23,
               'p_altura' => 180
              ));
*/


echo h('link', array(//falta component!
               'controller' => 'user',
               'action'     => 'create',
               'params'     => array(
                                     'name'   => 'Pablo',
                                     'edad'   => 23,
                                     'altura' => 180
                                     ),
               'body'      => 'Crear usuario'
              ));

echo "<pre>";

$a = array(
               'controller_user',
               'action_create',
               'p_name'   => 'Pablo',
               'p_edad'   => 23,
               'p_altura' => 180
          );

print_r( $a );

echo "</pre>";

?>
