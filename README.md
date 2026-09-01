# Mesa Digital — Red Itínere

Sistema administrativo integral para la recepción, trazabilidad, derivación y resolución de correspondencia, oficios judiciales, cartas documento, telegramas y paquetería institucional de los colegios pertenecientes a la **Red Itínere**.

---

## 🚀 Características Principales

- **Derivación Inteligente por Tipo de Documento**: Al cargar un nuevo documento, la recepcionista selecciona únicamente el *Tipo de Documento* y el sistema asigna de forma reactiva el área/categoría y su(s) responsable(s). Si hay un solo responsable se auto-asigna; si hay múltiples, permite derivar a un destinatario puntual o notificar al equipo completo.
- **Gestión Integral de Categorías y Responsables**: Visualización en filas con modal popup unificado para administrar propiedades, colores y asignación/desasignación de responsables en tiempo real.
- **Bandeja de Entrada con Vista Rápida**: Drawer lateral deslizable (*quick view*) con previsualización de adjuntos, historial de trazabilidad y gestión de estados (*Recibido*, *En curso*, *Resuelto*, *Cerrado*, *Anulado*).
- **Control de Vencimientos y Plazos Legales**: Cálculo automático de semaforización (Verde, Amarillo, Rojo) y recordatorios automatizados.
- **Autenticación Dual Segura**:
  - Entorno Local: *Login Simulado* instantáneo con selector de roles y usuarios sembrados.
  - Entorno Producción: *Google Workspace OAuth 2.0* restringido al dominio `@reditinere.com` con guardarraíles estrictos de seguridad.
- **Seguridad OWASP**: Protección CSRF en todas las peticiones mutables, cabeceras estrictas (`X-Frame-Options`, `CSP`, `X-Content-Type-Options`), rate limiting ante fuerza bruta, y control de acceso basado en roles (RBAC).

---

## 🛠️ Stack Tecnológico

- **Backend**: PHP 8.2+ (Tipado estricto, Clean MVC, Arquitectura orientada a servicios).
- **Enrutamiento e Inyección**: FastRoute + PHP-DI 7.
- **Base de Datos**: MySQL 8.0+ / MariaDB con PDO, transacciones ACID y migraciones Phinx.
- **Frontend**: Server-Side Rendering (SSR) semántico, Tailwind CSS + Alpine.js local (sin dependencias de CDN).
- **Mailing**: PHPMailer con soporte SMTP y plantillas HTML responsivas.

---

## 📂 Estructura del Proyecto

```text
MesaDigital/
├── config/                  # Archivos de configuración (app, database, mail, auth)
├── database/
│   ├── migrations/          # 13 migraciones Phinx estructuradas
│   ├── seeds/               # 7 seeders con datos iniciales completos
│   └── schema_and_seeds.sql # Dump SQL completo listo para importar
├── public/                  # Punto de entrada web y assets estáticos
│   ├── assets/              # CSS compilado, Alpine.js, scripts e iconos SVG
│   └── index.php            # Front Controller del sistema
├── resources/
│   └── views/               # Plantillas PHP (pages, partials, emails, layouts)
├── routes/                  # Definición de rutas web (routes/web.php)
├── src/                     # Código fuente de la aplicación (PSR-4 App\)
│   ├── Auth/                # Proveedores de autenticación (Google OAuth y Simulado)
│   ├── Controllers/         # Controladores MVC
│   ├── Middleware/          # Auth, CSRF, RBAC, RateLimit, SecurityHeaders
│   ├── Models/              # Entidades del dominio
│   ├── Repositories/        # Capa de persistencia con PDO
│   ├── Services/            # Lógica de negocio (Documento, Notificación, Exportación)
│   ├── Support/             # Router, View renderer, Helpers y Contenedor DI
│   └── Validation/          # Validador de formularios
├── storage/                 # Adjuntos y logs del sistema
├── tests/                   # Suite de pruebas automatizadas con PHPUnit
└── .env.example             # Plantilla de variables de entorno
```

---

## ⚙️ Instalación y Puesta en Marcha

### 1. Clonar el repositorio
```bash
git clone https://github.com/nicolasescudero5/MesaDigital.git
cd MesaDigital
```

### 2. Instalar dependencias de Composer
```bash
composer install --no-scripts
```

### 3. Configurar variables de entorno
Copiá el archivo `.env.example` a `.env` y configurá las credenciales de tu base de datos:
```bash
cp .env.example .env
```

Configuración típica para desarrollo local:
```env
APP_NAME="Mesa Digital"
APP_ENV=local
APP_URL=http://localhost:8085

AUTH_DRIVER=simulado
LOGIN_SIMULADO_HABILITADO=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mesa_digital
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

### 4. Base de Datos

Podés inicializar la base de datos de dos formas:

#### Opción A: Mediante el volcado SQL directo (Recomendado)
```bash
mysql -u root -p mesa_digital < database/schema_and_seeds.sql
```

#### Opción B: Mediante migraciones y seeders de Phinx
```bash
./vendor/bin/phinx migrate
./vendor/bin/phinx seed:run
```

---

## 🏃 Ejecutar en Servidor Local

Iniciá el servidor embebido de PHP en el puerto configurado (`8085`):
```bash
php -S 127.0.0.1:8085 -t public public/index.php
```

Ingresá en tu navegador a: **[http://localhost:8085](http://localhost:8085)**

---

## 🧪 Ejecución de Tests

Para correr la suite de pruebas unitarias y de integración:
```bash
./vendor/bin/phpunit
```

---

## 👥 Roles del Sistema

| Rol | Alcance y Permisos |
| :--- | :--- |
| **Administrador** | Control total: configuración global, ABM de sedes, categorías, tipos de documento, usuarios, reportes y anulaciones. |
| **Recepción de Sede** | Carga de correspondencia, digitalización de fotos/adjuntos y derivación a las áreas correspondientes. |
| **Dirección de Sede** | Carga y supervisión de los documentos vinculados a su sede escolar asignada. |
| **Responsable de Área** | Gestión de documentos de su categoría (tomar trámite, responder, adjuntar constancia de resolución y cierre). |
| **Supervisión General** | Vista global de lectura y reportes consolidados para auditoría ejecutiva. |

---

## 📄 Licencia

Uso exclusivo y confidencial para **Red Itínere**. Todos los derechos reservados.
