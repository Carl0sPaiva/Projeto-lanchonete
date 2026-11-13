<?php
class Pagamento {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function criar(int $idReserva, string $forma, float $valor): int {
        $formas = ['PIX', 'DINHEIRO', 'CARTAO_DEBITO', 'CARTAO_CREDITO'];
        if ($idReserva < 1 || !in_array($forma, $formas, true) || $valor <= 0) {
            throw new InvalidArgumentException('Dados de pagamento inválidos.');
        }
        $sql = $this->pdo->prepare("INSERT INTO pagamentos (id_reserva, forma_pagamento, valor, status) VALUES (:reserva, :forma, :valor, 'PENDENTE')");
        $sql->bindValue(':reserva', $idReserva, PDO::PARAM_INT);
        $sql->bindValue(':forma', $forma);
        $sql->bindValue(':valor', number_format($valor, 2, '.', ''));
        $sql->execute();
        return (int)$this->pdo->lastInsertId();
    }
}
