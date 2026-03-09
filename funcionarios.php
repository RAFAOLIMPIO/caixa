<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/funcoes.php';

verificar_login();

/* ==========================
   VARIÁVEIS DA SESSÃO
========================== */

$usuario_id = $_SESSION['usuario']['id'] ?? 0;
$numero_loja = $_SESSION['usuario']['numero_loja'] ?? '';

/* ==========================
   CONTROLE DE ERROS
========================== */

$erros = [];
$sucesso = '';
$funcionarios = [];
$estatisticas = [];
$entregas_motoboys = [];
$editar = null;


/* ==========================
   VERIFICAR USUÁRIO
========================== */

try {

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);

    if (!$stmt->fetch()) {

        session_destroy();
        header("Location: index.php");
        exit();

    }

} catch (PDOException $e) {

    $erros[] = "Erro ao verificar usuário: " . $e->getMessage();

}


/* ==========================
   EDITAR FUNCIONÁRIO
========================== */

if (isset($_GET['editar'])) {

    $id_editar = (int)$_GET['editar'];

    try {

        $stmt = $pdo->prepare("
            SELECT *
            FROM funcionarios
            WHERE id = ? AND usuario_id = ?
        ");

        $stmt->execute([$id_editar,$usuario_id]);

        $editar = $stmt->fetch();

        if (!$editar) {

            $erros[] = "Funcionário não encontrado.";

        }

    } catch(PDOException $e){

        $erros[] = $e->getMessage();

    }

}


/* ==========================
   SALVAR FUNCIONÁRIO
========================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $cargo = trim($_POST['cargo'] ?? '');
    $funcionario_id = (int)($_POST['funcionario_id'] ?? 0);

    if(strlen($nome) < 3){

        $erros[] = "Nome deve ter pelo menos 3 caracteres";

    }

    if(!in_array($tipo,['autozoner','motoboy'])){

        $erros[] = "Tipo inválido";

    }


    if(empty($erros)){

        try{

            if($funcionario_id > 0){

                $stmt = $pdo->prepare("
                    UPDATE funcionarios
                    SET nome = ?, tipo = ?, cargo = ?
                    WHERE id = ? AND usuario_id = ?
                ");

                $stmt->execute([
                    $nome,
                    $tipo,
                    $cargo,
                    $funcionario_id,
                    $usuario_id
                ]);

                $_SESSION['mensagem_sucesso'] = "Funcionário atualizado";

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO funcionarios
                    (nome,tipo,cargo,usuario_id,numero_loja)
                    VALUES (?,?,?,?,?)
                ");

                $stmt->execute([
                    $nome,
                    $tipo,
                    $cargo,
                    $usuario_id,
                    $numero_loja
                ]);

                $_SESSION['mensagem_sucesso'] = "Funcionário cadastrado";

            }

            header("Location: funcionarios.php");
            exit();

        }
        catch(PDOException $e){

            $erros[] = "Erro ao salvar funcionário: ".$e->getMessage();

        }

    }

}


/* ==========================
   EXCLUIR FUNCIONÁRIO
========================== */

if(isset($_GET['excluir'])){

    $id = (int)$_GET['excluir'];

    try{

        $stmt = $pdo->prepare("
            SELECT nome,tipo
            FROM funcionarios
            WHERE id = ? AND usuario_id = ?
        ");

        $stmt->execute([$id,$usuario_id]);

        $func = $stmt->fetch();

        if($func){

            if($func['tipo'] === 'autozoner'){

                $stmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM vendas
                    WHERE autozoner_id = ?
                ");

                $stmt->execute([$id]);

                if($stmt->fetchColumn() > 0){

                    $erros[] = "Não é possível excluir. Existem vendas vinculadas.";

                }
                else{

                    $pdo->prepare("DELETE FROM funcionarios WHERE id=?")
                        ->execute([$id]);

                    $_SESSION['mensagem_sucesso']="Funcionário excluído";

                    header("Location: funcionarios.php");
                    exit();

                }

            } else {

                $pdo->prepare("DELETE FROM funcionarios WHERE id=?")
                    ->execute([$id]);

                $_SESSION['mensagem_sucesso']="Funcionário excluído";

                header("Location: funcionarios.php");
                exit();

            }

        }

    }
    catch(PDOException $e){

        $erros[] = $e->getMessage();

    }

}


/* ==========================
   LISTAR FUNCIONÁRIOS
========================== */

try{

    $stmt = $pdo->prepare("
        SELECT *
        FROM funcionarios
        WHERE usuario_id = ?
        ORDER BY nome
    ");

    $stmt->execute([$usuario_id]);

    $funcionarios = $stmt->fetchAll();

}
catch(PDOException $e){

    $erros[] = "Erro ao listar funcionários: ".$e->getMessage();

    $funcionarios = [];

}


/* ==========================
   ESTATÍSTICAS
========================== */

try{

    foreach($funcionarios as $func){

        if($func['tipo']=='autozoner'){

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM vendas
                WHERE autozoner_id = ?
            ");

            $stmt->execute([$func['id']]);

            $estatisticas[$func['id']] = $stmt->fetchColumn();

        }

        if($func['tipo']=='motoboy'){

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM vendas
                WHERE LOWER(motoboy)=LOWER(?)
                AND DATE(data_venda)=CURRENT_DATE
            ");

            $stmt->execute([$func['nome']]);

            $entregas_motoboys[$func['id']] = $stmt->fetchColumn();

        }

    }

}
catch(PDOException $e){

    $erros[] = $e->getMessage();

}


/* ==========================
   MENSAGEM SUCESSO
========================== */

if(isset($_SESSION['mensagem_sucesso'])){

    $sucesso = $_SESSION['mensagem_sucesso'];

    unset($_SESSION['mensagem_sucesso']);

}
