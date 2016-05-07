

# Comando único para indicar parámetros de entrada #

Es la misma idea de los comandos de salida de los controladores, tanto para mostrar una vista como para hacer un redirect, solo que este comando de entrada se crearía al inicio del request. La idea sería que resuelva mucha de la funcionalidad que hoy se resuelve en el routing con Filter, Executer y RequestManager, tal que pueda resolver todos los parámetros de entrada, los parámetros de ejecución (app, controller, accion), y los parámetros contextuales de la ejecución (p.e. si muestro o no logs, el modo de ejecución, el locale, etc).

Luego el Executer debería tomar este comando que se auto-construye, y debería saber ejecutar todo correctamente, devolviendo un comando de salida. Este comando de salida, sera procesado por otro componente, ya en la capa de Vistas.


# Mejora de logs por componente #

La idea sería poder activar o desactivar los logs por componente (vistas, controladores, modelo, servicios, core, core.paquete) de forma independiente. Inclusive por aplicación (appxxx.controladores, appxxx.vistas, appxxx.modelo, appxxx.servicios).

También tener una mejora en la visualización de los logs. Por ejemplo, agrupándolos por componente, y mostrándolos de forma gráfica (en lugar de hacer una columna de logs única). Esta forma gráfica, podría simular la ejecución de los distintos componentes, de forma de seguir un flujo de trabajo y sus logs relacionados. Además se debería seguir el log de forma independiente de la vista que se genera, porque ahora los logs a pantalla se entreveran con el código generado por las vistas.

## Agregar parámetro para mostrar u ocultar logs ##

En lugar de tener una línea de código que se comenta y descomenta para mostrar u ocultar logs en index.php (como funciona hoy), la idea sería pasarle a RequestManager un parámetro que indique qué logs mostrar y cómo. Sería un comando que tenga los items mencionados antes como los componentes para los que se muestran o no sus logs. De esta forma, habría que cambiar este comando para definir que logs se verán, y es mucho más limpio que tener que comentar o descomentar una línea de código.


# Modos de ejecución por aplicación #

Los modos de ejecución del framework deberían ser en realidad modos de ejecución de cada aplicación. El esquema de modos de ejecución del framework, funciona cuando es un framework monoaplicación, pero yupp es multiaplicación, por lo que realmente no aplica.

En cambio, yupp si tendría modos de ejecución, pero sería según su uso. Por ejemplo, la diferencia se hace cuando el framework es utilizado por un usuario final para instalar y ejecutar aplicaciones, que es un esquema conceptualmente distinto al de un programador desarrollando aplicaciones. Para el segundo caso, lo que cambiaría es que el programador puede usar las herramientas de desarrollo y parametrización de la ejecución desde la GUI (p.e. si se muestran logs, la ejecución de tests, creación de aplicaciones, generación de esquemas de DB desde la GUI, etc).


# Arquitectura de plugins #

Conceptualmente, un plugin es como una aplicación: puede instalarse/desinstalarse, activarse/desactivarse, tiene modelo, vistas, controladores, y servicios, tiene JS y CSS, etc.

Un plugin podría depender de que otros plugins también estén instalados. Incluso podrían depender de versiones específicas de otros plugins. También se podría decir que depende de tal versión en adelante, de tal plugin.

Un plugin puede depender de una versión determinada del framework, o de tal versión en adelante.

Estas dos últimas ideas deberían extenderse a las aplicaciones, para poder saber si puedo o no instalar una aplicación en el yupp que tengo. Por ejemplo, una aplicación podría depender de que un plugin en cierta versión este instalado, o de que el framework tenga cierta versión en adelante. Pero una aplicación NO depende de otras aplicaciones.

Para los casos de aplicaciones y plugins, también podrían depender de cierta versión de librería javascript, que puede o no venir con la aplicación o plugin, pero que debe tener el framework instalada (esto se podría decir con la dependencia de cierta versión del framework que trae cierta versión de una lib javascript).

Una cosa importante es que 2 aplicaciones podrían usar el mismo plugin, que puede tener un modelo definido. Entonces los datos persistentes del plugin, que sean creados por las aplicaciones, no se deberían mezclar, por lo que se debe diferenciar que datos son de una o de otra aplicación que utilizan el mismo plugin. Una salida posible, es que cuando se indica desde la aplicación que se va a usar un plugin (inclusión del plugin), se toma en cuenta la aplicación actual, entonces, por ejemplo, los datos se guardan en distintas tablas.
También hay que resolver cómo se crean las tablas del modelo del plugin para que genere tablas distintas según la aplicación que usa el plugin.

Lo que habría que tener es un configurador de "usos de plugins", que permita indicar desde la GUI, cuales plugins usa una determinada aplicación. Una vez que se indica eso, al generar las tablas de la aplicación, se generan también las tablas de los plugins que usa la aplicación, y esas tablas son para esa aplicación. Si otra aplicación hace uso de los mismos plugins, se generan otras tablas.

La información de los plugins que usa una aplicación, debería estar incluida en el app descriptor, de forma de que cuando se quiera instalar tal aplicación, se pueda 1. verificar si todos los plugins de los que se depende están instaladores, 2. poder bajar de la web los plugins que falten (se debe tener la url de actualización del plugin).

Tal vez se necesite agregar algo en YuppLoader para cargar plugins.

## Estructura del framework ##

Para soportar plugins, el framework podría tener la siguiente estructura:
```
yupp
|_ apps
|_ core
   |_ plugin
|_ plugins
```

En yupp/core/plugin estaría toda la lógica que permite gestionar los plugins.

En yupp/plugins estarían los plugins instalados para su uso, y los plugins descargados para su instalación.

### Layout plugin ###

La idea de este plugin sería poder agregar estructuras (como una barra superior, barra inferior, menú, etc) como un contenedor del contenido que muestre una vista de alguna aplicación. Incluso, si la vista ya utiliza un layout de la aplicación, el plugin layout se podría ejecutar: 1. como extensión del layout de la aplicación, o 2. como un contenedor de la vista que devuelve la aplicación (con su layout o sin él), esta sería la idea de "layout anidado" que se van aplicando sucesivamente unos sobre otros, y en núcleo de esta ejecución sería la vista.

Para que esto funcione, el componente que hoy hace el rendering con layouts (LayoutManager), debería poder preguntarle a una aplicación si está usando el plugin de layout, y le daría lo necesario para que éste pueda aplicarse sobre el resultado de la vista y layout de la aplicación.

Otro tema interesante, es que el layout formalmente no dice cuál será si contenido, sino que define un contenedor. El programador será responsable de definir su contenido, por ejemplo si quiere mostrar una barra superior con un menú, o cualquier otro elemento que aplique sobre toda la vista.
Una parametrización posbiles sería el poder verificar una condición, que si no se cumple, no se aplica el plugin layout sobre la vista. Esto podría ser una regla de seguridad, por ejemplo, si la barra superior con menú que muestra el plugin layout, es un editor de una página, si el usuario no está logueado o si no tiene permisos de edición, no se le mostrará el menú.