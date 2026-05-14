# UTS Assistant

Asistente virtual académico para la UTS (Universidad Tecnológica de Santander). Permite a los usuarios subir documentos académicos en PDF, indexarlos mediante técnicas de RAG (Retrieval-Augmented Generation) y consultarlos a través de un chat conversacional impulsado por LLMs.

---

## Tabla de contenidos

1. [Arquitectura general](#1-arquitectura-general)
2. [Servicios y puertos](#2-servicios-y-puertos)
3. [Requisitos](#3-requisitos)
4. [Configuración y puesta en marcha](#4-configuración-y-puesta-en-marcha)
5. [Backend — Laravel 12](#5-backend--laravel-12)
6. [Frontend — Vue 3 + Inertia](#6-frontend--vue-3--inertia)
7. [Microservicio IA — FastAPI](#7-microservicio-ia--fastapi)
8. [Base de datos](#8-base-de-datos)
9. [Variables de entorno](#9-variables-de-entorno)
10. [Flujo de datos](#10-flujo-de-datos)
11. [Estado actual del proyecto](#11-estado-actual-del-proyecto)

---

## 1. Arquitectura general

El proyecto sigue una arquitectura de microservicios con dos capas principales:

```
┌─────────────────────────────────────────────────────────────┐
│                        Usuario / Browser                    │
└───────────────────────────┬─────────────────────────────────┘
                            │ http://localhost:8080
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Nginx (reverse proxy)                    │
│                     docker/nginx/default.conf               │
└───────────┬────────────────────────────┬────────────────────┘
            │ /                          │ /ai/*
            ▼                            ▼
┌───────────────────┐        ┌───────────────────────────────┐
│  Laravel (PHP 8.2)│        │     FastAPI (Python 3.11)     │
│  puerto interno   │        │         puerto 8000           │
│  php:9000         │        │                               │
│                   │───────▶│  Embeddings (local)           │
│  DDD modular      │  HTTP  │  ChromaDB (vectores)          │
│  Inertia SSR      │        │  LLM (Nvidia / OpenAI /       │
│  Multi-tenant     │        │        Anthropic / Gemini)    │
└─────────┬─────────┘        └───────────────────────────────┘
          │
          ▼
┌───────────────────┐    ┌──────────────────────────────────┐
│  PostgreSQL 16    │    │  Vite / Node 20                  │
│  puerto 5432      │    │  puerto 5173 (dev, hot reload)   │
└───────────────────┘    └──────────────────────────────────┘
```

---

## 2. Servicios y puertos

| Servicio    | Imagen base              | Puerto expuesto | Función                                |
|-------------|--------------------------|-----------------|----------------------------------------|
| `nginx`     | nginx:1.27-alpine        | **8080** → 80   | Reverse proxy, sirve estáticos         |
| `php`       | php:8.2-fpm (custom)     | interno 9000    | Backend Laravel + PHP-FPM              |
| `node`      | node:20                  | **5173** → 5173 | Vite dev server con hot reload         |
| `postgres`  | postgres:16-alpine       | **5432** → 5432 | Base de datos principal                |
| `fastapi`   | python:3.11-slim (custom)| **8000** → 8000 | Microservicio de IA y RAG              |

Red interna: `uts_network` (bridge). Los contenedores se comunican por nombre de servicio.

---

## 3. Requisitos

- Docker >= 24
- Docker Compose >= 2.20
- Git

No se necesita PHP, Python, Node ni ninguna dependencia instalada localmente — todo corre dentro de contenedores.

---

## 4. Configuración y puesta en marcha

### 4.1 Clonar el repositorio

```bash
git clone <url-del-repo>
cd uts-assistant
```

### 4.2 Crear archivos de entorno

```bash
# Backend Laravel
cp laravel/.env.example laravel/.env

# Microservicio IA
cp ai-service/.env.example ai-service/.env
```

Editar cada archivo con los valores correctos (ver [sección 9](#9-variables-de-entorno)).

### 4.3 Levantar los servicios

```bash
docker compose up -d --build
```

### 4.4 Inicializar Laravel

```bash
# Generar clave de aplicación
docker compose exec php php artisan key:generate

# Ejecutar migraciones
docker compose exec php php artisan migrate

# (Opcional) Seeders iniciales
docker compose exec php php artisan db:seed
```

### 4.5 Compilar assets (desarrollo)

El contenedor `node` levanta Vite automáticamente con hot reload en `http://localhost:5173`.

Para producción:

```bash
docker compose exec node pnpm run build
```

### 4.6 Verificar que todo funciona

| URL                              | Descripción                      |
|----------------------------------|----------------------------------|
| http://localhost:8080            | Aplicación principal (Laravel)   |
| http://localhost:8080/ai/health  | Health check del microservicio IA |
| http://localhost:8000/docs       | Swagger UI de FastAPI             |

---

## 5. Backend — Laravel 12

### Estructura modular (DDD)

El backend sigue una arquitectura **Domain-Driven Design (DDD)** organizada en módulos independientes dentro de `laravel/app/Modules/`. Cada módulo encapsula su propia lógica de dominio, aplicación e infraestructura.

```
laravel/app/Modules/
├── DocumentTypes/          # Tipos de documentos académicos
├── AcademicDocuments/      # Gestión y carga de PDFs
└── Assistants/             # Conversaciones con el asistente IA
```

Cada módulo sigue esta estructura interna:

```
Módulo/
├── Domain/
│   ├── Entities/           # Entidades del negocio (sin acoplamiento a BD)
│   ├── ValueObjects/       # Objetos de valor (enums, tipos)
│   ├── Repositories/       # Interfaces de persistencia
│   └── Exceptions/         # Excepciones de dominio
├── Application/
│   ├── Commands/           # Casos de uso (comandos)
│   ├── Handlers/           # Orquestación de la lógica
│   └── DTOs/               # Data Transfer Objects
├── Infrastructure/
│   ├── Database/
│   │   ├── Models/         # Eloquent Models
│   │   └── Repositories/   # Implementaciones concretas
│   └── Http/
│       ├── Controllers/    # Controladores HTTP
│       ├── Requests/       # Form Requests (validación)
│       └── Routes/         # Rutas del módulo
└── ModuleServiceProvider.php
```

---

### Módulo: DocumentTypes

Gestión de los tipos de documentos académicos (ej. Tesis, Trabajo de grado, Artículo).

**Entidad principal:** `DocumentType`
- `uuid`, `name`, `prefix`
- `status` (Active / Inactive)
- `hasPrefix`, `hasDate`, `dateFormat`, `lengthSequence`

**Casos de uso implementados:**
- Listar tipos con filtros
- Crear tipo de documento
- Editar tipo de documento
- Eliminar (soft delete)
- Validar unicidad del prefijo

**Rutas:**

| Método | Ruta                                  | Acción              |
|--------|---------------------------------------|---------------------|
| GET    | `/tipos-documentos`                   | Listar              |
| GET    | `/document-types/create`              | Formulario crear    |
| POST   | `/document-types`                     | Guardar             |
| GET    | `/document-types/{uuid}/edit`         | Formulario editar   |
| PUT    | `/document-types/{uuid}`              | Actualizar          |
| DELETE | `/document-types/{uuid}`              | Eliminar            |
| POST   | `/document-types/validate-prefix`     | Validar prefijo     |

---

### Módulo: AcademicDocuments

Gestión del ciclo de vida de documentos PDF académicos: carga, indexación en ChromaDB y consulta.

**Entidad principal:** `AcademicDocument`
- `uuid`, `title`, `filename`, `mimeType`, `sizeBytes`
- `status`: `pending` → `processing` → `indexed` / `error`
- `chromaIds`: array de IDs de chunks almacenados en ChromaDB
- `uploadedBy`: referencia al usuario

**Flujo de estados:**

```
[pending] ──▶ [processing] ──▶ [indexed]
                    └──────────▶ [error]
```

**Estado:** Estructura de dominio completa. Controladores y vistas en desarrollo.

---

### Módulo: Assistants

Gestión de conversaciones entre el usuario y el asistente IA.

**Entidades:**
- `Conversation`: agrupa mensajes de una sesión (`uuid`, `user_id`, `session_id`)
- `Message`: mensaje individual (`uuid`, `role`: user/assistant, `content`, `sources` JSON)

El campo `sources` almacena los fragmentos de documentos que el LLM usó para generar la respuesta, permitiendo mostrar las fuentes al usuario.

**Estado:** Estructura de dominio y migraciones listas. Lógica de aplicación y vistas en desarrollo.

---

### Autenticación y multitenancy

- **Jetstream** + **Sanctum**: autenticación con soporte para equipos y tokens API.
- **Passport**: OAuth2 para integraciones externas.
- **Multi-tenant**: middleware `tenant` que asigna la conexión de BD correcta según el cliente. Dos conexiones configuradas: `landlord` (sistema global) y `tenant` (datos del cliente).

---

### Dependencias principales de PHP

| Paquete                     | Versión  | Uso                             |
|-----------------------------|----------|---------------------------------|
| laravel/framework           | ^12.0    | Framework base                  |
| laravel/jetstream           | ^5.0     | Auth + equipos                  |
| laravel/sanctum             | ^4.0     | Token auth API                  |
| laravel/passport            | ^13.7    | OAuth2                          |
| inertiajs/inertia-laravel   | ^1.0     | Bridge Inertia SSR              |
| guzzlehttp/guzzle           | ^7.2     | Cliente HTTP para llamar FastAPI|
| pestphp/pest                | ^3.0     | Testing                         |

---

## 6. Frontend — Vue 3 + Inertia

### Stack

- **Vue 3** (Options API)
- **Inertia.js** — elimina la necesidad de una API REST separada; el backend devuelve componentes Vue directamente
- **Vite** — bundler con hot reload en desarrollo
- **Bootstrap 5** — framework CSS base
- **pnpm** — gestor de paquetes

### Estructura de páginas

```
laravel/resources/js/
├── Pages/
│   ├── DocumentTypes/      # CRUD completo (Index, Create, Edit, Show)
│   ├── AcademicDocuments/  # Index (en desarrollo)
│   ├── Assistants/         # Index (en desarrollo)
│   ├── Dashboard/          # Panel principal
│   └── Auth/               # Login, registro, 2FA, recuperación de contraseña
├── Layouts/
│   ├── main.vue            # Layout principal
│   ├── vertical.vue        # Sidebar vertical
│   ├── horizontal.vue      # Navbar horizontal
│   └── twocolumn.vue       # Dos columnas
├── Components/
│   ├── DataTable.vue       # Tabla reutilizable con búsqueda
│   └── ...
└── Composables/
    ├── useFetchPetition.js     # Cliente HTTP (axios wrapper)
    ├── useSweetAlert.js        # Modales de confirmación
    ├── useFormErrorFocus.js    # Focus automático en errores
    ├── useDateFormatter.js     # Formateo de fechas
    ├── useCurrencyFormatter.js # Formateo de moneda
    ├── useEmailValidator.js    # Validación de email
    └── useDVValidator.js       # Validación de dígito de verificación
```

### Dependencias principales de Node

| Paquete                | Uso                                        |
|------------------------|--------------------------------------------|
| vue ^3.4               | Framework frontend                         |
| @inertiajs/vue3        | Integración Inertia                        |
| @vuelidate/core        | Validación de formularios                  |
| bootstrap ^5.3         | CSS framework                              |
| sweetalert2            | Modales y alertas                          |
| apexcharts / echarts   | Gráficos y dashboards                      |
| @fullcalendar/vue3     | Calendario interactivo                     |
| datatables.net         | Tablas avanzadas con paginación y búsqueda |
| leaflet                | Mapas interactivos                         |
| vue-i18n               | Internacionalización                       |

---

## 7. Microservicio IA — FastAPI

### Propósito

Servicio independiente en Python que se encarga de:

1. **Indexación**: extraer texto de PDFs, dividirlo en fragmentos (chunks), generar embeddings y almacenarlos en ChromaDB.
2. **Consulta RAG**: dado un mensaje del usuario, recuperar los fragmentos más relevantes de ChromaDB y enviárselos al LLM junto con la pregunta para generar una respuesta contextualizada.

### Stack tecnológico

| Componente         | Tecnología                                        |
|--------------------|---------------------------------------------------|
| Framework API      | FastAPI 0.111                                     |
| Servidor ASGI      | Uvicorn                                           |
| Extracción de PDF  | PyPDF                                             |
| Embeddings         | sentence-transformers (`all-MiniLM-L6-v2`, local) |
| Base vectorial     | ChromaDB (persistente en `./chroma_db/`)          |
| LLM soportados     | Nvidia NIM, OpenAI, Anthropic, Google Gemini      |

### Configuración RAG

| Parámetro          | Valor por defecto | Descripción                              |
|--------------------|-------------------|------------------------------------------|
| `RAG_CHUNK_SIZE`   | 500               | Caracteres por fragmento de documento    |
| `RAG_CHUNK_OVERLAP`| 50                | Solapamiento entre fragmentos            |
| `RAG_TOP_K`        | 4                 | Fragmentos recuperados por consulta      |
| `EMBEDDING_MODEL`  | all-MiniLM-L6-v2  | Modelo de embeddings (corre localmente)  |

### Endpoints actuales

| Método | Ruta       | Descripción                      |
|--------|------------|----------------------------------|
| GET    | `/health`  | Health check del servicio        |
| GET    | `/docs`    | Swagger UI (auto-generada)       |

**Endpoints pendientes de implementar:**

| Método | Ruta              | Descripción                                        |
|--------|-------------------|----------------------------------------------------|
| POST   | `/documents`      | Indexar un PDF en ChromaDB                        |
| GET    | `/documents`      | Listar documentos indexados                       |
| DELETE | `/documents/{id}` | Eliminar documento de ChromaDB                    |
| POST   | `/query`          | Consulta RAG: recuperar chunks + respuesta del LLM|

### LLM soportados

El microservicio soporta múltiples proveedores de LLM. Se configura cuál usar con la variable `LLM_PROVIDER` en el `.env`:

| Proveedor  | Variable de activación        | Modelo por defecto           |
|------------|-------------------------------|------------------------------|
| `nvidia`   | `NVIDIA_API_KEY`              | meta/llama-3.1-8b-instruct   |
| `openai`   | `OPENAI_API_KEY`              | gpt-4o-mini                  |
| `anthropic`| `ANTHROPIC_API_KEY`           | claude-haiku-3-5             |
| `gemini`   | `GEMINI_API_KEY`              | gemini-1.5-flash             |

---

## 8. Base de datos

Motor: **PostgreSQL 16**

### Tablas principales

#### `academic_documents`
| Columna        | Tipo                                          | Descripción                              |
|----------------|-----------------------------------------------|------------------------------------------|
| id             | bigint PK                                     |                                          |
| uuid           | uuid unique                                   | Identificador público                    |
| title          | varchar                                       | Título del documento                     |
| filename       | varchar                                       | Nombre del archivo en disco              |
| mime_type      | varchar                                       | Tipo MIME (application/pdf)              |
| size_bytes     | bigint                                        | Tamaño en bytes                          |
| status         | enum(pending, processing, indexed, error)     | Estado de indexación                     |
| error_message  | text nullable                                 | Mensaje de error si aplica               |
| chroma_ids     | json nullable                                 | IDs de chunks en ChromaDB                |
| uploaded_by    | bigint FK users                               | Usuario que subió el documento           |
| created_at     | timestamp                                     |                                          |
| updated_at     | timestamp                                     |                                          |

#### `conversations`
| Columna    | Tipo           | Descripción                              |
|------------|----------------|------------------------------------------|
| id         | bigint PK      |                                          |
| uuid       | uuid unique    | Identificador público                    |
| user_id    | bigint FK nullable | Usuario autenticado (null si anónimo)|
| session_id | varchar nullable | Sesión anónima                         |
| created_at | timestamp      |                                          |
| updated_at | timestamp      |                                          |

#### `messages`
| Columna         | Tipo                    | Descripción                            |
|-----------------|-------------------------|----------------------------------------|
| id              | bigint PK               |                                        |
| uuid            | uuid unique             | Identificador público                  |
| conversation_id | bigint FK conversations |                                        |
| role            | enum(user, assistant)   | Emisor del mensaje                     |
| content         | text                    | Contenido del mensaje                  |
| sources         | json nullable           | Fragmentos de docs usados por el LLM   |
| created_at      | timestamp               |                                        |
| updated_at      | timestamp               |                                        |

---

## 9. Variables de entorno

### laravel/.env

```dotenv
APP_NAME="UTS Assistant"
APP_ENV=local
APP_KEY=                        # Generar con: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_LOCALE=es

DB_CONNECTION=pgsql
DB_HOST=postgres                # Nombre del servicio Docker
DB_PORT=5432
DB_DATABASE=uts_db
DB_USERNAME=uts_user
DB_PASSWORD=secret

CACHE_STORE=database
SESSION_DRIVER=database

AI_SERVICE_URL=http://fastapi:8000   # URL interna Docker
AI_SERVICE_TIMEOUT=60
```

### ai-service/.env

```dotenv
# Proveedor activo: nvidia | openai | anthropic | gemini
LLM_PROVIDER=nvidia

NVIDIA_API_KEY=
NVIDIA_BASE_URL=https://integrate.api.nvidia.com/v1
NVIDIA_MODEL=meta/llama-3.1-8b-instruct

OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini

ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-haiku-3-5

GEMINI_API_KEY=
GEMINI_MODEL=gemini-1.5-flash

CHROMA_PATH=./chroma_db
CHROMA_COLLECTION=uts_documents

EMBEDDING_MODEL=all-MiniLM-L6-v2

RAG_TOP_K=4
RAG_CHUNK_SIZE=500
RAG_CHUNK_OVERLAP=50

APP_ENV=local
APP_PORT=8000
TZ=America/Bogota
```

---

## 10. Flujo de datos

### Subida e indexación de un documento

```
Usuario sube PDF
      │
      ▼
Laravel valida y guarda el archivo en disco
      │ status → pending
      ▼
Laravel llama POST /documents en FastAPI
      │
      ▼
FastAPI extrae texto del PDF (PyPDF)
      │
      ▼
FastAPI divide el texto en chunks
(RAG_CHUNK_SIZE=500, RAG_CHUNK_OVERLAP=50)
      │
      ▼
FastAPI genera embeddings para cada chunk
(sentence-transformers, local, sin API key)
      │
      ▼
FastAPI almacena chunks + embeddings en ChromaDB
      │
      ▼
FastAPI retorna lista de IDs de chunks a Laravel
      │ status → indexed, chroma_ids = [...]
      ▼
Usuario ve el documento disponible para consulta
```

### Consulta al asistente

```
Usuario envía mensaje en el chat
      │
      ▼
Laravel crea o recupera la Conversation
      │
      ▼
Laravel guarda Message (role=user)
      │
      ▼
Laravel llama POST /query en FastAPI
(conversation_id, message, top_k=4)
      │
      ▼
FastAPI genera embedding del mensaje
      │
      ▼
FastAPI recupera los top_k chunks más similares de ChromaDB
      │
      ▼
FastAPI construye el prompt:
  [contexto de los chunks] + [mensaje del usuario]
      │
      ▼
FastAPI llama al LLM configurado (Nvidia/OpenAI/Anthropic/Gemini)
      │
      ▼
FastAPI retorna respuesta + fuentes usadas a Laravel
      │
      ▼
Laravel guarda Message (role=assistant, sources=[...])
      │
      ▼
Usuario ve la respuesta con las fuentes citadas
```

---

## 11. Estado actual del proyecto

### Completado

- Infraestructura Docker con 5 servicios orquestados
- Arquitectura modular DDD en Laravel
- Autenticación multitenant con Jetstream y Sanctum
- **CRUD completo de DocumentTypes** (backend + frontend)
- Migraciones para `academic_documents`, `conversations` y `messages`
- Estructura de dominio para AcademicDocuments y Assistants
- Frontend Vue 3 + Inertia con layouts y composables reutilizables
- Skeleton del microservicio FastAPI con health check
- Soporte multi-proveedor LLM en configuración (Nvidia, OpenAI, Anthropic, Gemini)

### En desarrollo

| Funcionalidad                             | Módulo             |
|-------------------------------------------|--------------------|
| Carga y visualización de PDFs             | AcademicDocuments  |
| Indexación de PDFs en ChromaDB (FastAPI)  | AcademicDocuments  |
| Endpoint RAG `/query` (FastAPI)           | Assistants         |
| Chat conversacional (frontend)            | Assistants         |
| Historial de conversaciones               | Assistants         |
| Mostrar fuentes citadas en respuestas     | Assistants         |
| Tests unitarios e integración             | General            |

---

## Estructura de archivos clave

```
uts-assistant/
├── docker-compose.yml                          # Orquestación de servicios
├── docker/
│   ├── nginx/default.conf                      # Routing HTTP (Nginx)
│   ├── php/Dockerfile                          # Imagen PHP 8.2-FPM custom
│   └── fastapi/Dockerfile                      # Imagen Python 3.11 custom
├── laravel/
│   ├── app/
│   │   └── Modules/
│   │       ├── DocumentTypes/                  # Módulo CRUD tipos documentos
│   │       ├── AcademicDocuments/              # Módulo gestión PDFs
│   │       └── Assistants/                     # Módulo chat IA
│   ├── routes/
│   │   ├── web.php                             # Rutas Inertia principales
│   │   └── api.php                             # Rutas API REST
│   ├── database/migrations/                    # Migraciones de BD
│   ├── resources/js/
│   │   ├── Pages/                              # Componentes Vue 3 (vistas)
│   │   ├── Layouts/                            # Layouts reutilizables
│   │   └── Composables/                        # Hooks Vue reutilizables
│   └── config/app.php                          # Configuración principal
└── ai-service/
    ├── app/main.py                             # Aplicación FastAPI
    └── requirements.txt                        # Dependencias Python
```
