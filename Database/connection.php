<?php
//conexão com o banco de dados Mysql usando o método Singleton
class connectionDbMysql {

    private static $instance = null;

    private $connection;

    private $dsn = "mysql:host=localhost;dbname=minha_api;charset=utf8";
    private $user = "root";
    private $password = "cauazin37713770";

    private function __construct() {

        try {

            $options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, 
                    PDO::ATTR_EMULATE_PREPARES => false];

            $this->connection = new PDO($this->dsn, $this->user, $this->password, $options);

            

        } catch (PDOException $e) {
            
            throw new Exception("Falha na conexão". $e->getMessage());

        
                }
    

    }
    private function __clone():void {}
    public function __wakeup():void {}

    public static function getInstance():self {
        if (self::$instance === null) {
            self::$instance = new self();

        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }


}
?>