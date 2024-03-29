<?php

namespace Lasse\LPM\Dao;

use Lasse\LPM\Model\CondutorModel;
use PDO;

class CondutorDao extends CrudDao {

    //Insere Objeto CondutorModel no Banco de Dados
    function cadastrar(CondutorModel $condutor){
        $comando = "INSERT INTO tbCondutor (nome,cnh,validadeCNH) values (:nome, :cnh, :validadeCNH)";
        $stm = $this->pdo->prepare($comando);

        $stm->bindValue(':nome',$condutor->getNome());
        $stm->bindValue(':cnh',$condutor->getCnh());
        $stm->bindValue(':validadeCNH',$condutor->getValidadeCNH()->format('Y-m-d'));

        $stm->execute();
    }

    function excluir($id){
        $comando = "DELETE FROM tbCondutor WHERE id = :id";
        $stm = $this->pdo->prepare($comando);

        $stm->bindParam(':id',$id);
        $stm->execute();

    }

    //Retorna todos os condutores em uma lista de objetos da classe modelo CondutorModel
    public function listar(){
        $comando = "SELECT * FROM tbCondutor";
        $stm = $this->pdo->prepare($comando);
        $stm->execute();
        $result =array();
        while($row = $stm->fetch(PDO::FETCH_ASSOC)){
            $obj = new CondutorModel($row['nome'],$row['cnh'],$row['validadeCNH'],$row['id']);
            $result[] = $obj;
        }
        return $result;
    }

    function atualizar(CondutorModel $condutor){

        $comando = "UPDATE tbCondutor SET nome=:nome,cnh=:cnh,validadeCNH=:validadeCNH WHERE id = :id";
        $stm = $this->pdo->prepare($comando);

        $stm->bindValue(':nome',$condutor->getNome());
        $stm->bindValue(':cnh',$condutor->getCnh());
        $stm->bindValue(':validadeCNH',$condutor->getValidadeCNH()->format('Y-m-d'));
        $stm->bindValue(':id',$condutor->getId());

        $stm->execute();

    }

    public function listarPorId($id){
        $comando = "SELECT * from tbCondutor WHERE id = :id";
        $stm = $this->pdo->prepare($comando);

        $stm->bindValue(':id',$id);

        $stm->execute();
        $resul = $stm->fetch(PDO::FETCH_ASSOC);
        if ($resul != false) {
            $obj = new CondutorModel($resul['nome'],$resul['cnh'],$resul['validadeCNH'],$resul['id']);
            return $obj;
        } else {
            return false;
        }
    }

}
