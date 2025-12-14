 <?php 

class usuario {

    public function login($email, $senha) {

        global $pdo;

        $sql = $pdo->prepare(
        "SELECT * FROM usuario WHERE email = :email AND senha = :senha"
        );

        $sql->bindValue(":email", $email);
        $sql->bindValue(":senha", md5($senha));

        if($sql->rowCount() > 0){
            $dado = $sql->fetch();

            echo $dado['idusuario'];
        }
    }
}
