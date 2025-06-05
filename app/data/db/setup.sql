CREATE TABLE IF NOT EXISTS payments (
id INTEGER PRIMARY KEY AUTOINCREMENT,
amount INTEGER,
currency TEXT,
customer TEXT,
intent TEXT,
created_at TIMESTAMP DEFAULT (strftime('%s',CURRENT_TIMESTAMP,'localtime'))
);

CREATE TABLE IF NOT EXISTS mail (
id INTEGER PRIMARY KEY AUTOINCREMENT,
mail_to TEXT,
mail_from TEXT,
subject TEXT,
created_at TIMESTAMP DEFAULT (strftime('%s',CURRENT_TIMESTAMP,'localtime'))
);

CREATE TABLE IF NOT EXISTS api (
id INTEGER PRIMARY KEY AUTOINCREMENT,
api_key TEXT,
api_slug TEXT,
api_usage INTEGER DEFAULT 0,
api_allowed_domains TEXT,
status INTEGER DEFAULT 1,
created_by TEXT,
created_at TIMESTAMP DEFAULT (strftime('%s',CURRENT_TIMESTAMP,'localtime'))
);

CREATE TABLE IF NOT EXISTS permissions (
id INTEGER PRIMARY KEY AUTOINCREMENT,
name TEXT,
def INTEGER DEFAULT 0
);

INSERT INTO permissions (id, name, def)
VALUES (1, 'Access', 1);
INSERT INTO permissions (id, name, def)
VALUES (2, 'Database', 1);
INSERT INTO permissions (id, name, def)
VALUES (3, 'Payments', 1);
INSERT INTO permissions (id, name, def)
VALUES (4, 'Email', 1);
INSERT INTO permissions (id, name, def)
VALUES (5, 'API', 1);
INSERT INTO permissions (id, name, def)
VALUES (6, 'Files', 1);
INSERT INTO permissions (id, name, def)
VALUES (7, 'Integrations', 1);
INSERT INTO permissions (id, name, def)
VALUES (8, 'Settings', 1);
INSERT INTO permissions (id, name, def)
VALUES (9, 'Maintenance & Support', 1);

CREATE TABLE IF NOT EXISTS roles (
id INTEGER PRIMARY KEY AUTOINCREMENT,
name TEXT,
description TEXT,
access TEXT,
color TEXT,
def INTEGER DEFAULT 0,
is_admin INTEGER DEFAULT 0
);

INSERT INTO roles (id, name, description, access, color, def, is_admin)
VALUES (1, 'Admin', NULL, '*', NULL, 1, 1);

CREATE TABLE IF NOT EXISTS site (
id INTEGER PRIMARY KEY AUTOINCREMENT,
site_name TEXT,
site_title TEXT,
site_description TEXT,
site_keywords TEXT,
site_logo TEXT,
smtp_host TEXT,
smtp_mail TEXT,
smtp_port TEXT,
smtp_scheme TEXT,
smtp_user TEXT,
smtp_pass TEXT,
stripe_status INTEGER DEFAULT 0,
stripe_pk TEXT,
stripe_sk TEXT,
paypal_status INTEGER DEFAULT 0,
paypal_pk TEXT,
paypal_sk TEXT,
uri_backend TEXT,
uri_auth TEXT,
base_url TEXT,
color_primary TEXT,
color_primary_text TEXT,
color_dark TEXT,
color_light TEXT,
color_dark_secondary TEXT,
color_light_secondary TEXT,
setup_wizzard INTEGER DEFAULT 1,
enable_frontend INTEGER DEFAULT 1,
enable_api INTEGER DEFAULT 1,
enable_register INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS users (
id INTEGER PRIMARY KEY AUTOINCREMENT,
user_id TEXT,
displayName TEXT,
avatar TEXT,
phone TEXT,
email TEXT,
bio TEXT,
address TEXT,
dob TEXT,
crypt TEXT,
confirmed INTEGER DEFAULT 0,
nonce TEXT,
role INTEGER DEFAULT 0,
ip_address TEXT,
last_online TEXT,
login_alerts INTEGER DEFAULT 0,
login_try_count INTEGER DEFAULT 0,
login_last_try INTEGER DEFAULT 0,
login_count INTEGER DEFAULT 0,
passkey INTEGER DEFAULT 0,
passkey_id TEXT,
pin INTEGER DEFAULT 0,
pin_code TEXT,
is_admin INTEGER DEFAULT 0,
is_super_admin INTEGER DEFAULT 0,
created_at TIMESTAMP,
status INTEGER DEFAULT 0
);