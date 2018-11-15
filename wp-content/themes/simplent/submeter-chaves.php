<?php
/*
Template Name: Chaves
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

<?php

require  $_SERVER['DOCUMENT_ROOT'].'/admin/conexao.php';
require $_SERVER['DOCUMENT_ROOT'].'/admin/credenciais.php';


# FUNÇÃO : Persiste os dados do usuário em banco de dados.
# TODO: Implementar tratamento de exceção
# RETORNA: null

function persistirDados($nick,$email,$chave,$db){
    $s = $db->prepare("INSERT INTO usuario (nick, email,chave, aprovado) VALUES (?, ?, ?, FALSE)");
    $s->bindParam('1', $nick); 
    $s->bindParam('2', $email);
    $s->bindParam('3', $chave);
    $s->execute();
    
    $a = $db->prepare(" SELECT * FROM usuario;");
    $a->execute();

    
} 

# INICIO VALIDAÇÃO DE CAMPOS
if(isset($_POST["chave"]) && $_POST["chave"]  != '' 
&& isset($_POST["email"]) && $_POST["email"]  != ''
&& isset($_POST["nick"]) && $_POST["nick"]  != ''){
    #Exibe alerta de sucesso
    
    
    $valido = true;
    
    #nick - permite apenas letras, numeros e espaços
    if(preg_match('/[^\d\s\p{L}]/iu',$_POST["nick"])){
        $valido = false;
    }
    
    #email
    if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)){
        $valido = false;
    }
    
    #chave PGP - permite caracteres do base64 (letra,num,-,+,\,quebra de linha, espaço, =)
    if(preg_match('/[^a-z\d\-\+\\\n\/\s\=]/i', $_POST["chave"])){
        $valido = false;
    }
    if($valido){
        
        persistirDados($_POST["nick"],$_POST["email"],$_POST["chave"],iniciaConexaoDB());
        
        
        echo '
        <div class="alert alert-success" role="alert">
        <h4>Obrigado por submeter sua chave e e-mail. Se forem aceitas, serão exibidas <a href="#/chaves">aqui</a>.</h4>
        </div>
        ';
    } else {
        echo '
        <div class="alert alert-danger" role="alert">
        <h4>Caracteres inválidos nos campos informados. Não são permitidos caracteres especiais no nick.</h4>
        </div>
        ';
    }

}else{
if($_SERVER['REQUEST_METHOD'] === 'POST') echo "<script>alert('Favor preencher os campos!');</script>";
}
#var_dump($_POST);


# FINAL VALIDAÇÃO DE CAMPOS
?>
<h1 class='entry-title'> Submissão de e-mails e chaves PGP</h1>


	<div id="primary" class="content-area row">
		<main id="main" class="site-main <?php echo esc_attr($simplent_layout_class); ?>" role="main">


			<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
    <div>
        <label for="nickname">Nick:</label>
        <input type="text" name="nick" />
    </div>
    <div>
        <label for="email">E-mail:</label>
        <input type="email" name="email" />
    </div>
    
    <div>
        <label for="chave">Chave PGP:</label>
        <textarea name="chave"></textarea>
    </div>
    <div class="button">
        <button type="submit">Enviar</button>
    </div>
</form>



		</main><!-- .site-main -->
		<?php get_sidebar(); ?>
	</div><!-- content-area -->

<?php get_footer(); ?>