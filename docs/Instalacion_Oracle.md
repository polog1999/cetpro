# Guía de Instalación: Oracle OCI8 para PHP 8.4 en Windows

## Requisitos
- PHP 8.4 (Laravel Herd)
- Windows 10/11 64-bit
- Acceso como administrador

---

## PASO 1: Descargar Oracle Instant Client

1. Ve a: https://www.oracle.com/database/technologies/instant-client/winx64-64-downloads.html

2. Descarga **"Basic Package"** (ZIP):
   ```
   instantclient-basic-windows.x64-21.15.0.0.0dbru.zip
   ```
   (aproximadamente 80 MB)

3. Crea la carpeta:
   ```
   C:\oracle\instantclient
   ```

4. Extrae el contenido del ZIP directamente ahí. Debe quedar así:
   ```
   C:\oracle\instantclient\
   ├── oci.dll
   ├── oraociei21.dll
   ├── oraocci21.dll
   └── (otros archivos .dll)
   ```

---

## PASO 2: Agregar Oracle Instant Client al PATH

1. Presiona `Win + R`, escribe `sysdm.cpl` y presiona Enter

2. Ve a pestaña **"Opciones avanzadas"** → **"Variables de entorno"**

3. En **"Variables del sistema"**, busca `Path` y haz clic en **"Editar"**

4. Clic en **"Nuevo"** y agrega:
   ```
   C:\oracle\instantclient
   ```

5. Clic en **"Aceptar"** en todas las ventanas

6. **IMPORTANTE**: Cierra y abre cualquier terminal/CMD para que tome efecto

---

## PASO 3: Descargar extensión OCI8 para PHP

1. Ve a: https://pecl.php.net/package/oci8/3.4.0/windows

2. Busca la versión que diga:
   - **8.4**
   - **N.. Thread Safe (NTS)**
   - **x64**
   
   El archivo se llama algo como: `php_oci8-3.4.0-8.4-ts-vs17-x64.zip`

3. Descarga y extrae el ZIP

4. Del ZIP extraído, copia el archivo **`php_oci8.dll`**

5. Pégalo en la carpeta de extensiones de PHP Herd:
   ```
   C:\Users\jordonez\.config\herd\bin\php84\ext\
   ```

---

## PASO 4: Activar OCI8 en php.ini

1. Abre el archivo:
   ```
   C:\Users\jordonez\.config\herd\bin\php84\php.ini
   ```

2. Busca la sección de extensiones (busca `extension=`)

3. Agrega esta línea:
   ```ini
   extension=oci8
   ```

4. Guarda el archivo

---

## PASO 5: Reiniciar PHP/Herd

1. Abre **Laravel Herd** desde la bandeja del sistema

2. Clic derecho → **Stop All** (o similar)

3. Espera unos segundos

4. Clic derecho → **Start All**

**Alternativa**: Reinicia Windows para asegurar que todo se cargue bien

---

## PASO 6: Verificar instalación

Abre una **nueva** ventana de CMD o PowerShell y ejecuta:

```cmd
php -m | findstr oci
```

**Si funciona**, verás:
```
oci8
```

**Si hay error**, ejecuta esto para ver más detalles:
```cmd
php -i | findstr -i oci
```

---

## PASO 7: Probar conexión a Oracle

En tu proyecto Laravel, ejecuta:

```cmd
cd C:\Jhimmy\cetpro-mdlm
php artisan tinker
```

Luego en tinker:
```php
$oracle = new App\Services\OracleTusneService();
$oracle->verificarConexion();
```

---

## Solución de problemas comunes

### Error: "oci8 extension not found"
- Verifica que `php_oci8.dll` esté en la carpeta `ext`
- Verifica que la línea `extension=oci8` esté en php.ini
- Reinicia Herd completamente

### Error: "OCI.dll not found" o similar
- Verifica que `C:\oracle\instantclient` esté en el PATH
- Reinicia Windows después de modificar el PATH

### Error: "Architecture mismatch"
- Asegúrate de descargar versiones x64 de todo (Instant Client y OCI8)

---

## Configuración del .env

Una vez que OCI8 funcione, agrega esto a tu `.env`:

```env
ORACLE_HOST=IP_DEL_SERVIDOR
ORACLE_PORT=1521
ORACLE_SERVICE_NAME=NOMBRE_DEL_SERVICIO
ORACLE_USERNAME=TU_USUARIO
ORACLE_PASSWORD=TU_PASSWORD
```
