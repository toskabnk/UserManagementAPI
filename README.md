# xDir Backend

Backend del sistema de gestion de usuarios, clientes, organizaciones y roles de Ximdex

## Requisitos
- PHP 8.1, extensiones Ctype, cURL, DOM PHP, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, XML.
- Composer
- Apache2 o Nginx
- MariaDB 10.10.2

## Instalacion

Clona el repositorio en una carpeta con el nombre del proyecto:
```shell
git clone https://github.com/XIMDEX/xdir-back
```
O en la carpeta actual con:
```shell
git clone https://github.com/XIMDEX/xdir-back .
```

Renombra el archivo `.env.example` a `.env` y modifica los valores de usuario de la base de datos asi como el nombre, modifica los demas valores que creas necesarios.

Instalamos las dependencias ejecutando el siguiente comando en una terminal:
```shell
composer install
```

Una vez instalada las dependencias, genera una nueva clave de aplicacion ejecutando:
```shell
php artisan key:generate
```

Crea las migraciones en la base de datos y genera los datos minimos necesarios:
```shell
php artisan migrate --seed
```

Instala la claves necesarias para la autenticacion de Laravel Passport con:
```shell
php artisan passport:install
```

## Ejecucion

Ejecuta el proyecto en un entorno local con:
```shell
php artisan serve
```

## Middlewares
Se han creado varios middleware para facilitar quien puede acceder a los endpoints de la API. Los middlewares se encuentran en la carpeta `app/Http/Middlware`. Los middlewares estan registrados con unos nombres mas faciles de recordar en `app/Http/Kernel.php`.
A continuacion se detallan los middlewares creados, como estan registrados y su funcion:
- CheckPermission: Registrado como `clientPermission`. Comprueba que usuario logueado sea correcto. Tambien ofrece informacion de si el usuario actual tiene los roles administrativos de Admin o SuperAdmin. Tambien ofrece informacion de si el usuario actual es un Cliente o un Miembro. En ambos casos, si la ruta dispone de `memberId` o `clientId` tambien ofrece informacion si el mismo que se pasa por parametro. Los datos se añaden a la variable `Request`
- CheckIfClient: Reigstado como `isClient`. Comprueba que el usuario sea un cliente. Si no lo es, devuelve una respuesta `401`. Requiere el uso del middleware `clientPermission`.
- CheckIfSameMember: Registrado como `sameMember`. Comprueba que el usuario actual es el mismo miembro pasado por `memberId`. Requiere el uso del middleware `clientPermission`.
- CheckSuperAdminPermission: Registrado como `superAdmin`. Comprueba que el usuario actual tenga el Rol de SuperAdmin. No es necesario utilizar un middleware anterior.

> [!WARNING]
> El orden de uso de los middlewares que requieren uno anterior es importante. Siempre debe ser usado primero `clientPermission` antes que `isClient` y `sameMember`

## Rutas de la API:
La ruta base configurada de la API es: `/api/v2`. La ruta se puede modificar en `app/Providers/RouteServiceProvider.php` modificando el `prefix` del siguiente fragmento:
```php
Route::middleware('api')
                ->prefix('api/v2')
                ->group(base_path('routes/api/v2/api.php'));
```

Las rutas de la API estan divididas en dos grupos, si estas autenticado o no.
- Si no estas autenticado las rutas disponibles son las siguientes:
    - GET `/register/{clientId}`: Consigue el nombre del Cliente pasado por `clientId`.
    - POST `/register`: Registra un nuevo usuario en la aplicacion.
    - POST `/login`: Permite el inicio de sesion en la aplicacion creando un token.
- Si estas autenticado, las rutas disponibles son las siguientes:
    - POST `/logout`: Cierra la sesion invalidando el token actual.
    - Rutas con el prefijo `/client`
        - GET `/`: Devuelve una lista con todos los clientes registrados en la aplicacion. Solo es posible para los Clientes que tengan el Rol de SuperAdmin
        - GET `/{clientId}`: Devuelve los detalles del cliente pasado por parametro. Solo es posible si eres el mismo Cliente, eres un Usuario que pertenece a alguna Organizacion del Cliente o un SuperAdmin.
        - POST `/`: Crea un nuevo cliente. Solo es posible para los Clientes que tengan el Rol de SuperAdmin.
        - POST `/{clientId}/role/{roleId}`: Añade el Rol al Cliente pasado por parametro. Solo es posible para los Clientes que tengan el Rol de SuperAdmin.
        - POST `/{clientId}/organization/{organizationId}`: Añade la Organizacion al Cliente pasado por parametro. Solo es posible para los Clientes que tengan el Rol de SuperAdmin.
        - PUT `/{clientId}`: Edita los datos del Cliente pasado por parametro. Solo es posible editar los datos el mismo Cliente o un Cliente con el Rol de SuperAdmin.
        - PUT `/{clientId}/role/{roleId}`: Modifica el Rol por defecto del Cliente pasado por parametro. Solo es posible editar el Rol por defecto si es el mismo Client.
        - PUT `/{clientId}/organization/{organizationId}`: Modifica la Organizacion por defecto del Cliente pasado por parametro. Solo es posible editar la Organizacion por defecto si es el mismo Client.
        - DELETE `/{clientId}`: Elimina el Cliente pasado por parametro. Solo es posible para los Clientes con Rol de SuperAdmin.
        - DELETE `/{clientId}/role/{roleId}`: Elimina del Cliente el Rol pasado por parametro. Solo es posible para los Clientes con Rol de SuperAdmin.
        - DELETE `/{clientID}/organization/{organizationID}`: Elimina del Cliente la Organizacion pasado por parametro. Solo es posible para los Clientes con Rol de SuperAdmin.
    - Rutas con el prefijo `/member`
        - GET `/`: Consigue una lista de todos los miembros registrados en la aplicacion. Un Cliente con Rol de SuperAdmin vera todos los miembros registrados, un Cliente con Rol de Admin vera todos los miembros que esten registrados en alguna de sus Organizaciones.
        - GET `/{memberId}`: Consigue los detalles del miembro pasado por parametro. Los detalles solo los podra ver el mismo miembro, un Cliente que tenga registro al miembro en alguna de sus organizaciones o un Cliente que tenga el Rol de SuperAdmin.
        - POST `/`: Crea un nuevo miembro en la aplicacion. Solo lo puede crear un Cliente.
        - POST `/{memberId}/role/{roleId}`: Añade el Rol al Miembro pasado por parametro. Solo es posible para los Clientes que tengan el Rol de SuperAdmin, o un Cliente con Rol de Admin y que el miembro pertenezca a alguna de sus Organizaciones
        - POST `/{memberId}/organization/{organizationId}`: Añade la Organizacion al Miembro pasado por parametro. Solo es posible para los Clientes que tengan el Rol de SuperAdmin, o un Cliente con Rol de Admin y que el miembro pertenezca a alguna de sus Organizaciones.
        - PUT `/{memberId}`: Edita los detalles del miembro pasado por parametro. Solo podra editarlo el mismo miembro, un Cliente con Rol de Admin y que el miembro pertenezca a alguna de sus Organizaciones o un Cliente con rol de SuperAdmin.
        - DELETE `/{memberId}`: Elimina el Miembro pasado por parametro. Solo es posible para los Clientes con Rol de SuperAdmin o Clientes con Rol de Admin y que el miembro pertenezca a alguna de sus organizaciones.
        - DELETE `/{memberId}/role/{roleId}`: Elimina del Miembro el Rol pasado por parametro. Solo es posible para los Clientes con Rol de SuperAdmin o Clientes con Rol de Admin y que el miembro pertenezca a alguna de sus organizaciones.
        - DELETE `/{memberId}/organization/{organizationID}`: Elimina del Miembro la Organizacion pasado por parametro.Solo es posible para los Clientes con Rol de SuperAdmin o Clientes con Rol de Admin y que el miembro pertenezca a alguna de sus organizaciones.
    - Ruta con el prefijo `/user`
        - PUT `/{id}/password`: Modifica la contraseña del Cliente o Miembro que tenga como id de user el pasado por parametro.
    - Rutas con el prefijo `/role`
        - GET `/`: Consigue una lista con todos los roles del Cliente. Si el Cliente tiene el Rol de SuperAdmin, consigue la lista con todos los roles de todos los Clientes.
        - GET `/{roleId}`: Consigue los detalles del Rol pasado por parametro.
        - GET `/users/{roleId}`: Consigue los Miembros y Clientes que tengan asignado ese Rol si eres un Cliente con Rol de SuperAdmin. Si eres un Cliente con Rol de Admin solo devuelve una lista con los Miembros que pertenecen al Rol pasado por parametro y que pertenezcan a alguna de sus Organizaciones.
        - POST `/`: Crea un nuevo rol. Solo los pueden crear Clientes.
        - PUT `/{roleId}`: Modifica el Rol pasado por parametro. Solo puedes modificarlo si eres un Cliente y el Rol te pertenece.
        - DELETE `/{roleId}`: Borra el Rol pasado por parametro. Solo pueden borrar Roles Clientes.
    - Rutas con el prefijo `/organization`:
        - GET `/`: Devuelve una lista con todas las Organizaciones. Si eres un Cliente con rol de SuperAdmin, devuelve la lista completa de todas las organizaciones creadas en la aplicacion. Si ers un Cliente con rol de Admin, devuelve la lista de las organizaciones que le pertenecen.
        - GET `/{organizationId}`: Devuelve los detalles de la Organizacion pasada por parametro. Solo pueden ver los detalles los Miembros y Clientes que pertenecen a ella o un Cliente con rol de SuperAdmin.
        - GET `/{organizationId}/members`: Devuelve la lista de los miembros que pertecen a la Organizacion pasada por parametro. Solo pueden ver los Miembros el Cliente al que pertenezca la Organizacio o un Cliente con Rol de SuperAdmin.
        - POST `/`: Crea una nueva Organizacion. Solo pueden crearla Clientes.
        - PUT `/{organizationId}`: Edita los detalles de la Organizacion pasada por parametro. Solo pueden editar los detalles el Cliente al que pertenece la Organizacion o un Cliente con rol de SuperAdmin.
        - DELETE `/{organizationId}`: Elimina la Organizacion pasada por parametro. Solo puede eliminarla el Cliente al que pertenece la Organizacion o un Cliente con rol de SuperAdmin.
