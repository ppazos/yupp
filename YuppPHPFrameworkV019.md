# NOTAS DE LA VERSIÓN: #

  * Versión 0.1.9 de Yupp PHP Framework


# Incluye los siguientes componentes: #

  * versión 0.8.5 del YORM (Yupp Object Relational Mapping)
  * versión 0.3.5 del YMVC (Yupp Model View Controller)


# Requisitos: #

  * Versión de PHP: 5.2.x (nosotros utilizamos 5.2.3)
    * Probado en 5.2.3, 5.2.4, 5.2.5, 5.2.7, 5.2.8, 5.2.9-2 y 5.2.11 (versión de Windows)
    * Probado con WAMP 2.0 y con AppServ 2.5.10
  * Motor de bases de datos: MySQL 5.x o superior (nosotros utilizamos 5.0.41 o 5.1.33)
  * Motor de bases de datos: SQLite (nosotros utilizamos 2.8.17)
  * Motor de bases de datos: PostgreSQL 8.3
  * Tener el módulo de Apache MOD\_REWRITE instalado y activado.


# Cambios con respecto a la versión 0.1.8: #

Esta versión incluye grandes mejoras de YORM en el soporte a persistencia de estructuras de clases con herencia y varias mejoras al YMVC.


  * Implementar soporte para serializacion de instancias del modelo persistente a XML
  * Crear demos/tests de los helpers
  * Creación de juegos de test para validar el ORM
  * Soporte para requests y responses HTTP.
  * Se corrigieron bugs en PersistenManager, Helpers, RequestManager, YuppForm2 y String.


# Que contiene esta liberación? #

Contiene los componentes mencionados antes, el YORM y el YMVC.

Esta liberación contiene un ejemplo de un sistema de Blog sencillo donde
se pueden crear entradas y comentarios, mostrando el funcionamiento básico
del framework, con funcionalidades como:

  * Definicion de modelo persistente
  * Crear modelo
  * Modificar modelo
  * Obtener modelo
  * Definicion de controladores
  * Definicion de vistas
  * Uso de helpers basicos
  * Validacion automatica de informacion


También cuenta con el componente "Hello World". Este nuevo componente
muestra la implementación de la aplicación mínima en Yupp PHP Framework,
apenas una clase de dominio y un controller con 3 líneas de código!.
Este componente puede hacer altas, bajas y modificaciones de usuarios.


# Para correr el ejemplo #

Debes tener un servidor Apache con soporte para PHP corriendo.
Debes tener PHP 5.2.x o superior.
Debes tener MySQL 5.x o superior instalado y corriendo.
  * Alternativas: SQLite o PostgreSQL.

Descomprime el contenido de la liberacion que descargaste desde
http://www.simplewebportal.net/host/1022.htm o
http://code.google.com/p/yupp/downloads/list
en el "web root" de tu servidor Apache ("www" o "public\_html").


## Configuración de la base de datos y creación de la base ##

> Para configurar los datos de conexion a la base de datos MySQL/SQLite/PostgreSQL
> se debe editar la informacion presente en el archivo:

> "/core/config/core.config.YuppConfig.class.php",
> modificando el campo $dev\_datasource, estableciendo los valores correctos
> para cada clave de dicho array: url, user, pass y database.

> Y se debe crear la base de datos con el nombre que hayas configurado en
> "/core/config/core.config.YuppConfig.class.php" en el campo "database"
> antes de correr el framework.


## Acceder al ejemplo ##

> Accede al directorio donde se descomprimió Yupp Framework PHP desde un
> browser, aparecerá una página con links, esos links ejecutan las acciones
> por defecto de los controladores existentes, el único controlador que
> tiene utilidad por el momento es "EntradaBlog" que es de donde se permite
> crear entradas para el blog.


## Generación de las tablas ##

> Desde la pantalla de administración que aparece al instalar Yupp y accederlo
> mediante un navegador web, existe una sección llamada "Información del modelo",
> ahí se listan todas las clases presentes en el modelo de datos de todos los
> componentes instalados (con esta liberación el único componente instalado es
> el de "blog"). En esta sección hay un link "Generar tablas", que al hacerle
> clic ejecutará la generación de todas las tablas necesarias en la base de
> datos configurada previamente.

> Si tienes algún problema o alguna pregunta, no dudes en comunicarte con
> nosotros: http://groups.google.com/group/yuppframeworkphp


# CONTACTO #

Cualquier duda o sugerencia, publica tu comentario en nuestro grupo:
http://groups.google.com/group/yuppframeworkphp o en nuestra wiki:
http://code.google.com/p/yupp/w/list

```
Pablo Pazos Gutiérrez
Yupp PHP Framework
Líder del proyecto
```
http://www.simplewebwortal.net