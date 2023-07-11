# Wp2Joomla - Migración de Posts de Wordpress o K2 a Joomla 4

Plugin para Joomla 4 que permite migrar artículos y categorías de Wordpress o K2 a Joomla 4 desde la consola de comandos.

## 🐳 Docker

1. Renombrar el archivo .env.dist a .env
2. Poner el dump.sql de tu wordpress o k2 en la carpeta `docker/sql/wordpress.sql` o `docker/sql/k2.sql` respectivamente. **Es obligatorio que el prefijo de las tablas de wordpress sea `wp_` y para K2 el prefijo de las tablas tienen que ser igual al de Joomla 4** (*al levantar docker se instalará la base de datos de wordpress o k2 en mysql automáticamente.*)
4. Para levantar Joomla 4 de pruebas en docker posicionarse en la carpeta raíz de este proyecto y desde bash ejecutar:
    ```bash
    docker compose up -d
    ```
5. Ir a http://localhost para instalar Joomla 4 como cualquier instalación
6. En los datos de la base de datos configurar:
   - Host de la base de datos: mysql
   - Base de datos: joomla
   - Usuario: joomla
   - Password: joomla
7. Ir a http://localhost/administrator

### Instalar Plugin en Joomla 4 dentro del contenedor

1. Comprimir los archivos y carpetas dentro de plg_system_wp2joomla en un zip llamado `plg_system_wp2joomla.zip` dentro de la misma carpeta. 
2. Ingresa a la terminal del contenedor:
    ```bash
    # docker exec -ti nombreContenedor bash
    # Reemplazar nombreContenedor por el nombre del contenedor de php. Ejecutando docker ps obtenemos el nombre.
    # En mi caso sería:
    docker exec -ti wp2joomla-joomla_php8-1 bash
    ```
3. Dentro de la terminal del contenedor instalamos el zip `plg_system_wp2joomla.zip` anteriormente creado con:
    ```bash
    php cli/joomla.php extension:install --path=/var/www/html/plugins/system/wp2joomla/plg_system_wp2joomla.zip
    ```
4. Habilitamos el plugin en http://localhost/administrator/index.php?option=com_plugins&view=plugins&filter[search]=PLG_SYSTEM_WP2JOOMLA
5. Listo, ya podemos ejecutar los comandos de migración siguiendo los pasos de la sección "Ejecución".

## 🚀 Ejecución

Podemos usar Docker siguiente los pasos del punto anterior o tener instalado un Joomla 4 de pruebas con un xampp/wampp/servidor. Importar la base de datos de wordpress con el prefijo wp_ en la misma base de datos que Joomla 4. Una vez importada la base de datos, instalamos el plugin de la carpeta plg_system_wp2joomla en Joomla 4 como cualquier extensión desde el instalador en el administrador de Joomla 4 y lo habilitamos.
Vamos a la consola de comandos del servidor y nos ubicamos en la carpeta raíz de Joomla 4.

### Wordpress

Importamos las categorías de Wordpress en Joomla 4 con:
```bash
php cli/joomla.php migrate:categories --adapter=wordpress

# Si queremos importar sabiendo el id del usuario que se le asignará a las categorías:
php cli/joomla.php migrate:categories --userId=123 --adapter=wordpress
```

y luego los Posts de wordpress con:

```bash
php cli/joomla.php migrate:articles --adapter=wordpress

# Si queremos importar sabiendo el id del usuario que se le asignará a los artículos:
php cli/joomla.php migrate:articles --userId=123 --adapter=wordpress
```

### K2

Importamos las categorías de K2 en Joomla 4 con:
```bash
php cli/joomla.php migrate:categories --adapter=k2

# Si queremos importar sabiendo el id del usuario que se le asignará a las categorías:
php cli/joomla.php migrate:categories --userId=123 --adapter=k2
```

y luego los Posts de K2 con:

```bash
php cli/joomla.php migrate:articles --adapter=k2

# Si queremos importar sabiendo el id del usuario que se le asignará a los artículos:
php cli/joomla.php migrate:articles --userId=123 --adapter=k2
```

### 📝 Notas

Una vez finalizado el proceso de migración en el Joomla de pruebas, si todo va bien, podemos migrar los datos en el Joomla de producción.

**Aclaraciónes:**
1. ⚠️ Es importante el órden de ejecución de los comandos. Primero las categorías y luego los artículos. Los artículos dependen de las categorías.
2. Si un post de Wordpress tiene más de una categoría se elegirá la primera que se obtenga desde la base de datos.
