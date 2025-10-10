-- PostgreSQL schema for Caixa system
CREATE TABLE IF NOT EXISTS usuarios (
  id SERIAL PRIMARY KEY,
  numero_loja VARCHAR(50),
  email VARCHAR(255) UNIQUE,
  senha VARCHAR(255),
  pergunta_seguranca VARCHAR(255),
  resposta_seguranca VARCHAR(255),
  lembrar_token VARCHAR(255),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS funcionarios (
  id SERIAL PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  cargo VARCHAR(100),
  data_admissao DATE,
  salario NUMERIC(10,2)
);

CREATE TABLE IF NOT EXISTS vendas (
  id SERIAL PRIMARY KEY,
  cliente VARCHAR(255),
  valor NUMERIC(10,2) NOT NULL,
  valor_pago NUMERIC(10,2),
  troco NUMERIC(10,2),
  forma_pagamento VARCHAR(50),
  motoboy VARCHAR(50),
  pago BOOLEAN DEFAULT FALSE,
  numero_loja VARCHAR(50),
  autozoner_id VARCHAR(100),
  obs TEXT,
  data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
