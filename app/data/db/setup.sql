CREATE TABLE IF NOT EXISTS api (
  id INTEGER NOT NULL AUTOINCREMENT,
  api_key TEXT,
  api_slug TEXT,
  api_usage INTEGER DEFAULT 0,
  api_allowed_domains TEXT,
  created_by TEXT,
  created_at TIMESTAMP DEFAULT (strftime('%s',CURRENT_TIMESTAMP,'localtime')),
  PRIMARY KEY(id)
);

CREATE TABLE IF NOT EXISTS roles (
  id INTEGER NOT NULL AUTOINCREMENT,
  name TEXT,
  description TEXT,
  access TEXT,
  color TEXT,
  is_admin INTEGER DEFAULT 0,
  PRIMARY KEY(id)
);

INSERT INTO roles (id, name, description, access, color, is_admin) 
VALUES (0, 'Member', NULL, NULL, NULL, 0);

INSERT INTO roles (id, name, description, access, color, is_admin) 
VALUES (1, 'Admin', NULL, '*', NULL, 1);

CREATE TABLE IF NOT EXISTS site (
  id INTEGER NOT NULL AUTOINCREMENT,
  site_name TEXT,
  smtp_host TEXT,
  smtp_mail TEXT,
  smtp_port TEXT,
  smtp_scheme TEXT,
  smtp_user TEXT,
  smtp_pass TEXT,
  uri_backend TEXT,
  uri_auth TEXT,
  base_url TEXT,
  setup_wizzard INTEGER DEFAULT 1,
  enable_frontend INTEGER DEFAULT 1,
  PRIMARY KEY(id)
);

CREATE TABLE IF NOT EXISTS users (
  id INTEGER NOT NULL AUTOINCREMENT,
  user_id TEXT,
  displayName TEXT,
  email TEXT,
  crypt TEXT,
  confirmed INTEGER DEFAULT 0,
  nonce TEXT,
  role INTEGER DEFAULT 0,
  created_at TIMESTAMP,
  status INTEGER DEFAULT 0,
  PRIMARY KEY(id)
);
