# CRUD5 Sprinkle - Complete Documentation

**Version**: 5.0.0  
**Author**: Srinivas Nukala  
**License**: MIT  
**Compatible with**: UserFrosting 5.x

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Features Overview](#2-features-overview)
3. [Installation & Setup](#3-installation--setup)
4. [Architecture & Design](#4-architecture--design)
5. [Configuration Guide](#5-configuration-guide)
6. [Usage Examples](#6-usage-examples)
7. [API Reference](#7-api-reference)
8. [Advanced Topics](#8-advanced-topics)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Introduction

### 1.1 What is CRUD5?

The CRUD5 Sprinkle is a powerful, schema-driven CRUD (Create, Read, Update, Delete) framework for UserFrosting 5.x applications. It eliminates the need to write repetitive controller and view code by providing a generic, configurable system that works with any database table.

### 1.2 Key Benefits

- **⚡ Rapid Development**: Create full CRUD interfaces in minutes with YAML configuration
- **🎨 Consistent UI/UX**: Uniform interface across all CRUD operations
- **🔒 Built-in Security**: Integrated permission system and validation
- **📊 Data Tables**: Sortable, filterable, paginated data tables out of the box
- **📝 Auto Forms**: Automatic form generation with validation
- **🔧 Extensible**: Easy to customize and extend for specific needs
- **✅ Type-Safe**: Leverages PHP 8+ features for reliability

### 1.3 Requirements

| Component | Version |
|-----------|---------|
| PHP | ≥ 8.0 |
| UserFrosting | ≥ 5.0 |
| FormGenerator | ~5.1.0 |
| AdminLTE Theme | ≥ 5.0 |

---

## 2. Features Overview

### 2.1 Core Features

#### Dynamic CRUD Operations
Complete CRUD operations for any database table through configuration:
- **Create**: Add new records with validation and defaults
- **Read**: View records in lists and detail pages
- **Update**: Edit records with field-level updates
- **Delete**: Remove records with confirmation dialogs

#### Schema-Driven Configuration
Define your entire CRUD interface using YAML files:
```yaml
model: users
title: User Management
permission: c5_user
table:
  columns:
    user_name:
      label: "USERNAME"
      sortable: true
      searchable: true
```

#### Generic Controllers
Base controllers handle all operations:
- `BasePageListAction` - List views
- `BaseCreateAction` - Record creation
- `BaseEditAction` - Record updates
- `BaseDeleteAction` - Record deletion
- `BaseEditModal` - Form modals
- `BaseUpdateFieldAction` - Field updates

#### Dynamic Routing
Automatic route generation using pattern `/crud5/{crud_slug}`:
```
GET  /crud5/users               - List view
GET  /crud5/users/r/1           - Detail view
POST /api/crud5/users           - Create
PUT  /api/crud5/users/r/1       - Update
DELETE /api/crud5/users/r/1     - Delete
```

### 2.2 Advanced Features

#### FormGenerator Integration
- Automatic form generation from schemas
- Client-side validation with jQuery
- Server-side validation with Fortress
- Support for all form field types

#### Sprunje Data Tables
- Sorting by clicking column headers
- Multi-field text search
- Pagination with configurable page sizes
- AJAX data loading

#### Permission System
- `c5_user` - Basic CRUD operations
- `c5_admin` - Administrative operations
- Custom permissions per model
- Field-level permission checks

#### Middleware Injection
- Automatic model instantiation
- Dynamic table name assignment
- Record loading by ID
- Error handling

#### Multi-Language Support
- Translation keys in locale files
- Configurable labels and messages
- Support for multiple locales

---

## 3. Installation & Setup

### 3.1 Installation via Composer

```bash
composer require ssnukala/sprinkle-crud5
```

### 3.2 Register the Sprinkle

Edit your application's sprinkle class (e.g., `app/src/MyApp.php`):

```php
<?php

namespace MyApp;

use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Admin\Admin;
use UserFrosting\Sprinkle\CRUD5\CRUD5;
use UserFrosting\Theme\AdminLTE\AdminLTE;
use UserFrosting\Sprinkle\SprinkleRecipe;

class MyApp implements SprinkleRecipe
{
    public function getSprinkles(): array
    {
        return [
            Core::class,
            Account::class,
            Admin::class,
            AdminLTE::class,
            CRUD5::class,  // ← Add this
        ];
    }
    
    // ... other methods
}
```

### 3.3 Run Migrations

```bash
php bakery migrate
```

This creates two permissions in your database:
- `c5_user` - User CRUD Activities
- `c5_admin` - Admin CRUD Activities

### 3.4 Build Assets

```bash
npm install
npm run build
```

### 3.5 Assign Permissions

Assign permissions to roles via admin interface or database:

```sql
-- Give users c5_user permission
INSERT INTO permission_roles (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p, roles r
WHERE p.slug = 'c5_user' AND r.slug = 'user';

-- Give admins both permissions  
INSERT INTO permission_roles (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p, roles r
WHERE p.slug IN ('c5_user', 'c5_admin') AND r.slug = 'site-admin';
```

### 3.6 Verification

Visit `/crud5/groups` (if you have the groups table). You should see the CRUD interface.

---

## 4. Architecture & Design

### 4.1 Directory Structure

```
app/
├── assets/                    # Frontend assets
│   ├── crud5/
│   │   ├── css/              # Stylesheets
│   │   └── js/               # JavaScript modules
│   ├── main.js
│   ├── page.crud5.js
│   └── page.crudlist.js
├── locale/                    # Translations
│   └── en_US/
│       └── messages.php
├── schema/                    # Configuration files
│   ├── crud5/                # CRUD configurations
│   │   ├── users.yaml
│   │   └── groups.yaml
│   └── requests/             # Validation schemas
│       ├── users/
│       └── groups/
├── src/                       # PHP source code
│   ├── CRUD5.php             # Main sprinkle class
│   ├── Bakery/               # CLI commands
│   ├── Controller/
│   │   ├── Base/             # Generic CRUD controllers
│   │   ├── Dashboard/        # Dashboard controllers
│   │   └── Utility/          # Utility controllers
│   ├── Database/
│   │   ├── Factories/        # Model factories
│   │   ├── Migrations/       # Database migrations
│   │   ├── Models/           # Eloquent models
│   │   └── Seeds/            # Database seeders
│   ├── Exceptions/           # Custom exceptions
│   ├── Listener/             # Event listeners
│   ├── Middlewares/          # Route middlewares
│   ├── Routes/               # Route definitions
│   ├── ServicesProvider/     # DI services
│   └── Sprunje/              # Data table handlers
├── templates/                 # Twig templates
│   ├── pages/
│   ├── tables/
│   └── modals/
└── tests/                     # PHPUnit tests
```

### 4.2 Component Interactions

```
Browser Request
      ↓
   Router (Slim)
      ↓
 Middlewares (AuthGuard, NoCache, CRUD5Injector)
      ↓
 Controller (Base*Action)
      ↓
   Model (CRUD5Model) ← → Database
      ↓
   View (Twig Templates)
      ↓
 Response (HTML/JSON)
```

### 4.3 Data Flow Examples

#### List View Flow

```
1. GET /crud5/users
2. BasePageListAction::__invoke()
3. Load app/schema/crud5/users.yaml
4. Check permission (c5_user)
5. Render templates/pages/crudlist.html.twig
6. JavaScript loads data via AJAX
7. GET /api/crud5/users (Sprunje)
8. Return JSON data
9. DataTable renders rows
```

#### Create Flow

```
1. Click "Create" button
2. GET /modals/crud5/users/create
3. BaseEditModal renders form
4. Load schema/requests/users/create.yaml
5. Generate form fields with FormGenerator
6. User fills form
7. POST /api/crud5/users
8. BaseCreateAction validates & saves
9. Success alert displayed
10. Table refreshed
```

### 4.4 Design Patterns Used

| Pattern | Usage |
|---------|-------|
| **Strategy** | Base controllers as strategies for CRUD operations |
| **Template Method** | Controllers define operation skeleton |
| **Dependency Injection** | All dependencies injected via constructor |
| **Repository** | Models abstract data access |
| **Middleware** | PSR-15 middleware for request processing |
| **Factory** | Model factories for testing |
| **Service Locator** | DI container for service resolution |

---

## 5. Configuration Guide

### 5.1 CRUD5 Schema Files

Located in `app/schema/crud5/`, these files define your CRUD interface.

#### File Naming
`{table_name}.yaml` - Must match database table name

#### Complete Schema Example

```yaml
---
# Basic Configuration
model: users                    # Database table name
title: User Management          # Page title
description: Manage users       # Page description  
permission: c5_user             # Required permission

# Table Configuration
table:
  id: table-user               # HTML element ID
  css-class: crud5-table       # CSS classes
  schema: user                 # Related schema
  macrofile: macros/user.twig  # Optional: Custom macros
  
  # Column Definitions
  columns:
    user_name:
      label: "USERNAME"         # Column header (translation key or literal)
      template: info            # Display template type
      filter: true              # Enable text filtering
      sortable: true            # Enable sorting
      searchable: true          # Include in search
      
    email:
      label: "EMAIL"
      template: info
      filter: true
      sortable: true
      searchable: true
      
    group_id:
      label: "GROUP"
      template: info
      filter: true
      sortable: true
      searchable: false
      
    created_at:
      label: "CREATED"
      template: info
      filter: false
      sortable: true
      searchable: false
      
    actions:
      label: "ACTIONS"
      template: actions          # Special: renders action buttons
      filter: false
      sortable: false
      searchable: false
```

#### Available Templates

| Template | Description |
|----------|-------------|
| `info` | Plain text display |
| `actions` | Action buttons (edit/delete) |
| `groupid` | Link to group detail |
| `groupdesc` | Group description |
| Custom | Define in macrofile |

### 5.2 Request Schema Files

Located in `app/schema/requests/{model}/`, these define validation and forms.

#### Required Files Per Model

1. **create.yaml** - For creating new records
2. **edit-info.yaml** - For editing existing records
3. **edit-field.yaml** - For single field updates (optional)

#### Schema Structure

```yaml
field_name:
  validators:                   # Validation rules
    rule_name:
      param: value
      message: "Error message"
  form:                         # Form generation
    type: field_type
    label: "Field Label"
    icon: "fas fa-icon"
    placeholder: "Placeholder"
```

#### Complete Example

```yaml
# app/schema/requests/users/create.yaml

user_name:
  validators:
    length:
      min: 1
      max: 50
      message: "USERNAME.LENGTH"
    required:
      message: "USERNAME.REQUIRED"
    username:
      message: "USERNAME.INVALID"
  form:
    type: text
    label: "USERNAME"
    icon: "fas fa-user"
    placeholder: "Enter username"
    autocomplete: "username"

email:
  validators:
    length:
      min: 1
      max: 255
      message: "EMAIL.LENGTH"
    required:
      message: "EMAIL.REQUIRED"
    email:
      message: "EMAIL.INVALID"
  form:
    type: email
    label: "EMAIL"
    icon: "fas fa-envelope"
    placeholder: "Enter email"
    autocomplete: "email"

password:
  validators:
    length:
      min: 8
      max: 255
      message: "PASSWORD.LENGTH"
    required:
      message: "PASSWORD.REQUIRED"
  form:
    type: password
    label: "PASSWORD"
    icon: "fas fa-lock"
    placeholder: "Enter password"

first_name:
  validators:
    length:
      max: 100
      message: "FIRST_NAME.LENGTH"
  form:
    type: text
    label: "FIRST_NAME"
    placeholder: "Enter first name"

last_name:
  validators:
    length:
      max: 100
      message: "LAST_NAME.LENGTH"
  form:
    type: text
    label: "LAST_NAME"
    placeholder: "Enter last name"

group_id:
  validators:
    required:
      message: "GROUP.REQUIRED"
    integer:
      message: "GROUP.INVALID"
  form:
    type: select
    label: "GROUP"
    options: "group"            # Load from group model
```

#### Available Validators

| Validator | Parameters | Description |
|-----------|------------|-------------|
| `required` | - | Field must have value |
| `length` | min, max | String length constraints |
| `integer` | - | Must be integer |
| `numeric` | - | Must be numeric |
| `email` | - | Valid email format |
| `username` | - | Valid username format |
| `regex` | pattern | Match regex pattern |
| `min` | value | Minimum numeric value |
| `max` | value | Maximum numeric value |
| `equals` | value | Must equal value |
| `notEquals` | value | Must not equal value |

#### Available Form Types

| Type | Description | Parameters |
|------|-------------|------------|
| `text` | Text input | placeholder, autocomplete |
| `email` | Email input | placeholder |
| `password` | Password input | placeholder |
| `textarea` | Multi-line text | rows, placeholder |
| `select` | Dropdown | options |
| `checkbox` | Checkbox | - |
| `radio` | Radio button | options |
| `hidden` | Hidden field | - |
| `number` | Number input | min, max, step |
| `date` | Date picker | - |

### 5.3 Permission Configuration

Permissions are created in migrations and assigned to roles.

#### Built-in Permissions

| Permission | Description | Default Roles |
|------------|-------------|---------------|
| `c5_user` | Basic CRUD operations | User |
| `c5_admin` | Admin CRUD operations | Site Admin |

#### Custom Permissions

Create in a migration:

```php
$permission = new Permission([
    'slug' => 'crud_custom_table',
    'name' => 'Manage Custom Table',
    'conditions' => 'always()',
    'description' => 'Full access to custom table'
]);
$permission->save();

// Assign to role
$role = Role::where('slug', 'user')->first();
$role->permissions()->attach($permission->id);
```

Use in CRUD5 schema:

```yaml
model: custom_table
permission: crud_custom_table
```

#### Conditional Permissions

```php
$permission = new Permission([
    'slug' => 'crud_own_records',
    'name' => 'Manage Own Records',
    'conditions' => 'equals_num(self.user_id, user.id)',
    'description' => 'User can only manage their own records'
]);
```

---

## 6. Usage Examples

### 6.1 Quick Start: Create a CRUD Interface

Let's create a complete CRUD interface for a `products` table.

#### Step 1: Ensure Table Exists

```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Step 2: Create CRUD5 Schema

Create `app/schema/crud5/products.yaml`:

```yaml
---
model: products
title: Product Management
description: Manage products inventory
permission: c5_user

table:
  id: table-products
  css-class: crud5-table
  schema: products
  columns:
    name:
      label: "PRODUCT.NAME"
      template: info
      filter: true
      sortable: true
      searchable: true
    description:
      label: "PRODUCT.DESCRIPTION"
      template: info
      filter: true
      sortable: false
      searchable: true
    price:
      label: "PRODUCT.PRICE"
      template: info
      filter: false
      sortable: true
      searchable: false
    stock:
      label: "PRODUCT.STOCK"
      template: info
      filter: false
      sortable: true
      searchable: false
    created_at:
      label: "CREATED_AT"
      template: info
      filter: false
      sortable: true
      searchable: false
    actions:
      label: "ACTIONS"
      template: actions
      filter: false
      sortable: false
      searchable: false
```

#### Step 3: Create Request Schemas

Create `app/schema/requests/products/create.yaml`:

```yaml
name:
  validators:
    length:
      min: 1
      max: 255
      message: "Product name must be 1-255 characters"
    required:
      message: "Product name is required"
  form:
    type: text
    label: "Product Name"
    placeholder: "Enter product name"

description:
  validators:
    length:
      max: 5000
      message: "Description must be 5000 characters or less"
  form:
    type: textarea
    label: "Description"
    placeholder: "Enter product description"
    rows: 4

price:
  validators:
    required:
      message: "Price is required"
    numeric:
      message: "Price must be a number"
    min:
      value: 0
      message: "Price must be positive"
  form:
    type: number
    label: "Price"
    placeholder: "0.00"
    step: "0.01"

stock:
  validators:
    required:
      message: "Stock is required"
    integer:
      message: "Stock must be an integer"
    min:
      value: 0
      message: "Stock cannot be negative"
  form:
    type: number
    label: "Stock"
    placeholder: "0"
```

Create `app/schema/requests/products/edit-info.yaml` (same as create.yaml).

#### Step 4: Add Translations (Optional)

Edit `app/locale/en_US/messages.php`:

```php
return [
    // ... existing translations
    
    'PRODUCT' => [
        'NAME' => 'Product Name',
        'DESCRIPTION' => 'Description',
        'PRICE' => 'Price ($)',
        'STOCK' => 'Stock',
    ],
];
```

#### Step 5: Access Your CRUD Interface

Navigate to: `https://yoursite.com/crud5/products`

**You now have**:
- ✅ Product list with sorting and search
- ✅ Create new products
- ✅ Edit existing products
- ✅ Delete products
- ✅ Permission checks
- ✅ Validation
- ✅ Activity logging

### 6.2 Common Usage Patterns

#### Adding Navigation Link

Edit your navigation template:

```twig
<li class="nav-item">
    <a href="{{ urlFor('crud5-model', {'crud_slug': 'products'}) }}" class="nav-link">
        <i class="fas fa-box"></i>
        <p>Products</p>
    </a>
</li>
```

#### Programmatic CRUD Operations

```php
use UserFrosting\Sprinkle\CRUD5\Database\Models\CRUD5Model;

// Create
$product = new CRUD5Model();
$product->setTable('products');
$product->setFillable(['name', 'description', 'price', 'stock']);
$product->fill([
    'name' => 'New Product',
    'description' => 'Product description',
    'price' => 29.99,
    'stock' => 100
]);
$product->save();

// Read
$product = CRUD5Model::on()->setTable('products')->find(1);
echo $product->name;

// Update
$product->name = 'Updated Name';
$product->save();

// Delete
$product->delete();

// Query
$products = CRUD5Model::on()
    ->setTable('products')
    ->where('price', '>', 20)
    ->orderBy('name')
    ->get();
```

#### AJAX Operations

```javascript
// List data
$.ajax({
    url: '/api/crud5/products',
    type: 'GET',
    data: {
        size: 25,
        page: 0,
        sorts: [{field: 'name', direction: 'asc'}],
        filters: {name: 'search term'}
    },
    success: function(data) {
        console.log(data);
    }
});

// Create
$.ajax({
    url: '/api/crud5/products',
    type: 'POST',
    data: {
        name: 'New Product',
        price: 29.99,
        stock: 50
    },
    success: function() {
        alert('Created!');
    }
});

// Update
$.ajax({
    url: '/api/crud5/products/r/123',
    type: 'PUT',
    data: {
        name: 'Updated Name',
        price: 39.99
    },
    success: function() {
        alert('Updated!');
    }
});

// Delete
$.ajax({
    url: '/api/crud5/products/r/123',
    type: 'DELETE',
    success: function() {
        alert('Deleted!');
    }
});
```

### 6.3 Advanced Examples

#### Custom Display Template

Create custom macro in `app/templates/macros/products.twig`:

```twig
{% macro priceFormatted(value, row) %}
    <span class="badge badge-success">${{ value|number_format(2) }}</span>
{% endmacro %}

{% macro stockBadge(value, row) %}
    {% if value > 50 %}
        <span class="badge badge-success">{{ value }}</span>
    {% elseif value > 10 %}
        <span class="badge badge-warning">{{ value }}</span>
    {% else %}
        <span class="badge badge-danger">{{ value }}</span>
    {% endif %}
{% endmacro %}
```

Update CRUD5 schema:

```yaml
table:
  macrofile: macros/products.twig
  columns:
    price:
      label: "PRICE"
      template: priceFormatted
    stock:
      label: "STOCK"
      template: stockBadge
```

#### With Relationships

```php
class Product extends CRUD5Model
{
    protected $table = 'products';
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
```

In Sprunje:

```php
class ProductSprunje extends CRUD5Sprunje
{
    protected function baseQuery()
    {
        return parent::baseQuery()->with(['category', 'reviews']);
    }
}
```

---

## 7. API Reference

### 7.1 HTTP Endpoints

#### Page Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/crud5/{slug}` | List page | Yes |
| GET | `/crud5/{slug}/r/{id}` | Detail page | Yes |
| GET | `/dashboard/crud5` | Dashboard | Yes |

#### API Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/crud5/{slug}` | List data (Sprunje) | Yes |
| POST | `/api/crud5/{slug}` | Create record | Yes |
| PUT | `/api/crud5/{slug}/r/{id}` | Update record | Yes |
| DELETE | `/api/crud5/{slug}/r/{id}` | Delete record | Yes |
| PUT | `/api/crud5/{slug}/r/{id}/{field}` | Update field | Yes |

#### Modal Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/modals/crud5/{slug}/create` | Create modal | Yes |
| GET | `/modals/crud5/{slug}/edit` | Edit modal | Yes |
| GET | `/modals/crud5/{slug}/confirm-delete` | Delete modal | Yes |

### 7.2 PHP Classes

#### BasePageListAction

```php
namespace UserFrosting\Sprinkle\CRUD5\Controller\Base;

class BasePageListAction
{
    public function __invoke(Request $request, Response $response): Response;
    public function sprunje(Request $request, Response $response): Response;
    protected function loadConfig(string $slug): void;
    protected function validateAccess(): void;
    protected function getSortable(): array;
    protected function getFilterable(): array;
}
```

#### CRUD5Model

```php
namespace UserFrosting\Sprinkle\CRUD5\Database\Models;

class CRUD5Model extends Model implements CRUD5ModelInterface
{
    public function setTable(string $table): void;
    public function setFillable(array $fillable): void;
    public function getFillable(): array;
}
```

#### CRUD5Sprunje

```php
namespace UserFrosting\Sprinkle\CRUD5\Sprunje;

class CRUD5Sprunje extends Sprunje
{
    public function setupSprunje(
        string $name, 
        array $sortable = [], 
        array $filterable = []
    ): void;
    
    protected function baseQuery();
}
```

### 7.3 JavaScript API

#### FormGenerator

```javascript
// Show create modal
uf.FormGenerator.modal({
    sourceUrl: '/modals/crud5/products/create',
    msgTarget: $('#alerts-page')
});

// Show edit modal
uf.FormGenerator.modal({
    sourceUrl: '/modals/crud5/products/edit?id=123',
    msgTarget: $('#alerts-page')
});

// Submit form
uf.FormGenerator.submitForm({
    form: $('#product-form'),
    url: '/api/crud5/products',
    method: 'POST'
});
```

#### DataTable

```javascript
// Initialize table
$('#table-products').ufTable({
    dataUrl: '/api/crud5/products'
});

// Reload table
$('#table-products').ufTable('reload');
```

---

## 8. Advanced Topics

### 8.1 Custom Controllers

Extend base controllers:

```php
namespace MyApp\Controller\Products;

use UserFrosting\Sprinkle\CRUD5\Controller\Base\BaseCreateAction;

class ProductCreateAction extends BaseCreateAction
{
    protected string $schema = 'schema://requests/products/create.yaml';
    
    protected function setDefaults($data)
    {
        $data = parent::setDefaults($data);
        
        // Add custom defaults
        $data['sku'] = $this->generateSKU();
        $data['status'] = 'active';
        
        return $data;
    }
    
    protected function handle(Request $request): void
    {
        // Custom pre-processing
        $this->checkInventory();
        
        // Call parent
        parent::handle($request);
        
        // Custom post-processing
        $this->notifyWarehouse($this->crudModel);
    }
    
    private function generateSKU(): string
    {
        return 'PRD-' . strtoupper(uniqid());
    }
    
    private function checkInventory(): void
    {
        // Custom business logic
    }
    
    private function notifyWarehouse($product): void
    {
        // Send notification
    }
}
```

Register custom route:

```php
$app->post('/api/crud5/products', ProductCreateAction::class)
    ->setName('api.crud5.products.create');
```

### 8.2 Custom Validation

Create custom validator:

```php
namespace MyApp\Fortress\Validator;

use UserFrosting\Fortress\Validator\AbstractValidator;

class SkuValidator extends AbstractValidator
{
    public function validate($value, $params = []): bool
    {
        return preg_match('/^[A-Z]{3}-[0-9]{6}$/', $value) === 1;
    }
}
```

Register validator:

```php
$validator->registerValidator('sku', SkuValidator::class);
```

Use in schema:

```yaml
sku:
  validators:
    sku:
      message: "SKU must be in format ABC-123456"
```

### 8.3 Event Hooks

Listen to CRUD events:

```php
namespace MyApp\Listener;

use UserFrosting\Event\AppEvent;

class ProductCreatedListener
{
    public function __construct(
        private LoggerInterface $logger,
        private NotificationService $notifications
    ) {}
    
    public function __invoke(AppEvent $event): void
    {
        $product = $event->get('product');
        
        // Log creation
        $this->logger->info("Product created", [
            'product_id' => $product->id,
            'name' => $product->name
        ]);
        
        // Send notification
        $this->notifications->send(
            'admin',
            "New product created: {$product->name}"
        );
    }
}
```

### 8.4 Custom Sprunje

```php
namespace MyApp\Sprunje;

use UserFrosting\Sprinkle\CRUD5\Sprunje\CRUD5Sprunje;

class ProductSprunje extends CRUD5Sprunje
{
    protected function baseQuery()
    {
        return parent::baseQuery()
            ->with(['category', 'brand'])
            ->where('status', 'active');
    }
    
    protected function filterPrice($query, $value)
    {
        if (isset($value['min'])) {
            $query->where('price', '>=', $value['min']);
        }
        if (isset($value['max'])) {
            $query->where('price', '<=', $value['max']);
        }
        return $query;
    }
    
    protected function sortPopularity($query, $direction)
    {
        return $query->orderBy('view_count', $direction);
    }
}
```

---

## 9. Troubleshooting

### 9.1 Common Issues

#### Schema File Not Found

**Error**: `FileNotFoundException: schema://crud5/tablename.yaml`

**Solutions**:
1. Check file exists: `ls app/schema/crud5/tablename.yaml`
2. Verify filename matches exactly (case-sensitive)
3. Check YAML syntax: `php bakery debug:yaml app/schema/crud5/tablename.yaml`

#### Permission Denied

**Error**: `ForbiddenException` or blank page

**Solutions**:
1. Check user has required permission
2. Verify permission slug in CRUD5 schema
3. Check role-permission assignments:

```sql
SELECT r.slug as role, p.slug as permission
FROM roles r
JOIN permission_roles pr ON r.id = pr.role_id
JOIN permissions p ON p.id = pr.permission_id
WHERE p.slug LIKE 'c5%';
```

#### Table Not Found

**Error**: `SQLSTATE[42S02]: Base table or view not found`

**Solutions**:
1. Verify table exists in database
2. Check table name in CRUD5 schema matches exactly
3. Run migrations if needed

#### Request Schema Not Found

**Error**: `FileNotFoundException: schema://requests/table/create.yaml`

**Solutions**:
1. Create required schema files:
   - `app/schema/requests/table/create.yaml`
   - `app/schema/requests/table/edit-info.yaml`
2. Check directory structure matches table name

#### Middleware Errors

**Error**: `Invalid CRUD slug` or `Record not found`

**Solutions**:
1. Check slug format (alphanumeric + underscore only)
2. Verify record ID exists
3. Check middleware is registered on route

### 9.2 Debugging

Enable debug mode in `.env`:

```env
DEBUG=true
DEBUG_VERBOSE=true
LOG_LEVEL=debug
```

Check logs:

```bash
tail -f app/logs/userfrosting.log
grep "CRUD5" app/logs/userfrosting.log
```

Debug in controllers:

```php
$this->debugLogger->debug('CRUD5 Debug', [
    'slug' => $slug,
    'data' => $data,
    'model' => $this->crudModel->toArray()
]);
```

### 9.3 Performance Tips

1. **Add Database Indexes**:
```sql
CREATE INDEX idx_name ON products(name);
CREATE INDEX idx_created ON products(created_at);
```

2. **Eager Load Relationships**:
```php
protected function baseQuery()
{
    return parent::baseQuery()->with(['category', 'brand']);
}
```

3. **Cache Configurations**:
```php
$config = Cache::remember(
    "crud5_config_{$slug}",
    3600,
    fn() => $loader->load()
);
```

---

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Add tests for new features
4. Submit a pull request

## License

MIT License - see LICENSE.md

## Support

- GitHub Issues: https://github.com/ssnukala/sprinkle-crud5/issues
- UserFrosting Chat: https://chat.userfrosting.com
- Documentation: This file

---

**End of Documentation**
