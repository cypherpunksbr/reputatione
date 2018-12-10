<?php

require $_SERVER['DOCUMENT_ROOT'].'/reputatione/admin/conexao.php';
require $_SERVER['DOCUMENT_ROOT'].'/reputatione/admin/credenciais.php';

$db = iniciaConexaoDB();

#INICIO POST
# Se o método for POST
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  // var_dump($_POST);
  //var_dump(explode('?', $_SERVER[SERVER_NAME].$_SERVER['REQUEST_URI'], 2)[0]);
  // die();
  # Checa a senha
  if(!password_verify($_POST['pass'],APROVAR_REP_KEY)){
    echo '403 unauthorized';
    die();
  }

  # Seleciona a quantidade de pontos baseado no tipo de contribuicao
  switch(intval($_POST['tipo'])){

    # Tradução
  case 1:
  $pontos = 10;
  break;

  # Revisão de Texto
  case 2:
  $pontos = 10;
  break;

  # Software
  case 3:
  $pontos = 15;
  break;

  # Outro
  case 4:
  $pontos = 10;
  break;

  default:
  $tipo = "INVALIDO, Problema no banco";
  break;

  }
  # Update no BD - aprova e soma pontos a contribuição

  # update contribuicao
  $s =  $db->prepare(" UPDATE info_usuarios.contrib SET aprovado = true WHERE id = ?");
  $s->bindParam(1,intval($_POST['idContrib']),PDO::PARAM_INT);

  # update pontos do usuario
  $u = $db->prepare(" UPDATE info_usuarios.usuario SET pontos = pontos + ? WHERE id = ?");
  $u->bindParam(1,intval($pontos),PDO::PARAM_INT);
  $u->bindParam(2,intval($_POST['idUser']),PDO::PARAM_INT);


  # Se algum dos updates falhar, exibe mensagem de erro
  if( !($s->execute() && $u->execute()) ){
    echo "<h2> Erro ao executar a operação no BD. </h2>";
    die();
  }
  header("Location: ".explode('?',$_SERVER['REQUEST_URI'], 2)[0]."?0gN6DoAL=".$_POST["pass"]);

  die();
}

#FIM POST
############



# Verifica a senha
if(!password_verify($_GET['0gN6DoAL'],APROVAR_REP_KEY)){
  echo '403 unauthorized';
  die();

}

?>
<html>

<head>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<!--
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
-->

<style>
#main {
   margin: auto;
   width: 70% !important;
}

body{
background-color: #DEF5FF;
}

h3{
margin: 30px;
text-align: center;

}
</style>

<title>Aprovação de contribuições</title>
</head>


<body>
<div id='main'>
<h3> Aprovar contribuições</h3>
<table class="table table-dark text-center" align='center'>
  <thead class="thead-dark">
    <tr>
      <th scope="col">Nick</th>
      <th scope="col">Tipo de contribuição</th>
      <th scope="col">URL</th>
      <th scope="col">Descrição</th>
      <th scope="col">#</th>
    </tr>
  </thead>
  <tbody>

</div>

<?php

$s =  $db->prepare("SELECT U.id as idUser,U.nick,C.tipo,C.descricao,C.url,C.id as idContrib
FROM usuario as U
INNER JOIN rel_usuario_contrib as R ON R.idUsuario = U.id
INNER JOIN contrib as C ON C.id = R.idContrib
WHERE C.aprovado = 0");


if($s->execute()){

    while ($row = $s->fetch()){

    switch($row['tipo']){

    case 1:
    $tipo = "Tradução";
    $tipoNum = 1;
    break;

    case 2:
    $tipo = "Revisão de Texto";
    $tipoNum = 2;
    break;

    case 3:
    $tipo = "Software";
    $tipoNum = 3;
    break;

    case 4:
    $tipo = "Outro";
    $tipoNum = 4;
    break;

    default:
    $tipo = "INVALIDO, Problema no banco";
    break;

    }


?>


    <tr>
      <td><?php echo htmlspecialchars($row['nick'],ENT_QUOTES) ?></td>
      <td><?php echo $tipo ?></td>
      <td>
      <a target = "_blank" href="<?php echo htmlspecialchars($row['url'],ENT_QUOTES) ?>"><?php echo htmlspecialchars($row['url'],ENT_QUOTES) ?></a>
      </td>
      <td>
      <textarea name="chave" class="form-control" id="desc" cols="30" rows="2" disabled><?php echo htmlspecialchars($row['descricao'],ENT_QUOTES) ?></textarea>
      </td>
      <td>
      <br>&nbsp&nbsp&nbsp&nbsp

      <form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
        <input type="hidden" name="tipo" value='<?php echo  $tipoNum ?>'>
        <input type="hidden" name="pass" value="<?php echo $_GET['0gN6DoAL'] ?>">
        <input type="hidden" name="idUser" value='<?php echo  $row['idUser'] ?>'>
        <button type="submit" class="btn btn-outline-info" name="idContrib" value="<?php echo  htmlspecialchars($row['idContrib'], ENT_QUOTES) ?>">Aprovar</button>
      </form>
      </td>

    </tr>


<?php
  }
}else{
die();
}

?>

</tbody>
</table>
</body>

</html>
