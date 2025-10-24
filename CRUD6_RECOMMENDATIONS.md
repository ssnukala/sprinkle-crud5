# CRUD6 Feature Recommendations for UserFrosting 6

**Version**: 1.0  
**Date**: October 2024  
**Author**: Analysis of sprinkle-crud5 for migration to sprinkle-crud6  
**Target Framework**: UserFrosting 6.x

---

## Executive Summary

This document provides comprehensive recommendations for features to carry over from sprinkle-crud5 to sprinkle-crud6 for UserFrosting 6. The analysis focuses on modernizing the PHP Twig-based rendering approach while leveraging modern JavaScript frameworks (potentially Vue.js or React) to replace custom JavaScript that emulates framework features.

### Key Highlights

**Strengths to Preserve:**
- ✅ Schema-driven CRUD configuration (YAML-based)
- ✅ Common/unified search bar for tables (instead of per-column filters)
- ✅ Individual field inline editing with pencil icon
- ✅ Generic base controllers eliminating boilerplate
- ✅ Permission-based access control
- ✅ Automatic form generation from schemas

**Areas for Modernization:**
- 🔄 Replace custom jQuery widgets with Vue.js/React components
- 🔄 Move from Twig-heavy rendering to API-first with modern frontend
- 🔄 Enhance real-time features with WebSocket support
- 🔄 Add TypeScript for type safety in frontend code

---

## Table of Contents

1. [Core Features to Port](#1-core-features-to-port)
2. [UI/UX Features to Enhance](#2-uiux-features-to-enhance)
3. [Architecture Modernization](#3-architecture-modernization)
4. [JavaScript Framework Migration](#4-javascript-framework-migration)
5. [New Features for CRUD6](#5-new-features-for-crud6)
6. [Implementation Priority](#6-implementation-priority)
7. [Migration Strategy](#7-migration-strategy)
8. [Technical Specifications](#8-technical-specifications)

---

## 1. Core Features to Port

### 1.1 Schema-Driven Configuration ⭐⭐⭐⭐⭐

**Current Implementation (CRUD5):**
```yaml
# app/schema/crud5/products.yaml
model: products
title: Product Management
permission: c5_user

table:
  columns:
    name:
      label: "PRODUCT NAME"
      sortable: true
      searchable: true
      filter: true
```

**Recommendation for CRUD6:**
- ✅ **KEEP**: YAML-based schema configuration
- ✅ **ENHANCE**: Add JSON Schema validation for config files
- ✅ **ADD**: Schema versioning and migration support
- ✅ **ADD**: Schema inheritance for common patterns

**Rationale:** Schema-driven approach eliminates 80%+ of boilerplate code and is the killer feature of CRUD5. Must be preserved and enhanced.

**CRUD6 Enhanced Schema:**
```yaml
# app/schema/crud6/products.yaml
version: "6.0"
extends: "base-crud"  # Schema inheritance

model: products
title: Product Management
permission: crud6_user
icon: "box"  # For UI consistency

# API Configuration
api:
  endpoint: "/api/v1/products"
  methods: [GET, POST, PUT, DELETE]
  rate_limit: 100  # requests per minute

# Table Configuration
table:
  pagination:
    default_size: 25
    sizes: [10, 25, 50, 100]
  
  search:
    type: "unified"  # NEW: unified search bar
    placeholder: "Search products..."
    fields: [name, description, sku]  # Fields to search across
  
  columns:
    name:
      label: "PRODUCT.NAME"
      type: "string"
      sortable: true
      searchable: true
      editable: true  # NEW: inline editing
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
      format: "currency"  # NEW: formatting
      
    stock:
      label: "PRODUCT.STOCK"
      type: "integer"
      sortable: true
      editable: true
      edit_type: "number"
      badge:  # NEW: conditional styling
        - condition: "value > 50"
          class: "success"
        - condition: "value > 10 && value <= 50"
          class: "warning"
        - condition: "value <= 10"
          class: "danger"
    
    actions:
      label: "ACTIONS"
      type: "actions"
      buttons: [edit, delete, clone]  # NEW: configurable actions

# Form Configuration
forms:
  create:
    schema: "requests/products/create.yaml"
    layout: "two-column"  # NEW: layout options
    sections:
      - title: "Basic Information"
        fields: [name, description]
      - title: "Pricing"
        fields: [price, cost]
      - title: "Inventory"
        fields: [stock, reorder_level]
  
  edit:
    schema: "requests/products/edit.yaml"
    layout: "two-column"
    tabs:  # NEW: tabbed forms
      - title: "Details"
        fields: [name, description, price]
      - title: "Inventory"
        fields: [stock, warehouse_location]
      - title: "History"
        component: "AuditLog"  # NEW: custom components

# Relationships (NEW)
relationships:
  category:
    type: "belongsTo"
    model: "Category"
    display: "name"
  supplier:
    type: "belongsTo"
    model: "Supplier"

# Bulk Actions (NEW)
bulk_actions:
  - action: "delete"
    label: "Delete Selected"
    confirm: true
  - action: "export"
    label: "Export Selected"
    format: [csv, xlsx, pdf]
  - action: "update_field"
    label: "Bulk Update"
    fields: [status, category_id]

# Hooks (NEW)
hooks:
  before_create: "validateInventory"
  after_create: "notifyWarehouse"
  before_delete: "checkOrders"
```

---

### 1.2 Unified Search Bar ⭐⭐⭐⭐⭐

**Current Implementation:**
Single search box that searches across multiple columns simultaneously.

**Why This is Superior:**
- ✅ Faster user experience (one search box vs multiple)
- ✅ More intuitive for users (Google-like search)
- ✅ Cleaner UI (less cluttered than per-column filters)
- ✅ Better mobile experience

**Recommendation for CRUD6:**

**Backend (PHP):**
```php
// app/src/Sprunje/CRUD6Sprunje.php
namespace UserFrosting\Sprinkle\CRUD6\Sprunje;

class CRUD6Sprunje extends Sprunje
{
    /**
     * Unified search across configured fields
     */
    protected function filterUnifiedSearch($query, $value)
    {
        $searchFields = $this->config['search']['fields'] ?? [];
        
        return $query->where(function($q) use ($value, $searchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$value}%");
            }
        });
    }
    
    /**
     * Optional: Advanced search with operators
     */
    protected function parseAdvancedSearch(string $query): array
    {
        // Support for:
        // name:apple price:>10 stock:<50
        // "exact phrase" -excluded
        
        preg_match_all('/(\w+):([^\s]+)|"([^"]+)"|-(\w+)|(\w+)/', $query, $matches);
        
        return [
            'field_filters' => $matches[1], // name:apple
            'exact_phrases' => $matches[3], // "exact phrase"
            'excluded' => $matches[4],      // -excluded
            'keywords' => $matches[5]       // general keywords
        ];
    }
}
```

**Frontend (Vue.js Component):**
```vue
<!-- app/assets/crud6/components/UnifiedSearch.vue -->
<template>
  <div class="unified-search">
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input
        v-model="searchQuery"
        @input="debounceSearch"
        :placeholder="placeholder"
        class="form-control"
        type="text"
      />
      <button 
        v-if="searchQuery" 
        @click="clearSearch"
        class="clear-btn"
      >
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <!-- Optional: Search syntax helper -->
    <div v-if="showHelper" class="search-helper">
      <p>Search tips:</p>
      <ul>
        <li><code>name:apple</code> - Search in specific field</li>
        <li><code>"exact phrase"</code> - Exact match</li>
        <li><code>price:>10</code> - Numeric comparison</li>
        <li><code>-word</code> - Exclude results</li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'

const props = defineProps<{
  placeholder?: string
  modelValue: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'search', query: string): void
}>()

const searchQuery = ref(props.modelValue)
const showHelper = ref(false)

const debounceSearch = useDebounceFn(() => {
  emit('update:modelValue', searchQuery.value)
  emit('search', searchQuery.value)
}, 300)

const clearSearch = () => {
  searchQuery.value = ''
  debounceSearch()
}

watch(() => props.modelValue, (newVal) => {
  searchQuery.value = newVal
})
</script>

<style scoped>
.unified-search {
  margin-bottom: 1rem;
}

.search-bar {
  position: relative;
  display: flex;
  align-items: center;
}

.search-bar i.fa-search {
  position: absolute;
  left: 12px;
  color: #6c757d;
}

.search-bar input {
  padding-left: 38px;
  padding-right: 38px;
}

.clear-btn {
  position: absolute;
  right: 8px;
  background: none;
  border: none;
  cursor: pointer;
  color: #6c757d;
}

.search-helper {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 4px;
  font-size: 0.875rem;
}
</style>
```

---

### 1.3 Inline Field Editing (Pencil Icon) ⭐⭐⭐⭐⭐

**Current Implementation (CRUD5):**
Click pencil icon → opens modal → edit field → save → modal closes

**Recommendation for CRUD6:**
Modern inline editing with Vue.js - click once to edit, click outside or press Enter to save.

**Vue.js Component:**
```vue
<!-- app/assets/crud6/components/InlineEdit.vue -->
<template>
  <div class="inline-edit" :class="{ editing: isEditing }">
    <!-- Display mode -->
    <div v-if="!isEditing" class="display-mode">
      <span class="value">{{ displayValue }}</span>
      <button 
        v-if="editable"
        @click="startEdit" 
        class="edit-btn"
        :title="$t('EDIT')"
      >
        <i class="fas fa-pencil-alt"></i>
      </button>
    </div>
    
    <!-- Edit mode -->
    <div v-else class="edit-mode">
      <component
        :is="inputComponent"
        v-model="editValue"
        @keydown.enter="saveEdit"
        @keydown.esc="cancelEdit"
        ref="inputRef"
        :type="inputType"
        :class="['form-control', { 'is-invalid': hasError }]"
      />
      
      <div class="edit-actions">
        <button 
          @click="saveEdit" 
          class="btn btn-sm btn-success"
          :disabled="saving"
        >
          <i class="fas fa-check"></i>
        </button>
        <button 
          @click="cancelEdit" 
          class="btn btn-sm btn-secondary"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div v-if="hasError" class="invalid-feedback">
        {{ errorMessage }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { useInlineEdit } from '@/composables/useInlineEdit'

interface Props {
  modelValue: any
  fieldName: string
  recordId: string | number
  apiEndpoint: string
  type?: 'text' | 'number' | 'email' | 'textarea' | 'select'
  editable?: boolean
  formatter?: (value: any) => string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  editable: true
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
  (e: 'saved', value: any): void
}>()

const {
  isEditing,
  editValue,
  saving,
  hasError,
  errorMessage,
  startEdit,
  saveEdit: save,
  cancelEdit
} = useInlineEdit(props, emit)

const inputRef = ref<HTMLInputElement>()

const displayValue = computed(() => {
  return props.formatter 
    ? props.formatter(props.modelValue) 
    : props.modelValue
})

const inputComponent = computed(() => {
  return props.type === 'textarea' ? 'textarea' : 'input'
})

const inputType = computed(() => {
  return props.type === 'textarea' ? undefined : props.type
})

const saveEdit = async () => {
  await save()
  if (!hasError.value) {
    emit('saved', editValue.value)
  }
}

// Auto-focus on edit start
watch(isEditing, async (editing) => {
  if (editing) {
    await nextTick()
    inputRef.value?.focus()
    inputRef.value?.select()
  }
})
</script>

<style scoped>
.inline-edit {
  display: flex;
  align-items: center;
  min-height: 2rem;
}

.display-mode {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}

.value {
  flex: 1;
}

.edit-btn {
  opacity: 0;
  transition: opacity 0.2s;
  background: none;
  border: none;
  cursor: pointer;
  color: #6c757d;
  padding: 0.25rem 0.5rem;
}

.inline-edit:hover .edit-btn {
  opacity: 1;
}

.edit-mode {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}

.edit-actions {
  display: flex;
  gap: 0.25rem;
}

.edit-mode input,
.edit-mode textarea {
  flex: 1;
}
</style>
```

**Backend Controller:**
```php
// app/src/Controller/Base/BaseUpdateFieldAction.php (Enhanced)
namespace UserFrosting\Sprinkle\CRUD6\Controller\Base;

class BaseUpdateFieldAction
{
    public function __invoke(
        CRUD6ModelInterface $model,
        string $field,
        Request $request,
        Response $response
    ): Response {
        // Validate field is editable
        $this->validateFieldEditable($model, $field);
        
        // Get new value
        $data = $request->getParsedBody();
        $newValue = $data['value'] ?? null;
        
        // Load field schema for validation
        $schema = $this->getFieldSchema($model->getTable(), $field);
        
        // Transform and validate
        $validated = $this->validator->validate($schema, [$field => $newValue]);
        
        // Update field
        $this->db->transaction(function () use ($model, $field, $validated) {
            $oldValue = $model->$field;
            $model->$field = $validated[$field];
            $model->save();
            
            // Log change
            $this->auditLogger->logFieldChange(
                $model,
                $field,
                $oldValue,
                $validated[$field]
            );
        });
        
        // Return updated value with formatting
        $payload = [
            'value' => $model->$field,
            'formatted' => $this->formatField($model, $field)
        ];
        
        return $response->withJson($payload);
    }
}
```

---

### 1.4 Generic Base Controllers ⭐⭐⭐⭐

**Keep and Enhance:**

Current CRUD5 has:
- `BasePageListAction`
- `BaseCreateAction`
- `BaseEditAction`
- `BaseDeleteAction`
- `BaseUpdateFieldAction`

**CRUD6 Enhancements:**

```php
// app/src/Controller/Base/BaseCRUD6Controller.php
namespace UserFrosting\Sprinkle\CRUD6\Controller\Base;

/**
 * Abstract base controller with common CRUD operations
 * Uses PHP 8.2+ features
 */
abstract class BaseCRUD6Controller
{
    protected string $modelClass;
    protected string $configKey;
    
    public function __construct(
        protected AlertStream $alert,
        protected Authenticator $authenticator,
        protected Connection $db,
        protected CRUD6ConfigLoader $configLoader,
        protected ValidatorInterface $validator,
        protected AuditLoggerInterface $auditLogger,
        protected CacheInterface $cache,
    ) {}
    
    /**
     * List records with pagination, filtering, sorting
     */
    public function index(Request $request, Response $response): Response
    {
        $config = $this->loadConfig();
        $this->validateAccess('read', $config);
        
        $sprunje = $this->createSprunje($config);
        $result = $sprunje->getResults();
        
        return $response->withJson($result);
    }
    
    /**
     * Show single record
     */
    public function show(
        string|int $id,
        Request $request,
        Response $response
    ): Response {
        $model = $this->findOrFail($id);
        $this->validateAccess('read', $model);
        
        return $response->withJson([
            'data' => $model->toArray(),
            'relationships' => $this->loadRelationships($model)
        ]);
    }
    
    /**
     * Create new record
     */
    public function store(Request $request, Response $response): Response
    {
        $this->validateAccess('create');
        
        $data = $request->getParsedBody();
        $validated = $this->validateData($data, 'create');
        
        $model = $this->db->transaction(function () use ($validated) {
            $model = $this->createModel($validated);
            $this->auditLogger->logCreate($model);
            $this->triggerHooks('after_create', $model);
            return $model;
        });
        
        $this->alert->addMessage('success', 'RECORD.CREATED');
        
        return $response->withJson([
            'data' => $model->toArray()
        ], 201);
    }
    
    /**
     * Update record
     */
    public function update(
        string|int $id,
        Request $request,
        Response $response
    ): Response {
        $model = $this->findOrFail($id);
        $this->validateAccess('update', $model);
        
        $data = $request->getParsedBody();
        $validated = $this->validateData($data, 'update');
        
        $this->db->transaction(function () use ($model, $validated) {
            $changes = $this->getChanges($model, $validated);
            $model->update($validated);
            $this->auditLogger->logUpdate($model, $changes);
            $this->triggerHooks('after_update', $model);
        });
        
        $this->alert->addMessage('success', 'RECORD.UPDATED');
        
        return $response->withJson([
            'data' => $model->fresh()->toArray()
        ]);
    }
    
    /**
     * Delete record
     */
    public function destroy(
        string|int $id,
        Request $request,
        Response $response
    ): Response {
        $model = $this->findOrFail($id);
        $this->validateAccess('delete', $model);
        
        $this->db->transaction(function () use ($model) {
            $this->auditLogger->logDelete($model);
            $this->triggerHooks('before_delete', $model);
            $model->delete();
        });
        
        $this->alert->addMessage('success', 'RECORD.DELETED');
        
        return $response->withStatus(204);
    }
    
    /**
     * Bulk operations (NEW)
     */
    public function bulk(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $action = $data['action'] ?? null;
        $ids = $data['ids'] ?? [];
        
        $this->validateAccess('bulk_' . $action);
        
        $result = match($action) {
            'delete' => $this->bulkDelete($ids),
            'update' => $this->bulkUpdate($ids, $data['updates']),
            'export' => $this->bulkExport($ids, $data['format']),
            default => throw new InvalidArgumentException("Invalid bulk action")
        };
        
        return $response->withJson($result);
    }
    
    // Abstract methods for customization
    abstract protected function createModel(array $data): Model;
    abstract protected function loadConfig(): array;
}
```

---

## 2. UI/UX Features to Enhance

### 2.1 Table Features

**From CRUD5 - Keep:**
- ✅ Sortable columns (click header to sort)
- ✅ Pagination with configurable page sizes
- ✅ Column visibility toggle
- ✅ Action buttons (edit, delete)

**For CRUD6 - Add:**
- ✅ Column reordering (drag & drop)
- ✅ Column resizing
- ✅ Saved table views (user preferences)
- ✅ Export to CSV/Excel/PDF
- ✅ Bulk selection and operations
- ✅ Row expansion for details
- ✅ Frozen columns
- ✅ Virtual scrolling for large datasets
- ✅ Real-time updates via WebSocket

**Vue.js Data Table Component:**
```vue
<!-- app/assets/crud6/components/DataTable.vue -->
<template>
  <div class="crud6-table">
    <!-- Toolbar -->
    <div class="table-toolbar">
      <UnifiedSearch 
        v-model="searchQuery"
        @search="handleSearch"
      />
      
      <div class="toolbar-actions">
        <button @click="showColumnSelector" class="btn btn-sm">
          <i class="fas fa-columns"></i> Columns
        </button>
        <button @click="exportData" class="btn btn-sm">
          <i class="fas fa-download"></i> Export
        </button>
        <button 
          v-if="selectedRows.length > 0"
          @click="showBulkActions"
          class="btn btn-sm btn-primary"
        >
          <i class="fas fa-tasks"></i> 
          Bulk Actions ({{ selectedRows.length }})
        </button>
      </div>
    </div>
    
    <!-- Table -->
    <div class="table-container" ref="tableContainer">
      <table class="table table-hover">
        <thead>
          <tr>
            <th v-if="selectable" class="select-column">
              <input 
                type="checkbox"
                v-model="selectAll"
                @change="handleSelectAll"
              />
            </th>
            
            <th
              v-for="column in visibleColumns"
              :key="column.name"
              :class="getColumnClass(column)"
              @click="handleSort(column)"
              :style="{ width: column.width }"
            >
              {{ column.label }}
              <i v-if="column.sortable" :class="getSortIcon(column)"></i>
              
              <!-- Resize handle -->
              <div
                v-if="column.resizable"
                class="resize-handle"
                @mousedown="startResize($event, column)"
              ></div>
            </th>
            
            <th v-if="hasActions" class="actions-column">
              Actions
            </th>
          </tr>
        </thead>
        
        <tbody>
          <tr
            v-for="row in paginatedData"
            :key="row.id"
            :class="{ selected: isSelected(row) }"
          >
            <td v-if="selectable">
              <input 
                type="checkbox"
                :checked="isSelected(row)"
                @change="toggleRow(row)"
              />
            </td>
            
            <td
              v-for="column in visibleColumns"
              :key="column.name"
            >
              <InlineEdit
                v-if="column.editable"
                v-model="row[column.name]"
                :field-name="column.name"
                :record-id="row.id"
                :api-endpoint="apiEndpoint"
                :type="column.edit_type"
                :formatter="column.formatter"
                @saved="handleCellSaved(row, column, $event)"
              />
              <CellRenderer
                v-else
                :value="row[column.name]"
                :column="column"
                :row="row"
              />
            </td>
            
            <td v-if="hasActions">
              <ActionButtons
                :row="row"
                :actions="config.actions"
                @action="handleAction"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <Pagination
      v-model:page="currentPage"
      v-model:per-page="perPage"
      :total="totalRecords"
      :per-page-options="[10, 25, 50, 100]"
    />
  </div>
</template>

<script setup lang="ts">
// Implementation with composition API
import { ref, computed, onMounted } from 'vue'
import { useDataTable } from '@/composables/useDataTable'
import UnifiedSearch from './UnifiedSearch.vue'
import InlineEdit from './InlineEdit.vue'
import CellRenderer from './CellRenderer.vue'
import ActionButtons from './ActionButtons.vue'
import Pagination from './Pagination.vue'

// ... implementation
</script>
```

---

### 2.2 Form Features

**From CRUD5 - Keep:**
- ✅ Modal forms
- ✅ Auto-generated from schema
- ✅ Client & server-side validation

**For CRUD6 - Add:**
- ✅ Multi-step forms/wizards
- ✅ Conditional field display
- ✅ File upload with drag & drop
- ✅ Rich text editor
- ✅ Auto-save drafts
- ✅ Field dependencies
- ✅ Dynamic field addition

---

## 3. Architecture Modernization

### 3.1 API-First Approach

**Current (CRUD5):** Twig renders HTML → JavaScript enhances

**Recommended (CRUD6):** RESTful API → Vue.js consumes

```
┌─────────────────────────────────────────┐
│           Frontend (Vue.js)             │
│  ┌─────────────────────────────────┐   │
│  │  Pages (Vue Router)             │   │
│  │  - /products (List)             │   │
│  │  - /products/:id (Detail)       │   │
│  │  - /products/create (Form)      │   │
│  └─────────────────────────────────┘   │
│              ↓ HTTP/REST                │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│         Backend (PHP/UF6)               │
│  ┌─────────────────────────────────┐   │
│  │  RESTful API Controllers        │   │
│  │  - GET    /api/v1/products      │   │
│  │  - POST   /api/v1/products      │   │
│  │  - GET    /api/v1/products/:id  │   │
│  │  - PUT    /api/v1/products/:id  │   │
│  │  - DELETE /api/v1/products/:id  │   │
│  │  - PATCH  /api/v1/products/:id  │   │
│  └─────────────────────────────────┘   │
│              ↓                          │
│  ┌─────────────────────────────────┐   │
│  │  Business Logic Layer           │   │
│  │  - Validation                   │   │
│  │  - Authorization                │   │
│  │  - Transactions                 │   │
│  └─────────────────────────────────┘   │
│              ↓                          │
│  ┌─────────────────────────────────┐   │
│  │  Data Layer (Eloquent)          │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

### 3.2 TypeScript Integration

**Benefits:**
- Type safety for API contracts
- Better IDE support
- Fewer runtime errors
- Self-documenting code

```typescript
// app/assets/crud6/types/api.ts
export interface Product {
  id: number
  name: string
  description: string | null
  price: number
  stock: number
  created_at: string
  updated_at: string
}

export interface PaginatedResponse<T> {
  data: T[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    total_pages: number
  }
}

export interface TableConfig {
  columns: ColumnConfig[]
  search: SearchConfig
  actions: ActionConfig[]
}

// Auto-generated from PHP schema
export interface ProductTableConfig extends TableConfig {
  model: 'products'
  permission: 'crud6_user'
  // ... type-safe config
}
```

---

## 4. JavaScript Framework Migration

### 4.1 Why Vue.js (Recommended)

**Advantages:**
- ✅ Progressive adoption (can coexist with existing code)
- ✅ Excellent TypeScript support
- ✅ Smaller learning curve than React
- ✅ Great for data-heavy tables (virtual scrolling)
- ✅ Composition API aligns with modern patterns
- ✅ Official router and state management

**Alternative: React**
- ✅ Larger ecosystem
- ✅ More job market demand
- ✅ Better for complex SPAs
- ❌ Steeper learning curve
- ❌ More boilerplate

### 4.2 Migration Strategy

**Phase 1: Coexistence**
- Keep Twig for main layout
- Use Vue.js for CRUD6 tables/forms
- Share authentication state

**Phase 2: Component Migration**
- Port components one by one
- Start with data tables
- Then forms
- Then modals

**Phase 3: Full SPA (Optional)**
- Vue Router for navigation
- Lazy loading routes
- Server-side rendering (SSR) for SEO

---

## 5. New Features for CRUD6

### 5.1 Real-Time Updates

**Use Case:** Multiple users editing same data

**Implementation:**
```php
// Backend: WebSocket support
use Ratchet\MessageComponentInterface;

class CRUD6WebSocket implements MessageComponentInterface
{
    protected $clients;
    
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);
        
        // Broadcast changes to all clients watching this record
        foreach ($this->clients[$data->model][$data->id] as $client) {
            if ($client !== $from) {
                $client->send(json_encode([
                    'type' => 'update',
                    'field' => $data->field,
                    'value' => $data->value
                ]));
            }
        }
    }
}
```

### 5.2 Advanced Filtering

**Filter Builder UI:**
```vue
<template>
  <div class="filter-builder">
    <div 
      v-for="(filter, index) in filters" 
      :key="index"
      class="filter-row"
    >
      <select v-model="filter.field">
        <option 
          v-for="field in fields" 
          :value="field.name"
        >
          {{ field.label }}
        </option>
      </select>
      
      <select v-model="filter.operator">
        <option value="equals">Equals</option>
        <option value="not_equals">Not Equals</option>
        <option value="contains">Contains</option>
        <option value="starts_with">Starts With</option>
        <option value="greater_than">Greater Than</option>
        <option value="less_than">Less Than</option>
        <option value="between">Between</option>
        <option value="in">In List</option>
        <option value="is_null">Is Empty</option>
      </select>
      
      <component
        :is="getInputComponent(filter)"
        v-model="filter.value"
      />
      
      <button @click="removeFilter(index)">
        <i class="fas fa-trash"></i>
      </button>
    </div>
    
    <button @click="addFilter" class="btn btn-sm">
      <i class="fas fa-plus"></i> Add Filter
    </button>
  </div>
</template>
```

### 5.3 Audit Trail

**Track all changes:**
```php
// Automatic audit logging
class AuditLogger
{
    public function logFieldChange(
        Model $model,
        string $field,
        mixed $oldValue,
        mixed $newValue
    ): void {
        AuditLog::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ]);
    }
}
```

### 5.4 Import/Export

**Bulk data operations:**
```typescript
// Export
async function exportData(format: 'csv' | 'xlsx' | 'pdf') {
  const response = await api.post('/api/v1/products/export', {
    format,
    filters: currentFilters,
    columns: selectedColumns
  })
  
  // Download file
  downloadFile(response.data.url)
}

// Import with validation
async function importData(file: File) {
  const formData = new FormData()
  formData.append('file', file)
  
  const response = await api.post('/api/v1/products/import', formData, {
    onUploadProgress: (e) => {
      uploadProgress.value = (e.loaded / e.total) * 100
    }
  })
  
  if (response.data.errors) {
    showErrorsDialog(response.data.errors)
  }
}
```

### 5.5 Saved Views

**User preferences:**
```typescript
interface SavedView {
  id: string
  name: string
  filters: Filter[]
  sort: SortConfig
  columns: string[]
  page_size: number
  is_default: boolean
}

// Save current view
async function saveView(name: string) {
  await api.post('/api/v1/saved-views', {
    model: 'products',
    name,
    config: {
      filters: currentFilters,
      sort: currentSort,
      columns: visibleColumns,
      page_size: perPage
    }
  })
}

// Load saved view
async function loadView(viewId: string) {
  const view = await api.get(`/api/v1/saved-views/${viewId}`)
  applyViewConfig(view.config)
}
```

---

## 6. Implementation Priority

### Priority 1: Must Have (MVP)
1. ✅ Schema-driven configuration system
2. ✅ Unified search bar
3. ✅ Inline field editing
4. ✅ RESTful API controllers
5. ✅ Vue.js data table component
6. ✅ Basic CRUD operations
7. ✅ Permission system integration

### Priority 2: High Value
1. ✅ Advanced filtering UI
2. ✅ Bulk operations
3. ✅ Export functionality
4. ✅ Audit logging
5. ✅ TypeScript types
6. ✅ Form validation

### Priority 3: Nice to Have
1. ✅ Real-time updates (WebSocket)
2. ✅ Import functionality
3. ✅ Saved views
4. ✅ Column reordering/resizing
5. ✅ Multi-step forms
6. ✅ Rich text editor

### Priority 4: Future Enhancements
1. ✅ GraphQL API option
2. ✅ Mobile app support
3. ✅ Offline mode
4. ✅ Advanced analytics
5. ✅ AI-powered search
6. ✅ Custom dashboards

---

## 7. Migration Strategy

### Step 1: Setup (Week 1-2)
```bash
# Install dependencies
composer require ssnukala/sprinkle-crud6
npm install vue@3 pinia vue-router @vueuse/core

# Create directory structure
mkdir -p app/assets/crud6/{components,composables,stores,types}
```

### Step 2: Core API (Week 3-4)
- Implement RESTful API controllers
- Add authentication/authorization
- Set up Sprunje for data tables
- Create request schemas

### Step 3: Vue.js Components (Week 5-8)
- Build data table component
- Create inline edit component
- Implement unified search
- Add form components

### Step 4: Integration (Week 9-10)
- Connect Vue.js to API
- Implement state management (Pinia)
- Add error handling
- Set up loading states

### Step 5: Testing & Polish (Week 11-12)
- Unit tests (PHPUnit + Vitest)
- E2E tests (Playwright)
- Performance optimization
- Documentation

---

## 8. Technical Specifications

### 8.1 API Endpoints

```
# Standard CRUD
GET    /api/v1/{model}              # List with pagination, filtering, sorting
POST   /api/v1/{model}              # Create
GET    /api/v1/{model}/{id}         # Show
PUT    /api/v1/{model}/{id}         # Update
PATCH  /api/v1/{model}/{id}         # Partial update
DELETE /api/v1/{model}/{id}         # Delete

# Field operations
PATCH  /api/v1/{model}/{id}/{field} # Update single field

# Bulk operations
POST   /api/v1/{model}/bulk         # Bulk operations
DELETE /api/v1/{model}/bulk         # Bulk delete

# Import/Export
POST   /api/v1/{model}/import       # Import data
POST   /api/v1/{model}/export       # Export data

# Metadata
GET    /api/v1/{model}/schema       # Get table schema
GET    /api/v1/{model}/config       # Get table configuration

# Saved views
GET    /api/v1/saved-views          # List views
POST   /api/v1/saved-views          # Create view
GET    /api/v1/saved-views/{id}     # Get view
PUT    /api/v1/saved-views/{id}     # Update view
DELETE /api/v1/saved-views/{id}     # Delete view
```

### 8.2 Request/Response Format

**List Request:**
```json
GET /api/v1/products?page=1&per_page=25&search=laptop&sort=-price

{
  "data": [
    {
      "id": 1,
      "name": "Gaming Laptop",
      "price": 1299.99,
      "stock": 15,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 100,
    "total_pages": 4
  },
  "meta": {
    "search_query": "laptop",
    "filters_applied": 0,
    "sort": "-price"
  }
}
```

**Create Request:**
```json
POST /api/v1/products
Content-Type: application/json

{
  "name": "New Product",
  "price": 99.99,
  "stock": 50
}

Response 201:
{
  "data": {
    "id": 101,
    "name": "New Product",
    "price": 99.99,
    "stock": 50,
    "created_at": "2024-10-24T10:00:00Z"
  }
}
```

**Update Field:**
```json
PATCH /api/v1/products/101/stock

{
  "value": 45
}

Response 200:
{
  "data": {
    "stock": 45
  },
  "formatted": "45 units"
}
```

### 8.3 Frontend Structure

```
app/assets/crud6/
├── components/
│   ├── DataTable.vue           # Main table component
│   ├── UnifiedSearch.vue       # Search bar
│   ├── InlineEdit.vue          # Inline editing
│   ├── CellRenderer.vue        # Cell formatting
│   ├── ActionButtons.vue       # Row actions
│   ├── Pagination.vue          # Pagination controls
│   ├── FilterBuilder.vue       # Advanced filters
│   ├── ColumnSelector.vue      # Show/hide columns
│   ├── BulkActions.vue         # Bulk operations
│   └── FormModal.vue           # Form modals
├── composables/
│   ├── useDataTable.ts         # Table logic
│   ├── useInlineEdit.ts        # Inline edit logic
│   ├── useApi.ts               # API client
│   ├── useAuth.ts              # Authentication
│   └── useNotifications.ts     # Alerts/toasts
├── stores/
│   ├── crud.ts                 # CRUD state (Pinia)
│   ├── auth.ts                 # Auth state
│   └── ui.ts                   # UI state
├── types/
│   ├── api.ts                  # API types
│   ├── models.ts               # Model types
│   └── config.ts               # Config types
├── utils/
│   ├── formatters.ts           # Value formatters
│   ├── validators.ts           # Validation helpers
│   └── helpers.ts              # Utilities
├── pages/
│   ├── List.vue                # List page
│   ├── Detail.vue              # Detail page
│   └── Form.vue                # Form page
└── main.ts                     # App entry point
```

---

## Summary

### Top 5 Features to Port from CRUD5

1. **Schema-Driven Configuration** ⭐⭐⭐⭐⭐
   - YAML-based table/form definitions
   - Eliminates boilerplate code
   - Easy to maintain and extend

2. **Unified Search Bar** ⭐⭐⭐⭐⭐
   - Single search box for all fields
   - Better UX than per-column filters
   - Google-like search experience

3. **Inline Field Editing** ⭐⭐⭐⭐⭐
   - Click pencil → edit → save
   - No modal required for simple edits
   - Faster workflow

4. **Generic Base Controllers** ⭐⭐⭐⭐
   - Reusable CRUD logic
   - Easy to extend/customize
   - Consistent API patterns

5. **Permission-Based Access** ⭐⭐⭐⭐
   - Field-level permissions
   - Role-based access control
   - Integrated with UserFrosting

### Key Modernizations for CRUD6

1. **Replace jQuery with Vue.js**
   - Modern reactive framework
   - Better state management
   - TypeScript support

2. **API-First Architecture**
   - RESTful backend
   - Decoupled frontend
   - Better testability

3. **Real-Time Features**
   - WebSocket updates
   - Collaborative editing
   - Live notifications

4. **Enhanced Table Features**
   - Column reordering/resizing
   - Saved views
   - Virtual scrolling
   - Export/import

5. **Better Developer Experience**
   - TypeScript throughout
   - Auto-generated types
   - Hot module replacement
   - Comprehensive docs

---

## Next Steps

1. **Review this document** with the team
2. **Prioritize features** based on user needs
3. **Create technical specs** for high-priority items
4. **Set up development environment** for CRUD6
5. **Start with MVP** (Priority 1 features)
6. **Iterate based on feedback**

---

**Questions or Feedback?**

Please open an issue on the sprinkle-crud6 repository or contact the development team.

**Document Version**: 1.0  
**Last Updated**: October 2024  
**Next Review**: December 2024
