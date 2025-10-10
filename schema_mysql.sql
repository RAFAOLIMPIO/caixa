-- MySQL schema for Caixa system
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero_loja VARCHAR(50),
  email VARCHAR(255) UNIQUE,
  senha VARCHAR(255),
  pergunta_seguranca VARCHAR(255),
  resposta_seguranca VARCHAR(255),
  lembrar_token VARCHAR(255),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS funcionarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  cargo VARCHAR(100),
  data_admissao DATE,
  salario DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS vendas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente VARCHAR(255),
  valor DECIMAL(10,2) NOT NULL,
  valor_pago DECIMAL(10,2),
  troco DECIMAL(10,2),
  forma_pagamento VARCHAR(50),
  motoboy VARCHAR(50),
  pago TINYINT(1) DEFAULT 0,
  numero_loja VARCHAR(50),
  autozoner_id VARCHAR(100),
  obs TEXT,
  data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
