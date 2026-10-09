# App Alumnos — desplegada en Railway

App fullstack de alumnos (Semanas 6 a 10 de Aplicaciones Móviles y Cloud Computing):
API REST en PHP + frontend en JavaScript + MySQL, con geocodificación, mapa y aviso por email.

- **Integrantes:** _(apellido y nombre)_
- **URL pública:** https://_tu-app_.up.railway.app
- **URL de la API:** https://_tu-app_.up.railway.app/api/alumnos.php

## Cómo correrla en local (XAMPP)

1. Copiar la carpeta del proyecto dentro de `C:\xampp\htdocs\` (por ejemplo `htdocs\mi-app\`).
2. En phpMyAdmin, crear la base `semana6` (si no existe), seleccionarla y, en la pestaña SQL,
   ejecutar `sql/base.sql`.
3. Abrir `http://localhost/mi-app/`. Sin variables de entorno, `api/config.php` usa los valores
   de XAMPP (`localhost`, `root` sin contraseña) y el email queda en modo simulación
   (`api/avisos.log`).

## Endpoints de la API

| Método | Ruta | Recibe | Devuelve | Códigos |
|---|---|---|---|---|
| GET | `/api/alumnos.php` | — | lista de alumnos | 200, 500 |
| GET | `/api/alumnos.php?id=N` | — | un alumno | 200, 404, 500 |
| POST | `/api/alumnos.php` | JSON `{nombre, email, ciudad?}` | el alumno creado, con coordenadas | 201, 400, 422, 500 |
| PUT | `/api/alumnos.php?id=N` | JSON `{nombre, email}` | el alumno modificado | 200, 400, 404, 422, 500 |
| DELETE | `/api/alumnos.php?id=N` | — | (vacío) | 204, 400, 404, 500 |

- **400:** falta el id o faltan campos. **422:** email inválido, nombre vacío o ciudad que el
  servicio de geocodificación no conoce. **405:** otro método.

## Variables de entorno

| Variable | Para qué |
|---|---|
| `DB_HOST` | Servidor de la base |
| `DB_PORT` | Puerto de MySQL |
| `DB_NAME` | Nombre de la base |
| `DB_USER` | Usuario de la base |
| `DB_PASS` | Contraseña de la base |
| `RESEND_API_KEY` | Clave de Resend; vacía = modo simulación (el aviso va al log) |
| `EMAIL_AVISOS` | Email al que llegan los avisos (el de la cuenta de Resend) |
| `ENTORNO` | `produccion` en la nube: apaga `display_errors` |

## Servicios externos

| Servicio | Quién lo llama | Por qué | Datos que salen | Si falla |
|---|---|---|---|---|
| Open-Meteo Geocoding | Backend (cURL) | Hay que validar la ciudad antes de guardar (422 si no existe) y guardar las coordenadas | El nombre de la ciudad | El alta se guarda sin coordenadas |
| Leaflet + OpenStreetMap | Navegador | Las teselas del mapa son imágenes que muestra el navegador; no hay nada que proteger | La zona del mapa que se mira (y la IP del visitante) | La tabla y el CRUD siguen funcionando, sin mapa |
| Resend (email) | Backend (cURL) | Necesita una clave secreta: en el navegador quedaría a la vista | Nombre, email y ciudad del alumno, hacia los servidores de Resend | El alumno se guarda igual; el error queda en el log |

## Despliegue

- **Proveedor:** Railway (PaaS). Elegido porque ofrece MySQL en el mismo proyecto, variables de
  entorno en el panel y HTTPS automático; no hay que administrar un servidor (eso sería IaaS).
- **Cómo se construye:** el `Dockerfile` arma Apache + PHP 8.2 con `pdo_mysql`, igual que XAMPP.
- **Volver a desplegar:** con GitHub, cada `git push` a la rama principal redespliega solo.
  Sin GitHub: `railway up` desde la carpeta del proyecto.

## Limitaciones conocidas

- La edición (PUT) cambia nombre y email, no la ciudad.
- En modo simulación, `avisos.log` se borra en cada despliegue (el disco de Railway es efímero);
  por eso el aviso también se escribe en el log del servicio.
- Sin dominio propio verificado, Resend sólo entrega al email del titular de la cuenta.
