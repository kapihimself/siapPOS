PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS businesses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    username TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'manager', 'cashier')),
    pin_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(business_id, username),
    FOREIGN KEY (business_id) REFERENCES businesses(id)
);

CREATE TABLE IF NOT EXISTS outlets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    address TEXT,
    phone TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id)
);

CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    business_name TEXT NOT NULL,
    outlet_name TEXT NOT NULL DEFAULT 'Outlet Utama',
    active_template TEXT NOT NULL DEFAULT 'retail' CHECK (active_template IN ('fnb', 'service', 'retail', 'kelontong_bangunan')),
    onboarding_completed INTEGER NOT NULL DEFAULT 0 CHECK (onboarding_completed IN (0, 1)),
    pb1_rate REAL NOT NULL DEFAULT 10,
    currency_code TEXT NOT NULL DEFAULT 'IDR',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id)
);

CREATE TABLE IF NOT EXISTS contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('customer', 'supplier', 'both')),
    name TEXT NOT NULL,
    email TEXT,
    phone TEXT,
    address TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id)
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id)
);

CREATE TABLE IF NOT EXISTS brands (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id)
);

CREATE TABLE IF NOT EXISTS units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    short_name TEXT NOT NULL,
    allow_decimal INTEGER NOT NULL DEFAULT 0 CHECK (allow_decimal IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id)
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    category_id INTEGER,
    brand_id INTEGER,
    unit_id INTEGER NOT NULL,
    type TEXT NOT NULL DEFAULT 'single' CHECK (type IN ('single', 'variable', 'combo')),
    is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

CREATE TABLE IF NOT EXISTS product_variations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS variations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    product_variation_id INTEGER,
    sku TEXT NOT NULL,
    name TEXT NOT NULL,
    sell_price_inc_tax_cents INTEGER NOT NULL CHECK (sell_price_inc_tax_cents >= 0),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(sku),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variation_id) REFERENCES product_variations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS variation_location_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    variation_id INTEGER NOT NULL,
    outlet_id INTEGER NOT NULL,
    qty_available REAL NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(variation_id, outlet_id),
    FOREIGN KEY (variation_id) REFERENCES variations(id) ON DELETE CASCADE,
    FOREIGN KEY (outlet_id) REFERENCES outlets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    order_number TEXT NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('draft', 'checked_out', 'cancelled')),
    subtotal_cents INTEGER NOT NULL,
    discount_type TEXT NOT NULL CHECK (discount_type IN ('none', 'percent', 'fixed')),
    discount_value REAL NOT NULL DEFAULT 0,
    discount_cents INTEGER NOT NULL DEFAULT 0,
    tax_rate REAL NOT NULL DEFAULT 10,
    tax_cents INTEGER NOT NULL DEFAULT 0,
    total_cents INTEGER NOT NULL,
    payment_method TEXT NOT NULL CHECK (payment_method IN ('cash', 'qris')),
    cash_received_cents INTEGER,
    change_cents INTEGER NOT NULL DEFAULT 0,
    contact_id INTEGER,
    created_by INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(business_id, order_number),
    FOREIGN KEY (business_id) REFERENCES businesses(id),
    FOREIGN KEY (contact_id) REFERENCES contacts(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS order_lines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    variation_id INTEGER NOT NULL,
    product_name TEXT NOT NULL,
    qty REAL NOT NULL CHECK (qty > 0),
    unit_price_cents INTEGER NOT NULL CHECK (unit_price_cents >= 0),
    line_total_cents INTEGER NOT NULL CHECK (line_total_cents >= 0),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (variation_id) REFERENCES variations(id)
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL,
    user_id INTEGER,
    action TEXT NOT NULL,
    context_json TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_order_lines_order_id ON order_lines(order_id);
CREATE INDEX IF NOT EXISTS idx_products_name ON products(name);
CREATE INDEX IF NOT EXISTS idx_variations_sku ON variations(sku);
CREATE INDEX IF NOT EXISTS idx_contacts_name ON contacts(name);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs(created_at);
