<?php

require $_SERVER['DOCUMENT_ROOT'].'/admin/conexao.php';
require $_SERVER['DOCUMENT_ROOT'].'/admin/credenciais.php';

# Verifica a senha
if($_GET['0gN6DoAL'] != APROVAR_REP_KEY){
echo 'em construcao';
die();

}

?>
<html>

<head>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<!-- 
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
-->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

<script>
$(document).ready(function(){
    $("button").click(function(){
        $.ajax({
        method: "POST",
        url: "/admin/aprova.php",
        id: this.id,
        data: { id: this.id, Ya9WWMCJ09: '<?php echo APROVAR_API_KEY ?>' }
        }).done(function(msg){
        switch(msg){
        case '1':
        alert("Aprovado com sucesso, recarregando...");
        location.reload(false);
        break;
        
        case '2':
        alert("Erro na conexão ao BD");
        break;
        
        default:
        alert("Erro?");
        
        }
         
        }).fail(function(jqXHR, textStatus, msg){
            alert("Não foi possível completar a requisição\nURL: "+this.url+"\ndados: "+this.data+"\nStatus: "+textStatus);
        });
    });
});

</script>
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

$db = iniciaConexaoDB();
$s =  $db->prepare("SELECT U.nick,C.tipo,C.descricao,C.url,C.id
FROM usuario as U
INNER JOIN rel_usuario_contrib as R ON R.idUsuario = U.id
INNER JOIN contrib as C ON C.id = R.idContrib
WHERE C.aprovado = 0");


if($s->execute()){
  
    while ($row = $s->fetch()){

    switch($row['tipo']){

    case 1:
    $tipo = "Tradução";
    break;

    case 2:
    $tipo = "Revisão de Texto";
    break;

    case 3:
    $tipo = "Software";
    break;

    case 4:
    $tipo = "Outro";
    break;
    
    default:
    $tipo = "INVALIDO, Problema no banco";
    break;
    
    }
    
   
     echo '
    <tr>
      <td>'.htmlspecialchars($row['nick'],ENT_QUOTES).'</td>
      <td>'.$tipo.'</td>
      <td>
      <a target = "_blank" href="'.htmlspecialchars($row['url'],ENT_QUOTES).'">'.htmlspecialchars($row['url'],ENT_QUOTES).'</a>
      </td>
      <td>
      <textarea name="chave" class="form-control" id="desc" cols="30" rows="2" disabled>'.htmlspecialchars($row['descricao'],ENT_QUOTES).'</textarea>
      </td>
      <td>
      <br>&nbsp&nbsp&nbsp&nbsp
      <button type="button" class="btn btn-outline-info" id="'.htmlspecialchars($row['id'], ENT_QUOTES).'">Aprovar</button>
      </td>
    </tr>';
    }
    
}else{
die();
}

?>
</tbody>
</table>
</body>

</html>