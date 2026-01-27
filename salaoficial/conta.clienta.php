<?php
public function cadastro($name, $email, $password) {
    global $pdo;

    try {
        $sql = $pdo->prepare("
            INSERT INTO cliente (name, email, password, sit)
            VALUES (:name, :email, :password, :sit)
        ");

        $sql->bindValue(":name", $name);
        $sql->bindValue(":email", $email);
        $sql->bindValue(":password", $password);
        $sql->bindValue(":sit", 1); // 1 = ativo (exemplo)

        return $sql->execute(); // ✅ ENVIA PARA O BANCO
    } catch (PDOException $e) {
        echo "ERRO AO SALVAR: " . $e->getMessage();
        exit;
    }
}

?>