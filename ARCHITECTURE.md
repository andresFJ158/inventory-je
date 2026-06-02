# ERP System - Recommended Architecture

## Current State (Monolithic)

```
┌─────────────────────────────────────┐
│      Frontend (Nuxt 4 + Vue 3)      │
│                                     │
│  ├─ pages/ (24 pages)              │
│  ├─ components/ (7 components)      │
│  └─ stores/ (Pinia)                │
└──────────────┬──────────────────────┘
               │ HTTP API calls
               │
┌──────────────▼──────────────────────┐
│    Single API File (4,403 lines)    │
│    /ajax/pos.ajax.php               │
│                                     │
│  ├─ 52 handlers (mixed concerns)   │
│  ├─ No error handling               │
│  ├─ No input validation             │
│  └─ No permission checks            │
└──────────────┬──────────────────────┘
               │ SQL queries (unparameterized)
               │
┌──────────────▼──────────────────────┐
│      Database (MariaDB)             │
│      25 tables, missing indexes     │
└─────────────────────────────────────┘
```

**Problems**:
- 🔴 Impossible to test individual features
- 🔴 Difficult to debug errors
- 🔴 No permission enforcement
- 🔴 Performance issues with missing indexes
- 🔴 SQL injection vulnerabilities

---

## Recommended Architecture (3-Tier Layered)

```
┌──────────────────────────────────────────────────────────┐
│         Presentation Layer (Frontend)                    │
│                                                          │
│  ├─ Nuxt Pages (customers, orders, invoices, etc)      │
│  ├─ Vue Components (modular, reusable)                 │
│  ├─ Pinia Stores (state management)                    │
│  └─ Error Boundaries + Loading States                  │
└─────────────────────┬──────────────────────────────────┘
                      │ RESTful API (JSON)
                      │
┌─────────────────────▼──────────────────────────────────┐
│         API Layer (Controllers + Middleware)            │
│                                                        │
│  ├─ Auth Middleware (check session, user role)       │
│  ├─ Validation Middleware (Validator class)           │
│  ├─ CORS Middleware (cross-origin requests)           │
│  ├─ Permission Middleware (role-based access)         │
│  ├─ Rate Limit Middleware (prevent abuse)             │
│  │                                                    │
│  ├─ OrderController                                  │
│  ├─ ProductController                                │
│  ├─ InvoiceController                                │
│  ├─ SupplierController                               │
│  ├─ InventoryController                              │
│  └─ ClientController                                 │
│                                                        │
│  └─ Response Handler (ErrorHandler + success)         │
└─────────────────────┬──────────────────────────────────┘
                      │ Service calls
                      │
┌─────────────────────▼──────────────────────────────────┐
│         Business Logic Layer (Services)                │
│                                                        │
│  ├─ OrderService                                      │
│  │  ├─ createOrder()                                 │
│  │  ├─ updateOrderStatus()                           │
│  │  ├─ cancelOrder()                                 │
│  │  └─ calculateOrderTotal()                         │
│  │                                                    │
│  ├─ ProductService                                    │
│  │  ├─ getProduct()                                  │
│  │  ├─ updatePrice()                                 │
│  │  └─ getInventory()                                │
│  │                                                    │
│  ├─ InvoiceService                                   │
│  │  ├─ createInvoice()                               │
│  │  ├─ emitInvoice()                                 │
│  │  └─ voidInvoice()                                 │
│  │                                                    │
│  ├─ PaymentService                                   │
│  │  ├─ processPayment()                              │
│  │  ├─ recordAbono()                                 │
│  │  └─ calculateBalance()                            │
│  │                                                    │
│  ├─ InventoryService                                 │
│  │  ├─ decrementStock()                              │
│  │  ├─ transferStock()                               │
│  │  └─ adjustQuantity()                              │
│  │                                                    │
│  └─ AuditService                                      │
│     ├─ logChange()                                    │
│     ├─ getAuditLog()                                  │
│     └─ trackUserAction()                              │
└─────────────────────┬──────────────────────────────────┘
                      │ SQL queries (parameterized)
                      │ Transactions (ACID)
                      │
┌─────────────────────▼──────────────────────────────────┐
│         Data Access Layer (Repositories)              │
│                                                        │
│  ├─ OrderRepository                                  │
│  │  ├─ findById($id)                                 │
│  │  ├─ findByStatus($status)                         │
│  │  ├─ save($order)                                  │
│  │  └─ delete($id)                                   │
│  │                                                    │
│  ├─ ProductRepository                                │
│  ├─ ClientRepository                                │
│  ├─ InvoiceRepository                                │
│  ├─ PaymentRepository                                │
│  └─ AuditLogRepository                               │
└─────────────────────┬──────────────────────────────────┘
                      │ Database driver (PDO)
                      │
┌─────────────────────▼──────────────────────────────────┐
│         Database Layer (MariaDB)                       │
│                                                        │
│  ├─ orders (with indexes on FK + composite)          │
│  ├─ products (with inventory tracking)               │
│  ├─ invoices (with audit fields)                     │
│  ├─ payments (with payment method tracking)          │
│  ├─ inventory_transactions (atomic transfers)        │
│  ├─ audit_logs (complete change history)             │
│  ├─ users (with permissions)                         │
│  ├─ roles_permissions (RBAC matrix)                  │
│  └─ ... (other 17 tables)                            │
└──────────────────────────────────────────────────────┘
```

---

## Recommended Directory Structure

```
erp-system/
├─ app.vue                          # Main layout
├─ pages/                           # 24 Nuxt pages
│  ├─ ordenes.vue
│  ├─ productos.vue
│  ├─ facturacion.vue
│  └─ ... (21 more pages)
│
├─ components/                      # 10 reusable components
│  ├─ ErrorBoundary.vue
│  ├─ ConfirmDialog.vue
│  ├─ LoadingSpinner.vue
│  ├─ NotaVenta.vue
│  └─ ... (6 more)
│
├─ stores/                          # Pinia state management
│  ├─ authStore.ts
│  ├─ orderStore.ts
│  └─ ... (6 stores)
│
├─ ajax/
│  ├─ config.php                   # Database connection + constants
│  ├─ middleware/
│  │  ├─ AuthMiddleware.php
│  │  ├─ ValidationMiddleware.php
│  │  ├─ PermissionMiddleware.php
│  │  └─ RateLimitMiddleware.php
│  │
│  ├─ controllers/
│  │  ├─ OrderController.php       # 600 lines, 8 methods
│  │  ├─ ProductController.php     # 400 lines, 6 methods
│  │  ├─ InvoiceController.php     # 300 lines, 5 methods
│  │  ├─ SupplierController.php    # 250 lines, 5 methods
│  │  ├─ InventoryController.php   # 200 lines, 4 methods
│  │  └─ ClientController.php      # 200 lines, 4 methods
│  │
│  ├─ services/
│  │  ├─ OrderService.php
│  │  ├─ ProductService.php
│  │  ├─ InvoiceService.php
│  │  ├─ PaymentService.php
│  │  ├─ InventoryService.php
│  │  ├─ AuditService.php
│  │  └─ ReportService.php
│  │
│  ├─ repositories/
│  │  ├─ OrderRepository.php
│  │  ├─ ProductRepository.php
│  │  ├─ ClientRepository.php
│  │  ├─ InvoiceRepository.php
│  │  └─ ... (more repositories)
│  │
│  ├─ models/
│  │  ├─ Order.php
│  │  ├─ Product.php
│  │  ├─ Invoice.php
│  │  └─ ... (data models)
│  │
│  ├─ Validator.php                # Input validation framework
│  ├─ ErrorHandler.php             # Error handling + logging
│  ├─ Database.php                 # PDO connection wrapper
│  ├─ Router.php                   # Route to controller mapping
│  └─ index.php                    # Application entry point
│
├─ tests/
│  ├─ OrderServiceTest.php
│  ├─ ProductServiceTest.php
│  ├─ PaymentServiceTest.php
│  └─ ... (test files)
│
├─ migrations/
│  ├─ 2026_01_create_audit_logs.sql
│  ├─ 2026_01_add_indexes.sql
│  └─ 2026_01_add_audit_columns.sql
│
├─ logs/
│  ├─ erp_errors.log
│  └─ audit.log
│
├─ config/
│  ├─ database.php
│  ├─ app.php
│  └─ permissions.php
│
├─ nuxt.config.ts
├─ tailwind.config.ts
├─ tsconfig.json
├─ package.json
├─ composer.json
│
├─ ARCHITECTURE.md                 # This file
├─ BEST_PRACTICES.md
├─ IMPROVEMENT_ROADMAP.md
└─ ANALYSIS_SUMMARY.md
```

---

## API Routing Pattern

### Current (Monolithic)
```php
if ($_POST['action'] == 'createOrder') {
    // 50 lines of order creation
} elseif ($_POST['action'] == 'getOrders') {
    // 30 lines of order fetching
} elseif ($_POST['action'] == 'updateOrder') {
    // ... (and so on)
}
```

### Recommended (Controller-Based)
```php
// ajax/index.php
require_once 'Router.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'middleware/ValidationMiddleware.php';

$router = new Router();

// Orders
$router->post('/orders', 'OrderController@create')->middleware(['auth', 'validation']);
$router->get('/orders', 'OrderController@list')->middleware(['auth']);
$router->get('/orders/{id}', 'OrderController@show')->middleware(['auth']);
$router->patch('/orders/{id}', 'OrderController@update')->middleware(['auth', 'validation']);
$router->delete('/orders/{id}', 'OrderController@delete')->middleware(['auth']);

// Products
$router->get('/products', 'ProductController@list')->middleware(['auth']);
$router->post('/products', 'ProductController@create')->middleware(['auth', 'admin']);
$router->patch('/products/{id}', 'ProductController@update')->middleware(['auth', 'admin']);

// Run router
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```

### Controller Example
```php
// ajax/controllers/OrderController.php
class OrderController
{
    private $orderService;
    
    public function create()
    {
        try {
            // Validate input
            $order = Validator::batch($_POST, [
                'id_client' => [function($v) { return Validator::integer($v, 'Client ID', true); }],
                'items' => [function($v) { return Validator::array($v, 'Items', true); }],
            ]);
            
            // Business logic
            $created_order = $this->orderService->createOrder($order);
            
            // Return response
            return ErrorHandler::success($created_order, 'Order created', 201);
        } catch (Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
```

---

## Data Flow Example: Create Order

### Current (Vulnerable)
```
Frontend → /ajax/pos.ajax.php (action=createOrder)
  ↓
Raw POST access: $id_client = $_POST['id_client'];
  ↓
No validation: $items = $_POST['items'];
  ↓
Unsafe query: SELECT * FROM products WHERE id IN ({$ids})  [SQL INJECTION]
  ↓
Separate updates: INSERT orders; INSERT order_items; UPDATE inventory
  ↓ (If middle step fails → data inconsistency)
```

### Recommended (Secure)
```
Frontend → POST /ajax/orders (JSON)
  ↓
AuthMiddleware: Check session, get current user
  ↓
ValidationMiddleware: Call Validator::batch() on request body
  ↓
OrderController@create(): Route to handler
  ↓
OrderService::createOrder():
  - Validate business rules (items exist, quantities valid)
  - db->beginTransaction()
  - Insert order record
  - Insert order items (parameterized query)
  - Decrement inventory (atomic transfer)
  - Create invoice draft
  - Log audit trail
  - db->commit()
  ↓
OrderRepository::save(): Persist to database
  ↓
ErrorHandler::success(): Return standardized JSON
  ↓
Frontend: Handle response, update UI state
```

---

## Error Handling Flow

```
┌─────────────────────────────────────┐
│  Exception occurs in Service         │
│  throw new ValidationException(...)  │
└────────────┬────────────────────────┘
             │
             ▼
     ┌───────────────────┐
     │ Controller        │
     │ } catch (...)     │
     └─────────┬─────────┘
               │
               ▼
    ┌──────────────────────┐
    │ ErrorHandler::handle │
    │ - Map exception type │
    │ - Set status code    │
    │ - Log error context  │
    │ - Return JSON response
    └─────────┬────────────┘
              │
              ▼
    ┌──────────────────────┐
    │ Frontend             │
    │ Response interceptor │
    │ - Parse status code  │
    │ - Show error message │
    │ - Log to Sentry      │
    └──────────────────────┘
```

---

## Permission Checking

### Current (Insufficient)
```php
if ($_SESSION['user_role'] == 'admin') {
    // Allow action
} else {
    die("Admin only");
}
```

**Problems**: Coarse-grained, no granular control, no audit trail

### Recommended (RBAC)
```php
// Database structure
Table: roles
├─ id_role
├─ name (superadmin, admin, cajero, etc)
└─ description

Table: permissions
├─ id_permission
├─ name (order.create, order.delete, invoice.emit, etc)
└─ resource_type

Table: role_permissions (junction)
├─ id_role
├─ id_permission
└─ granted_at

// In middleware
function requirePermission($permission) {
    $user_role = $_SESSION['user_role'];
    $user_permissions = getPermissionsForRole($user_role);
    
    if (!in_array($permission, $user_permissions)) {
        throw new PermissionException("Insufficient permissions");
    }
}

// In controller
requirePermission('order.delete');
$this->orderService->deleteOrder($id);
```

---

## Testing Strategy

### Current: 0% coverage

### Recommended Coverage:
- Core business logic: **80%**
- API endpoints: **60%**
- Vue components: **40%**

### Test Hierarchy
```
Unit Tests (80 tests)
├─ OrderService::createOrder()
├─ OrderService::updateStatus()
├─ ProductService::getAvailability()
├─ PaymentService::processPayment()
├─ Validator::batch()
└─ ... (75 more)

Integration Tests (30 tests)
├─ Create order → Decrement inventory
├─ Create invoice → Generate PDF
├─ Process payment → Update balance
└─ ... (27 more)

E2E Tests (10 tests)
├─ POS flow: Select items → Create order → Pay → Complete
├─ Lab flow: Enter materials → Complete task
└─ ... (8 more)
```

---

## Performance Optimization Path

### Week 1: Database (40x speed improvement)
- Add 23 foreign key indexes
- Add composite indexes on common joins
- Enable query logging for slow queries

### Week 2: API Optimization
- Add pagination to list endpoints (max 100 items)
- Implement ETag caching headers
- Add gzip compression

### Week 3: Frontend
- Lazy load components
- Implement virtual scrolling for large lists
- Add service worker for offline support

### Week 4: Monitoring
- Set up APM (New Relic, Datadog)
- Add performance budgets
- Alert on performance regressions

---

## Deployment Strategy

### Development Environment
```bash
docker run -d \
  -e DB_HOST=mariadb \
  -e DB_NAME=inventory_je \
  -p 3000:3000 \
  erp:dev
```

### Staging Environment
- Same as production
- Anonymized copy of production data
- Smoke tests before production

### Production Environment
- Multiple replicas (load balanced)
- Read replicas for reporting
- Backup database (daily snapshots)
- CDN for static assets

---

## Migration Checklist (Phased Approach)

- [ ] Week 1: Deploy Validator.php + ErrorHandler.php
- [ ] Week 1: Add database indexes
- [ ] Week 1: Add error boundaries + loading states (frontend)
- [ ] Week 2: Create OrderController + OrderService
- [ ] Week 2: Create ProductController + ProductService
- [ ] Week 2: Migrate 30% of endpoints to new architecture
- [ ] Week 3: Migrate remaining 70% of endpoints
- [ ] Week 3: Create comprehensive test suite
- [ ] Week 3: Performance testing + optimization
- [ ] Week 4: Deploy to staging
- [ ] Week 4: Full integration testing
- [ ] Week 5: Cutover to production
- [ ] Week 5+: Monitoring + optimization

---

## Success Metrics

| Metric | Current | Target | Timeline |
|--------|---------|--------|----------|
| Code organization | 1 file, 4400 lines | 6 files, <1000 lines each | Week 3 |
| Test coverage | 0% | 70% | Week 4 |
| API response time (p95) | Unknown | <500ms | Week 2 |
| Security score | 5/10 | 9/10 | Week 1 |
| Uptime SLA | 95% | 99.5% | Week 5+ |

**This architecture is designed for:**
- ✅ Easy testing and maintenance
- ✅ Clear separation of concerns
- ✅ Role-based access control
- ✅ Comprehensive audit trail
- ✅ Scalability to 1M+ records
- ✅ Enterprise compliance readiness
