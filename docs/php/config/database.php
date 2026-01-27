<?php
/**
 * PREMIUM MENU - Configuração do Banco de Dados
 * 
 * Este arquivo contém as configurações de conexão com o MySQL
 * e a classe de conexão PDO singleton.
 */

// =====================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'premium_menu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// CONFIGURAÇÕES DA APLICAÇÃO
// =====================================================
define('APP_NAME', 'Cardápio Floripa');
define('APP_URL', 'https://cardapiofloripa.com.br');
define('APP_DEBUG', false); // Mudar para false em produção!

// Diretório de uploads
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Limites de upload
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_VIDEO_SIZE', 50 * 1024 * 1024); // 50MB

// Tipos permitidos
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm']);

// =====================================================
// CLASSE DE CONEXÃO PDO (Singleton)
// =====================================================
class Database {
    private static ?PDO $instance = null;
    
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    die('Erro de conexão: ' . $e->getMessage());
                } else {
                    die('Erro de conexão com o banco de dados.');
                }
            }
        }
        
        return self::$instance;
    }
    
    // Prevenir clonagem
    private function __clone() {}
    
    // Prevenir deserialização
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Função auxiliar para obter conexão
 */
function db(): PDO {
    return Database::getInstance();
}
