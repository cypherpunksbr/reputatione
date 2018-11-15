
<?php
/*
Template Name: Enviar_Reputacao
*/

/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @since Simplent 1.0
 */
get_header(); ?>



<?php

/**
 * Simplent Layout Options
 */
$simplent_site_layout    =   get_theme_mod( 'simplent_layout_options_setting' );
$simplent_layout_class   =   'col-md-8 col-sm-12';

if( $simplent_site_layout == 'left-sidebar' && is_active_sidebar( 'sidebar-1' ) ) :
	$simplent_layout_class = 'col-md-8 col-sm-12  site-main-right';
elseif( $simplent_site_layout == 'no-sidebar' || !is_active_sidebar( 'sidebar-1' ) ) :
	$simplent_layout_class = 'col-md-8 col-sm-12 col-md-offset-2';
endif;

?>

	<div id="primary" class="content-area row">
		<main id="main" class="site-main <?php echo esc_attr($simplent_layout_class); ?>" role="main">



<?php
require  $_SERVER['DOCUMENT_ROOT'].'/admin/conexao.php';
require $_SERVER['DOCUMENT_ROOT'].'/admin/credenciais.php';


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
# RETORNA: null

function persistirDados($nick,$url,$obs,$tipo,$db){

    # Verifica se o usuário já existe no banco
    $userExiste = false;
    if(USERS){
        foreach(USERS as $user){
            if(strcasecmp($nick,$user['nick']) === 0){
                $userExiste = true;
                $idUserAtual = $user['id'];
            }
        }
    }
    
    # Se não existir, cria o usuário
    if(!$userExiste){
        $s = $db->prepare("INSERT INTO info_usuarios.usuario (nick,aprovado) VALUES (?, FALSE )");
        $s->bindParam('1', $nick);
        if(!$s->execute()){
            die();
        }
        $idUserAtual = $db->lastInsertId();
    }
    
    
    # Insere o pedido de contribuição
    if($obs != NULL){
        $c = $db->prepare("INSERT INTO info_usuarios.contrib (tipo, url, descricao, aprovado) VALUES (?,?,?,FALSE)");
        $c->bindParam('1',intval($tipo), PDO::PARAM_INT);
        $c->bindParam('2',$url);
        $c->bindParam('3',$obs);
        try{
        $c->execute();
        
        }catch(PDOException $e){
            die();
        
        }
        
        
        $idContribuicao = $db->lastInsertId();
    }else{
        
        $c = $db->prepare("INSERT INTO info_usuarios.contrib (tipo, url, aprovado) VALUES (?,?,FALSE)");
        $c->bindParam('1',intval($tipo), PDO::PARAM_INT);
        $c->bindParam('2',$url);
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


}


# INICIO VALIDAÇÃO DE CAMPOS
if(isset($_POST["url"]) && $_POST["url"]  != '' 
&& isset($_POST["nick"]) && $_POST["nick"]  != ''
&& isset($_POST["g-recaptcha-response"]) && $_POST["g-recaptcha-response"]  != ''
&& isset($_POST["tipo"]) && $_POST["tipo"]  != ''){
    
    $valido = true;
    $obs = false; 
    
    
    #nick - permite apenas letras, numeros e espaços
    if(preg_match('/[^\d\s\p{L}]/iu',$_POST["nick"])){
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
    
    
    # Valida ReCaptcha
    if(!verificaCaptcha($_POST["g-recaptcha-response"])){
        $valido = false;
    }
    
    if($valido){
        # Se a observação não for vazia e passar por todos os filtros, será adicionada no BD
        if($obs){
            persistirDados($_POST["nick"],$_POST["url"],$_POST["obs"],$_POST["tipo"],$db);
        }else{
            persistirDados($_POST["nick"],$_POST["url"],NULL,$_POST["tipo"],$db);
        }
        
        
        echo '
        <div class="alert alert-success" role="alert">
        <h4>Obrigado por submeter seu pedido. Se for aceito, poderá consultar sua reputação <a href="#">aqui</a>.</h4>
        </div>
        ';
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
<div id="primary" class="content-area row">
<main id="main" class="site-main <?php echo esc_attr($simplent_layout_class); ?>" role="main">



<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
    <div>
        <label for="nickname">Nick:</label>
        <input type="text" name="nick"  required/>
    </div>
     <div>
        <label for="url">Link da contribuição:</label>
        <input type="url" name="url"/>
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
        <br><br>
     </div>
     <div class="g-recaptcha" data-sitekey="6LfDNXUUAAAAAK1CItloBYDko5hG4pXDxZaXpxZR"></div>
    <div class="button">
        <button type="submit">Enviar</button>
    </div>
</form>

<script>
var obs = document.getElementById('obs');
obs.onkeyup = function(e){
    obs.value = obs.value.substring(0,60);
    obstxt = document.getElementById('obstxt');
    obstxt.innerHTML = '(não obrigatório, max 60 caracteres - '+(60-obs.value.length)+' restantes)';
};
</script>


		</main><!-- .site-main -->
		<?php get_sidebar(); ?>
	</div><!-- content-area -->

<?php get_footer(); ?>