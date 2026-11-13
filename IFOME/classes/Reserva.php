<?php
class Reserva {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function criar(int $idUsuario, array $itens, ?string $retirada, string $formaPagamento, string $tipo): int {
        if ($idUsuario < 1 || count($itens) === 0) {
            throw new InvalidArgumentException('O carrinho está vazio ou o usuário é inválido.');
        }
        $dataRetirada = null;
        if ($retirada !== null && $retirada !== '') {
            $dt = DateTime::createFromFormat('!Y-m-d H:i:s', $retirada);
            if (!$dt || $dt->format('Y-m-d H:i:s') !== $retirada) {
                throw new InvalidArgumentException('Data e horário de retirada inválidos.');
            }
            $dataRetirada = $dt->format('Y-m-d H:i:s');
        }

        $this->pdo->beginTransaction();
        try {
            $status = strtoupper($tipo) === 'COMPRA' ? 'CONFIRMADA' : 'PENDENTE';
            $sql = $this->pdo->prepare('INSERT INTO reservas (id_usuario, data_retirada, status) VALUES (:usuario, :retirada, :status)');
            $sql->bindValue(':usuario', $idUsuario, PDO::PARAM_INT);
            $sql->bindValue(':retirada', $dataRetirada);
            $sql->bindValue(':status', $status);
            $sql->execute();
            $idReserva = (int)$this->pdo->lastInsertId();
            $total = 0.0;

            foreach ($itens as $item) {
                $idProduto = (int)($item['id'] ?? 0);
                $quantidade = (int)($item['qtd'] ?? 0);
                if ($idProduto < 1 || $quantidade < 1 || $quantidade > 100) {
                    throw new InvalidArgumentException('Existe um item com quantidade inválida.');
                }
                $produto = $this->pdo->prepare('SELECT id_produto, nome, preco, quantidade_estoque FROM produtos WHERE id_produto = :id AND ativo = 1 FOR UPDATE');
                $produto->bindValue(':id', $idProduto, PDO::PARAM_INT);
                $produto->execute();
                $dadosProduto = $produto->fetch();
                if (!$dadosProduto) {
                    throw new RuntimeException('Um produto não está disponível. Atualize o cardápio.');
                }
                if ((int)$dadosProduto['quantidade_estoque'] < $quantidade) {
                    throw new RuntimeException('Estoque insuficiente para: ' . $dadosProduto['nome']);
                }
                $preco = (float)$dadosProduto['preco'];
                $total += $preco * $quantidade;

                $insereItem = $this->pdo->prepare('INSERT INTO itens_reserva (id_reserva, id_produto, quantidade, preco_unitario) VALUES (:reserva, :produto, :quantidade, :preco)');
                $insereItem->bindValue(':reserva', $idReserva, PDO::PARAM_INT);
                $insereItem->bindValue(':produto', $idProduto, PDO::PARAM_INT);
                $insereItem->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
                $insereItem->bindValue(':preco', number_format($preco, 2, '.', ''));
                $insereItem->execute();

                // CORRIGIDO AQUI: Uso de parâmetros posicionados para evitar o conflito do PDO com parâmetros nomeados repetidos
                $atualizaEstoque = $this->pdo->prepare('UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id_produto = ? AND quantidade_estoque >= ?');
                $atualizaEstoque->execute([$quantidade, $idProduto, $quantidade]);

                if ($atualizaEstoque->rowCount() !== 1) {
                    throw new RuntimeException('Não foi possível atualizar o estoque. Tente novamente.');
                }
            }

            if ($total <= 0) {
                throw new InvalidArgumentException('O valor total da reserva precisa ser maior que zero.');
            }

            $formas = [
                'PIX' => 'PIX',
                'Dinheiro' => 'DINHEIRO',
                'DINHEIRO' => 'DINHEIRO',
                'Cartão de Crédito/Débito' => 'CARTAO_CREDITO',
                'CARTAO_DEBITO' => 'CARTAO_CREDITO',
                'CARTAO_CREDITO' => 'CARTAO_CREDITO'
            ];
            $forma = $formas[$formaPagamento] ?? 'DINHEIRO';
            (new Pagamento($this->pdo))->criar($idReserva, $forma, $total);
            $this->pdo->commit();
            return $idReserva;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function listar(?int $idUsuario = null): array {
        $sql = 'SELECT r.id_reserva, r.id_usuario, u.nome AS cliente, r.data_reserva, r.data_retirada, r.status, r.observacao, pg.forma_pagamento, pg.valor AS valor_pagamento, pg.status AS status_pagamento, GROUP_CONCAT(CONCAT(ir.quantidade, "x ", pr.nome) ORDER BY pr.nome SEPARATOR ", ") AS itens, COALESCE(SUM(ir.quantidade * ir.preco_unitario), 0) AS total FROM reservas r INNER JOIN usuarios u ON u.id_usuario = r.id_usuario LEFT JOIN pagamentos pg ON pg.id_reserva = r.id_reserva LEFT JOIN itens_reserva ir ON ir.id_reserva = r.id_reserva LEFT JOIN produtos pr ON pr.id_produto = ir.id_produto';
        if ($idUsuario !== null) {
            $sql .= ' WHERE r.id_usuario = :usuario';
        }
        $sql .= ' GROUP BY r.id_reserva, r.id_usuario, u.nome, r.data_reserva, r.data_retirada, r.status, r.observacao, pg.forma_pagamento, pg.valor, pg.status ORDER BY r.data_reserva DESC';
        $consulta = $this->pdo->prepare($sql);
        if ($idUsuario !== null) $consulta->bindValue(':usuario', $idUsuario, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function atualizarStatus(int $id, string $novoStatus): bool {
        $permitidos = ['PENDENTE', 'CONFIRMADA', 'EM_PREPARACAO', 'PRONTA', 'RETIRADA', 'CANCELADA'];
        if ($id < 1 || !in_array($novoStatus, $permitidos, true)) {
            throw new InvalidArgumentException('Reserva ou status inválido.');
        }
        $this->pdo->beginTransaction();
        try {
            $consulta = $this->pdo->prepare('SELECT status FROM reservas WHERE id_reserva = :id FOR UPDATE');
            $consulta->bindValue(':id', $id, PDO::PARAM_INT);
            $consulta->execute();
            $reserva = $consulta->fetch();
            if (!$reserva) throw new RuntimeException('Reserva não encontrada.');
            $anterior = $reserva['status'];

            if ($anterior === 'RETIRADA' && $novoStatus === 'CANCELADA') {
                throw new InvalidArgumentException('Uma reserva já retirada não pode ser cancelada.');
            }

            if ($anterior !== 'CANCELADA' && $anterior !== 'RETIRADA' && $novoStatus === 'CANCELADA') {
                $itens = $this->pdo->prepare('SELECT id_produto, quantidade FROM itens_reserva WHERE id_reserva = :id');
                $itens->bindValue(':id', $id, PDO::PARAM_INT);
                $itens->execute();
                foreach ($itens->fetchAll() as $item) {
                    $estoque = $this->pdo->prepare('UPDATE produtos SET quantidade_estoque = quantidade_estoque + :qtd WHERE id_produto = :produto');
                    $estoque->bindValue(':qtd', (int)$item['quantidade'], PDO::PARAM_INT);
                    $estoque->bindValue(':produto', (int)$item['id_produto'], PDO::PARAM_INT);
                    $estoque->execute();
                }
            } elseif ($anterior === 'CANCELADA' && $novoStatus !== 'CANCELADA') {
                $itens = $this->pdo->prepare('SELECT ir.id_produto, ir.quantidade, p.nome, p.quantidade_estoque FROM itens_reserva ir INNER JOIN produtos p ON p.id_produto = ir.id_produto WHERE ir.id_reserva = :id FOR UPDATE');
                $itens->bindValue(':id', $id, PDO::PARAM_INT);
                $itens->execute();
                foreach ($itens->fetchAll() as $item) {
                    if ((int)$item['quantidade_estoque'] < (int)$item['quantidade']) throw new RuntimeException('Estoque insuficiente para reativar a reserva: ' . $item['nome']);
                    $estoque = $this->pdo->prepare('UPDATE produtos SET quantidade_estoque = quantidade_estoque - :qtd WHERE id_produto = :produto');
                    $estoque->bindValue(':qtd', (int)$item['quantidade'], PDO::PARAM_INT);
                    $estoque->bindValue(':produto', (int)$item['id_produto'], PDO::PARAM_INT);
                    $estoque->execute();
                }
            }
            $atualiza = $this->pdo->prepare('UPDATE reservas SET status = :status WHERE id_reserva = :id');
            $atualiza->bindValue(':status', $novoStatus);
            $atualiza->bindValue(':id', $id, PDO::PARAM_INT);
            $atualiza->execute();
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): bool {
        if ($id < 1) throw new InvalidArgumentException('Reserva inválida.');
        $this->pdo->beginTransaction();
        try {
            $consulta = $this->pdo->prepare('SELECT status FROM reservas WHERE id_reserva = :id FOR UPDATE');
            $consulta->bindValue(':id', $id, PDO::PARAM_INT);
            $consulta->execute();
            $reserva = $consulta->fetch();
            if (!$reserva) throw new RuntimeException('Reserva não encontrada.');

            if ($reserva['status'] !== 'CANCELADA' && $reserva['status'] !== 'RETIRADA') {
                $itens = $this->pdo->prepare('SELECT id_produto, quantidade FROM itens_reserva WHERE id_reserva = :id');
                $itens->bindValue(':id', $id, PDO::PARAM_INT);
                $itens->execute();
                foreach ($itens->fetchAll() as $item) {
                    $estoque = $this->pdo->prepare('UPDATE produtos SET quantidade_estoque = quantidade_estoque + :qtd WHERE id_produto = :produto');
                    $estoque->bindValue(':qtd', (int)$item['quantidade'], PDO::PARAM_INT);
                    $estoque->bindValue(':produto', (int)$item['id_produto'], PDO::PARAM_INT);
                    $estoque->execute();
                }
            }
            $pagamento = $this->pdo->prepare('DELETE FROM pagamentos WHERE id_reserva = :id');
            $pagamento->bindValue(':id', $id, PDO::PARAM_INT);
            $pagamento->execute();
            $excluir = $this->pdo->prepare('DELETE FROM reservas WHERE id_reserva = :id');
            $excluir->bindValue(':id', $id, PDO::PARAM_INT);
            $excluir->execute();
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}