# DEPENDENCIAS PARA PORDER UTIRLIZAR LARAVEL

Para utilizar el framerork de laravel V. 12, necesitamos las siguientes dependencias:

- 1 Composer
  url = <https://getcomposer.org/download/>
  Comando para validar su version = compoer --version o compoer -v

- NODEJS
  url = <https://nodejs.org/es/>
  Comando para validar la version = node -v o node --version

IDE poara desarrollar - Visual Studio Code
url = <https://code.visualstudio.com/> - PHPSTORM
url = <https://www.jetbrains.com/es-es/phpstorm/> - NVIM

- GIT
  url = <https://git-scm.com/downloads>
  Comando para validar su version = git --version o git -v

  - github manejra los repositorios
    url =<https://github.com/>

## DESARROLLO

Para desarrollar en un entorno de laravel, cencesitamos un servidor local, para ello tenemos varias opciones:

- XAMPP
  url = <https://www.apachefriends.org/es/index.html>
- LARAGON <--- es el idoneo
  url = <https://laragon.org/>
- MAMP
  url = <https://www.mamp.info/en/downloads/>
- WAMP
  url = <http://www.wampserver.com/en/>

## PHP

Vamos a conocer los comandos basicos de PHP, para ello abrimos una terminal y escribimos:

- php -v o php --version
  Con este comando validamos que tenemos instalado PHP y nos muestra la version instalada

- php -m
  Con este comando validamos los modulos que tenemos instalados en PHP

- php -i
  Con este comando validamos la configuracion de PHP

- php -r 'echo "Hola Mundo";'
  Con este comando podemos ejecutar codigo PHP desde la terminal

- php -a
  Con este comando entramos en el modo interactivo de PHP

- php -l archivo.php
  Con este comando validamos la sintaxis de un archivo PHP

- php -S localhost:8000
  Con este comando iniciamos un servidor web local en el puerto 8000

Etiquetas: #Laravel #PHP #Composer #NodeJS #GIT #XAMPP #LARAGON #MAMP #WAMP #VisualStudioCode #PHPSTORM #NVIM
Ejemplo de uso de etiquetas: #etiqueta1 #etiqueta2 #etiqueta3

````PHP
  <?php
    echo "hola mundo";
  ?>

  ```html
    <body>
      <div>
        <?php echo "hola mundo"; ?>
      </div>
    </body>
````

## Que vamos a hacer con laravel, con la arquitectura MVC

- Crear un proyecto
- Crear un modelo
- Crear una migracion
- Crear un controlador
- Crear una vista
- Crear una ruta
- Crear un formulario
- Validar un formulario
- Guardar datos en la base de datos
- Mostrar datos en la vista
- Actualizar datos en la base de datos
- Eliminar datos en la base de datos

## Crear el Proyecto en laravel 12

Si estas windows y xampp debes dirigirte al directorio _htdocs_
Si estas usando laragon el directorio se llama _www_

```bash

composer create-project laravel/laravel sistema_logistica

```

### Movemos al directorio recien creado

```bash

cd sistema_logistica

```

### configuracion de la base de datos

Por default laravel en el archivo .env (en donde configuracion de variables de entorno), ahi realizamos las configuracion para la base datos.

Base de datos por default = sqlite

### Store procedures

```SQL
DELIMITER $$
CREATE PROCEDURE ActualizarKilometrajeCamion(IN camion_id_param INT, IN kilometraje_recorridos_param decimal(10,2)))

BEGIN
  --sume los kilometros del neuvo viaje al kilometraje del camion
  UPDATE camiones
  SET kilometraje_actual = kilometraje_actual +
  kilometraje_recorridos_param
  WHERE id = camion_id_param;
END$$
DELIMITER;
```

### COMO LLAMAR LOS STORE PROCEDURE o Invocar

Los procedimentos almacenados de laravel podemos ejecutarlo usando DB. Laravel tiene el metodo especificos como DB::select() o DB::statement(). esto nos permite enviar comandos SQL 'CRUDOS'(RAW) a la base de datos.

### En donde llamar al procedimiento almacenado

En laravel podemos llamar a los store procedure desde un controlador o un Observer.

```bash

#comando de laravel para crear un controlador
php artisan make:controller camion_controller


#comando de laravel para crear un modelo
php artisan make:model camion

```
