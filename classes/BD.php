<?php
class BD {
    private static ?BD $instancia = null;
    private PDO $conexao;
    private string $host;
    private string $dbname;
    private string $username;
    private string $password;
    private string $charset;

    public static function getInstancia(): BD {
        if (self::$instancia == null) {
            self::$instancia = new BD();
        }
        return self::$instancia;
    }

    private function __construct() {
        $this->loadConfig();
        $this->conectar();
    }

    public function loadConfig(): void {
        $config = parse_ini_file(__DIR__ . '/../config.ini');
        if ($config === false) {
            throw new RuntimeException('Não foi possível carregar o arquivo config.ini.');
        }
        $this->host = $config['host'] ?? 'localhost';
        $this->dbname = $config['dbname'] ?? 'IFOME';
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';
        $this->charset = $config['charset'] ?? 'utf8mb4';
    }

    public function conectar(): void {
        try {
            $this->conexao = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}",
                $this->username,
                $this->password
            );
            $this->conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conexao->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new RuntimeException('Não foi possível conectar ao banco IFOME. Confira o config.ini e se o MySQL está iniciado.');
        }
    }

    public function getConexao(): PDO {
        return $this->conexao;
    }
}
