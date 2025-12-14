<?php
class Usuario {

    public function login($email, $senha) {
        global $pdo;

        $sql = $pdo->prepare(
            "SELECT idusuario FROM usuario 
             WHERE email = :email AND password = :senha"
        );

        $sql->bindValue(":email", $email);
        $sql->bindValue(":senha", $senha); // ou md5($senha)
        $sql->execute();

        if ($sql->rowCount() > 0) {
            $dado = $sql->fetch(PDO::FETCH_ASSOC);
            $_SESSION['idUser'] = $dado['idusuario'];
            return true;
        }
        return false;
    }
}
