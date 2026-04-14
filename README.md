# Sistema de Gestión Hospitalaria - 8nsb

Este proyecto es un sistema integral de gestión hospitalaria diseñado para administrar las operaciones clínicas y administrativas de un centro de salud. Permite el control detallado de pacientes, personal médico, infraestructura y procedimientos médicos.

## Características Principales

- **Gestión de Pacientes:** Control de ingresos, egresos y consultas médicas.
- **Cuerpo Médico:** Administración de expedientes de médicos y sus especialidades.
- **Infraestructura:** Gestión de áreas, departamentos, consultorios, habitaciones, quirófanos y laboratorios.
- **Servicios Clínicos:** Control de tipos de estudios, procedimientos quirúrgicos y laboratorios.
- **Seguridad:** Sistema de autenticación con roles de usuario (Administrador, Recepción, Médico, Laboratorio).

## Tecnologías Utilizadas

- **Backend:** PHP (API RESTful)
- **Frontend:** HTML5, CSS3, JavaScript (Fetch API)
- **Base de Datos:** MySQL
- **Servidor Recomendado:** XAMPP

## Requisitos Previos

1. Tener instalado un servidor local (XAMPP).
2. PHP 7.4 o superior.
3. MySQL

##  Instalación y Configuración

1. **Clonar el proyecto:**
   Coloca la carpeta del proyecto dentro del directorio `htdocs` de tu instalación de XAMPP (ej. `C:\xampp\htdocs\8nsb-Proyectos-De-Software`).

2. **Configurar la Base de Datos:**
   - Abre **phpMyAdmin**.
   - Crea una nueva base de datos llamada `hospital_db`.
   - Importa el archivo `hospital_db.sql` que se encuentra en la raíz del proyecto.

3. **Verificar Conexión:**
   Asegúrate de que las credenciales en `config/database.php` coincidan con las de tu servidor local:
   ```php
   private string $host = "localhost";
   private string $db_name = "hospital_db";
   private string $username = "root";
   private string $password = "";
   ```

4. **Crear Usuario Administrador:**
   Si es la primera vez que lo usas, puedes ejecutar el script `crear_admin.php` desde tu navegador para generar las credenciales iniciales.

##  Cómo Correr el Proyecto

### Opción 1: Usando el acceso rápido (Windows)
Si estás en Windows y usas XAMPP con las rutas por defecto, puedes ejecutar el archivo:
- `run.bat`

### Opción 2: Manualmente
1. Inicia los módulos de **Apache** y **MySQL** en el Panel de Control de XAMPP.
2. Abre tu navegador y dirígete a:
   `http://localhost/8nsb-Proyectos-De-Software/frontend/login.html`

##  Estructura del Proyecto

- `api/`: Lógica del servidor organizada por módulos (CRUDs).
- `config/`: Configuración de la conexión a la base de datos.
- `frontend/`: Interfaz de usuario (HTML, CSS, JS).
- `helpers/`: Utilidades para autenticación y respuestas JSON.
- `hospital_db.sql`: Esquema y datos iniciales de la base de datos.
