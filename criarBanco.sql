# Para funcionar, o usuário do MySQL tem que ter permissão para criar essa tabela.
# Se o usuário tem acesso total isso não é problema, mas caso não tenha, precisa dar permissão pra criar especificamente este nome de tabela com:
# grant all privileges on info_usuarios.* to 'usuario'@'localhost';

CREATE DATABASE info_usuarios;

USE info_usuarios;

# Usuário e contribuições precisam serem aprovadas antes de aparecerem para o público.

# Tabela de usuarios
CREATE TABLE usuario(
    id int  PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nick VARCHAR(255),
    senha VARCHAR(255),
    email VARCHAR(255),
    recovery_mail VARCHAR(255),
    chave TEXT,
    pontos int default 0,
    aprovado boolean
 );
 

# Tabela de Contribuições
CREATE TABLE contrib(
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    tipo int,
    descricao VARCHAR(255),
    url VARCHAR(255),
    aprovado boolean
);


# Tabela abaixo relaciona usuário com contribuição (1 usuário -> 0..* contribuições)
CREATE TABLE rel_usuario_contrib(
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    idUsuario int not null,
    idContrib int not null,
    FOREIGN KEY (idUsuario) REFERENCES usuario(id),
    FOREIGN KEY (idContrib) REFERENCES contrib(id)
);