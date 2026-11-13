<?php
class Produto {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listar(bool $somenteAtivos = false): array {
        $sql = 'SELECT p.id_produto, p.nome, p.descricao, p.preco, p.quantidade_estoque, p.id_categoria, p.ativo, p.imagem, c.nome AS categoria FROM produtos p INNER JOIN categorias c ON c.id_categoria = p.id_categoria';
        if ($somenteAtivos) {
            $sql .= ' WHERE p.ativo = 1 AND c.ativo = 1 AND p.quantidade_estoque > 0';
        }
        $sql .= ' ORDER BY p.nome';
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function salvar(array $dados, ?array $arquivoImagem = null): int {
        $nome = trim((string)($dados['nome'] ?? ''));
        $descricao = trim((string)($dados['descricao'] ?? ''));
        $preco = filter_var($dados['preco'] ?? null, FILTER_VALIDATE_FLOAT);
        $categoria = (int)($dados['id_categoria'] ?? 0);
        $ativo = !empty($dados['ativo']) ? 1 : 0;

        if ($nome === '' || strlen($nome) > 100 || $preco === false || $preco < 0 || $categoria < 1 || strlen($descricao) > 255) {
            throw new InvalidArgumentException('Preencha corretamente nome, descrição, preço e categoria do produto.');
        }

        $cat = $this->pdo->prepare('SELECT id_categoria FROM categorias WHERE id_categoria = :id AND ativo = 1');
        $cat->bindValue(':id', $categoria, PDO::PARAM_INT);
        $cat->execute();
        if (!$cat->fetch()) {
            throw new InvalidArgumentException('A categoria informada não existe ou está inativa.');
        }

        // Validacao do upload pelo conteudo, nao apenas pela extensao.
        $nomeImagem = null;
        if ($arquivoImagem && ($arquivoImagem['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($arquivoImagem['error'] ?? null) !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Falha ao enviar a imagem.');
            }
            if (($arquivoImagem['size'] ?? 0) > 2 * 1024 * 1024) {
                throw new InvalidArgumentException('A imagem deve ter no maximo 2 MB.');
            }
            $tmp = $arquivoImagem['tmp_name'] ?? '';
            if (!is_uploaded_file($tmp)) throw new InvalidArgumentException('Upload invalido.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($extensoes[$mime]) || @getimagesize($tmp) === false) {
                throw new InvalidArgumentException('Imagem invalida. Use JPG, PNG ou WEBP.');
            }
            $destino = __DIR__ . '/../imagens/';
            if (!is_dir($destino) && !mkdir($destino, 0755, true)) {
                throw new RuntimeException('Nao foi possivel criar a pasta de imagens.');
            }
            $nomeImagem = bin2hex(random_bytes(16)) . '.' . $extensoes[$mime];
            if (!move_uploaded_file($tmp, $destino . $nomeImagem)) {
                throw new RuntimeException('Nao foi possivel salvar a imagem.');
            }
        }

        $id = (int)($dados['id_produto'] ?? 0);
        if ($id > 0) {
            if ($nomeImagem) {
                $sql = $this->pdo->prepare('UPDATE produtos SET nome = :nome, descricao = :descricao, preco = :preco, id_categoria = :categoria, ativo = :ativo, imagem = :imagem WHERE id_produto = :id');
                $sql->bindValue(':imagem', $nomeImagem);
            } else {
                $sql = $this->pdo->prepare('UPDATE produtos SET nome = :nome, descricao = :descricao, preco = :preco, id_categoria = :categoria, ativo = :ativo WHERE id_produto = :id');
            }
            $sql->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            $estoque = (int)($dados['quantidade_estoque'] ?? 20);
            if ($estoque < 0) {
                throw new InvalidArgumentException('O estoque não pode ser negativo.');
            }
            $sql = $this->pdo->prepare('INSERT INTO produtos (nome, descricao, preco, quantidade_estoque, id_categoria, ativo, imagem) VALUES (:nome, :descricao, :preco, :estoque, :categoria, :ativo, :imagem)');
            $sql->bindValue(':estoque', $estoque, PDO::PARAM_INT);
            $sql->bindValue(':imagem', $nomeImagem);
        }

        $sql->bindValue(':nome', $nome);
        $sql->bindValue(':descricao', $descricao !== '' ? $descricao : null);
        $sql->bindValue(':preco', number_format((float)$preco, 2, '.', ''));
        $sql->bindValue(':categoria', $categoria, PDO::PARAM_INT);
        $sql->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $sql->execute();

        return $id > 0 ? $id : (int)$this->pdo->lastInsertId();
    }

    public function alterarStatus(int $id): bool {
        if ($id < 1) throw new InvalidArgumentException('Produto inválido.');
        $sql = $this->pdo->prepare('UPDATE produtos SET ativo = IF(ativo = 1, 0, 1) WHERE id_produto = :id');
        $sql->bindValue(':id', $id, PDO::PARAM_INT);
        $sql->execute();
        return $sql->rowCount() > 0;
    }

    public function excluir(int $id): bool {
        if ($id < 1) throw new InvalidArgumentException('Produto inválido.');
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM itens_reserva WHERE id_produto = :id');
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        if ((int)$consulta->fetchColumn() > 0) {
            $sql = $this->pdo->prepare('UPDATE produtos SET ativo = 0 WHERE id_produto = :id');
        } else {
            $sql = $this->pdo->prepare('DELETE FROM produtos WHERE id_produto = :id');
        }
        $sql->bindValue(':id', $id, PDO::PARAM_INT);
        $sql->execute();
        return $sql->rowCount() > 0;
    }
}