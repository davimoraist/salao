<?php
class Usuario {

    public function login($email, $senha) {
        global $pdo;

        $sql = $pdo->prepare( "SELECT * FROM usuario  WHERE email = :email AND password = :password" );

        $sql->bindValue(":email", $email);
        $sql->bindValue(":password", $senha); // sem md5 se o banco for texto
        $sql->execute();

        if ($sql->rowCount() > 0) {
            $dado = $sql->fetch(PDO::FETCH_ASSOC);
            $_SESSION['idusuario'] = $dado['idusuario']; // ajuste ao nome real
            return true;
        }

        return false;
    }

   public function loggod($id){
    global $pdo;

    $sql = $pdo->prepare(
        "SELECT nome FROM usuario WHERE idusuario = :id"
    );
    $sql->bindValue(":id", $id, PDO::PARAM_INT);
    $sql->execute();

    if ($sql->rowCount() > 0) {
        return $sql->fetch(PDO::FETCH_ASSOC);
    }

    return [];
}

}

?>