# LearnPro API — Pure PHP MVC with JWT Auth

> A clean **PHP 8.0+ MVC REST API** that mirrors the Node.js/Express project, using JWT authentication and role-based permission control — **zero frameworks, zero bloat**.

---

## 📁 Folder Structure

```
learnpro-api/
├── public/
│   └── index.php               ← Entry point (all requests land here)
├── app/
│   ├── Core/
│   │   ├── Router.php          ← HTTP router with middleware pipeline
│   │   ├── Controller.php      ← Base controller (json(), getBody(), input())
│   │   ├── Model.php           ← Base model (find, findBy, create, update, delete)
│   │   └── Database.php        ← PDO singleton
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php   ← login, me, profile
│   │   ├── UserController.php       ← User CRUD
│   │   ├── RoleController.php       ← Role management + permission assignment
│   │   └── PermissionController.php ← Permission CRUD
│   ├── Models/
│   │   ├── UserMain.php        ← user_mains table
│   │   ├── UserRole.php        ← user_roles table (+ joins)
│   │   ├── UserPermission.php  ← user_permissions table
│   │   └── RolePermission.php  ← role_permissions pivot
│   ├── Middleware/
│   │   ├── AuthMiddleware.php        ← Verifies Bearer JWT
│   │   └── PermissionMiddleware.php  ← Checks a named permission
│   └── Utils/
│       ├── JwtHelper.php       ← Pure PHP HS256 JWT (no library needed)
│       └── HttpClient.php      ← cURL wrapper for external auth server
├── routes/
│   └── api.php                 ← All route definitions
├── database/
│   └── schema.sql              ← Full DB schema + seed data
├── .env.example                ← Copy to .env and fill in values
├── .htaccess                   ← Apache rewrite rules
├── nginx.conf                  ← Nginx config example
└── composer.json               ← Optional (only needed if you add libraries)
```

---

## ⚙️ Setup — Step by Step

### Step 1 — Clone / copy the project

```bash
cp -r learnpro-api /var/www/learnpro-api
```

### Step 2 — Create your `.env` file

```bash
cd /var/www/learnpro-api
cp .env.example .env
nano .env          # fill in DB credentials, JWT secret, external auth URL
```

```.env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=pb_learnpro_db
DB_USER=root
DB_PASS=your_password

JWT_SECRET=replace_with_long_random_string
JWT_EXPIRE=3600

EXTERNAL_AUTH_URL=http://localhost:3000/ad-request/login
```

### Step 3 — Import the database schema

```bash
mysql -u root -p < database/schema.sql
```

This creates all 4 tables and seeds default roles + permissions.

### Step 4 — Configure your web server

#### Apache
The `.htaccess` at the root handles rewriting. Point your VirtualHost `DocumentRoot` to the `public/` folder:

```apache
<VirtualHost *:80>
    ServerName learnpro.local
    DocumentRoot /var/www/learnpro-api/public
    <Directory /var/www/learnpro-api/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable mod_rewrite:
```bash
a2enmod rewrite && systemctl restart apache2
```

#### Nginx
Copy `nginx.conf` to `/etc/nginx/sites-available/learnpro` and enable it:

```bash
ln -s /etc/nginx/sites-available/learnpro /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

#### PHP Built-in Server (development only)
```bash
cd /var/www/learnpro-api
php -S localhost:8000 -t public
```

### Step 5 — Test it

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret"}'

# Use the token
curl http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer <your_token>"
```

---

## 🗄️ Database Tables

### `user_mains`
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK | Auto increment |
| email | VARCHAR(255) UNIQUE | Login identifier |
| password | VARCHAR(255) | `EXTERNAL_AUTH` for AD users |
| service_number | VARCHAR(50) | Optional staff number |
| role_id | INT FK → user_roles | Default: 4 (Staff) |
| is_active | TINYINT | 1 = active |
| is_delete | TINYINT | Soft delete flag |
| created_at | DATETIME | |
| updated_at | DATETIME | |

### `user_roles`
| Column | Type | Notes |
|---|---|---|
| role_id | INT UNSIGNED PK | |
| role_name | VARCHAR(100) | e.g. "Admin" |
| level | TINYINT | Higher = more privileged |
| is_active | TINYINT | |
| is_delete | TINYINT | |
| createdAt | DATETIME | |
| updatedAt | DATETIME | |

### `user_permissions`
| Column | Type | Notes |
|---|---|---|
| permission_id | INT UNSIGNED PK | |
| name | VARCHAR(100) UNIQUE | Machine key e.g. `USER_VIEW` |
| display_name | VARCHAR(150) | Human-readable label |
| description | TEXT | |
| is_active | TINYINT | |
| createdAt | DATETIME | |
| updatedAt | DATETIME | |

### `role_permissions`
| Column | Type | Notes |
|---|---|---|
| role_id | INT FK | Composite PK |
| permission_id | INT FK | Composite PK |
| is_active | TINYINT | 0 = revoked |
| createdAt | DATETIME | |
| updatedAt | DATETIME | |

---

## 🔐 Auth Flow

```
Client POST /api/auth/login  { email, password }
    │
    ├─► External AD Server (EXTERNAL_AUTH_URL)
    │       └─ Returns { status: "success", data: { email, ... } }
    │
    ├─► Check user_mains — auto-register if new
    │
    ├─► Load role → permissions from role_permissions
    │
    └─► Return JWT  { user_id, email, role_id, role_permissions[] }


Client GET /api/auth/me
    Authorization: Bearer <token>
    │
    ├─► AuthMiddleware verifies JWT signature + expiry
    ├─► Attaches decoded payload to $_REQUEST['auth_user']
    └─► Controller returns user info


Client GET /api/auth/profile
    Authorization: Bearer <token>
    │
    ├─► AuthMiddleware (verify JWT)
    ├─► PermissionMiddleware ('PROFILE_MANAGEMENT')
    │       ├─ Fast path: check token's role_permissions[]
    │       └─ Fallback: query role_permissions table in DB
    └─► Controller responds
```

---

## 🛣️ API Endpoints

### Public
| Method | Route | Description |
|---|---|---|
| POST | `/api/auth/login` | Login + get JWT |

### Protected (JWT required)
| Method | Route | Permission | Description |
|---|---|---|---|
| GET | `/api/auth/me` | — | Current user info |
| GET | `/api/auth/profile` | `PROFILE_MANAGEMENT` | Profile page |
| GET | `/api/users` | `USER_VIEW` | List all users |
| GET | `/api/users/{id}` | `USER_VIEW` | Get user + role |
| PUT | `/api/users/{id}` | `USER_EDIT` | Update user |
| DELETE | `/api/users/{id}` | `USER_DELETE` | Soft delete user |
| GET | `/api/roles` | `ROLE_VIEW` | List roles |
| GET | `/api/roles/{id}` | `ROLE_VIEW` | Role + permissions |
| POST | `/api/roles` | `ROLE_CREATE` | Create role |
| POST | `/api/roles/{id}/permissions` | `ROLE_EDIT` | Assign permission |
| DELETE | `/api/roles/{id}/permissions/{permissionId}` | `ROLE_EDIT` | Revoke permission |
| GET | `/api/permissions` | `PERMISSION_VIEW` | List permissions |
| GET | `/api/permissions/{id}` | `PERMISSION_VIEW` | Get permission |
| POST | `/api/permissions` | `PERMISSION_CREATE` | Create permission |
| PUT | `/api/permissions/{id}` | `PERMISSION_EDIT` | Update permission |

---

## 🧩 How Middleware Works

The `Router` passes each middleware class into a **pipeline** (like middleware chains in Express/Laravel). Each middleware receives `$next` as a callable and either calls it to continue or returns early:

```php
// AuthMiddleware.php
public function handle(callable $next): void
{
    // 1. Verify token
    $decoded = JwtHelper::verifyToken($token);
    $_REQUEST['auth_user'] = $decoded;
    // 2. Pass to next middleware or controller
    $next();
}
```

In routes, you stack them like this:
```php
$router->get('/api/users', [UserController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('USER_VIEW'),  // generated dynamic class
]);
```

---

## 🔑 JWT Payload Structure

```json
{
  "iss": "learnpro-api",
  "iat": 1712345678,
  "exp": 1712349278,
  "user_id": 1,
  "email": "user@example.com",
  "role_id": 2,
  "service_number": "SN-001",
  "role_permissions": ["PROFILE_MANAGEMENT", "USER_VIEW", "USER_EDIT"]
}
```

---

## 🚀 Adding a New Protected Route

1. Create a controller in `app/Controllers/`
2. Add the route in `routes/api.php`:
```php
$router->get('/api/reports', [ReportController::class, 'index'], [
    AuthMiddleware::class,
    permissionMiddleware('REPORT_VIEW'),
]);
```
3. Add the permission to the DB:
```sql
INSERT INTO user_permissions (name, display_name) VALUES ('REPORT_VIEW', 'View Reports');
INSERT INTO role_permissions (role_id, permission_id) VALUES (1, LAST_INSERT_ID());
```
#   p b _ l e a r n p r o _ a p i  
 