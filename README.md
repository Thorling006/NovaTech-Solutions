# NovaTech Solutions

## Universidad

Universidad Gerardo Barrios (UGB)

## Facultad

Facultad de Ciencia y Tecnología

## Carrera

Ingeniería en Sistemas y Redes Informáticas

## Asignatura

Programación Computacional IV

## Docente

William Montes

## Año

2026

---

# Integrantes

- Omar David Ventura Cruz
- Yensi Elizabeth Valladares Ventura
- Brayan Adaly Campos Martinez
- Kevin Antonio Castro Araujo
- Jeremias Neftaly Fuentes Mendez

---

# Descripción del Proyecto

NovaTech Solutions es un sistema web desarrollado para la gestión integral de inventario, ventas y logística de distribución. La plataforma permite administrar productos, categorías, movimientos de inventario, usuarios, rutas de entrega y seguimiento de pedidos desde una interfaz moderna y eficiente.

El objetivo principal del sistema es optimizar los procesos operativos de una empresa tecnológica mediante la automatización de tareas administrativas y logísticas, permitiendo un mejor control de los recursos y una mayor eficiencia en la toma de decisiones.

---

# Objetivo General

Desarrollar una plataforma web que permita gestionar de manera eficiente el inventario, las ventas y los procesos logísticos de una empresa, proporcionando herramientas que faciliten el control de la información y mejoren la productividad organizacional.

---

# Objetivos Específicos

- Administrar productos y categorías.
- Controlar entradas y salidas de inventario.
- Gestionar usuarios mediante roles y permisos.
- Registrar y consultar ventas.
- Administrar rutas de entrega.
- Facilitar el seguimiento de pedidos.
- Centralizar la información operativa en una única plataforma.

---

# Arquitectura del Sistema

El sistema fue desarrollado utilizando una arquitectura SPA (Single Page Application) basada en tecnologías modernas.

## Frontend

- Vue.js 3
- Inertia.js
- Bootstrap 5
- Vite

## Backend

- Laravel 11
- PHP 8.2

## Base de Datos

- PostgreSQL

## Patrón de Diseño

- MVC (Modelo - Vista - Controlador)

---

# Tecnologías Utilizadas

| Tecnología | Función |
|------------|----------|
| Laravel 11 | Backend |
| Vue.js 3 | Interfaz de usuario |
| Inertia.js | Comunicación Frontend-Backend |
| PostgreSQL | Base de datos |
| Bootstrap 5 | Diseño responsivo |
| Vite | Compilación de recursos |
| Laravel Breeze | Autenticación |
| Ziggy | Rutas JavaScript |
| DomPDF | Generación de documentos PDF |

---

# Funcionalidades Implementadas

## Gestión de Usuarios

- Registro de usuarios.
- Inicio de sesión.
- Recuperación de contraseña.
- Verificación de correo electrónico.
- Gestión de perfiles.

## Gestión de Roles

- Superusuario.
- Administrador.
- Empleado.
- Conductor.

## Gestión de Categorías

- Crear categorías.
- Editar categorías.
- Eliminar categorías.
- Consultar categorías.

## Gestión de Productos

- Crear productos.
- Editar productos.
- Eliminar productos.
- Asignación de categorías.
- Control de stock.

## Gestión de Inventario

- Registro de entradas.
- Registro de salidas.
- Historial de movimientos.
- Actualización automática de existencias.

## Gestión de Ventas

- Registro de ventas.
- Consulta de ventas.
- Relación entre ventas y productos.

## Dashboard Administrativo

- Estadísticas generales.
- Indicadores de productos.
- Indicadores de inventario.
- Indicadores de ventas.

## Catálogo Público

- Visualización de productos.
- Información detallada.
- Consulta pública.

## Gestión Logística

- Administración de rutas.
- Asignación de conductores.
- Actualización de estados.

## Seguimiento de Entregas

- Consulta de pedidos.
- Seguimiento de entregas.
- Actualización de estados logísticos.

---

# Estructura General del Sistema

NovaTech Solutions se encuentra organizado en módulos independientes que permiten mantener una arquitectura escalable y fácil de mantener.

- Módulo de Autenticación.
- Módulo de Usuarios.
- Módulo de Roles.
- Módulo de Categorías.
- Módulo de Productos.
- Módulo de Inventario.
- Módulo de Ventas.
- Módulo de Logística.
- Módulo de Conductores.
- Módulo de Seguimiento.

---

# Instalación del Proyecto

## Clonar repositorio

```bash
git clone https://github.com/Thorling006/NovaTech-Solutions.git
```

## Ingresar al proyecto

```bash
cd NovaTech-Solutions
```

## Instalar dependencias PHP

```bash
composer install
```

## Instalar dependencias Node

```bash
npm install
```

## Crear archivo .env

```bash
cp .env.example .env
```

## Generar APP_KEY

```bash
php artisan key:generate
```

## Ejecutar migraciones

```bash
php artisan migrate
```

## Ejecutar seeders

```bash
php artisan db:seed
```

## Iniciar frontend

```bash
npm run dev
```

## Iniciar servidor Laravel

```bash
php artisan serve
```

---

# Credenciales de Acceso

## Superusuario

Correo:

```text
admin@novastock.test
```

Contraseña:

```text
password
```

Rol:

```text
Superusuario
```

---

## Conductores

### Carlos

Correo:

```text
carlos@conductor.test
```

Contraseña:

```text
password
```

### Luis

Correo:

```text
luis@conductor.test
```

Contraseña:

```text
password
```

### Juan

Correo:

```text
juan@conductor.test
```

Contraseña:

```text
password
```

---

# Roles Disponibles

- Superusuario
- Administrador
- Empleado
- Conductor

---

# Estado Actual del Proyecto

### Implementado

✅ Gestión de usuarios

✅ Gestión de roles

✅ Gestión de categorías

✅ Gestión de productos

✅ Gestión de inventario

✅ Gestión de ventas

✅ Dashboard administrativo

✅ Catálogo público

✅ Gestión logística

✅ Seguimiento de pedidos

✅ Panel de conductor

### Mejoras Futuras

- Reportes avanzados.
- Exportación de información.
- Notificaciones automáticas.
- Mejoras visuales.
- Optimización de rendimiento.

---

# Repositorio Oficial

GitHub:

https://github.com/Thorling006/NovaTech-Solutions

---

© 2026 - NovaTech Solutions
Universidad Gerardo Barrios
Programación Computacional IV
