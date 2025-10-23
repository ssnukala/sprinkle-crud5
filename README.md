# CRUD5 Sprinkle for UserFrosting 5.x

[![Version](https://img.shields.io/badge/version-5.0.0-blue.svg)](https://github.com/ssnukala/sprinkle-crud5)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)
[![UserFrosting](https://img.shields.io/badge/UserFrosting-5.x-brightgreen.svg)](https://www.userfrosting.com)

A powerful, schema-driven CRUD (Create, Read, Update, Delete) system for UserFrosting 5.x that allows you to rapidly build complete CRUD interfaces through simple YAML configuration files.

## ✨ Features

- **🚀 Rapid Development**: Create full CRUD interfaces in minutes with YAML configuration
- **📋 Schema-Driven**: Define tables, columns, validations, and forms using YAML
- **🎨 Consistent UI**: Uniform interface across all CRUD operations using AdminLTE
- **🔒 Permission-Based**: Built-in permission system integrated with UserFrosting
- **📝 Auto Forms**: Automatic form generation with client & server-side validation
- **📊 Data Tables**: Sortable, filterable, paginated tables with Sprunje
- **🔧 Extensible**: Easy to customize controllers, views, and business logic
- **✅ Type-Safe**: Leverages PHP 8+ features with strict typing

## 📖 Documentation

**Complete documentation is available in [DOCUMENTATION.md](DOCUMENTATION.md)**

The documentation includes:
- Detailed installation instructions
- Complete architecture overview
- Configuration guide with examples
- Usage examples and best practices
- API reference
- Advanced topics and customization
- Troubleshooting guide

## 🚀 Quick Start

### Installation

```bash
composer require ssnukala/sprinkle-crud5
```

### Register Sprinkle

```php
// app/src/MyApp.php
use UserFrosting\Sprinkle\CRUD5\CRUD5;

public function getSprinkles(): array
{
    return [
        Core::class,
        Account::class,
        Admin::class,
        AdminLTE::class,
        CRUD5::class,  // Add this
    ];
}
```

### Run Migrations

```bash
php bakery migrate
npm install
npm run build
```

### Create Your First CRUD Interface

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
  columns:
    name:
      label: "PRODUCT NAME"
      sortable: true
      searchable: true
    price:
      label: "PRICE"
      sortable: true
    actions:
      label: "ACTIONS"
      template: actions
```

Create request schema `app/schema/requests/products/create.yaml`:

```yaml
name:
  validators:
    required:
      message: "Product name is required"
    length:
      max: 255
  form:
    type: text
    label: "Product Name"
    placeholder: "Enter product name"

price:
  validators:
    required:
      message: "Price is required"
    numeric:
      message: "Must be a number"
  form:
    type: number
    label: "Price"
    step: "0.01"
```

Copy `create.yaml` to `edit-info.yaml`, then access: **`/crud5/products`**

**That's it!** You now have a complete CRUD interface with list, create, edit, and delete functionality.

## 🎯 Key Components

### Routes

| Pattern | Description |
|---------|-------------|
| `/crud5/{slug}` | List view page |
| `/api/crud5/{slug}` | API endpoints (GET, POST, PUT, DELETE) |
| `/modals/crud5/{slug}/create` | Create modal form |
| `/modals/crud5/{slug}/edit` | Edit modal form |

### Controllers

- **BasePageListAction** - List views with Sprunje
- **BaseCreateAction** - Create records
- **BaseEditAction** - Update records
- **BaseDeleteAction** - Delete records
- **BaseEditModal** - Form modals

### Configuration Files

- `app/schema/crud5/{table}.yaml` - Table configuration
- `app/schema/requests/{table}/create.yaml` - Create form schema
- `app/schema/requests/{table}/edit-info.yaml` - Edit form schema

## 💡 Example Use Cases

- User management systems
- Product catalogs
- Content management
- Inventory tracking
- Any database table CRUD operations

## 🛠️ Requirements

- PHP ≥ 8.0
- UserFrosting ≥ 5.0
- FormGenerator ~5.1.0
- AdminLTE Theme ≥ 5.0

## 📚 Features In Detail

### Dynamic CRUD Operations
Complete Create, Read, Update, Delete operations for any table through configuration alone.

### Schema-Driven Configuration  
Define everything in YAML: columns, validation rules, form fields, permissions, display templates.

### Permission System
Built-in permissions (`c5_user`, `c5_admin`) with support for custom permissions.

### Form Generation
Automatic forms with validation using FormGenerator library.

### Data Tables
Sortable, searchable, paginated tables using Sprunje.

### Middleware Injection
Automatic model loading and injection based on routes.

### Extensibility
Easy to extend with custom controllers, validators, templates, and business logic.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📝 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## 👤 Author

**Srinivas Nukala**
- Website: https://srinivasnukala.com
- GitHub: [@ssnukala](https://github.com/ssnukala)

## 🙏 Acknowledgments

Built with:
- [UserFrosting](https://www.userfrosting.com) - PHP framework
- [FormGenerator](https://github.com/lcharette/UF_FormGenerator) - Dynamic forms
- [AdminLTE](https://adminlte.io) - Admin theme

## 📖 Further Reading

- [Complete Documentation](DOCUMENTATION.md)
- [UserFrosting Documentation](https://learn.userfrosting.com)
- [GitHub Repository](https://github.com/ssnukala/sprinkle-crud5)

---

**Note**: For complete installation instructions, configuration options, API reference, and advanced usage, please refer to [DOCUMENTATION.md](DOCUMENTATION.md).
