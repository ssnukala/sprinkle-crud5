# CRUD5 Sprinkle Architecture

This document provides an in-depth look at the CRUD5 sprinkle architecture, design decisions, and implementation details.

## Overview

The CRUD5 sprinkle follows a **schema-driven, convention-over-configuration** approach to provide generic CRUD functionality for any database table in a UserFrosting 5.x application.

## Core Principles

### 1. Single Responsibility
Each component has a single, well-defined purpose:
- **Controllers**: Handle HTTP requests and responses
- **Models**: Represent data and business logic
- **Sprunje**: Handle data table queries
- **Middleware**: Process requests and inject dependencies
- **Routes**: Define URL patterns and handlers

### 2. Convention Over Configuration
- Table schemas follow naming conventions
- Routes follow predictable patterns
- Configuration files use consistent structure

### 3. Don't Repeat Yourself (DRY)
- Generic base controllers eliminate code duplication
- YAML configuration replaces repetitive code
- Shared templates for all CRUD operations

### 4. Open/Closed Principle
- Base classes are open for extension
- Core functionality is closed for modification
- Easy to customize without changing base code

## Architecture Layers

```
┌─────────────────────────────────────────────────────┐
│                  Presentation Layer                 │
│  ┌───────────┐  ┌──────────┐  ┌─────────────────┐ │
│  │  Twig     │  │  Assets  │  │   JavaScript    │ │
│  │ Templates │  │  CSS/JS  │  │  (FormGen, DT)  │ │
│  └───────────┘  └──────────┘  └─────────────────┘ │
└─────────────────────────────────────────────────────┘
                         ↕
┌─────────────────────────────────────────────────────┐
│                 Application Layer                   │
│  ┌──────────────────────────────────────────────┐  │
│  │           HTTP Request/Response              │  │
│  │  ┌─────────┐  ┌────────────┐  ┌──────────┐ │  │
│  │  │ Routing │→ │ Middleware │→ │Controller│ │  │
│  │  └─────────┘  └────────────┘  └──────────┘ │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
                         ↕
┌─────────────────────────────────────────────────────┐
│                   Business Layer                    │
│  ┌────────────┐  ┌──────────┐  ┌────────────────┐ │
│  │ Validation │  │ Services │  │  Event System  │ │
│  │ (Fortress) │  │   (DI)   │  │  (Listeners)   │ │
│  └────────────┘  └──────────┘  └────────────────┘ │
└─────────────────────────────────────────────────────┘
                         ↕
┌─────────────────────────────────────────────────────┐
│                     Data Layer                      │
│  ┌────────────┐  ┌──────────┐  ┌────────────────┐ │
│  │  Eloquent  │→ │ Database │  │     Sprunje    │ │
│  │   Models   │  │   PDO    │  │  (Queries)     │ │
│  └────────────┘  └──────────┘  └────────────────┘ │
└─────────────────────────────────────────────────────┘
```

## Request Flow

### 1. Page View Request

```
User Browser
    ↓ GET /crud5/users
Router (Slim Framework)
    ↓
Middleware Chain:
    ↓ AuthGuard (check authentication)
    ↓ NoCache (prevent caching)
    ↓
BasePageListAction::__invoke()
    ↓
    ├→ Load YAML config (app/schema/crud5/users.yaml)
    ├→ Validate user permission (c5_user)
    └→ Render Twig template
         ↓
    templates/pages/crudlist.html.twig
         ↓
Response (HTML)
    ↓
User Browser
    ↓ Page renders
    ↓ JavaScript initializes
    ↓ AJAX request for data
    ↓ GET /api/crud5/users
    ↓
[Continue to Sprunje flow]
```

### 2. Sprunje Data Request

```
JavaScript DataTable
    ↓ GET /api/crud5/users?size=25&page=0&sorts[name]=asc
Router
    ↓
Middleware Chain (AuthGuard, NoCache)
    ↓
BasePageListAction::sprunje()
    ↓
    ├→ Load YAML config
    ├→ Validate permission
    ├→ Extract sortable/filterable fields
    └→ Setup CRUD5Sprunje
         ↓
CRUD5Sprunje
    ├→ Set table name
    ├→ Build query (Eloquent)
    ├→ Apply filters
    ├→ Apply sorting
    ├→ Apply pagination
    └→ Execute query
         ↓
JSON Response
{
  "rows": [...],
  "count": 100,
  "count_filtered": 25
}
    ↓
JavaScript renders table rows
```

### 3. Create Record Request

```
User fills form & clicks Submit
    ↓ POST /api/crud5/users
    ↓ Body: {user_name: "john", email: "john@example.com", ...}
Router
    ↓
Middleware Chain (AuthGuard, NoCache)
    ↓
BaseCreateAction::__invoke()
    ↓
    ├→ Validate access (c5_user permission)
    ├→ Load request schema (requests/users/create.yaml)
    ├→ Transform data (Fortress)
    ├→ Validate data (Fortress)
    │   ├→ Length checks
    │   ├→ Required fields
    │   ├→ Format validation
    │   └→ Custom rules
    ├→ Set defaults
    │   ├→ created_by = current user
    │   ├→ user_id (if null)
    │   └→ meta = {}
    └→ Database Transaction
         ├→ Create CRUD5Model
         ├→ Set table name
         ├→ Set fillable fields
         ├→ Fill data
         ├→ Save to database
         └→ Log activity
              ↓
Success Alert
    ↓
JSON Response {}
    ↓
JavaScript shows success message
    ↓
Table reloads data
```

### 4. Update Record Request

```
User clicks Edit → Modal opens
    ↓ GET /modals/crud5/users/edit?id=123
Router
    ↓
Middleware Chain:
    ↓ AuthGuard, NoCache
    ↓ CRUD5Injector
         ↓
         ├→ Extract crud_slug (users)
         ├→ Extract id (123)
         ├→ Validate slug
         ├→ Set table name
         ├→ Load record from database
         └→ Inject into request attribute
              ↓
BaseEditModal::__invoke(CRUD5ModelInterface $crudModel)
    ↓
    ├→ Load request schema (requests/users/edit-info.yaml)
    ├→ Generate form (FormGenerator)
    │   ├→ Read schema
    │   ├→ Create field HTML
    │   ├→ Add validation rules
    │   └→ Populate current values
    └→ Render modal template
         ↓
Modal HTML Response
    ↓
User edits fields & clicks Save
    ↓ PUT /api/crud5/users/r/123
    ↓ Body: {user_name: "johnny", ...}
Router
    ↓
Middleware Chain (AuthGuard, NoCache, CRUD5Injector)
    ↓ [Record loaded and injected]
BaseEditAction::__invoke(CRUD5ModelInterface $crudModel)
    ↓
    ├→ Load request schema
    ├→ Transform data
    ├→ Validate data
    ├→ Check field permissions
    └→ Database Transaction
         ├→ Update model attributes
         ├→ Save changes
         └→ Log activity
              ↓
Success Alert
    ↓
JSON Response {}
    ↓
Modal closes, table reloads
```

### 5. Delete Record Request

```
User clicks Delete → Confirmation modal
    ↓ GET /modals/crud5/users/confirm-delete?id=123
Router → Middleware → CRUD5Injector → BaseDeleteModal
    ↓
Modal shows record details
    ↓
User confirms deletion
    ↓ DELETE /api/crud5/users/r/123
Router
    ↓
Middleware Chain (AuthGuard, NoCache, CRUD5Injector)
    ↓ [Record loaded]
BaseDeleteAction::__invoke(CRUD5ModelInterface $crudModel)
    ↓
    ├→ Validate access
    ├→ Check business rules
    │   ├→ Not default record?
    │   └→ No related records?
    └→ Database Transaction
         ├→ Delete record
         └→ Log activity
              ↓
Success Alert
    ↓
JSON Response {}
    ↓
Modal closes, table reloads
```

## Component Details

### Controllers

#### BasePageListAction

**Responsibilities**:
- Load CRUD5 YAML configuration
- Validate user permissions
- Render list view page
- Provide Sprunje endpoint for data

**Key Methods**:
```php
__invoke(Request, Response): Response
    - Main entry point
    - Loads config
    - Validates access
    - Renders template

sprunje(Request, Response): Response
    - API endpoint for data
    - Sets up Sprunje
    - Returns JSON

loadConfig(string $slug): void
    - Loads schema file
    - Sets permission
    - Caches config

getSortable(): array
    - Extracts sortable columns
    - Returns field names

getFilterable(): array
    - Extracts filterable columns
    - Returns field names
```

**Configuration Loading**:
```php
// File: app/schema/crud5/{slug}.yaml
$configFile = "schema://crud5/$slug.yaml";
$loader = new YamlFileLoader($configFile);
$config = $loader->load(false);
```

#### BaseCreateAction

**Responsibilities**:
- Validate create permission
- Load request schema
- Transform and validate data
- Set default values
- Create database record
- Log user activity

**Data Flow**:
```
POST Body
    ↓
getParsedBody()
    ↓
Load Schema
    ↓
Transform (Fortress)
    ├→ Whitelist fields
    ├→ Apply transformers
    └→ Set defaults
        ↓
Validate (Fortress)
    ├→ Required fields
    ├→ Length constraints
    ├→ Format validation
    └→ Custom rules
        ↓
Set fillable fields (dynamic)
    ↓
Fill model
    ↓
Transaction
    ├→ Save
    └→ Log
        ↓
Success
```

**Default Values**:
```php
protected function setDefaults($data)
{
    $currentUser = $this->authenticator->user();
    
    // User ID
    if (isset($data['user_id']) && $data['user_id'] == null) {
        $data['user_id'] = $currentUser->id;
    }
    
    // Created by
    $data['created_by'] = $currentUser->id;
    
    // Meta field
    if (!isset($data['meta'])) {
        $data['meta'] = [];
    }
    
    return $data;
}
```

#### BaseEditAction

**Responsibilities**:
- Validate update permission (field-level)
- Load request schema
- Transform and validate data
- Update database record
- Log user activity

**Permission Check**:
```php
// Field-level permission checking
$fieldNames = array_keys($data);

if (!$this->authenticator->checkAccess('update_group_field', [
    'group'  => $crudModel,
    'fields' => array_values(array_unique($fieldNames)),
])) {
    throw new ForbiddenException();
}
```

### Models

#### CRUD5Model

**Purpose**: Generic Eloquent model that can represent any table

**Dynamic Properties**:
```php
class CRUD5Model extends Model
{
    protected $table = 'CRUD5_NOT_SET';  // ← Set at runtime
    protected $fillable = [];             // ← Set at runtime
    protected $casts = ['meta' => 'array'];
    public $timestamps = true;
}
```

**Usage Pattern**:
```php
// 1. Create instance
$model = new CRUD5Model();

// 2. Set table name
$model->setTable('products');

// 3. Set fillable fields
$model->setFillable(['name', 'price', 'stock']);

// 4. Fill and save
$model->fill($data);
$model->save();
```

**Why Dynamic?**
- Single model class for all tables
- Configured at runtime based on route
- Reduces code duplication
- Simplifies dependency injection

### Sprunje

#### CRUD5Sprunje

**Purpose**: Handle data table queries with sorting, filtering, pagination

**Setup**:
```php
public function setupSprunje(
    string $name,        // Table name
    array $sortable,     // Sortable columns
    array $filterable    // Filterable columns
): void {
    $this->model->setTable($name);
    $this->sortable = $sortable;
    $this->filterable = $filterable;
    $this->query = $this->baseQuery();
}
```

**Query Building**:
```php
protected function baseQuery()
{
    // Returns Eloquent query builder
    return $this->model->newQuery();
}

// Automatically handles:
// - Sorting: ?sorts[name]=asc
// - Filtering: ?filters[name]=john
// - Pagination: ?size=25&page=0
```

**Extensibility**:
```php
// Custom filters
protected function filterPrice($query, $value)
{
    return $query->where('price', '>=', $value['min'])
                 ->where('price', '<=', $value['max']);
}

// Custom sorts
protected function sortPopularity($query, $direction)
{
    return $query->orderBy('view_count', $direction);
}
```

### Middleware

#### CRUD5Injector

**Purpose**: Inject model instance into controller based on route

**Process Flow**:
```
Request
    ↓
Extract Parameters
    ├→ crud_slug (from route)
    └→ id (from route or query)
        ↓
Validate Slug
    ├→ Alphanumeric + underscore only
    └→ No SQL injection risk
        ↓
Set Table Name
    ↓
Check Mode
    ├→ inject_only = true?
    │   └→ Just inject model (for create)
    └→ inject_only = false
        ├→ Load record by ID
        └→ Throw if not found
            ↓
Add to Request Attributes
    request->withAttribute('crudModel', $model)
        ↓
Next Handler
```

**Security**:
```php
protected function validateSlug(string $slug): bool
{
    // Only allow safe characters
    return preg_match('/^[a-zA-Z0-9_]+$/', $slug) === 1;
}
```

**Error Handling**:
```php
try {
    $instance = $this->getInstance($id);
} catch (CRUD5NotFoundException $e) {
    // Log error
    // Return empty model or error response
}
```

### Routing

**Pattern-Based Routes**:
```php
// All routes follow same pattern
/crud5/{crud_slug}            // List
/crud5/{crud_slug}/r/{id}     // Detail
/api/crud5/{crud_slug}        // API
/modals/crud5/{crud_slug}/*   // Modals
```

**Middleware Stack**:
```php
$app->group('/crud5/{crud_slug}', function($group) {
    // ... routes
})
->add(AuthGuard::class)       // Require login
->add(NoCache::class);        // Prevent caching
```

## Design Patterns

### 1. Strategy Pattern

**Controllers as Strategies**:
```php
// All implement same interface
interface CRUDStrategy {
    public function __invoke(Request, Response): Response;
}

// Different implementations
class BaseCreateAction implements CRUDStrategy { }
class BaseEditAction implements CRUDStrategy { }
class BaseDeleteAction implements CRUDStrategy { }
```

### 2. Template Method Pattern

**Base Controller Structure**:
```php
abstract class BaseAction
{
    // Template method
    public function __invoke(Request $request, Response $response): Response
    {
        $this->validateAccess();    // Can override
        $this->handle($request);     // Can override
        return $this->respond();     // Can override
    }
    
    abstract protected function handle(Request $request): void;
}
```

### 3. Dependency Injection

**Constructor Injection**:
```php
class BaseCreateAction
{
    public function __construct(
        protected AlertStream $alert,
        protected Authenticator $authenticator,
        protected Connection $db,
        protected CRUD5ModelInterface $crudModel,
        protected Translator $translator,
        protected RequestDataTransformer $transformer,
        protected ServerSideValidator $validator,
        protected UserActivityLogger $userActivityLogger,
        protected DebugLoggerInterface $debugLogger
    ) {}
}
```

### 4. Middleware (Chain of Responsibility)

**Request Pipeline**:
```php
Request
    ↓
Middleware 1 (AuthGuard)
    ↓ process()
Middleware 2 (NoCache)
    ↓ process()
Middleware 3 (CRUD5Injector)
    ↓ process()
Controller
```

### 5. Factory Pattern

**Model Factory**:
```php
class CRUD5Factory extends Factory
{
    protected $model = CRUD5Model::class;
    
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            // ...
        ];
    }
}
```

### 6. Repository Pattern

**Models as Repositories**:
```php
// Model encapsulates data access
$users = CRUD5Model::on()
    ->setTable('users')
    ->where('active', true)
    ->get();
```

## Configuration System

### YAML Schema Structure

**crud5/{table}.yaml**:
```yaml
model: table_name              # Required
title: "Display Title"         # Required
description: "Description"     # Optional
permission: permission_slug    # Required

table:
  id: html-id                  # Optional
  css-class: classes           # Optional
  schema: schema_name          # Optional
  macrofile: path.twig         # Optional
  
  columns:
    field_name:                # Column definitions
      label: "LABEL"           # Required
      template: type           # Required
      filter: bool             # Optional (default: false)
      sortable: bool           # Optional (default: false)
      searchable: bool         # Optional (default: false)
```

**requests/{table}/create.yaml**:
```yaml
field_name:
  validators:                   # Validation rules
    rule_name:
      param: value
      message: "Error"
      
  form:                         # Form generation
    type: field_type
    label: "Label"
    icon: "icon-class"
    placeholder: "text"
```

### Configuration Loading

**Load Process**:
```php
// 1. Construct path
$configFile = "schema://crud5/{$slug}.yaml";

// 2. Use stream wrapper
$loader = new YamlFileLoader($configFile);

// 3. Parse YAML
$config = $loader->load(false);

// 4. Extract values
$permission = $config['permission'];
$columns = $config['table']['columns'];
```

## Security

### Authentication
- All routes protected by `AuthGuard`
- Requires valid user session
- Automatic redirect to login

### Authorization
- Permission checks at controller level
- Field-level permissions for updates
- Configurable per model

### Input Validation
- Schema-based validation (Fortress)
- Whitelist approach (only defined fields)
- Type safety (PHP 8 types)
- SQL injection prevention (Eloquent)

### XSS Prevention
- Twig auto-escaping
- Explicit escaping in templates
- CSP headers (UserFrosting)

### CSRF Protection
- Token validation on state-changing operations
- Automatic token injection in forms
- AJAX request headers

## Performance

### Optimization Strategies

**1. Database**:
- Lazy loading by default
- Eager loading when needed
- Proper indexes on tables
- Query result caching

**2. Configuration**:
- YAML parsing cached
- Schema objects reused
- Minimal file reads

**3. Assets**:
- Webpack bundling
- Minification in production
- CDN for libraries

**4. Caching**:
- Route caching
- Twig template caching
- Config caching

## Testing

### Test Structure
```
tests/
├── Controller/
│   └── Base/
│       ├── BasePageListActionTest.php
│       ├── BaseCreateActionTest.php
│       └── ...
├── Sprunje/
│   └── CRUD5SprunjeTest.php
├── Listener/
└── CRUD5TestCase.php
```

### Testing Approach
- Unit tests for business logic
- Integration tests for controllers
- Factory-based test data
- Mock external dependencies

## Extensibility

### Extension Points

**1. Controllers**:
```php
class CustomCreateAction extends BaseCreateAction
{
    protected function setDefaults($data)
    {
        // Custom logic
        return parent::setDefaults($data);
    }
}
```

**2. Models**:
```php
class ProductModel extends CRUD5Model
{
    protected $table = 'products';
    
    // Add relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

**3. Sprunje**:
```php
class ProductSprunje extends CRUD5Sprunje
{
    protected function filterCategory($query, $value)
    {
        return $query->where('category_id', $value);
    }
}
```

**4. Templates**:
```twig
{# app/templates/pages/custom_list.html.twig #}
{% extends "pages/crudlist.html.twig" %}

{% block custom_header %}
    <!-- Custom content -->
{% endblock %}
```

**5. Validators**:
```php
class CustomValidator extends AbstractValidator
{
    public function validate($value, $params = []): bool
    {
        // Custom validation
    }
}
```

## Future Enhancements

### Planned Features
- [ ] CLI command to generate schemas from DB
- [ ] Export functionality (CSV, Excel, PDF)
- [ ] Import with validation
- [ ] Advanced filtering UI
- [ ] Bulk operations
- [ ] Relationship management
- [ ] Audit trail
- [ ] Visual schema editor

### Improvements
- [ ] Better caching strategies
- [ ] GraphQL API support
- [ ] WebSocket updates
- [ ] Advanced search
- [ ] Custom field types
- [ ] Plugin system

---

This architecture enables rapid development while maintaining flexibility, security, and maintainability. The schema-driven approach eliminates boilerplate code while the extension points allow customization when needed.
