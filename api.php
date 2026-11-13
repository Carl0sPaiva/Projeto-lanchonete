<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/classes/BD.php';
require_once __DIR__ . '/classes/Usuario.php';
require_once __DIR__ . '/classes/Produto.php';
require_once __DIR__ . '/classes/Reserva.php';
require_once __DIR__ . '/classes/Pagamento.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function resposta($dados, int $codigo = 200): never {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function entrada(): array {
    if (str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')) return $_POST;
    $conteudo = file_get_contents('php://input');
    $dados = json_decode($conteudo, true);
    return is_array($dados) ? $dados : $_POST;
}

function usuarioLogado(): array {
    if (empty($_SESSION['usuario'])) {
        resposta(['erro' => 'Faça login para continuar.'], 401);
    }
    return $_SESSION['usuario'];
}

function administrador(): array {
    $usuario = usuarioLogado();
    if (($usuario['perfil'] ?? '') !== 'ADMINISTRADOR') {
        resposta(['erro' => 'Acesso permitido somente ao administrador.'], 403);
    }
    return $usuario;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') resposta(['erro' => 'Metodo nao permitido.'], 405);
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        $origem = parse_url($_SERVER['HTTP_ORIGIN']);
        $host = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
        if (!$origem || strcasecmp($origem['host'] ?? '', $host) !== 0) resposta(['erro' => 'Origem nao autorizada.'], 403);
    }
    $pdo = BD::getInstancia()->getConexao();
    $acao = $_GET['acao'] ?? '';
    $dados = entrada();

    switch ($acao) {
        case 'login':
            $usuario = (new Usuario($pdo))->autenticar((string)($dados['usuario'] ?? ''), (string)($dados['senha'] ?? ''));
            if (!$usuario) resposta(['erro' => 'Usuário ou senha incorretos.'], 401);
            session_regenerate_id(true);
            $_SESSION['usuario'] = $usuario;
            resposta(['sucesso' => true, 'perfil' => $usuario['perfil'], 'nome' => $usuario['nome']]);

        case 'cadastro':
            $id = (new Usuario($pdo))->cadastrar(
                (string)($dados['nome'] ?? ''),
                (string)($dados['usuario'] ?? ''),
                (string)($dados['senha'] ?? '')
            );
            resposta(['sucesso' => true, 'id' => $id], 201);

        case 'sair':
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $parametros = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
            }
            session_destroy();
            resposta(['sucesso' => true]);

        case 'sessao':
            resposta(['sucesso' => true, 'usuario' => usuarioLogado()]);

        case 'categorias':
            usuarioLogado();
            $sql = $pdo->prepare('SELECT id_categoria, nome, descricao FROM categorias WHERE ativo = 1 ORDER BY nome');
            $sql->execute();
            resposta($sql->fetchAll());

        case 'produtos_listar':
            $usuario = usuarioLogado();
            $somenteAtivos = $usuario['perfil'] !== 'ADMINISTRADOR';
            $produtos = (new Produto($pdo))->listar($somenteAtivos);
            foreach ($produtos as &$produto) {
                $produto['id'] = (int)$produto['id_produto'];
                $produto['preco'] = (float)$produto['preco'];
                $produto['quantidade_estoque'] = (int)$produto['quantidade_estoque'];
                $produto['disponivel'] = (bool)$produto['ativo'] && $produto['quantidade_estoque'] > 0;
                $produto['imagem'] = !empty($produto['imagem']) ? 'imagens/' . $produto['imagem'] : null;
                if ($produto['categoria'] === 'Salgados') $produto['categoria'] = 'Salgados & Lanches';
            }
            unset($produto);
            resposta($produtos);

        case 'produto_salvar':
            administrador();
            $categorias = [
                'Salgados & Lanches' => 'Salgados',
                'Salgados' => 'Salgados',
                'Bebidas' => 'Bebidas',
                'Doces' => 'Doces',
                'Sanduíches' => 'Sanduíches'
            ];
            $nomeCategoria = $categorias[(string)($dados['categoria'] ?? '')] ?? '';
            if ($nomeCategoria === '') resposta(['erro' => 'Categoria inválida.'], 422);
            $consulta = $pdo->prepare('SELECT id_categoria FROM categorias WHERE nome = :nome AND ativo = 1');
            $consulta->bindValue(':nome', $nomeCategoria);
            $consulta->execute();
            $idCategoria = (int)$consulta->fetchColumn();
            if (!$idCategoria) resposta(['erro' => 'Categoria não encontrada no banco de dados.'], 422);
            
            $id = (new Produto($pdo))->salvar([
                'id_produto' => $dados['id'] ?? null,
                'nome' => $dados['nome'] ?? '',
                'descricao' => $dados['descricao'] ?? '',
                'preco' => $dados['preco'] ?? null,
                'id_categoria' => $idCategoria,
                'ativo' => !empty($dados['disponivel']),
                'quantidade_estoque' => $dados['quantidade_estoque'] ?? 20
            ], $_FILES['imagem'] ?? null);

            resposta(['sucesso' => true, 'id' => $id]);

        case 'produto_status':
            administrador();
            (new Produto($pdo))->alterarStatus((int)($dados['id'] ?? 0));
            resposta(['sucesso' => true]);

        case 'produto_excluir':
            administrador();
            $idProduto = (int)($dados['id'] ?? 0);
            
            $pdo->beginTransaction();
            $stmtItens = $pdo->prepare('DELETE FROM itens_reserva WHERE id_produto = ?');
            $stmtItens->execute([$idProduto]);
            
            try {
                (new Produto($pdo))->excluir($idProduto);
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
            
            resposta(['sucesso' => true]);

        case 'reserva_criar':
            $usuario = usuarioLogado();
            $horario = (string)($dados['horario'] ?? '');
            $hora = DateTime::createFromFormat('!H:i', $horario);
            if (!$hora || $hora->format('H:i') !== $horario) resposta(['erro' => 'Informe um horário de retirada válido.'], 422);
            $retirada = date('Y-m-d') . ' ' . $horario . ':00';
            $id = (new Reserva($pdo))->criar(
                (int)$usuario['id_usuario'],
                is_array($dados['itens'] ?? null) ? $dados['itens'] : [],
                $retirada,
                (string)($dados['pagamento'] ?? 'Dinheiro'),
                (string)($dados['tipo'] ?? 'Reserva')
            );
            resposta(['sucesso' => true, 'id' => $id], 201);

        case 'reservas_listar':
            $usuario = usuarioLogado();
            $idUsuario = $usuario['perfil'] === 'ADMINISTRADOR' ? null : (int)$usuario['id_usuario'];
            $reservas = (new Reserva($pdo))->listar($idUsuario);
            $status = [
                'PENDENTE' => 'Pendente', 'CONFIRMADA' => 'Confirmada',
                'EM_PREPARACAO' => 'Em Preparo', 'PRONTA' => 'Pronto',
                'RETIRADA' => 'Retirada', 'CANCELADA' => 'Cancelada'
            ];
            foreach ($reservas as &$reserva) {
                $reserva['id'] = (int)$reserva['id_reserva'];
                $reserva['total'] = (float)$reserva['total'];
                $reserva['horario'] = $reserva['data_retirada'] ? date('H:i', strtotime($reserva['data_retirada'])) : '';
                $reserva['data'] = date('H:i', strtotime($reserva['data_reserva']));
                $reserva['pagamento'] = $reserva['forma_pagamento'] ?? 'A combinar';
                $reserva['status'] = $status[$reserva['status']] ?? $reserva['status'];
            }
            unset($reserva);
            resposta($reservas);

        case 'reserva_status':
            administrador();
            $mapa = [
                'Pendente' => 'PENDENTE', 'Confirmada' => 'CONFIRMADA',
                'Em Preparo' => 'EM_PREPARACAO', 'Pronto' => 'PRONTA',
                'Retirada' => 'RETIRADA', 'Cancelada' => 'CANCELADA'
            ];
            $status = (string)($dados['status'] ?? '');
            $status = $mapa[$status] ?? $status;
            (new Reserva($pdo))->atualizarStatus((int)($dados['id'] ?? 0), $status);
            resposta(['sucesso' => true]);

        case 'reserva_excluir':
            administrador();
            (new Reserva($pdo))->excluir((int)($dados['id'] ?? 0));
            resposta(['sucesso' => true]);

        case 'usuarios_listar':
            administrador();
            resposta((new Usuario($pdo))->listar());

        case 'usuario_atualizar':
            administrador();
            $ok = (new Usuario($pdo))->atualizar((int)($dados['id'] ?? 0), (string)($dados['nome'] ?? ''), (string)($dados['email'] ?? ''), !empty($dados['ativo']));
            resposta(['sucesso' => $ok]);

        case 'usuario_excluir':
            administrador();
            $ok = (new Usuario($pdo))->excluir((int)($dados['id'] ?? 0));
            resposta(['sucesso' => $ok]);

        default:
            resposta(['erro' => 'Ação não encontrada.'], 404);
    }
} catch (InvalidArgumentException $e) {
    resposta(['erro' => $e->getMessage()], 422);
} catch (PDOException $e) {
    error_log('IFOME SQL: ' . $e->getMessage());
    resposta(['erro' => 'Erro no banco de dados. Consulte os logs do servidor.'], 500);
} catch (Throwable $e) {
    error_log('IFOME: ' . $e->getMessage());
    resposta(['erro' => 'Erro interno. Consulte os logs do servidor.'], 500);
}