# Propuesta de Proyecto — Tienda Online Lumen Lighting

---

## 1. Resumen del Proyecto

Desarrollo de una tienda online B2B/B2C para la venta de productos de iluminación industrial y comercial. La plataforma se sincronizará automáticamente con el sistema de inventario existente (Sistema Lumen), manteniendo precios, stock y catálogo siempre actualizados.

---

## 2. ¿Qué se entrega?

### 2.1 Sitio Web (E-commerce)

**Páginas públicas:**
- **Inicio** — Hero con imagen principal, categorías destacadas, productos nuevos y destacados
- **Catálogo** — Listado completo de productos con filtros avanzados
- **Detalle de Producto** — Información completa, imágenes, variantes, especificaciones técnicas
- **Carrito de Compras** — Gestión de productos seleccionados
- **Solicitud de Cotización** — Formulario B2B para pedidos grandes
- **Contacto** — Formulario de contacto general

**Panel de Administración:**
- Gestión de pedidos y cotizaciones
- Historial de sincronización con Sistema Lumen
- Configuración básica del sitio

---

## 3. Funcionalidades Principales

### 3.1 Catálogo de Productos

- Visualización de productos con imagen, nombre, SKU, precio y stock
- **Filtros por:**
  - Categoría (Area Lights, High Bay, Flood, Emergency, etc.)
  - Marca
  - Potencia (Wattage): 30W, 50W, 80W, 100W, etc.
  - Temperatura de color (CCT): 3000K, 4000K, 5000K
  - Voltaje: 120-277V, 200-480V
  - Certificación: UL, DLC, Energy Star
- Búsqueda por nombre o SKU
- Ordenamiento por precio o nombre
- Productos destacados y productos en promoción

### 3.2 Página de Producto

- Galería de imágenes del producto
- Selector de variantes (Wattage / CCT / Voltaje)
- Precio y stock actualizado según variante seleccionada
- Tabla de especificaciones técnicas del producto
- Productos relacionados
- Opción de agregar a carrito o solicitar cotización

### 3.3 Carrito de Compras

- Agregar productos con cantidad
- Modificar cantidades
- Eliminar productos
- Ver subtotal
- Proceder a solicitud de cotización

### 3.4 Solicitud de Cotización (B2B)

- Formulario con datos del cliente: nombre, empresa, email, teléfono
- Lista de productos del carrito
- Campo de notas/mensaje
- Envío por correo electrónico al equipo de ventas
- Registro en base de datos para seguimiento

### 3.5 Sincronización con Sistema Lumen

La tienda se actualizará automáticamente con la información de Sistema Lumen:

| Dato | Se sincroniza desde Lumen |
|---|---|
| Nombre del producto | ✅ |
| Código / SKU | ✅ |
| Precio de venta | ✅ |
| Existencias (stock) | ✅ |
| Imágenes | ✅ |
| Descripción | ✅ |
| Categoría | ✅ |
| Marca | ✅ |
| Productos en promoción | ✅ |
| Productos destacados | ✅ |
| Variantes | ✅ |

**Frecuencia de sincronización:** configurable (sugerido: cada 6 horas o en tiempo real según necesidad)

---

## 4. Diseño y UX

### 4.1 Estilo Visual

- Diseño limpio y profesional orientado a clientes B2B (contractors, electricistas, empresas)
- Navegación clara y fácil acceso a categorías
- Mobile responsive (funciona en celular, tablet y escritorio)
- Optimizado para carga rápida de imágenes de productos

### 4.2 Flujo del Usuario

```
Visitar sitio
    ↓
Navegar catálogo / Buscar producto
    ↓
Ver detalle del producto
    ↓
Seleccionar variante (wattage/CCT/voltage)
    ↓
Agregar al carrito O Solicitar cotización
    ↓
Enviar solicitud
    ↓
Equipo de ventas recibe la notificación
```

---

## 5. Infraestructura y Tecnología

| Componente | Tecnología |
|---|---|
| Frontend del sitio | Next.js (React) |
| Base de datos | PostgreSQL |
| Sincronización | API integrada con Sistema Lumen |
| Hosting | Servidor VPS o nube (a definir) |
| Dominio | A definir con el cliente |

---

## 6. Lo que necesitamos del cliente

### 6.1 Acceso e Información

- ✅ Acceso al servidor donde está Sistema Lumen (para configurar la sincronización)
- ✅ Acceso a la base de datos de Sistema Lumen
- ✅ Listado de categorías y marcas a mostrar en el sitio
- ✅ Información de contacto de la empresa (dirección, teléfono, email)
- ✅ Logo de la empresa

### 6.2 Imágenes de Productos

- Las imágenes se tomarán del sistema existente
- Si hay productos sin imagen, se usará una imagen placeholder
- Opcional: sesión de fotos profesional de productos (recomendado para el lanzamiento)

### 6.3 Contenido

- Textos de la página "Quiénes Somos" (si aplica)
- Información de políticas de venta, envíos, devoluciones
- Datos de contacto adicionales

---

## 7. Lo que NO incluye esta propuesta

- Procesamiento de pagos en línea (PayPal, tarjeta de crédito, etc.)
- Envío automático de cotizaciones firmadas
- Integración con sistemas de contabilidad além de Sistema Lumen
- Desarrollo de app móvil
- SEO avanzado o campañas de marketing digital
- Capacitación extensa del cliente (se entrega manual básico)

---

## 8. Flujo de Sincronización

```
┌──────────────────────┐         ┌──────────────────────┐
│   Sistema Lumen      │         │   Nueva Tienda Online │
│   (Inventario)       │ ──────► │                      │
│                      │  sync   │   Catálogo público    │
│   - Productos        │ cada 6h │   Precios actualizados│
│   - Precios          │         │   Stock en tiempo real │
│   - Stock            │         │                       │
│   - Imágenes         │         │                       │
└──────────────────────┘         └──────────────────────┘
```

Los productos, precios y existencias se actualizan automáticamente. El equipo de ventas recibe las cotizaciones por email y las gestiona desde el panel.

---

## 9. Estructura de la Sincronización

### Lo que se sincroniza automáticamente:

| Campo | Sistema Lumen → Tienda |
|---|---|
| Nombre del producto | name |
| Código / SKU | code |
| Precio | price |
| Costo | cost |
| Stock actual | qty |
| Imagen | image |
| Descripción larga | product_details |
| Categoría | category_id |
| Marca | brand_id |
| ¿Está en promoción? | promotion |
| Precio promocional | promotion_price |
| ¿Es destacado? | featured |
| Variantes | product_variants |
| ¿Producto activo? | is_active |

### Lo que NO se sincroniza (gestión propia del e-commerce):

- Órdenes y cotizaciones
- Clientes registrados
- Configuración del sitio
- Contenido estático (textos, imágenes de banners)

---

## 10. Próximos Pasos

1. **Aprobación de la propuesta** — El cliente revisa y approve el plan
2. **Reunión técnica** — Definir detalles de acceso al servidor y Base de Datos
3. **Kickoff** — Inicio del desarrollo
4. **Entregas parciales** — Se muestran avances periódicamente
5. **Pruebas y ajustes** — QA con datos reales
6. **Lanzamiento** — Deploy en producción

---

## 11. Consideraciones

- El desarrollo se realiza con datos de prueba inicialmente; la sincronización real con Sistema Lumen se configura en una fase posterior
- El sitio se entrega funcional para comenzar a recibir cotizaciones mientras se afinan detalles
- Cualquier funcionalidad adicional fuera del alcance puede agregarse en una segunda fase

---

*Propuesta preparada para revisión*
