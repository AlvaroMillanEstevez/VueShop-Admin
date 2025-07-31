# Comandos para configurar VueShop Admin

## 1. Limpiar y recrear la base de datos completa
```bash
# Limpiar migraciones y recrear con seeders
php artisan migrate:fresh --seed

# Si necesitas configurar JWT (solo la primera vez)
php artisan jwt:secret

# Ejecutar el servidor Laravel
php artisan serve
```

## 2. Verificar que todo funciona
```bash
# Test de conexión API
curl http://localhost:8000/api/dashboard/test

# Test de login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@vueshop.com","password":"password123"}'
```

## 3. Datos generados

### Usuarios:
- **Admin:** admin@vueshop.com / password123
- **Manager:** juan@vueshop.com / password123  
- **Manager:** maria@vueshop.com / password123
- **Manager:** carlos@vueshop.com / password123

### Datos por Manager:
- 8-12 clientes únicos
- 6-9 productos únicos  
- 15-30 pedidos con fechas realistas
- Items de pedido distribuidos inteligentemente

### Características:
- ✅ **Datos únicos por usuario** - cada manager ve solo sus datos
- ✅ **Admin ve todo** - datos globales de todos los managers
- ✅ **Fechas realistas** - más pedidos recientes que antiguos
- ✅ **Estados de pedidos** - distribución realista (más entregados que pendientes)
- ✅ **Precios variados** - productos similares con precios ligeramente diferentes
- ✅ **SKUs únicos** - por usuario para evitar conflictos
- ✅ **Relaciones correctas** - productos, clientes y pedidos conectados por usuario

## 4. Estructura de archivos generada

```
database/
├── migrations/
│   ├── create_users_table.php (actualizada con roles)
│   ├── create_customers_table.php (con user_id)
│   ├── create_products_table.php (con user_id)
│   ├── create_orders_table.php (con user_id)
│   ├── create_order_items_table.php
│   └── create_login_logs_table.php
└── seeders/
    ├── UserSeeder.php (4 usuarios)
    ├── CustomerSeeder.php (datos por usuario)
    ├── ProductSeeder.php (datos por usuario)
    ├── OrderSeeder.php (datos por usuario)
    └── DatabaseSeeder.php (orquestador)
```