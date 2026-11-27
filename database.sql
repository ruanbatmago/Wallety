CREATE DATABASE IF NOT EXISTS wallety CHARACTER SET utf8mb4;
USE wallety;

CREATE TABLE IF NOT EXISTS usuarios (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nome   VARCHAR(100) NOT NULL,
  email  VARCHAR(150) NOT NULL UNIQUE,
  senha  VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS transacoes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT NOT NULL,
  tipo            ENUM('entrada', 'despesa') NOT NULL,
  descricao       VARCHAR(150) NOT NULL,
  categoria       VARCHAR(50) NOT NULL,
  valor           DECIMAL(10,2) NOT NULL,
  data_transacao  DATE NOT NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS metas (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id   INT NOT NULL,
  nome         VARCHAR(100) NOT NULL,
  valor_alvo   DECIMAL(10,2) NOT NULL,
  valor_atual  DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS limites (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT NOT NULL,
  categoria     VARCHAR(50) NOT NULL,
  valor_limite  DECIMAL(10,2) NOT NULL,
  UNIQUE KEY limite_unico (usuario_id, categoria),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
