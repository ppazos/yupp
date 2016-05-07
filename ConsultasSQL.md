# Listados SQL #

```
SELECT column FROM table
LIMIT 10 OFFSET 10
```

```
FROM "nombre_tabla"
[WHERE "condición"]
ORDER BY "nombre_columna" [ASC, DESC]
...
ORDER BY "nombre1_columna" [ASC, DESC], "nombre2_columna" [ASC, DESC]
```


# Información de las tablas #

## Resultado ##

Array con descripción de las columnas de la tabla:

```
Array
(
   [Field] => id
   [Type] => int(11)
   [Null] => NO
   [Key] => PRI
   [Default] => 
   [Extra] => 
)
```

## General ##

Descripción de una tabla:
```
SHOW columns FROM tableName;
```

Descripción de una columna:
```
SHOW columns FROM tableName LIKE colName;
```


## MySQL ##

```
DESCRIBE `person`;
```

http://dev.mysql.com/doc/refman/5.0/en/show-columns.html
```
SHOW COLUMNS FROM `person`; 
```


# Creación de tablas #

```
CREATE TABLE `tabla_nueva` (
  `id`    INT NOT NULL ,
  `user`  VARCHAR( 50 ) NOT NULL ,
  PRIMARY KEY ( `id` ) // puedo declarar más de un campo en la PK
) ENGINE = innodb;

CREATE TABLE table_name (
  id   INTEGER  PRIMARY KEY, // con esta forma de declarar PKs no puedo declarar mas de un campo.
  col2 CHARACTER VARYING(20),
  col3 INTEGER REFERENCES other_table(column_name), // declaración de FKs
... )
```