# Plan Técnico — E-commerce Sistema Lumen

---

## Arquitectura General

```
┌─────────────────────────┐         ┌─────────────────────────┐
│   Sistema Lumen         │         │   Hosting E-commerce     │
│   (Laravel + MySQL)     │ ──────► │   (Next.js + PostgreSQL)│
│   Puerto 8084           │  Cron   │   Puerto 3000            │
│   DB: lumensistema      │  sync   │   DB: lumen_ecommerce    │
└─────────────────────────┘         └─────────────────────────┘
```

---

## Tech Stack

| Capa | Herramienta |
|---|---|
| Frontend | Next.js 14 (App Router), Tailwind CSS, TypeScript |
| API | Next.js API Routes |
| Base de datos | PostgreSQL + Prisma ORM |
| Auth (admin) | NextAuth.js |
| Email | Nodemailer / Resend |
| Hosting | Vercel (frontend) + Railway/Render (DB) o VPS propio |
| Sync | Cron job en Lumen → POST a API e-commerce |

---

## Estructura de Base de Datos (E-commerce)

### Tablas principales

```sql
-- Categorías de productos
categories
├── id (PK, UUID)
├── lumen_id (INT, nullable) -- ID en Sistema Lumen
├── name (VARCHAR)
├── slug (VARCHAR, UNIQUE)
├── description (TEXT, nullable)
├── image_url (VARCHAR, nullable)
├── parent_id (UUID, nullable, self-ref)
├── is_active (BOOLEAN)
├── created_at / updated_at

-- Marcas
brands
├── id (PK, UUID)
├── lumen_id (INT, nullable)
├── name (VARCHAR)
├── slug (VARCHAR, UNIQUE)
├── logo_url (VARCHAR, nullable)
├── is_active (BOOLEAN)
├── created_at / updated_at

-- Productos
products
├── id (PK, UUID)
├── lumen_id (INT) -- ID del producto en Sistema Lumen (único para sync)
├── sku (VARCHAR, UNIQUE)
├── name (VARCHAR)
├── slug (VARCHAR)
├── description (TEXT, nullable) -- product_details de Lumen
├── price (DECIMAL 10,2)
├── compare_at_price (DECIMAL 10,2, nullable) -- precio "antes"
├── cost (DECIMAL 10,2, nullable) -- costo de Lumen
├── qty (INT, DEFAULT 0) -- stock total
├── image_url (VARCHAR, nullable)
├── images_url (JSONB, DEFAULT '[]') -- galería
├── category_id (UUID, FK)
├── brand_id (UUID, FK, nullable)
├── is_featured (BOOLEAN, DEFAULT false)
├── is_promotion (BOOLEAN, DEFAULT false)
├── promotion_price (DECIMAL 10,2, nullable)
├── specs (JSONB, DEFAULT '{}') -- { lumens, efficiency, cct, voltage, ip_rating... }
├── tags (JSONB, DEFAULT '[]') -- ["outdoor", "dimmable"]
├── certifications (JSONB, DEFAULT '[]') -- ["UL", "DLC"]
├── weight (VARCHAR, nullable)
├── dimensions (VARCHAR, nullable)
├── upc (VARCHAR, nullable)
├── mpn (VARCHAR, nullable)
├── is_active (BOOLEAN, DEFAULT true)
├── synced_at (TIMESTAMP) -- última sync
├── created_at / updated_at

-- Variantes de producto
product_variants
├── id (PK, UUID)
├── product_id (UUID, FK)
├── lumen_variant_id (INT, nullable)
├── sku (VARCHAR)
├── name (VARCHAR) -- ej: "30W / 3000K / 120-277V"
├── price (DECIMAL 10,2) -- puede diferir del producto principal
├── qty (INT, DEFAULT 0)
├── attributes (JSONB) -- { wattage: "30W", cct: "3000K", voltage: "120-277V" }
├── is_active (BOOLEAN, DEFAULT true)
├── created_at / updated_at

-- Órdenes / Cotizaciones
quotes
├── id (PK, UUID)
├── quote_number (VARCHAR, UNIQUE)
├── customer_name (VARCHAR)
├── customer_email (VARCHAR)
├── customer_phone (VARCHAR, nullable)
├── customer_company (VARCHAR, nullable)
├── shipping_address (TEXT, nullable)
├── subtotal (DECIMAL 10,2)
├── notes (TEXT, nullable)
├── status (ENUM: pending, reviewed, responded, closed)
├── created_at / updated_at

-- Items de cotización
quote_items
├── id (PK, UUID)
├── quote_id (UUID, FK)
├── product_id (UUID, FK, nullable)
├── variant_id (UUID, FK, nullable)
├── sku (VARCHAR)
├── name (VARCHAR)
├── price (DECIMAL 10,2)
├── qty (INT)
├── attributes (JSONB, nullable)
├── created_at

-- Configuraciones del sitio
settings
├── id (PK)
├── key (VARCHAR, UNIQUE)
├── value (TEXT)
├── updated_at
```

---

## API Endpoints

### Productos (públicos)
```
GET /api/products                    -- Lista con filtros, pagination
GET /api/products/[id]               -- Detalle con variantes
GET /api/products/featured           -- Productos destacados
GET /api/products/search?q=          -- Búsqueda
```

### Catálogo
```
GET /api/categories                 -- Lista de categorías
GET /api/categories/[slug]          -- Categoría con productos
GET /api/brands                     -- Lista de marcas
```

### Carrito (no requiere auth, solo localStorage)

### Cotizaciones
```
POST /api/quotes                    -- Crear cotización
GET /api/quotes/[id]               -- Ver cotización (con token)
```

### Sync (protegido con API key)
```
POST /api/sync/lumen                -- Recibir datos de Lumen
GET  /api/sync/status              -- Estado de última sync
POST /api/sync/trigger             -- Forzar sync (admin)
```

---

## Páginas del Frontend

```
/                           -- Home
/catalog                    -- Catálogo con filtros
/catalog/[category]         -- Filtrado por categoría
/products/[id]              -- Detalle de producto
/cart                       -- Carrito
/quote-request               -- Formulario de cotización
/contact                    -- Contacto
/quote/[id]                 -- Ver cotización (cliente)
/admin                      -- Panel admin (auth requerido)
/admin/quotes               -- Gestión de cotizaciones
/admin/orders               -- Gestión de pedidos
/admin/sync                 -- Estado de sincronización
/admin/settings             -- Configuraciones
```

---

## Componentes UI

| Componente | Descripción |
|---|---|
| `ProductCard` | Card: imagen, nombre, SKU, precio, stock, botón |
| `ProductGrid` | Grid responsive de ProductCards |
| `FilterSidebar` | Filtros: categoría, marca, wattage, CCT, voltage, certificación, rango precio |
| `VariantSelector` | Selectores radio/select para variantes, actualiza precio y stock |
| `ImageGallery` | Galería con thumbnails y lightbox |
| `ProductSpecs` | Tabla de especificaciones técnicas |
| `SearchBar` | Búsqueda con sugerencias |
| `CartDrawer` | Carrito deslizable (slide-out) |
| `QuoteForm` | Formulario B2B de solicitud de cotización |
| `PriceDisplay` | Muestra precio actual / tachado si hay descuento |
| `StockBadge` | Badge: "In Stock", "Low Stock", "Out of Stock" |
| `Breadcrumb` | Navegación de migas de pan |
| `Pagination` | Paginación del catálogo |
| `SortSelect` | Ordenamiento |

---

## Flujo de Sincronización

### Desde Sistema Lumen (cada X horas):

```bash
# Lumen ejecuta un script que:
# 1. Hace query a la DB de productos, categorías, marcas
# 2. Genera JSON con todos los datos
# 3. Hace POST a https://ecommerce.com/api/sync/lumen

curl -X POST https://ecommerce.com/api/sync/lumen \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: $SYNC_API_KEY" \
  -d '{
    "products": [...],
    "categories": [...],
    "brands": [...]
  }'
```

### El endpoint `/api/sync/lumen`:
1. Valida API key
2. Por cada producto: `upsert` (insert or update)
3. Por cada categoría: `upsert`
4. Por cada marca: `upsert`
5. Actualiza `synced_at` en todos los registros
6. Registra log de sync
7. Responde con `{ success: true, synced: N }`

### Mapeo de datos Lumen → E-commerce:

```javascript
// Producto Lumen
{
  id: 123,
  code: "SBC12-AL",
  name: "SBC12 Area Light",
  price: 149.99,
  cost: 89.99,
  qty: 250,
  image: "/storage/products/sbc12.jpg",
  product_details: "<p>Perfect for parking lots...</p>",
  category_id: 5,
  brand_id: 2,
  is_variant: true,
  featured: 1,
  promotion: 0,
  // variants de product_variants table...
}

// Mapea a:
{
  lumen_id: 123,
  sku: "SBC12-AL",
  name: "SBC12 Area Light",
  price: 149.99,
  cost: 89.99,
  qty: 250,
  image_url: "/storage/products/sbc12.jpg", // o URL absoluta
  description: "<p>Perfect for parking lots...</p>",
  category_lumen_id: 5,
  brand_lumen_id: 2,
  is_featured: true,
  is_promotion: false,
  // variants mapeados en tabla separate
}
```

---

## Campos de Producto Lumen — Mapeo

| Campo Lumen | Campo E-commerce | Notas |
|---|---|---|
| `id` | `lumen_id` | ID único para sync |
| `code` | `sku` | SKU del producto |
| `name` | `name` | — |
| `price` | `price` | — |
| `cost` | `cost` | — |
| `qty` | `qty` | Stock total (suma variantes) |
| `image` | `image_url` | URL de imagen |
| `product_details` | `description` | HTML permitido |
| `category_id` | `category_lumen_id` | Se resuelve a category_id |
| `brand_id` | `brand_lumen_id` | Se resuelve a brand_id |
| `featured` | `is_featured` | Boolean |
| `promotion` | `is_promotion` | Boolean |
| `promotion_price` | `promotion_price` | — |
| `is_active` | `is_active` | Solo productos activos |
| `is_variant` | → `product_variants` | Tabla separada |

---

## Mejoras Sugeridas en Sistema Lumen

Para que la sync sea más completa, considerar agregar en Lumen:

1. **Campo `upc`** en products — código de barras oficial
2. **Campo `mpn`** en products — part number del fabricante
3. **Campo `weight`** y **`dimensions`** — para shipping
4. **Campo `specs`** (JSON) — specs técnicas como JSON
5. **Múltiples imágenes** — actualmente solo 1 imagen
6. **Relación directa category.name** a API export

---

## Autenticación Admin

- NextAuth.js con credentials provider
- Email + contraseña (del admin de Lumen)
- Roles: admin, sales
- Protege rutas `/admin/*`
- Dashboard muestra:
  - Cotizaciones pendientes
  - Último sync status
  - Quick actions

---

## Deployment Sugerido

### Opción A: Todo en VPS propio
```
VPS (Ubuntu 22.04)
├── Nginx (reverse proxy)
│   ├── lumen-ecommerce.com.br:443 → Next.js (port 3000)
│   └── api.lumen-ecommerce.com.br:443 → API routes
├── PostgreSQL 15
├── PM2 (process manager para Next.js)
└── Cron (script de sync desde Lumen)
```

### Opción B: Híbrido (recomendado)
```
Vercel (o Railway)
├── Next.js frontend + API
└── PostgreSQL (Railway)

VPS Existente (Sistema Lumen)
└── Script de sync (PHP/Node)
```

---

## Tasks / TODO por fase

### Fase 1: Setup
- [ ] Inicializar Next.js project
- [ ] Configurar Tailwind + TypeScript
- [ ] Configurar PostgreSQL + Prisma
- [ ] Crear Prisma schema completo
- [ ] Ejecutar migraciones

### Fase 2: Backend API
- [ ] API: products (list, detail, search, featured)
- [ ] API: categories, brands
- [ ] API: quotes (create)
- [ ] API: sync/lumen (upsert products)
- [ ] Seed data de prueba

### Fase 3: Frontend público
- [ ] Layout + Header + Footer
- [ ] Página Home
- [ ] Catálogo con filtros
- [ ] Página producto (con variantes)
- [ ] Carrito (Context/Zustand)
- [ ] Quote request form
- [ ] Página contacto

### Fase 4: Admin
- [ ] Setup NextAuth
- [ ] Dashboard admin
- [ ] Gestión de quotes
- [ ] Página de status sync
- [ ] Logs de sync

### Fase 5: Sync
- [ ] Endpoint POST /api/sync/lumen
- [ ] Script en Lumen (export JSON)
- [ ] Configurar cron de sync
- [ ] Manejo de errores y retries
- [ ] Notificaciones de fallo de sync

### Fase 6: Polish
- [ ] Responsive mobile
- [ ] SEO (meta tags, sitemap, OG images)
- [ ] Performance (next/image, lazy loading)
- [ ] Testing E2E
- [ ] Deploy producción
