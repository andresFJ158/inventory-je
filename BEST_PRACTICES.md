# ERP Development Best Practices

## 1. Input Validation Pattern

### ✅ DO - Always validate input
```php
<?php
require_once 'Validator.php';

try {
    $order_id = Validator::integer($_POST['id_order'], 'Order ID', true);
    $quantity = Validator::money($_POST['quantity'], 'Quantity', true);
    $notes = Validator::string($_POST['notes'], 'Notes', 0, 500, false);
    
    // Proceed with validated data
    updateOrder($order_id, $quantity, $notes);
} catch (ValidationException $e) {
    ErrorHandler::response(422, 'Validation failed', $e->errors);
}
```

### ❌ DON'T - Raw POST access
```php
$order_id = $_POST['id_order'];  // SQL injection risk
$quantity = $_POST['quantity'];  // No type checking
```

---

## 2. Error Handling Pattern

### ✅ DO - Use centralized error handler
```php
<?php
require_once 'ErrorHandler.php';

try {
    // Perform operations
    $user = getUser($id);
    if (!$user) {
        throw new NotFoundException("User not found");
    }
    
    ErrorHandler::success($user, 'User retrieved');
} catch (Exception $e) {
    ErrorHandler::handle($e, isDevelopment: false);
}
```

### ❌ DON'T - Echo responses directly
```php
echo "error|User not found";  // Inconsistent format
```

---

## 3. Database Transaction Pattern

### ✅ DO - Atomic operations
```php
try {
    $db->beginTransaction();
    
    // Multiple related operations
    $order_id = $db->insert('orders', [...]);
    $db->insert('order_items', ['id_order' => $order_id, ...]);
    $db->update('product_inventory', [...]);
    
    $db->commit();
    ErrorHandler::success(['id_order' => $order_id]);
} catch (Exception $e) {
    $db->rollback();
    ErrorHandler::handle($e);
}
```

### ❌ DON'T - Multi-step without transaction
```php
$order_id = createOrder(...);  // If next step fails, order exists but is incomplete
insertOrderItems($order_id, ...);
updateInventory(...);
```

---

## 4. Permission/Authorization Pattern

### ✅ DO - Check permissions explicitly
```php
<?php
// In middleware/auth check
function requirePermission($permission) {
    $user_role = $_SESSION['user_role'] ?? null;
    $permissions = getPermissionsForRole($user_role);
    
    if (!in_array($permission, $permissions)) {
        throw new PermissionException("Insufficient permissions");
    }
}

// In handler
requirePermission('order.create');
createOrder(...);
```

### ❌ DON'T - Role-based only
```php
if ($_SESSION['user_role'] !== 'admin') {  // Too coarse-grained
    die("Admin only");
}
```

---

## 5. API Response Pattern

### ✅ DO - Standardized response format
```json
{
  "status": 200,
  "message": "Order created",
  "timestamp": "2026-06-01 14:30:45",
  "data": {
    "id_order": 42,
    "total": 250.50
  }
}
```

### Error response:
```json
{
  "status": 422,
  "message": "Validation failed",
  "timestamp": "2026-06-01 14:30:45",
  "details": {
    "quantity": "Quantity must be positive",
    "email": "Invalid email address"
  }
}
```

### ❌ DON'T - Inconsistent formats
```
"OK|Order 42 created"  // Pipe-delimited, hard to parse
{"error": true}  // Missing context
```

---

## 6. Component Reusability Pattern (Vue)

### ✅ DO - Extract to reusable component
```vue
<!-- components/ConfirmDialog.vue -->
<script setup lang="ts">
defineProps<{ isOpen: boolean; title: string; message: string }>()
defineEmits<{ confirm: []; cancel: [] }>()
</script>

<template>
  <UModal :open="isOpen">
    <template #body>{{ message }}</template>
    <template #footer>
      <UButton @click="$emit('cancel')">Cancel</UButton>
      <UButton color="error" @click="$emit('confirm')">Confirm</UButton>
    </template>
  </UModal>
</template>

<!-- Usage in any page -->
<ConfirmDialog
  :is-open="showConfirm"
  message="Delete this recipe?"
  @confirm="deleteRecipe"
  @cancel="showConfirm = false"
/>
```

### ❌ DON'T - Copy-paste dialogs
```vue
<!-- materialespage.vue -->
<UModal v-model:open="deleteModal">
  <template #body>Delete material?</template>
</UModal>

<!-- insumos.vue -->
<UModal v-model:open="deleteModal">
  <template #body>Delete insumo?</template>  <!-- Duplicated -->
</UModal>
```

---

## 7. Error Boundary Pattern (Vue)

### ✅ DO - Wrap critical sections
```vue
<!-- components/ErrorBoundary.vue -->
<script setup>
import { onErrorCaptured, ref } from 'vue'

const error = ref(null)

onErrorCaptured((err) => {
  error.value = err
  return false  // Prevent propagation
})
</script>

<template>
  <div v-if="error" class="error-box">
    <h2>{{ error.message }}</h2>
    <button @click="error = null">Retry</button>
  </div>
  <slot v-else />
</template>

<!-- In app.vue -->
<ErrorBoundary>
  <NuxtPage />
</ErrorBoundary>
```

---

## 8. Async Loading Pattern (Vue)

### ✅ DO - Show loading state during async operations
```vue
<script setup>
const loading = ref(false)
const data = ref([])
const error = ref(null)

async function fetchData() {
  loading.value = true
  error.value = null
  try {
    data.value = await api.getData()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>

<template>
  <div v-if="loading" class="loading">
    <UIcon name="i-lucide-loader-2" class="animate-spin" />
  </div>
  <div v-else-if="error" class="error">{{ error }}</div>
  <div v-else>
    <!-- Content -->
  </div>
</template>
```

### ❌ DON'T - Silent loading
```vue
<div>{{ data }}</div>  <!-- Blank while loading, confusing -->
```

---

## 9. Form Validation Pattern (Vue)

### ✅ DO - Real-time validation feedback
```vue
<script setup>
const form = ref({ email: '', amount: 0 })
const errors = ref({})

const isEmailValid = computed(() => {
  if (!form.value.email) return null
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)
})

async function submit() {
  try {
    const res = await api.submit(form.value)
    success('Form submitted')
  } catch (e) {
    errors.value = e.details  // From 422 response
  }
}
</script>

<template>
  <form @submit.prevent="submit">
    <div class="field">
      <input v-model="form.email" />
      <span v-if="isEmailValid === false" class="error">Invalid email</span>
    </div>
    <button :disabled="isEmailValid === false">Submit</button>
  </form>
</template>
```

---

## 10. Dark Mode Pattern (Vue)

### ✅ DO - Use Tailwind dark: prefix
```vue
<div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
  <h1 class="text-slate-800 dark:text-slate-100">Title</h1>
  <p class="text-slate-500 dark:text-slate-400">Subtitle</p>
</div>
```

### ❌ DON'T - Hardcoded colors
```vue
<div style="background: white; color: black;">  <!-- Always light mode -->
  Dark mode breaks here
</div>
```

---

## 11. Pagination Pattern

### ✅ DO - Validate limit parameter
```php
$limit = Validator::integer($_GET['limit'] ?? 50, 'Limit', true);
$limit = min($limit, 1000);  // Cap at 1000 to prevent memory issues
$offset = Validator::integer($_GET['offset'] ?? 0, 'Offset', true);

$query = "SELECT * FROM orders LIMIT ? OFFSET ?";
$results = $db->query($query, [$limit, $offset]);
```

### ❌ DON'T - Unlimited queries
```php
$results = $db->query("SELECT * FROM orders");  // Could return millions
```

---

## 12. Testing Pattern

### ✅ DO - Unit test business logic
```php
// tests/OrderServiceTest.php
class OrderServiceTest extends TestCase {
    public function testCreateOrderReducesInventory() {
        $initial_stock = 100;
        $order = OrderService::create(['item' => ['qty' => 5]]);
        
        $final_stock = Product::find(1)->stock;
        $this->assertEquals($initial_stock - 5, $final_stock);
    }
    
    public function testCreateOrderFailsWithInvalidQuantity() {
        $this->expectException(ValidationException::class);
        OrderService::create(['item' => ['qty' => -1]]);
    }
}
```

### ❌ DON'T - No tests
```php
// Hope it works
function createOrder() { ... }
```

---

## 13. Logging Pattern

### ✅ DO - Structured logging
```php
ErrorHandler::log([
    'level' => 'info',
    'event' => 'order_created',
    'order_id' => 42,
    'total' => 250.50,
    'user_id' => 5,
    'timestamp' => date('Y-m-d H:i:s')
]);
```

### ❌ DON'T - Unstructured logs
```php
error_log("Order created");  // No context
```

---

## 14. Query Optimization

### ✅ DO - Use indexes and limit results
```php
// Query with index on (office_id, status, date)
$orders = $db->query(
    "SELECT * FROM orders 
     WHERE id_office_order = ? AND status_order = ? 
     ORDER BY date_created_order DESC 
     LIMIT 50",
    [$office_id, 'pending']
);
```

### ❌ DON'T - Full table scans
```php
$orders = $db->query("SELECT * FROM orders WHERE date_created_order LIKE '%2026%'");
```

---

## 15. Code Organization

### ✅ DO - Modular structure
```
/ajax/
  ├── controllers/
  │   ├── OrderController.php
  │   ├── ProductController.php
  │   └── InventoryController.php
  ├── services/
  │   ├── OrderService.php
  │   └── PricingService.php
  ├── middleware/
  │   ├── AuthMiddleware.php
  │   └── ValidationMiddleware.php
  └── Validator.php, ErrorHandler.php
```

### ❌ DON'T - Monolithic file
```
/ajax/
  └── pos.ajax.php (4,403 lines)  <!-- Everything in one file -->
```

---

## Security Checklist

- [ ] All POST parameters validated with Validator class
- [ ] All error messages go through ErrorHandler
- [ ] CSRF tokens checked on state-changing operations
- [ ] Permissions verified before sensitive actions
- [ ] SQL queries use parameterized statements
- [ ] User input escaped before output
- [ ] Sensitive data not logged
- [ ] Transactions used for multi-step operations
- [ ] Rate limiting on expensive operations
- [ ] Sensitive endpoints require authentication

---

## Performance Checklist

- [ ] Foreign key columns have indexes
- [ ] Query LIMIT validated (max 1000)
- [ ] Pagination offset validated
- [ ] No N+1 queries in loops
- [ ] Bulk operations used for multiple items
- [ ] Long operations run in background jobs
- [ ] Database connection pooling enabled
- [ ] API responses under 5MB
- [ ] Frontend pagination implemented
- [ ] Loading states shown during async ops

---

## Code Quality Checklist

- [ ] No hardcoded values (use constants or config)
- [ ] Component extraction (< 500 lines per file)
- [ ] Consistent naming conventions
- [ ] TypeScript types for Vue components
- [ ] Dark mode support
- [ ] Accessibility (ARIA labels, color-independent)
- [ ] Error handling on all async operations
- [ ] Unit tests for business logic
- [ ] Integration tests for workflows
- [ ] API documentation (OpenAPI/Swagger)
