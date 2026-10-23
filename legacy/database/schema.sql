-- Instalação APENAS em banco novo, dedicado à beta.
-- Não migra reportStock existente. Sem FOREIGN KEY / REFERENCES / cascatas.
CREATE TABLE beta_schema (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 version INT NOT NULL, UNIQUE KEY (longId), UNIQUE KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO beta_schema (longId,version) VALUES ('beta-schema-v1',1);
CREATE TABLE companies (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 symbol VARCHAR(32) NOT NULL,
 UNIQUE KEY (longId), UNIQUE KEY (symbol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE reports (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 companies VARCHAR(100) NOT NULL DEFAULT '',
 price_date DATE NOT NULL, cutoff DATETIME NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'draft',
 created_at DATETIME NOT NULL,
 UNIQUE KEY (longId), KEY (companies)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE report_qm_endpoints (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 method VARCHAR(80) NOT NULL, fields_json TEXT NOT NULL, source_row INT NOT NULL,
 UNIQUE KEY (longId), UNIQUE KEY (method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE report_qm_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 reports VARCHAR(100) NOT NULL DEFAULT '',
 report_qm_endpoints VARCHAR(100) NOT NULL DEFAULT '',
 request_hash CHAR(64) NOT NULL, parameters_json TEXT NOT NULL,
 response_body LONGTEXT NOT NULL, response_hash CHAR(64) NOT NULL,
 http_status INT NOT NULL DEFAULT 0, status VARCHAR(30) NOT NULL,
 error_message TEXT NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME NULL,
 pagination_status VARCHAR(80) NOT NULL DEFAULT 'not_confirmed',
 UNIQUE KEY (longId), KEY (reports, request_hash, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE report_data_points (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 reports VARCHAR(100) NOT NULL DEFAULT '',
 report_qm_requests VARCHAR(100) NOT NULL DEFAULT '',
 method VARCHAR(80) NOT NULL, field_name VARCHAR(120) NOT NULL,
 locator TEXT NOT NULL, value_json LONGTEXT NOT NULL,
 entity VARCHAR(100) NOT NULL DEFAULT '', context TEXT NOT NULL,
 period_label VARCHAR(100) NOT NULL DEFAULT '', reference_date DATE NULL,
 public_at DATETIME NULL, unit VARCHAR(80) NOT NULL DEFAULT '',
 scale_value DECIMAL(20,6) NOT NULL DEFAULT 1, currency VARCHAR(10) NOT NULL DEFAULT '',
 kind VARCHAR(30) NOT NULL DEFAULT 'reported', status VARCHAR(30) NOT NULL DEFAULT 'pending',
 absence_reason TEXT NOT NULL, reviewer VARCHAR(150) NOT NULL DEFAULT '', reviewed_at DATETIME NULL,
 UNIQUE KEY (longId), KEY (reports,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE report_data_evidence (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 report_data_points VARCHAR(100) NOT NULL DEFAULT '',
 report_qm_requests VARCHAR(100) NOT NULL DEFAULT '',
 locator TEXT NOT NULL, UNIQUE KEY (longId), KEY (report_data_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE report_validation_issues (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 reports VARCHAR(100) NOT NULL DEFAULT '',
 report_data_points VARCHAR(100) NOT NULL DEFAULT '',
 code VARCHAR(80) NOT NULL, message TEXT NOT NULL, created_at DATETIME NOT NULL,
 UNIQUE KEY (longId), KEY (reports)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE report_sections (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 reports VARCHAR(100) NOT NULL DEFAULT '', section_number INT NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'gap',
 UNIQUE KEY (longId), UNIQUE KEY (reports,section_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE report_section_data_points (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 longId VARCHAR(100) NOT NULL DEFAULT '',
 report_sections VARCHAR(100) NOT NULL DEFAULT '',
 report_data_points VARCHAR(100) NOT NULL DEFAULT '',
 UNIQUE KEY (longId), UNIQUE KEY (report_sections,report_data_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
