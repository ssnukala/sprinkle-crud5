# CRUD6 Feature Gap Analysis & Implementation Plan

**Purpose:** Identify features in sprinkle-crud5 that are missing in sprinkle-crud6 and provide an implementation plan.

**Target Repository:** ssnukala/sprinkle-crud6  
**Source Analysis:** ssnukala/sprinkle-crud5  
**Date:** October 2024

---

## Executive Summary

This document identifies specific features implemented in CRUD5 that should be reimplemented in CRUD6 (not migrating the entire repo). It provides a prioritized implementation plan with effort estimates.

---

## 🎯 Feature Gap Analysis

### Features CRUD5 Has That CRUD6 Needs

#### 1. ⭐⭐⭐⭐⭐ Unified Search Bar (Common Search)

**What it is:**
- Single search input that searches across multiple table columns simultaneously
- User types once, searches all searchable fields (name, description, SKU, etc.)
- Better UX than per-column filters

**Current CRUD5 Implementation:**
- Located in: `app/assets/crud5/js/pages/crudlist.js`
- Uses jQuery and ufTable plugin
- Backend: CRUD5Sprunje filters across multiple fields

**Implementation for CRUD6:**

```typescript
// Priority: HIGH
// Effort: Medium (2-3 days)
// Files to create in sprinkle-crud6:

// 1. Backend Sprunje Enhancement
// app/src/Sprunje/CRUD6Sprunje.php
protected function filterUnifiedSearch($query, $value)
{
    $searchFields = $this->getSearchableFields();
    
    return $query->where(function($q) use ($value, $searchFields) {
        foreach ($searchFields as $field) {
            $q->orWhere($field, 'LIKE', "%{$value}%");
        }
    });
}

protected function getSearchableFields(): array
{
    // Read from schema configuration
    return array_keys(array_filter(
        $this->config['table']['columns'],
        fn($col) => $col['searchable'] ?? false
    ));
}

// 2. Frontend Component (if using Vue.js)
// assets/components/UnifiedSearch.vue
<template>
  <div class="unified-search">
    <input 
      v-model="searchQuery"
      @input="debounceSearch"
      placeholder="Search..."
      class="form-control"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useDebounceFn } from '@vueuse/core'

const searchQuery = ref('')
const emit = defineEmits(['search'])

const debounceSearch = useDebounceFn(() => {
  emit('search', searchQuery.value)
}, 300)
</script>
```

**Dependencies:**
- Schema must define which columns are searchable
- Backend sprunje must support unified search filter

**Testing:**
- Test with 1 field, 5 fields, 10 fields
- Test with special characters
- Test performance with large datasets

---

#### 2. ⭐⭐⭐⭐⭐ Inline Field Editing (Pencil Icon)

**What it is:**
- Click pencil icon → field becomes editable inline
- Edit → save immediately (AJAX)
- No modal required for simple field updates

**Current CRUD5 Implementation:**
- Controller: `app/src/Controller/Base/BaseUpdateFieldAction.php`
- Modal-based editing in CRUD5 (opens modal)
- Field update endpoint: `PUT /api/crud5/{slug}/r/{id}/{field}`

**Implementation for CRUD6:**

```typescript
// Priority: HIGH
// Effort: High (4-5 days)

// 1. Backend Controller
// app/src/Controller/CRUD6UpdateFieldAction.php
namespace App\Controller;

class CRUD6UpdateFieldAction
{
    public function __invoke(
        string $model,
        int $id,
        string $field,
        Request $request,
        Response $response
    ): Response {
        // 1. Load model instance
        $instance = $this->loadModel($model, $id);
        
        // 2. Validate field is editable
        $this->validateFieldEditable($model, $field);
        
        // 3. Validate permission
        $this->validateAccess('update_field', $instance, $field);
        
        // 4. Get new value
        $data = $request->getParsedBody();
        $newValue = $data['value'];
        
        // 5. Validate value
        $validated = $this->validateFieldValue($model, $field, $newValue);
        
        // 6. Update
        $this->db->transaction(function() use ($instance, $field, $validated) {
            $oldValue = $instance->$field;
            $instance->$field = $validated;
            $instance->save();
            
            // Log change
            $this->auditLog->logFieldChange(
                $instance, 
                $field, 
                $oldValue, 
                $validated
            );
        });
        
        return $response->withJson([
            'value' => $instance->$field,
            'formatted' => $this->formatValue($instance, $field)
        ]);
    }
}

// 2. Frontend Component (Vue.js)
// assets/components/InlineEdit.vue
<template>
  <div class="inline-edit">
    <span v-if="!editing" @click="startEdit">
      {{ displayValue }}
      <i class="fas fa-pencil-alt edit-icon"></i>
    </span>
    
    <div v-else class="edit-mode">
      <input 
        v-model="editValue"
        @keydown.enter="save"
        @keydown.esc="cancel"
        ref="inputRef"
        :type="fieldType"
      />
      <button @click="save" class="btn-save">
        <i class="fas fa-check"></i>
      </button>
      <button @click="cancel" class="btn-cancel">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'

interface Props {
  modelValue: any
  fieldName: string
  recordId: number
  modelName: string
  fieldType?: string
}

const props = withDefaults(defineProps<Props>(), {
  fieldType: 'text'
})

const emit = defineEmits(['update:modelValue', 'saved'])

const editing = ref(false)
const editValue = ref(props.modelValue)
const inputRef = ref<HTMLInputElement>()

const displayValue = computed(() => props.modelValue)

async function startEdit() {
  editing.value = true
  editValue.value = props.modelValue
  await nextTick()
  inputRef.value?.focus()
  inputRef.value?.select()
}

async function save() {
  try {
    const response = await fetch(
      `/api/v1/${props.modelName}/${props.recordId}/${props.fieldName}`,
      {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ value: editValue.value })
      }
    )
    
    const data = await response.json()
    emit('update:modelValue', data.value)
    emit('saved', data)
    editing.value = false
  } catch (error) {
    console.error('Save failed:', error)
    // Show error message
  }
}

function cancel() {
  editValue.value = props.modelValue
  editing.value = false
}
</script>

<style scoped>
.inline-edit {
  position: relative;
}

.edit-icon {
  opacity: 0;
  transition: opacity 0.2s;
  margin-left: 0.5rem;
  cursor: pointer;
}

.inline-edit:hover .edit-icon {
  opacity: 1;
}

.edit-mode {
  display: flex;
  gap: 0.25rem;
}
</style>

// 3. Route Registration
// app/src/Routes/CRUD6Routes.php
$app->patch('/api/v1/{model}/{id}/{field}', CRUD6UpdateFieldAction::class)
    ->add(AuthGuard::class);
```

**Schema Configuration:**
```yaml
# app/schema/crud6/products.yaml
table:
  columns:
    name:
      label: "Name"
      editable: true          # NEW: Enable inline editing
      edit_type: "text"       # NEW: Input type
      validation:             # NEW: Inline validation
        required: true
        max_length: 255
```

**Dependencies:**
- Schema must define editable fields
- Permission system for field-level updates
- Frontend framework (Vue.js recommended)

**Testing:**
- Test text, number, date inputs
- Test validation errors
- Test permission checks
- Test with concurrent edits

---

#### 3. ⭐⭐⭐⭐⭐ Schema-Driven Configuration (YAML)

**What it is:**
- Define entire CRUD interface in YAML files
- No PHP code needed for basic CRUD
- Eliminates 80%+ of boilerplate

**Current CRUD5 Implementation:**
- Schema location: `app/schema/crud5/{model}.yaml`
- Loaded by: `BasePageListAction::loadConfig()`
- Defines: table columns, permissions, sortable fields, searchable fields

**Example CRUD5 Schema:**
```yaml
model: products
title: Product Management
permission: c5_user

table:
  id: table-products
  css-class: crud5-table
  columns:
    name:
      label: "PRODUCT.NAME"
      template: info
      sortable: true
      searchable: true
      filter: true
```

**Implementation for CRUD6:**

```yaml
# Priority: CRITICAL (Foundation)
# Effort: Medium (3-4 days)

# Enhanced Schema Format for CRUD6
# app/schema/crud6/products.yaml

version: "6.0"
extends: "base-crud"  # NEW: Schema inheritance

model: products
title: Product Management
description: Manage product inventory
permission: crud6_user
icon: "box"  # NEW: Icon for UI

# API Configuration (NEW)
api:
  endpoint: "/api/v1/products"
  rate_limit: 100  # requests per minute
  cache_ttl: 300   # seconds

# Table Configuration
table:
  pagination:
    default_size: 25
    sizes: [10, 25, 50, 100]
  
  # Unified Search (NEW)
  search:
    enabled: true
    placeholder: "Search products..."
    fields: [name, description, sku]
  
  # Column Definitions
  columns:
    name:
      label: "PRODUCT.NAME"
      type: "string"
      sortable: true
      searchable: true
      editable: true      # NEW: Inline editing
      edit_type: "text"
      validation:
        required: true
        max_length: 255
      
    price:
      label: "PRODUCT.PRICE"
      type: "currency"
      sortable: true
      editable: true
      edit_type: "number"
      format:             # NEW: Value formatting
        type: "currency"
        decimals: 2
        
    stock:
      label: "PRODUCT.STOCK"
      type: "integer"
      sortable: true
      editable: true
      badge:              # NEW: Conditional styling
        - condition: "value > 50"
          class: "success"
          icon: "check-circle"
        - condition: "value <= 10"
          class: "danger"
          icon: "exclamation-triangle"
    
    actions:
      label: "ACTIONS"
      type: "actions"
      buttons: [edit, delete, clone]

# Form Configuration
forms:
  create:
    schema: "requests/products/create.yaml"
    layout: "two-column"  # NEW: Layout control
    sections:             # NEW: Form sections
      - title: "Basic Info"
        fields: [name, description]
      - title: "Pricing"
        fields: [price, cost]
        
  edit:
    schema: "requests/products/edit.yaml"
    layout: "two-column"

# Relationships (NEW)
relationships:
  category:
    type: "belongsTo"
    model: "Category"
    display: "name"

# Bulk Actions (NEW)
bulk_actions:
  - action: "delete"
    label: "Delete Selected"
    permission: "delete_products"
    confirm: true
  - action: "export"
    label: "Export Selected"
    formats: [csv, xlsx]
```

**PHP Implementation:**
```php
// app/src/Config/CRUD6SchemaLoader.php
namespace App\Config;

class CRUD6SchemaLoader
{
    public function load(string $model): array
    {
        $schemaFile = "schema://crud6/{$model}.yaml";
        
        if (!file_exists($schemaFile)) {
            throw new SchemaNotFoundException($model);
        }
        
        $loader = new YamlFileLoader($schemaFile);
        $config = $loader->load();
        
        // Handle schema inheritance
        if (isset($config['extends'])) {
            $baseConfig = $this->load($config['extends']);
            $config = array_merge_recursive($baseConfig, $config);
        }
        
        // Validate schema
        $this->validate($config);
        
        // Cache parsed schema
        $this->cache->set("crud6_schema_{$model}", $config, 3600);
        
        return $config;
    }
    
    protected function validate(array $config): void
    {
        // Validate against JSON Schema
        $validator = new JsonSchemaValidator();
        $validator->validate($config, $this->getSchemaDefinition());
        
        if (!$validator->isValid()) {
            throw new InvalidSchemaException($validator->getErrors());
        }
    }
}
```

**Dependencies:**
- YAML parser (symfony/yaml)
- JSON Schema validator for validation
- Caching layer

**Testing:**
- Test schema loading
- Test inheritance
- Test validation errors
- Test caching

---

#### 4. ⭐⭐⭐⭐ Generic Base Controllers

**What it is:**
- Reusable controller classes that work for any model
- No need to write CRUD code for each table
- Configured via schemas

**Current CRUD5 Implementation:**
- `BasePageListAction` - List records
- `BaseCreateAction` - Create records
- `BaseEditAction` - Update records
- `BaseDeleteAction` - Delete records
- `BaseUpdateFieldAction` - Update single field

**Implementation for CRUD6:**

```php
// Priority: HIGH (Foundation)
// Effort: High (5-6 days)

// app/src/Controller/Base/BaseCRUD6Controller.php
namespace App\Controller\Base;

abstract class BaseCRUD6Controller
{
    protected string $modelName;
    
    public function __construct(
        protected SchemaLoader $schemaLoader,
        protected Authenticator $auth,
        protected Connection $db,
        protected ValidatorInterface $validator,
        protected CacheInterface $cache,
    ) {}
    
    /**
     * GET /api/v1/{model}
     * List records with pagination, search, filters
     */
    public function index(
        string $model,
        Request $request,
        Response $response
    ): Response {
        $config = $this->schemaLoader->load($model);
        $this->validateAccess('read', $config);
        
        $sprunje = $this->createSprunje($model, $config);
        $result = $sprunje->getResults();
        
        return $response->withJson($result);
    }
    
    /**
     * GET /api/v1/{model}/{id}
     * Get single record
     */
    public function show(
        string $model,
        int $id,
        Request $request,
        Response $response
    ): Response {
        $config = $this->schemaLoader->load($model);
        $instance = $this->findOrFail($model, $id);
        $this->validateAccess('read', $config, $instance);
        
        return $response->withJson([
            'data' => $instance->toArray(),
            'meta' => $this->getMetadata($instance)
        ]);
    }
    
    /**
     * POST /api/v1/{model}
     * Create new record
     */
    public function store(
        string $model,
        Request $request,
        Response $response
    ): Response {
        $config = $this->schemaLoader->load($model);
        $this->validateAccess('create', $config);
        
        $data = $request->getParsedBody();
        $schema = $this->getRequestSchema($model, 'create');
        $validated = $this->validator->validate($schema, $data);
        
        $instance = $this->db->transaction(function() use ($model, $validated) {
            $instance = $this->createModelInstance($model);
            $instance->fill($validated);
            $instance->save();
            
            $this->auditLog->logCreate($instance);
            $this->triggerHooks('after_create', $instance);
            
            return $instance;
        });
        
        return $response->withJson([
            'data' => $instance->toArray()
        ], 201);
    }
    
    /**
     * PUT /api/v1/{model}/{id}
     * Update entire record
     */
    public function update(
        string $model,
        int $id,
        Request $request,
        Response $response
    ): Response {
        $config = $this->schemaLoader->load($model);
        $instance = $this->findOrFail($model, $id);
        $this->validateAccess('update', $config, $instance);
        
        $data = $request->getParsedBody();
        $schema = $this->getRequestSchema($model, 'edit');
        $validated = $this->validator->validate($schema, $data);
        
        $this->db->transaction(function() use ($instance, $validated) {
            $changes = $this->getChanges($instance, $validated);
            $instance->update($validated);
            
            $this->auditLog->logUpdate($instance, $changes);
            $this->triggerHooks('after_update', $instance);
        });
        
        return $response->withJson([
            'data' => $instance->fresh()->toArray()
        ]);
    }
    
    /**
     * DELETE /api/v1/{model}/{id}
     * Delete record
     */
    public function destroy(
        string $model,
        int $id,
        Request $request,
        Response $response
    ): Response {
        $config = $this->schemaLoader->load($model);
        $instance = $this->findOrFail($model, $id);
        $this->validateAccess('delete', $config, $instance);
        
        $this->db->transaction(function() use ($instance) {
            $this->triggerHooks('before_delete', $instance);
            $this->auditLog->logDelete($instance);
            $instance->delete();
        });
        
        return $response->withStatus(204);
    }
    
    /**
     * PATCH /api/v1/{model}/{id}/{field}
     * Update single field (for inline editing)
     */
    public function updateField(
        string $model,
        int $id,
        string $field,
        Request $request,
        Response $response
    ): Response {
        // See inline editing section above
    }
    
    /**
     * POST /api/v1/{model}/bulk
     * Bulk operations
     */
    public function bulk(
        string $model,
        Request $request,
        Response $response
    ): Response {
        $config = $this->schemaLoader->load($model);
        $data = $request->getParsedBody();
        $action = $data['action'];
        $ids = $data['ids'];
        
        $this->validateAccess("bulk_{$action}", $config);
        
        $result = match($action) {
            'delete' => $this->bulkDelete($model, $ids),
            'update' => $this->bulkUpdate($model, $ids, $data['updates']),
            'export' => $this->bulkExport($model, $ids, $data['format']),
            default => throw new InvalidArgumentException()
        };
        
        return $response->withJson($result);
    }
    
    // Helper methods
    protected function createModelInstance(string $model): Model
    {
        $class = $this->getModelClass($model);
        return new $class();
    }
    
    protected function findOrFail(string $model, int $id): Model
    {
        $instance = $this->createModelInstance($model);
        return $instance->findOrFail($id);
    }
    
    protected function createSprunje(string $model, array $config): Sprunje
    {
        $sprunje = new CRUD6Sprunje($model, $config);
        $sprunje->setSearchableFields($this->getSearchableFields($config));
        $sprunje->setSortableFields($this->getSortableFields($config));
        return $sprunje;
    }
}
```

**Route Registration:**
```php
// app/src/Routes/CRUD6Routes.php
$app->group('/api/v1/{model}', function (RouteCollectorProxy $group) {
    $group->get('', [BaseCRUD6Controller::class, 'index']);
    $group->post('', [BaseCRUD6Controller::class, 'store']);
    $group->get('/{id:[0-9]+}', [BaseCRUD6Controller::class, 'show']);
    $group->put('/{id:[0-9]+}', [BaseCRUD6Controller::class, 'update']);
    $group->delete('/{id:[0-9]+}', [BaseCRUD6Controller::class, 'destroy']);
    $group->patch('/{id:[0-9]+}/{field}', [BaseCRUD6Controller::class, 'updateField']);
    $group->post('/bulk', [BaseCRUD6Controller::class, 'bulk']);
})->add(AuthGuard::class);
```

---

## 📋 Implementation Priority & Roadmap

### Phase 1: Foundation (Week 1-2)
**Goal:** Core infrastructure

1. **Schema System** (3-4 days)
   - [ ] CRUD6SchemaLoader class
   - [ ] YAML parser integration
   - [ ] Schema validation (JSON Schema)
   - [ ] Caching layer
   - [ ] Schema inheritance support

2. **Base Controllers** (4-5 days)
   - [ ] BaseCRUD6Controller
   - [ ] Route registration
   - [ ] Permission integration
   - [ ] Error handling

**Deliverable:** Basic CRUD operations work via API

---

### Phase 2: Search & Display (Week 3-4)
**Goal:** Table functionality

3. **Unified Search** (2-3 days)
   - [ ] Backend: CRUD6Sprunje::filterUnifiedSearch()
   - [ ] Schema: search configuration
   - [ ] Frontend: UnifiedSearch component
   - [ ] Testing with multiple fields

4. **Table Enhancements** (3-4 days)
   - [ ] Column configuration from schema
   - [ ] Sorting support
   - [ ] Pagination
   - [ ] Data formatting

**Deliverable:** Full-featured data tables with search

---

### Phase 3: Inline Editing (Week 5-6)
**Goal:** Interactive editing

5. **Inline Field Editing** (4-5 days)
   - [ ] Backend: updateField endpoint
   - [ ] Frontend: InlineEdit component
   - [ ] Field validation
   - [ ] Permission checks
   - [ ] Audit logging

6. **Form Enhancements** (2-3 days)
   - [ ] Form sections
   - [ ] Field dependencies
   - [ ] Conditional fields

**Deliverable:** Inline editing with pencil icon works

---

### Phase 4: Advanced Features (Week 7-8)
**Goal:** Power user features

7. **Bulk Operations** (3-4 days)
   - [ ] Bulk delete
   - [ ] Bulk update
   - [ ] Bulk export
   - [ ] Progress tracking

8. **Export/Import** (3-4 days)
   - [ ] CSV export
   - [ ] Excel export
   - [ ] Import with validation
   - [ ] Error reporting

**Deliverable:** Bulk operations and data exchange

---

## 🔧 Technical Requirements

### Backend (PHP)
- PHP 8.2+
- UserFrosting 6.x
- symfony/yaml for YAML parsing
- opis/json-schema for schema validation
- Eloquent ORM

### Frontend (if using Vue.js)
- Vue.js 3
- TypeScript
- Vite
- @vueuse/core (for utilities)

### Database
- MySQL 8.0+ or PostgreSQL 13+
- Support for JSON columns (for meta fields)

---

## 📊 Effort Estimates

| Feature | Priority | Complexity | Effort | Developer Days |
|---------|----------|------------|--------|----------------|
| Schema System | Critical | Medium | 3-4 days | 4 |
| Base Controllers | Critical | High | 4-5 days | 5 |
| Unified Search | High | Medium | 2-3 days | 3 |
| Table Display | High | Medium | 3-4 days | 4 |
| Inline Editing | High | High | 4-5 days | 5 |
| Form Enhancements | Medium | Medium | 2-3 days | 3 |
| Bulk Operations | Medium | Medium | 3-4 days | 4 |
| Export/Import | Medium | Medium | 3-4 days | 4 |

**Total: 32 developer days (~6-7 weeks for 1 developer)**

---

## 🎯 Quick Start Checklist

For implementing in sprinkle-crud6:

### Week 1
- [ ] Set up schema directory structure
- [ ] Implement CRUD6SchemaLoader
- [ ] Add schema validation
- [ ] Create first test schema (products.yaml)

### Week 2
- [ ] Implement BaseCRUD6Controller
- [ ] Register routes
- [ ] Test basic CRUD operations via API
- [ ] Add permission checks

### Week 3-4
- [ ] Implement unified search (backend)
- [ ] Create UnifiedSearch component
- [ ] Test search functionality
- [ ] Add frontend data table

### Week 5-6
- [ ] Implement inline field editing
- [ ] Create InlineEdit component
- [ ] Add field validation
- [ ] Test editing workflow

### Week 7-8
- [ ] Implement bulk operations
- [ ] Add export functionality
- [ ] Polish UI/UX
- [ ] Documentation

---

## 📝 Configuration Examples

### Minimal Schema
```yaml
# Bare minimum to get started
model: products
title: Products
permission: crud6_user

table:
  search:
    fields: [name]
  columns:
    name:
      label: "Name"
      sortable: true
      searchable: true
```

### Full-Featured Schema
```yaml
# All features enabled
version: "6.0"
extends: "base-crud"

model: products
title: Product Management
permission: crud6_admin
icon: "box"

api:
  rate_limit: 100
  cache_ttl: 300

table:
  search:
    enabled: true
    fields: [name, description, sku]
  
  columns:
    name:
      label: "Name"
      sortable: true
      searchable: true
      editable: true
      edit_type: "text"
      validation:
        required: true
        max_length: 255
    
    price:
      label: "Price"
      type: "currency"
      sortable: true
      editable: true
      format:
        decimals: 2

forms:
  create:
    schema: "requests/products/create.yaml"
    sections:
      - title: "Basic"
        fields: [name, description]

bulk_actions:
  - action: "delete"
    confirm: true
  - action: "export"
    formats: [csv, xlsx]
```

---

## 🚀 Success Metrics

Track these to measure implementation success:

1. **Feature Parity:** 100% of CRUD5 features in CRUD6
2. **Performance:** API response < 200ms
3. **Code Reduction:** 80% less boilerplate vs manual CRUD
4. **Test Coverage:** > 80% for core features
5. **Documentation:** Complete API docs + examples

---

## 📞 Support & Resources

- **CRUD5 Reference:** This repository (sprinkle-crud5)
- **Target Repo:** ssnukala/sprinkle-crud6
- **UF6 Docs:** https://learn.userfrosting.com/
- **Vue.js Docs:** https://vuejs.org/ (if using)

---

## Summary

**Top 3 Features to Implement First:**

1. **Schema System** - Foundation for everything else
2. **Unified Search** - High-value, relatively easy
3. **Inline Editing** - Differentiating feature, high user value

These three features represent the core value proposition of CRUD5 and should be prioritized for CRUD6.

**Estimated Timeline:** 6-7 weeks for one developer to implement all core features.

**Next Step:** Start with schema system implementation in sprinkle-crud6.
