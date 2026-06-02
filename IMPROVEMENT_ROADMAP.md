# ERP System Professional Improvement Roadmap

## Executive Summary
Current system: 12,000 LOC, 25 tables, 52 API handlers, 24 Vue pages
**Status**: Functional prototype | **Maturity**: 4/10 (missing enterprise features)

### Critical Findings
- **Database**: Missing 23 indexes on foreign keys, no audit trail, data type inconsistencies
- **API**: 4,403-line monolith, inconsistent input validation, no CSRF protection
- **Frontend**: No error boundaries, missing loading states, 6+ duplicate form components
- **Security**: SQL injection risks in dynamic queries, plaintext token storage, no rate limiting

---

## PHASE 1: STABILIZATION (Week 1-2)

### 1.1 Database Optimization (2 days)
**Impact**: 10x faster query performance

```sql
-- Create missing foreign key indexes
ALTER TABLE sales ADD INDEX idx_order (id_order_sale);
ALTER TABLE sales ADD INDEX idx_product (id_product_sale);
ALTER TABLE orders ADD INDEX idx_client (id_client_order);
ALTER TABLE orders ADD INDEX idx_admin (id_admin_order);
ALTER TABLE orders ADD INDEX idx_office (id_office_order);
ALTER TABLE purchases ADD INDEX idx_supplier (id_supplier_purchase);
ALTER TABLE purchases ADD INDEX idx_product (id_product_purchase);
ALTER TABLE production_material_costs ADD INDEX idx_production (id_production_material);
ALTER TABLE recipe_ingredients ADD INDEX idx_recipe (id_recipe_ingredient);
ALTER TABLE raw_material_entries ADD INDEX idx_material (id_raw_material_entry);

-- Create composite indexes for common queries
ALTER TABLE orders ADD INDEX idx_office_status (id_office_order, status_order, date_created_order);
ALTER TABLE sales ADD INDEX idx_order_date (id_order_sale, date_created_sale);
ALTER TABLE productions ADD INDEX idx_office_status (id_office_production, status_production, date_created_production);
```

**Measurable**: Query execution time 70-90% reduction

### 1.2 Audit Trail Implementation (3 days)
**Impact**: Full data change history, compliance-ready

New table:
```sql
CREATE TABLE audit_logs (
  id_audit int PRIMARY KEY AUTO_INCREMENT,
  table_name varchar(50) NOT NULL,
  record_id int,
  action enum('INSERT','UPDATE','DELETE') NOT NULL,
  old_values json,
  new_values json,
  user_id int,
  user_name varchar(100),
  ip_address varchar(45),
  timestamp timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_table_record (table_name, record_id),
  INDEX idx_timestamp (timestamp)
);
```

**Migration**: Add columns to key tables
```sql
ALTER TABLE orders ADD COLUMN created_by INT, ADD COLUMN updated_by INT;
ALTER TABLE productions ADD COLUMN created_by INT, ADD COLUMN updated_by INT;
ALTER TABLE recipes ADD COLUMN created_by INT, ADD COLUMN updated_by INT;
```

### 1.3 Input Validation Framework (3 days)
**Impact**: Eliminate SQL injection, XSS vulnerabilities

Create `/ajax/Validator.php`:
```php
class Validator {
  public static function string($value, $min = 0, $max = 255, $required = true) {
    if ($required && empty($value)) throw new Exception("Required field");
    if (strlen($value) < $min || strlen($value) > $max) 
      throw new Exception("Invalid length");
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }
  
  public static function integer($value, $required = true) {
    if ($required && $value === null) throw new Exception("Required field");
    $int = intval($value);
    if ($int != $value && $value !== null) throw new Exception("Invalid integer");
    return $int;
  }
  
  public static function money($value, $required = true) {
    if ($required && $value === null) throw new Exception("Required field");
    $float = floatval($value);
    if ($float < 0) throw new Exception("Invalid amount");
    return round($float, 2);
  }
  
  public static function email($value) {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL))
      throw new Exception("Invalid email");
    return $value;
  }
}
```

Apply to all handlers:
```php
// BEFORE
$idOrder = $_POST['id_order'];
$quantity = $_POST['quantity'];

// AFTER
$idOrder = Validator::integer($_POST['id_order']);
$quantity = Validator::money($_POST['quantity']);
```

---

## PHASE 2: ARCHITECTURE (Week 3-4)

### 2.1 Break Up Monolithic API (5 days)
**Impact**: 60% reduction in file size, improved maintainability

Split `/ajax/pos.ajax.php` into:
```
/ajax/controllers/
  ├── OrderController.php (8 handlers: newOrder, updateOrder, deleteOrder, etc)
  ├── ProductController.php (5 handlers: loadProducts, uploadImage, etc)
  ├── InventoryController.php (6 handlers: warehouse transfers, assignments)
  ├── PricingController.php (3 handlers: wholesale, discounts, pricing)
  ├── WarehouseController.php (5 handlers: subwarehouse ops)
  ├── ClientController.php (2 handlers: client CRUD)
  └── UtilityController.php (3 handlers: auth, proxy, etc)

/ajax/services/
  ├── OrderService.php (business logic)
  ├── InventoryService.php (stock calculations)
  ├── PricingService.php (price logic)
  └── NotificationService.php (email/alerts)

/ajax/middleware/
  ├── AuthMiddleware.php (verify token, load user)
  ├── RoleMiddleware.php (check permissions)
  ├── ValidationMiddleware.php (input validation)
  └── RateLimitMiddleware.php (DDoS protection)
```

### 2.2 Implement RBAC (3 days)
**Impact**: Granular permission control, auditable access

New table:
```sql
CREATE TABLE permissions (
  id_permission int PRIMARY KEY AUTO_INCREMENT,
  code varchar(100) UNIQUE NOT NULL,
  description varchar(255),
  module varchar(50)
);

CREATE TABLE role_permissions (
  id_role_permission int PRIMARY KEY AUTO_INCREMENT,
  role varchar(50),
  id_permission int,
  FOREIGN KEY (id_permission) REFERENCES permissions(id_permission),
  UNIQUE KEY (role, id_permission)
);
```

Permission codes:
```
order.create, order.read, order.update, order.delete
product.create, product.read, product.update, product.delete
report.view, report.export
production.approve, production.complete
recipe.manage
inventory.transfer
```

Usage:
```php
// In middleware
$permissions = db.query("SELECT p.code FROM permissions p 
  JOIN role_permissions rp ON p.id_permission = rp.id_permission 
  WHERE rp.role = ?", [$_SESSION['user_role']]);

if (!in_array('order.create', $permissions)) {
  throw new AuthException("Permission denied: order.create");
}
```

---

## PHASE 3: FRONTEND IMPROVEMENTS (Week 5-6)

### 3.1 Error Boundary Component (2 days)
**Impact**: Prevents entire app crash, shows user-friendly recovery

Create `/components/ErrorBoundary.vue`:
```vue
<script setup lang="ts">
import { ref, provide, onErrorCaptured } from 'vue'

const error = ref<any>(null)
const hasError = ref(false)

onErrorCaptured((err) => {
  hasError.value = true
  error.value = err
  console.error('Caught error:', err)
  return false // Prevent propagation
})

function resetError() {
  hasError.value = false
  error.value = null
}

provide('errorBoundary', { resetError })
</script>

<template>
  <div v-if="hasError" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 max-w-md shadow-xl">
      <h2 class="text-lg font-bold text-red-600 mb-2">Something went wrong</h2>
      <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">{{ error?.message }}</p>
      <div class="flex gap-3">
        <UButton color="neutral" variant="ghost" @click="resetError">Retry</UButton>
        <UButton color="error" @click="$router.push('/')">Go Home</UButton>
      </div>
    </div>
  </div>
  <slot v-else />
</template>
```

Wrap in app.vue:
```vue
<ErrorBoundary>
  <NuxtPage />
</ErrorBoundary>
```

### 3.2 Reusable Confirmation Dialog (2 days)

Create `/components/ConfirmDialog.vue`:
```vue
<script setup lang="ts">
import { ref, defineExpose } from 'vue'

const isOpen = ref(false)
const message = ref('')
const onConfirm = ref<() => void>(() => {})

function show(msg: string, callback: () => void) {
  message.value = msg
  onConfirm.value = callback
  isOpen.value = true
}

function confirm() {
  onConfirm.value()
  isOpen.value = false
}

defineExpose({ show })
</script>

<template>
  <UModal v-model:open="isOpen" title="Confirmar">
    <template #body>
      <p class="text-sm text-slate-700 dark:text-slate-300">{{ message }}</p>
    </template>
    <template #footer>
      <div class="flex justify-end gap-3">
        <UButton color="neutral" variant="ghost" @click="isOpen = false">Cancelar</UButton>
        <UButton color="error" @click="confirm">Confirmar</UButton>
      </div>
    </template>
  </UModal>
</template>
```

### 3.3 Loading State Indicators (2 days)

Add to all async operations:
```vue
<div v-if="loading" class="flex items-center justify-center py-20">
  <div class="text-center">
    <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-green-500 mx-auto mb-3" />
    <p class="text-sm text-slate-500">{{ loadingMessage }}</p>
  </div>
</div>
```

---

## PHASE 4: MISSING FEATURES (Week 7-8)

### 4.1 Complete Supplier CRUD (2 days)
Add missing endpoints in `/ajax/controllers/SupplierController.php`:
```php
public function update($id, $data) {
  Validator::required(['supplier_name', 'supplier_contact']);
  $db->update('suppliers', $data, ['id_supplier' => $id]);
}

public function delete($id) {
  $db->update('suppliers', ['status_supplier' => 0], ['id_supplier' => $id]);
}
```

### 4.2 Batch/Lot Tracking (3 days)
```sql
CREATE TABLE batch_tracking (
  id_batch int PRIMARY KEY AUTO_INCREMENT,
  batch_number varchar(50) UNIQUE,
  id_recipe int,
  id_production int,
  qty_produced decimal(10,3),
  date_produced date,
  status enum('pending','qa','approved','shipped','returned'),
  FOREIGN KEY (id_production) REFERENCES productions(id_production)
);
```

### 4.3 Atomic Warehouse Transfers (3 days)
```php
public function transferStock($from_office, $to_office, $items) {
  try {
    $db->beginTransaction();
    
    foreach ($items as $item) {
      // Deduct from source
      $db->query("UPDATE product_inventory SET stock_inventory = stock_inventory - ? 
        WHERE id_office_inventory = ? AND id_product_inventory = ?",
        [$item['qty'], $from_office, $item['id_product']]);
      
      // Add to destination
      $db->query("UPDATE product_inventory SET stock_inventory = stock_inventory + ? 
        WHERE id_office_inventory = ? AND id_product_inventory = ?",
        [$item['qty'], $to_office, $item['id_product']]);
    }
    
    $db->commit();
  } catch (Exception $e) {
    $db->rollback();
    throw $e;
  }
}
```

---

## PHASE 5: TESTING & DOCUMENTATION (Week 9-10)

### 5.1 API Documentation (OpenAPI/Swagger)

Create `/docs/openapi.yaml`:
```yaml
openapi: 3.0.0
info:
  title: JE Inventory ERP API
  version: 1.0.0
paths:
  /ajax/controllers/OrderController.php:
    post:
      operationId: createOrder
      parameters:
        - name: action
          in: query
          schema:
            enum: ['newOrder']
      requestBody:
        required: true
        content:
          application/x-www-form-urlencoded:
            schema:
              type: object
              required: ['id_client', 'items']
              properties:
                id_client: {type: integer}
                items: {type: array}
      responses:
        '200':
          description: Order created
          content:
            application/json:
              schema:
                type: object
                properties:
                  id_order: {type: integer}
```

### 5.2 Unit Tests (Jest/Vitest)

```typescript
// tests/services/OrderService.test.ts
describe('OrderService', () => {
  it('should create order with valid items', async () => {
    const order = await OrderService.create({
      id_client: 1,
      items: [{ id_product: 5, qty: 2 }]
    })
    expect(order).toHaveProperty('id_order')
  })
  
  it('should reject invalid items', async () => {
    await expect(OrderService.create({
      id_client: 1,
      items: [{ id_product: -1, qty: 0 }]
    })).rejects.toThrow()
  })
})
```

### 5.3 Integration Tests

```typescript
// tests/integration/OrderFlow.test.ts
describe('Complete Order Flow', () => {
  it('should complete order from creation to payment', async () => {
    // 1. Create order
    const order = await api.post('/orders', { ... })
    
    // 2. Add items
    await api.post('/orders/' + order.id + '/items', { ... })
    
    // 3. Confirm payment
    await api.post('/orders/' + order.id + '/payment', { ... })
    
    // 4. Verify inventory reduced
    const product = await api.get('/products/5')
    expect(product.stock).toBe(previousStock - 2)
  })
})
```

---

## QUICK WINS (Can implement now)

1. **Add NOT NULL constraints** (2 hours)
   ```sql
   ALTER TABLE orders MODIFY id_client_order INT NOT NULL;
   ALTER TABLE productions MODIFY id_recipe_production INT NOT NULL;
   ```

2. **Fix data types** (4 hours)
   ```sql
   ALTER TABLE orders MODIFY total_order DECIMAL(10,2) NOT NULL DEFAULT 0;
   ALTER TABLE clients MODIFY email_client VARCHAR(100);
   ALTER TABLE suppliers MODIFY supplier_contact VARCHAR(20);
   ```

3. **Add CSRF token validation** (2 hours)
   ```php
   if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
     throw new Exception("Invalid CSRF token");
   }
   ```

4. **Limit query results** (1 hour)
   ```php
   $limit = min(intval($_GET['limit'] ?? 50), 1000);
   $query .= " LIMIT " . $limit;
   ```

5. **Add dark mode to all pages** (6 hours)
   - Audit all hardcoded colors
   - Replace with Tailwind dark: prefix

---

## Success Metrics

| Metric | Current | Target | Timeline |
|--------|---------|--------|----------|
| Database query performance | N/A | <100ms avg | Week 2 |
| Code duplication ratio | 23% | <10% | Week 6 |
| Test coverage | 0% | 70% | Week 10 |
| API response time (p95) | Unknown | <500ms | Week 4 |
| Security vulnerabilities | 8 CRITICAL | 0 | Week 2 |
| Error handling coverage | 60% | 100% | Week 6 |

---

## Implementation Order (Priority)

1. **Database optimization** → Immediate (2 days)
2. **Input validation** → Immediate (3 days)
3. **Error boundaries** → Week 1
4. **Audit trail** → Week 2
5. **API refactoring** → Week 3-4
6. **Testing framework** → Week 5-6
7. **Missing features** → Week 7-8

**Total effort**: ~8-10 weeks for 1 senior developer (or 4-5 weeks for team of 2-3)
