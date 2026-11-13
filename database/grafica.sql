-- Gráfica Escolar – banco de dados (Etapa 1)
-- Como usar: no phpMyAdmin, aba "Importar", escolha este arquivo e clique em "Executar".
-- Pode ser importado de novo sem apagar nada: ele só cria o que ainda não existe.

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS grafica_escolar
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE grafica_escolar;

-- ---------- USUÁRIOS ----------
CREATE TABLE IF NOT EXISTS usuarios (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL,
  login     VARCHAR(50)  NOT NULL UNIQUE,
  senha     VARCHAR(255) NOT NULL,              -- guarda o hash, nunca a senha pura
  nivel     ENUM('gerente','funcionario') NOT NULL DEFAULT 'funcionario',
  ativo     TINYINT(1)   NOT NULL DEFAULT 1,    -- 1 = ativo, 0 = desativado
  criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- PRODUTOS ----------
-- tipo 'material' controla estoque; tipo 'servico' (impressão, encadernação...) só tem preço.
CREATE TABLE IF NOT EXISTS produtos (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  nome           VARCHAR(100) NOT NULL UNIQUE,
  tipo           ENUM('material','servico') NOT NULL DEFAULT 'material',
  preco          DECIMAL(10,2) NOT NULL DEFAULT 0,
  quantidade     INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS movimentacoes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT NOT NULL,
  tipo       ENUM('entrada','saida') NOT NULL,
  quantidade INT NOT NULL,
  data       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuario_id INT NOT NULL,
  FOREIGN KEY (produto_id) REFERENCES produtos(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------- VENDAS ----------
-- Cada linha é um item vendido (sem carrinho).
CREATE TABLE IF NOT EXISTS vendas (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  produto_id  INT NOT NULL,
  quantidade  INT NOT NULL,
  valor_total DECIMAL(10,2) NOT NULL,
  data_venda  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuario_id  INT NOT NULL,                     -- funcionário responsável
  FOREIGN KEY (produto_id) REFERENCES produtos(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------- GASTOS ----------
CREATE TABLE IF NOT EXISTS gastos (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  descricao  VARCHAR(150) NOT NULL,
  valor      DECIMAL(10,2) NOT NULL,
  data_gasto DATE NOT NULL
) ENGINE=InnoDB;

-- ---------- DADOS INICIAIS (para testar) ----------
-- Dois usuários de teste. Senha dos dois: admin123  (troque depois!)
INSERT IGNORE INTO usuarios (nome, login, senha, nivel) VALUES
('Gerente de Teste',     'gerente',     '$2b$12$QfAS0Q5Nb3dfwXd2UYcQQ.uTrnR0yIV3m.uNhaSXZYdo5pqqcj7Cq', 'gerente'),
('Funcionário de Teste', 'funcionario', '$2b$12$QfAS0Q5Nb3dfwXd2UYcQQ.uTrnR0yIV3m.uNhaSXZYdo5pqqcj7Cq', 'funcionario');

-- Serviços e materiais de exemplo. Os preços são inventados: ajuste para os valores reais da gráfica.
INSERT IGNORE INTO produtos (nome, tipo, preco, quantidade, estoque_minimo) VALUES
('Impressão P&B (por página)',       'servico',  0.50, 0, 0),
('Impressão colorida (por página)',  'servico',  2.00, 0, 0),
('Encadernação',                     'servico',  6.00, 0, 0),
('Plastificação (por documento)',    'servico',  4.00, 0, 0),
('Caderno 96 folhas',                'material', 12.90, 20, 5),
('Caneta azul',                      'material',  2.50, 50, 10),
('Papel sulfite A4 (pacote)',        'material', 28.00,  3, 5),
('Camiseta P',                       'material', 35.00, 10, 3),
('Camiseta M',                       'material', 35.00, 10, 3);
