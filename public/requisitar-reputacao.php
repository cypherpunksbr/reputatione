<html>
<head>
<link rel="stylesheet" type="text/css" href="page.css">
</head>
<body>
<div id = "main">

<?php




require  $_SERVER['DOCUMENT_ROOT'].'/reputatione/admin/conexao.php';
require $_SERVER['DOCUMENT_ROOT'].'/reputatione/admin/credenciais.php';


$db = iniciaConexaoDB();
$u = $db->prepare("SELECT id,nick FROM info_usuarios.usuario WHERE aprovado = false;");
$u->execute();
$users = $u->fetchAll();
define('USERS', $users);




# FUNÇÃO: Verifica o token ReCaptcha
# RETORNA: boolean
function verificaCaptcha($token){

# declarando variaveis
$url = 'https://www.google.com/recaptcha/api/siteverify';
$post = array(
'secret' => CAPTCHA_KEY,
'response' => $token
);

$post = http_build_query($post);

# requisição para API da google

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

# checando resultado

#print_r($response);
$response = json_decode($response);

return $response->success;
}


# FUNÇÃO : Persiste os dados do usuário em banco de dados.
# @returns:  array

function persistirDados($db){
    # Retorno da função
    $arrayRetorno;
    # Verifica se o usuário já existe no banco
    $userExiste = false;
    if(USERS){
        foreach(USERS as $user){
            if(strcasecmp($_POST['nick'],$user['nick']) === 0){
                $userExiste = true;
                $idUserAtual = $user['id'];
            }
        }
    }

    # Se não existir, cria o usuário
    if(!$userExiste){
      # Cria senha aleatória
        $pass = base64_encode(random_bytes(20));
        $hash = password_hash($pass,PASSWORD_ARGON2I);
        //var_dump($pass);
        //var_dump($hash);
        //var_dump($_POST['email']);
        $s = $db->prepare("INSERT INTO info_usuarios.usuario (nick,aprovado,senha,recovery_mail) VALUES (?, FALSE, ?, ? )");
        $s->bindParam('1', $_POST['nick']);
        $s->bindParam('2',$hash );
        $s->bindParam('3',$_POST['email'] );
        if(!$s->execute()){
            die();
        }
        $idUserAtual = $db->lastInsertId();
        $arrayRetorno['pass'] = $pass;
    }


    # Insere o pedido de contribuição
    if($_POST['obs'] != ''){
        $c = $db->prepare("INSERT INTO info_usuarios.contrib (tipo, url, descricao, aprovado) VALUES (?,?,?,FALSE)");
        $c->bindParam('1',intval($_POST['tipo']), PDO::PARAM_INT);
        $c->bindParam('2',$_POST['url']);
        $c->bindParam('3',$_POST['obs']);
        try{
        $c->execute();

        }catch(PDOException $e){
          var_dump($e);
            die();

        }


        $idContribuicao = $db->lastInsertId();
    }else{

        $c = $db->prepare("INSERT INTO info_usuarios.contrib (tipo, url, aprovado) VALUES (?,?,FALSE)");
        $c->bindParam('1',intval($_POST['tipo']), PDO::PARAM_INT);
        $c->bindParam('2',$_POST['url']);
        if(!$c->execute()){

            die();
        }
        $idContribuicao = $db->lastInsertId();
    }


    # Insere relacionamento
    $c = $db->prepare("INSERT INTO info_usuarios.rel_usuario_contrib (idUsuario, idContrib) VALUES (?,?)");
    $c->bindParam('1',intval($idUserAtual), PDO::PARAM_INT);
    $c->bindParam('2',intval($idContribuicao), PDO::PARAM_INT);
        if(!$c->execute()){
            die();
        }
    return $arrayRetorno;
}


# INICIO VALIDAÇÃO DE CAMPOS
if(isset($_POST["url"]) && $_POST["url"]  != ''
&& isset($_POST["nick"]) && $_POST["nick"]  != ''
&& isset($_POST["email"]) && $_POST["email"]  != ''
// && isset($_POST["g-recaptcha-response"]) && $_POST["g-recaptcha-response"]  != ''
&& isset($_POST["tipo"]) && $_POST["tipo"]  != ''){

    $valido = true;
    $obs = false;


    #nick - permite apenas letras, numeros e espaços
    if(preg_match('/[^\d\s\p{L}]/iu',$_POST["nick"])){
        $valido = false;

    }

    #email
    if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)){
        $valido = false;
    }

    #tipo - permite 1-4
    if(preg_match('/[^1-4]/',$_POST["tipo"])){
        $valido = false;

    }

    # observação - permite apenas letras, numeros, espaços e quebra de linha - MAX 60 CHARS
    if(isset($_POST["obs"]) && $_POST["obs"]  != ''){
        $obs = true;
        if(preg_match('/[^\p{L}\d\s\n\.]/iu', $_POST["obs"])){
            $valido = false;

        }

        # Checa tamanho da observação
        if(mb_strlen($_POST["obs"], 'utf8') > 60){
            $valido = false;
        }
    }

    # Valida e-mail
    if(!filter_var($_POST["url"], FILTER_VALIDATE_URL)){
        $valido = false;

    }


    # Valida ReCaptcha - desativado temporariamente
    // if(!verificaCaptcha($_POST["g-recaptcha-response"])){
    //     $valido = false;
    // }
    ?>


      <?php

    if($valido){
        # Se a observação não for vazia e passar por todos os filtros, será adicionada no BD
            $arrayRetorno = persistirDados($db);

        if(isset($arrayRetorno['pass'])){
          $pass = "<h3>Por favor, guarde sua SENHA para poder gerar seu e-mail quando possuir pontos suficientes:</h3>
                   <p style='font-weight:bold;color:red;'>".$arrayRetorno['pass']."</p>";
        }
        ?>

        <div class="alert alert-success" role="alert">
        <h4>Obrigado por submeter seu pedido. Se for aceito, poderá consultar sua reputação <a href="#">aqui</a></h4>
        <?php if(!is_null($arrayRetorno['pass'])) echo $pass; ?>
        </div>
        <?php
    } else {
        echo '
        <div class="alert alert-danger" role="alert">
        <h4>Caracteres inválidos nos campos informados. Não são permitidos caracteres especiais no nick, e a observação deve ter 60 caracteres ou menos.</h4>
        </div>
        ';
    }

}else{
if($_SERVER['REQUEST_METHOD'] === 'POST') echo "<script>alert('Favor preencher os campos!');</script>";
}
			?>



<h1 class='entry-title'> Requisitar reputação</h1>
<br>
<p>Para que consiga ter seu e-mail e chave PGP no site, é necessário que você contribua com o projeto.</p>
<p>Caso você tenha contribuido e deseje receber seus pontos, preencha o formulário abaixo.</p>
<br>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
        <div>
        <label for="nickname">Nick:</label>
        <input type="text" name="nick"  required/>
    </div>
     <div>
        <label for="url">Link da contribuição:</label>
        <br>
        <input type="url" name="url"/>
    </div>
    <div>
        <label for="email">E-mail de recuperação (caso esqueça sua senha):</label>
        <input type="email" name="email" />
    </div>
    <div>
        <br>
        <label for="observação" >Observação:</label>
        <label for="observação" id="obstxt" >(não obrigatório, max 60 caracteres - 60 restantes)</label>
        <textarea name="obs" id="obs"></textarea>
    </div>
     <div>
        <label for="utipo">Selecione o tipo de contribuição:</label>
        <select name="tipo">
        <option value="1">Tradução</option>
        <option value="2">Revisão de Texto</option>
        <option value="3">Software</option>
        <option value="4">Outro</option>
        </select>

     </div>
     <!--
     <div id="captcha">
     <div class="g-recaptcha" data-sitekey="6LfDNXUUAAAAAK1CItloBYDko5hG4pXDxZaXpxZR"></div>
    </div>
    -->
    <div>
        <button type="submit" class="btn">Enviar</button>
    </div>
</form>
</div>
<script>
var obs = document.getElementById('obs');
obs.onkeyup = function(e){
    obs.value = obs.value.substring(0,60);
    document.getElementById('obstxt').innerHTML = '(não obrigatório, max 60 caracteres - '+(60-obs.value.length)+' restantes)';
};
</script>
	</body>
	</html>
