<?php


    require_once('../Database/connection.php');
    class UserController {


        private $db;

        public function __construct() {

            $this->db = connectionDbMysql::getInstance()->getConnection();

        }

        //método para registrar um usuário
        public function registrar() {

            if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST["login"]) && isset($_POST["senha"])) {

                $login = trim($_POST["login"]);
                $senha_pura = $_POST["senha"];

                $senha_hashed = password_hash($senha_pura, PASSWORD_DEFAULT);

                $sqlInsert = "INSERT INTO usuarios (login, senha) VALUES (?, ?)";

                try {

                    $stmnt_insert = $this->db->prepare($sqlInsert);
                    $stmnt_insert -> execute([$login, $senha_hashed]);

                    header("Location: ". $_SERVER["PHP_SELF"]);
                    exit();

                } catch (PDOException $e) {
                    echo "Erro ao registrar". $e->getMessage();


        }

        }
    }
    // método de verificação/login do usuário registrado
    public function logar() {
        
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["login"]) || !isset($_POST["senha"])) {
            return null;
        }

        $login_recebido = trim($_POST["login"]);
        $senha_recebida = ($_POST["senha"]);

        $sqlSelect = "SELECT id, login, senha FROM usuarios WHERE login = ?";

        try {
            $stmnt = $this->db->prepare($sqlSelect);
            $stmnt -> execute([$login_recebido]);

            $usuario = $stmnt -> fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($senha_recebida, $usuario['senha'])) {

                return [ 
                    'id' => $usuario['id'],
                    'login' => $usuario['login']
                ];

            } else {
                return null;
            }
        } catch (PDOException $e) {
            error_log("Erro PDO no login". $e->getMessage());
            return null;
        }        
    }
}
    //pequeno teste
    $controller = new UserController();
    $dados_login = $controller->logar();

    if($dados_login) {

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success' , 'data' => $dados_login]);

    } else {

        header('Content-Type: application/json');
        echo json_encode(['status'=> 'error' , 'message' => 'Login ou senha inválidos']);
    }
?>