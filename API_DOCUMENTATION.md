# NutriSport API – REST Endpoints

Base URL (local Docker): `http://localhost:8000/api`

All JSON responses. Auth: send `Authorization: Bearer <token>`. Cart: send `X-Cart-Token: <token>` when required.

---

## Public (no auth)

### Register (customer)

- **POST** `/register`
- **Auth:** no
- **Body:**
  ```json
  {
    "name": "Jean Dupont",
    "email": "jean@example.com",
    "password": "secret",
    "password_confirmation": "secret",
    "site_id": 1
  }
  ```
- **Success (201):** `{ "user": { "id", "name", "email", "site_id" }, "token": "...", "expires_in": 21600 }`
- **Errors:** 422 validation (email exists, site_id invalid, etc.)

### Login (customer)

- **POST** `/login`
- **Auth:** no
- **Body:** `{ "email": "jean@example.com", "password": "secret" }`
- **Success (200):** `{ "user": {...}, "token": "...", "expires_in": 21600 }` (JWT 6h)
- **Errors:** 401 invalid credentials

### Product list

- **GET** `/products?site_id={id}&per_page=15&page=1`
- **Auth:** no
- **Query:** `site_id` required; `per_page`, `page` optional
- **Success (200):** `{ "data": [{ "id", "name", "price", "in_stock", "description" }], "meta": { "current_page", "per_page", "total" } }`
- **Errors:** 422 missing site_id

### Product detail

- **GET** `/products/{id}?site_id={id}`
- **Auth:** no
- **Query:** `site_id` required
- **Success (200):** `{ "data": { "id", "name", "price", "in_stock", "description" } }`
- **Errors:** 422 missing site_id, 404 product not found or no price for site

### Feeds (catalog)

- **GET** `/feeds/json` – JSON feed (id, name, in_stock, etc.)
- **GET** `/feeds/xml` – XML feed
- **Auth:** no
- **Success (200):** JSON or XML body

---

## Cart (guest or user; use X-Cart-Token)

### Add item

- **POST** `/cart/items`
- **Auth:** no (guest); optional for user
- **Headers:** `X-Cart-Token` (optional on first add; response returns token)
- **Body:** `{ "product_id": 1, "quantity": 2, "site_id": 1 }`
- **Success (201):** `{ "cart": { "items", "subtotal", "total", "item_count" } }` + header `X-Cart-Token`
- **Errors:** 422 out of stock, invalid site, product not available for site

### Get cart

- **GET** `/cart`
- **Headers:** `X-Cart-Token` required
- **Success (200):** `{ "cart": { "items", "subtotal", "total", "item_count" } }`

### Remove item

- **DELETE** `/cart/items/{product_id}`
- **Headers:** `X-Cart-Token` required
- **Success (200):** `{ "cart": {...} }`

### Clear cart

- **DELETE** `/cart`
- **Headers:** `X-Cart-Token` required
- **Success (200):** `{ "message": "Cart cleared." }`

---

## Customer (JWT 6h) – auth:api

### Logout

- **POST** `/logout`
- **Auth:** yes
- **Success (200):** `{ "message": "Logged out." }`

### Current user

- **GET** `/user`
- **Auth:** yes
- **Success (200):** `{ "user": { "id", "name", "email", "site_id" } }`

### Update profile

- **PUT** `/user/profile`
- **Auth:** yes
- **Body:** `{ "name": "...", "email": "..." }`
- **Success (200):** `{ "user": {...} }`
- **Errors:** 422 validation

### Update password

- **PUT** `/user/password`
- **Auth:** yes
- **Body:** `{ "current_password": "...", "password": "...", "password_confirmation": "..." }`
- **Success (200):** `{ "message": "..." }`
- **Errors:** 422 wrong current password or validation

### Order history

- **GET** `/orders?per_page=15&page=1`
- **Auth:** yes
- **Success (200):** `{ "data": [{ "id", "total", "status", "content", ... }], "meta": {...} }`

### Create order

- **POST** `/orders`
- **Auth:** yes
- **Headers:** `X-Cart-Token` (cart with items)
- **Body:**
  ```json
  {
    "payment_method": "bank_transfer",
    "address": {
      "full_name": "Jean Dupont",
      "address_line": "10 rue de la Paix",
      "city": "Paris",
      "country": "France"
    }
  }
  ```
- **Success (201):** `{ "order": { "id", "total", "status", "content" } }`
- **Errors:** 401 no auth, 422 cart empty or validation

### Order detail

- **GET** `/orders/{id}`
- **Auth:** yes (own order only)
- **Success (200):** `{ "order": {...} }`
- **Errors:** 403, 404

---

## Agent (BackOffice, JWT 8h) – auth:agent

### Agent login

- **POST** `/agent/login`
- **Auth:** no
- **Body:** `{ "email": "agent@...", "password": "..." }`
- **Success (200):** `{ "token": "...", "expires_in": 28800 }`
- **Errors:** 401

### Agent logout

- **POST** `/agent/logout`
- **Auth:** yes (agent)
- **Success (200):** `{ "message": "..." }`

### List orders (last 5 days, paginated)

- **GET** `/agent/orders?per_page=20&page=1`
- **Auth:** yes (agent); agent id=1 has full access
- **Success (200):** `{ "data": [{ "id", "customer_name", "total", "status", "remaining_amount" }], "meta": {...} }`
- **Errors:** 401

### Create product

- **POST** `/agent/products`
- **Auth:** yes (agent)
- **Body:** `{ "name": "Product name", "stock": 100, "prices": { "fr": 29.99, "it": 31.00, "be": 30.00 } }`
- **Success (201):** `{ "product": { "id", "name", "stock", "prices": { "fr", "it", "be" } } }`
- **Errors:** 401, 422 validation
