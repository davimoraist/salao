<?php
class Usuario {

    public function login($email, $senha) {
        global $pdo;

        $sql = $pdo->prepare( "SELECT * FROM cliente WHERE email = :email AND password = :password" );

        $sql->bindValue(":email", $email);
        $sql->bindValue(":password", $senha); // sem md5 se o banco for texto
        $sql->execute();

        if ($sql->rowCount() > 0) {
            $dado = $sql->fetch(PDO::FETCH_ASSOC);
            $_SESSION['idclinte'] = $dado['idcliente']; // ajuste ao nome real
            return true;
        }

        return false;
    }

   public function loggod($id){
    global $pdo;

    $sql = $pdo->prepare(
        "SELECT nome FROM cliente WHERE idcliente = :id"
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