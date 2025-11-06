<?php

require_once('../Database/connection.php');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

class ProdutoController {
    
    private $db;

    public function __construct() {

        $this -> db = connectionDbMysql::getInstance() -> getConnection();
    }

    // Função que verifica se o ID do grupo existe
    private function GrupoExiste($id) {
        $query = "SELECT id_grupo FROM grupo_produto WHERE id_grupo = ? LIMIT 1";
        $stmnt = $this -> db -> prepare($query);
        $stmnt -> execute([$id]);
        return $stmnt -> rowCount() > 0;    
    }
    
    //CREATE - Cria os novos produtos
    public function CriarProdutos($data) {

        if (!isset($data['nome_produto']) || !isset($data['valor_produto']) || !isset($data['id_grupo']) || !isset($data['imagem_url'])) {

            http_response_code(400); //BAD REQUEST
            
            echo json_encode(["message" => "Campos 'nome', 'valor', 'id_grupo' e 'imagem_url' são obrigatórios. "]);

            return;
        }

        $id_grupo = intval($data['id_grupo']);
        if (!$this -> GrupoExiste($id_grupo)) {
            http_response_code(400);
            echo json_encode(["message"=> "O ID do grupo ($id_grupo) não existe."]);
            return;
        }
        
        $nome = trim($data['nome_produto']);
        $imagemUrl = trim($data['imagem_url']);
        $valor = floatval($data['valor_produto']);

        $sqlInsert = "INSERT INTO produto_loja (nome_produto, valor_produto, id_grupo_produto, imagem_url) VALUES (?, ?, ?, ?)";

        try {
            $stmnt_insert = $this -> db -> prepare($sqlInsert); 
            $stmnt_insert -> execute([$nome, $valor, $id_grupo, $imagemUrl]);

            http_response_code(201); // CREATED ou sucesso na criação
            echo json_encode(["message"=> "Produto criado com sucesso" , "id" => $this->db->lastInsertId()]);
            

        } catch (Exception $e) {
            http_response_code(503); //Service Unavaliable
            echo json_encode(["message"=> "Erro ao criar o produto", "error" => $e->getMessage()]);
        }



    }

    //READ - Resgata os produtos do banco de dados 
    public function ResgateProdutos() {
        
        //JOIN para trazer o nome do grupo
        $sqlSelect = "SELECT
            p.id_produto, 
            p.nome_produto, 
            p.valor_produto,
            p.imagem_url,  
            g.nome_grupo, 
            g.id_grupo
        FROM produto_loja p
        INNER JOIN grupo_produto g ON p.id_grupo_produto = g.id_grupo
        ORDER BY p.nome_produto ASC
        ";

        try {
            $stmnt_select = $this -> db -> prepare($sqlSelect);
            $stmnt_select -> execute();
            $produtos = $stmnt_select -> fetchAll(PDO::FETCH_ASSOC);

            if(empty($produtos)) {
                http_response_code(404); //NOT FOUND ou não encontrado
                echo json_encode(["message"=> "Nenhum produto encontrado"]);
                return;
            } 

            http_response_code(200); //OK tudo certo 
            echo json_encode($produtos);

        } catch (Exception $e) {
            http_response_code(503); //Service not Unavaliable
            echo json_encode(["message"=> "Erro ao buscar produtos" , "error" => $e -> getMessage()]);
        }
       
    }

    //UPDATE / PUT - Atualiza o produto no banco de dados
    public function AtualizarProduto($data, $id) {

        if(!$id || !isset($data["nome_produto"]) || !isset( $data["valor_produto"]) || !isset( $data["id_grupo"]) || !isset($data["imagem_url"])) {
            http_response_code(400); //BAD REQUEST 
            echo json_encode(["message"=> "ID na URL e 'nome', 'preco', 'id_grupo', 'imagem_url' no JSON são obrigatórios."]);
            return;

        }

        $id_grupo = intval($data["id_grupo"]);
        if (!$this-> GrupoExiste($id_grupo)) {
            http_response_code(400); //BAD REQUEST
            echo json_encode(["message"=> "O ID do grupo ($id_grupo) não existe"]);
            return;
        }

        $nome = trim($data["nome_produto"]);
        $valor = floatval($data["valor_produto"]);
        $imagem_url = trim($data["imagem_url"]);

        $sqlUpdate = "UPDATE produto_loja SET nome_produto = ?, valor_produto = ?, id_grupo_produto = ?, imagem_url = ? WHERE id_produto = ?";

        try {
            $stmnt_update = $this -> db -> prepare($sqlUpdate);
            $stmnt_update -> execute([$nome, $valor, $id_grupo, $imagem_url, $id]);

            if ($stmnt_update ->rowCount() > 0) {
                http_response_code(200); //OK tudo certo coisa linda
                echo json_encode(["message"=> "Produto atualizado com sucesso"]);

            } else {
                http_response_code(404);
                echo json_encode(["message"=> "Produto com ID $id não encontrado"]);

            }
        } catch (PDOException $e) {
            http_response_code(503); //SERVICE UNAVALIABLE
            echo json_encode(["message" => "Erro ao atualizar o produto" , "error" => $e -> getMessage()]);
        }    
    }
    
    //DELETE - Deleta o produto do banco de dados
    public function DeletarProdutos($id) {
        if (!$id) {
            http_response_code(400); //BAD REQUEST
            echo json_encode(["message"=> "ID do produto ausente"]);
            return;
        }

        $sqlDelete = "DELETE FROM produto_loja WHERE id_produto = ?";

        try {
            $stmnt_delete = $this -> db -> prepare($sqlDelete);
            $stmnt_delete -> execute([$id]);

            if ($stmnt_delete ->rowCount() > 0) {
                http_response_code(204); //sem conteúdo ou conteudo ausente

            } else {
                http_response_code(404); //NOT FOUND não encontrado
                echo json_encode(["message"=> "Produto com o ID $id não encontrado"]);
            } 

        } catch (PDOException $e) {
            if ($e -> getCode() == 23000) {
                http_response_code(409); //Solicitação não pode ser processada
                echo json_encode(["message"=> "Não é possível excluir o produto."]);

            } else {
                http_response_code(503);
                echo json_encode(["message"=> "Erro ao excluir o produto." , "error" => $e -> getMessage()]);
            }
        }
    }
}

// ROUTER - ROTEADOR DA APIRESTFUL (FLUXO PRINCIPAL)
$ProdutoController = new ProdutoController();
$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        $ProdutoController -> ResgateProdutos();
        break;
    
    case 'POST':
        $ProdutoController -> CriarProdutos($data);
        break;

    case 'PUT':
        $ProdutoController -> AtualizarProduto($data, $id);
        break;

    case 'DELETE':
        $ProdutoController -> DeletarProdutos( $id);
        break;
        default:
}

?>