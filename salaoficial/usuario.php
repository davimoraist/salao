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

        $array = array(); 
        $array = [];

        $sql = "SELECT nome FROM usuario WHERE idusuario = :id";
        $sql = $pdo->prepare($sql);
        $sql->bindValue(":idusuario", $id);
        $sql->execute();
        
        if ($sql->rowCount() > 0) {
            $array = $sql->fetch(PDO::FETCH_ASSOC);
        }

        return $array;
    }
}
?>