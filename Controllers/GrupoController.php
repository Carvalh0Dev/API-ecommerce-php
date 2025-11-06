<?php

require_once('../Database/connection.php');
class GrupoController { 
     
    private $db;

    public function __construct() {

        $this->db = connectionDbMysql::getInstance()->getConnection();

    }

    //create - Cria os grupos de produtos
    public function criarGrupo() {
        
        if ($_SERVER['REQUEST_METHOD'] === "POST" && isset( $_POST["nome_grupo"] )) {

            $nome_grupo = $_POST["nome_grupo"];
            $sqlInsert = "INSERT INTO grupo_produto (nome_grupo) VALUES (?)";

            try {

                $stmnt_insert = $this->db->prepare($sqlInsert);
                $stmnt_insert -> execute([$nome_grupo]);

                header("Location: ". $_SERVER['PHP_SELF']);
                exit;

                } catch (PDOException $e) {
                    echo "Erro ao criar produto" . $e->getMessage();

            } 

        }
    }

    // READ - Mostra ao usuário os grupos de produtos
    public function resgateDeGrupos() {
        
        $sqlSelect = "SELECT id_grupo, nome_grupo, data_criacao_grupo FROM grupo_produto ORDER BY data_criacao_grupo DESC";
        $nome_grupos = [];

        try {
            $stmnt_select = $this->db->prepare($sqlSelect);
            $stmnt_select -> execute();
            $nome_grupos = $stmnt_select -> fetchAll(PDO::FETCH_ASSOC);

            return $nome_grupos;

        } catch (PDOException $e) {
            echo "Erro em buscar grupo de produtos". $e->getMessage();
        }
    }

    //DELETE - Exclui os grupos de produtos
    public function deletarGrupos() {
        
        if (isset($_GET['delete'])) {

            $id = intval($_GET['delete']);
            
            $sqlDelete = "DELETE FROM grupo_produto WHERE id_grupo = ?";

            try {
                $stmnt_delete = $this->db->prepare($sqlDelete);
                $stmnt_delete -> execute([$id]);

                header("Location: ". $_SERVER['PHP_SELF']);
                exit;


            } catch (PDOException $e) {
                echo 'Ação de excluir, não executada'. $e->getMessage();
            }
        }
    }

    //UPDATE - Atualiza os grupos de produtos
    public function AtualizarGrupos() {
        if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST["id_grupo_edit"]) && isset($_POST["nome_grupo_edit"])) {

            $id_grupo = intval($_POST["id_grupo_edit"]);
            $nome_grupo = trim($_POST["nome_grupo_edit"]);

            $sqlUpdate = "UPDATE grupo_produto SET nome_grupo = ? WHERE id_grupo = ?";

            if ($id_grupo > 0 && !empty($nome_grupo)) {

                try {
                    $stmnt_update = $this->db->prepare($sqlUpdate);
                    $stmnt_update -> execute([$nome_grupo, $id_grupo]);

                    header("Location: ". $_SERVER['PHP_SELF']);
                    exit;
                    } catch (PDOException $e) {
                    echo 'Erro ao atualizar grupo'. $e->getMessage();
                }
            } else {
            echo "Dados de atualização inválidos";
            }
        }
    }
}
    $GrupoController = new GrupoController();

    $grupo = $GrupoController->criarGrupo();

header('Content-Type: application/json');
echo json_encode($grupo);
exit;

    
?>