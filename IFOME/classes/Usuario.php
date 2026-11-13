<?php
class Usuario {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function cadastrar(string $nome, string $identificador, string $senha): int {
        $nome = trim($nome);
        $identificador = trim($identificador);
        if (strlen($nome) < 3 || strlen($nome) > 100) {
            throw new InvalidArgumentException('O nome deve ter entre 3 e 100 caracteres.');
        }
        if ($identificador === '' || strlen($identificador) > 150) {
            throw new InvalidArgumentException('Informe um usuário ou e-mail válido.');
        }
        if (strlen($senha) < 4) {
            throw new InvalidArgumentException('A senha deve possuir pelo menos 4 caracteres.');
        }

        // O formulário atual pede usuário, não e-mail. Quando receber matrícula,
        // é criado um e-mail técnico para respeitar o NOT NULL e UNIQUE do banco.
        if (filter_var($identificador, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($identificador);
            $matricula = null;
        } else {
            if (!preg_match('/^[A-Za-z0-9._-]{3,20}$/', $identificador)) {
                throw new InvalidArgumentException('O usuário deve ter de 3 a 20 caracteres e usar apenas letras, números, ponto, hífen ou underline.');
            }
            $matricula = $identificador;
            $emailBase = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $identificador));
            $email = $emailBase . '@aluno.ifto.edu.br';
        }

        $verifica = $this->pdo->prepare('SELECT id_usuario FROM usuarios WHERE email = :email OR matricula = :matricula LIMIT 1');
        $verifica->bindValue(':email', $email);
        $verifica->bindValue(':matricula', $matricula);
        $verifica->execute();
        if ($verifica->fetch()) {
            throw new InvalidArgumentException('Este usuário ou e-mail já está cadastrado.');
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = $this->pdo->prepare("INSERT INTO usuarios (nome, matricula, email, senha_hash, perfil) VALUES (:nome, :matricula, :email, :senha_hash, 'ALUNO')");
        $sql->bindValue(':nome', $nome);
        $sql->bindValue(':matricula', $matricula);
        $sql->bindValue(':email', $email);
        $sql->bindValue(':senha_hash', $senhaHash);
        $sql->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function autenticar(string $identificador, string $senha): ?array {
        $identificador = trim($identificador);
        if (strtolower($identificador) === 'admin') {
            $identificador = 'admin@ifto.edu.br';
        }
        $sql = $this->pdo->prepare('SELECT id_usuario, nome, matricula, email, senha_hash, perfil, ativo FROM usuarios WHERE email = :email OR matricula = :matricula LIMIT 1');
        $sql->bindValue(':email', $identificador);
        $sql->bindValue(':matricula', $identificador);
        $sql->execute();
        $usuario = $sql->fetch();
        if (!$usuario || !(bool)$usuario['ativo'] || !password_verify($senha, $usuario['senha_hash'])) {
            return null;
        }
        unset($usuario['senha_hash']);
        $usuario['id_usuario'] = (int)$usuario['id_usuario'];
        return $usuario;
    }

    public function listar(): array {
        $sql = $this->pdo->prepare('SELECT id_usuario, nome, matricula, email, perfil, ativo, criado_em FROM usuarios ORDER BY nome');
        $sql->execute();
        return $sql->fetchAll();
    }

    public function atualizar(int $id, string $nome, string $email, bool $ativo): bool {
        $nome = trim($nome);
        $email = trim($email);
        if ($id < 1 || strlen($nome) < 3 || strlen($nome) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Informe um nome e e-mail válidos.');
        }
        $sql = $this->pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, ativo = :ativo WHERE id_usuario = :id AND perfil = 'ALUNO'");
        $sql->bindValue(':nome', $nome);
        $sql->bindValue(':email', $email);
        $sql->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
        $sql->bindValue(':id', $id, PDO::PARAM_INT);
        $sql->execute();
        return $sql->rowCount() > 0;
    }

    public function excluir(int $id): bool {
        // Exclusão lógica: preserva reservas e pagamentos vinculados ao usuário.
        $sql = $this->pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id_usuario = :id AND perfil = 'ALUNO'");
        $sql->bindValue(':id', $id, PDO::PARAM_INT);
        $sql->execute();
        return $sql->rowCount() > 0;
    }
}
