<?php

require $_SERVER['DOCUMENT_ROOT'].'/admin/conexao.php';
require $_SERVER['DOCUMENT_ROOT'].'/admin/credenciais.php';


if($_POST["Ya9WWMCJ09"] === APROVAR_API_KEY){


$db = iniciaConexaoDB();
$s =  $db->prepare(" UPDATE info_usuarios.contrib SET aprovado = true WHERE id = ?");
$s->bindParam(1,intval($_POST['id']),PDO::PARAM_INT);

if($s->execute()){
    # Cod 1 - sucesso
    echo '1';

}else{
    # Cod 2 - erro
    echo '2';
}

}else{
echo '9';

}

?>