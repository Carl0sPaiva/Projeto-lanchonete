CREATE DATABASE IF NOT EXISTS IFOME
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE IFOME;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    matricula VARCHAR(20) UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('ALUNO', 'ADMINISTRADOR') NOT NULL DEFAULT 'ALUNO',
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(60) NOT NULL UNIQUE,
    descricao VARCHAR(255),
    ativo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE produtos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255),
    preco DECIMAL(10,2) NOT NULL,
    quantidade_estoque INT NOT NULL DEFAULT 0,
    imagem VARCHAR(255) DEFAULT NULL,
    id_categoria INT NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_produto_preco CHECK (preco >= 0),
    CONSTRAINT chk_produto_estoque CHECK (quantidade_estoque >= 0),
    CONSTRAINT fk_produto_categoria FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE reservas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    data_reserva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_retirada DATETIME,
    status ENUM('PENDENTE','CONFIRMADA','EM_PREPARACAO','PRONTA','RETIRADA','CANCELADA') NOT NULL DEFAULT 'PENDENTE',
    observacao VARCHAR(255),
    CONSTRAINT fk_reserva_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE itens_reserva (
    id_reserva INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id_reserva, id_produto),
    CONSTRAINT chk_item_quantidade CHECK (quantidade > 0),
    CONSTRAINT chk_item_preco CHECK (preco_unitario >= 0),
    CONSTRAINT fk_item_reserva FOREIGN KEY (id_reserva)
        REFERENCES reservas(id_reserva) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_item_produto FOREIGN KEY (id_produto)
        REFERENCES produtos(id_produto) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE pagamentos (
    id_pagamento INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva INT NOT NULL,
    forma_pagamento ENUM('PIX','DINHEIRO','CARTAO_DEBITO','CARTAO_CREDITO') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    status ENUM('PENDENTE','APROVADO','RECUSADO','ESTORNADO') NOT NULL DEFAULT 'PENDENTE',
    data_pagamento DATETIME DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_pagamento_valor CHECK (valor > 0),
    CONSTRAINT fk_pagamento_reserva FOREIGN KEY (id_reserva)
        REFERENCES reservas(id_reserva) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Senhas dos usuários de demonstração já estão protegidas com password_hash (bcrypt).
-- Ana e Carlos e Mariana: senha 1234. Administrador: senha admin123.
INSERT INTO usuarios (nome,matricula,email,senha_hash,perfil) VALUES
('Ana Silva','2026001','ana@ifto.edu.br','$2y$12$Tn0GNnNo8WIF.NPOsDLS6OvzN2ersPcXh/uClzZWHfBZP0CjRicqK','ALUNO'),
('Carlos Souza','2026002','carlos@ifto.edu.br','$2y$12$Tn0GNnNo8WIF.NPOsDLS6OvzN2ersPcXh/uClzZWHfBZP0CjRicqK','ALUNO'),
('Mariana Santos','2026003','mariana@ifto.edu.br','$2y$12$Tn0GNnNo8WIF.NPOsDLS6OvzN2ersPcXh/uClzZWHfBZP0CjRicqK','ALUNO'),
('Aluno Teste','aluno','aluno@ifto.edu.br','$2y$12$Tn0GNnNo8WIF.NPOsDLS6OvzN2ersPcXh/uClzZWHfBZP0CjRicqK','ALUNO'),
('Administrador IFOME',NULL,'admin@ifto.edu.br','$2y$12$1zycwpMTN29ELHbygTa0aeX2VPNitnBWGhSFAURzPky6whxmk2cLy','ADMINISTRADOR');

INSERT INTO categorias (nome,descricao) VALUES
('Salgados','Salgados assados e fritos'),
('Bebidas','Bebidas geladas e quentes'),
('Sanduíches','Sanduíches e hambúrgueres'),
('Doces','Doces e sobremesas');

INSERT INTO produtos (nome,descricao,preco,quantidade_estoque,id_categoria) VALUES
('Coxinha','Coxinha de frango',6.00,50,1),
('Pão de queijo','Pão de queijo tradicional',4.00,40,1),
('Refrigerante','Lata de 350 ml',5.00,60,2),
('Suco natural','Copo de suco natural',6.00,30,2),
('Hambúrguer','Hambúrguer com queijo',12.00,25,3),
('Bolo de chocolate','Fatia de bolo',7.00,20,4);

INSERT INTO reservas (id_usuario,data_reserva,data_retirada,status,observacao) VALUES
(1,'2026-10-28 08:00:00','2026-10-28 09:30:00','CONFIRMADA','Reserva'),
(2,'2026-10-28 08:15:00','2026-10-28 10:00:00','PENDENTE','Compra'),
(3,'2026-10-28 08:30:00','2026-10-28 10:15:00','CONFIRMADA','Reserva');

INSERT INTO itens_reserva (id_reserva,id_produto,quantidade,preco_unitario) VALUES
(1,1,2,6.00),(1,3,1,5.00),(2,5,1,12.00),(2,4,1,6.00),(3,2,2,4.00),(3,6,1,7.00);

INSERT INTO pagamentos (id_reserva,forma_pagamento,valor,status,data_pagamento) VALUES
(1,'PIX',17.00,'APROVADO','2026-10-28 08:05:00'),
(2,'DINHEIRO',18.00,'PENDENTE',NULL),
(3,'CARTAO_DEBITO',15.00,'APROVADO','2026-10-28 08:35:00');
