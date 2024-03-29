<?php

namespace Lasse\LPM\Dao;

use Lasse\LPM\Model\ViagemModel;
use PDO;

class ViagemDao extends CrudDao {

    function cadastrar(ViagemModel $viagem, $idTarefa)
    {

        $comando = "INSERT INTO tbViagem (idVeiculo,idTarefa,origem,destino,dataIda,dataVolta,justificativa,observacoes,passagem,saidaHosp,entradaHosp,
idUsuario,totalGasto,fonte,atividade,tipo,tipoPassagem) values (:idVeiculo, :idTarefa, :origem, :destino, :dataIda, :dataVolta, :justificativa, 
:observacoes, :passagem, :saidaHosp, :entradaHosp,:idUsuario,:totalGasto,:fonte,:atividade,:tipo,:tipoPassagem)";
        $stm = $this->pdo->prepare($comando);

        $stm->bindValue(':idVeiculo',$viagem->getVeiculo()->getId());
        $stm->bindValue(':idTarefa',$idTarefa);
        $stm->bindValue(':origem',$viagem->getOrigem());
        $stm->bindValue(':destino',$viagem->getDestino());
        $stm->bindValue(':dataIda',$viagem->getDtIda()->format('Y-m-d'));
        $stm->bindValue(':dataVolta',$viagem->getDtVolta()->format('Y-m-d'));
        $stm->bindValue(':justificativa',$viagem->getJustificativa());
        $stm->bindValue(':observacoes',$viagem->getObservacoes());
        $stm->bindValue(':passagem',$viagem->getPassagem());
        $stm->bindValue(':entradaHosp',$viagem->getEntradaHosp()->format('Y-m-d H:i:s'));
        $stm->bindValue(':saidaHosp',$viagem->getSaidaHosp()->format('Y-m-d H:i:s'));
        $stm->bindValue(':idUsuario',$viagem->getViajante()->getId());
        $stm->bindValue(':totalGasto',$viagem->getTotalGasto());
        $stm->bindValue(':fonte',$viagem->getFonte());
        $stm->bindValue(':atividade',$viagem->getAtividade());
        $stm->bindValue(':tipo',$viagem->getTipo());
        $stm->bindValue(':tipoPassagem',$viagem->getTipoPassagem());
        $stm->execute();

    }

    function excluir($id)
    {
        $comando = "DELETE FROM tbViagem WHERE id = :id";
        $stm = $this->pdo->prepare($comando);

        $stm->bindParam(':id',$id);
        $stm->execute();

    }

    public function listar(){
        $comando = "SELECT * from tbViagem";
        $stm = $this->pdo->prepare($comando);

        $stm->execute();
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $veiculoDAO = new VeiculoDao();
            $funcDAO = new UsuarioDao();
            $gastoDAO = new GastoDao();
            $viagens = array();

            foreach ($rows as $resul){
                $veiculo = $veiculoDAO->listarPorId($resul['idVeiculo']);
                $viajante = $funcDAO->listarPorId($resul['idUsuario']);
                $gastos = $gastoDAO->listarPorIdViagem($resul['id']);
                $obj = new ViagemModel($viajante,$veiculo,$resul['origem'],$resul['destino'],$resul['dataIda'],$resul['dataVolta'],$resul['passagem'],$resul['justificativa'],$resul['observacoes'],$resul['entradaHosp'],$resul['saidaHosp'],$resul['fonte'],$resul['atividade'],$resul['tipoPassagem'],$resul['tipo'],$resul['totalGasto'],$resul['id'],$gastos);
                $viagens[] = $obj;
            }

            return $viagens;
        } else {
            return false;
        }
    }

    function atualizar(ViagemModel $viagem){
        $comando = "UPDATE tbViagem SET origem = :origem, destino=:destino,dataIda=:dataIda, dataVolta=:dataVolta, justificativa=:justificativa, observacoes=:observacoes, passagem=:passagem,
                    idVeiculo=:idVeiculo, entradaHosp=:entradaHosp, saidaHosp=:saidaHosp,idUsuario=:idUsuario,fonte = :fonte, atividade = :atividade, tipoPassagem=:tipoPassagem, tipo =:tipo WHERE id = :id";
        $stm = $this->pdo->prepare($comando);

        $stm->bindValue(':idVeiculo',$viagem->getVeiculo()->getId());
        $stm->bindValue(':id',$viagem->getId());
        $stm->bindValue(':origem',$viagem->getOrigem());
        $stm->bindValue(':destino',$viagem->getDestino());
        $stm->bindValue(':dataIda',$viagem->getDtIda()->format('Y-m-d'));
        $stm->bindValue(':dataVolta',$viagem->getDtVolta()->format('Y-m-d'));
        $stm->bindValue(':justificativa',$viagem->getJustificativa());
        $stm->bindValue(':observacoes',$viagem->getObservacoes());
        $stm->bindValue(':passagem',$viagem->getPassagem());
        $stm->bindValue(':entradaHosp',$viagem->getEntradaHosp()->format('Y-m-d H:i:s'));
        $stm->bindValue(':saidaHosp',$viagem->getSaidaHosp()->format('Y-m-d H:i:s'));
        $stm->bindValue(':idUsuario',$viagem->getViajante()->getId());
        $stm->bindValue(':fonte',$viagem->getFonte());
        $stm->bindValue(':atividade',$viagem->getAtividade());
        $stm->bindValue(':tipo',$viagem->getTipo());
        $stm->bindValue(':tipoPassagem',$viagem->getTipoPassagem());

        $stm->execute();
    }

    public function listarPorIdTarefa($id){
        $comando = "SELECT * from tbViagem WHERE idTarefa = :id";
        $stm = $this->pdo->prepare($comando);

        $stm->bindValue(':id',$id);

        $stm->execute();
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
        $veiculoDAO = new VeiculoDao();
        $funcDAO = new UsuarioDao();
        $gastoDAO = new GastoDao();
        $viagens = array();

        foreach ($rows as $resul){
            $veiculo = $veiculoDAO->listarPorId($resul['idVeiculo']);
            $viajante = $funcDAO->listarPorId($resul['idUsuario']);
            $gastos = $gastoDAO->listarPorIdViagem($resul['id']);
            $obj = new ViagemModel($viajante,$veiculo,$resul['origem'],$resul['destino'],$resul['dataIda'],$resul['dataVolta'],$resul['passagem'],$resul['justificativa'],$resul['observacoes'],$resul['entradaHosp'],$resul['saidaHosp'],$resul['fonte'],$resul['atividade'],$resul['tipoPassagem'],$resul['tipo'],$resul['totalGasto'],$resul['id'],$gastos);
            $obj->setTotalGasto($resul['totalGasto']);
            $viagens[] = $obj;
        }

        return $viagens;
    }

    public function listarPorId($id)
    {
        $comando = "SELECT * from tbViagem WHERE id = :id";
        $stm = $this->pdo->prepare($comando);

        $stm->bindValue(':id',$id);

        $stm->execute();
        $resul = $stm->fetch(PDO::FETCH_ASSOC);
        if ($resul != false) {
            $veiculoDAO = new VeiculoDao();
            $funcDAO = new UsuarioDao();
            $gastoDAO = new GastoDao();

            $veiculo = $veiculoDAO->listarPorId($resul['idVeiculo']);
            $viajante = $funcDAO->listarPorId($resul['idUsuario']);
            $gastos = $gastoDAO->listarPorIdViagem($resul['id']);
            $obj = new ViagemModel($viajante,$veiculo,$resul['origem'],$resul['destino'],$resul['dataIda'],$resul['dataVolta'],$resul['passagem'],$resul['justificativa'],$resul['observacoes'],$resul['entradaHosp'],$resul['saidaHosp'],$resul['fonte'],$resul['atividade'],$resul['tipoPassagem'],$resul['tipo'],null,$resul['id'],$gastos);

            return $obj;
        } else {
            return false;
        }
    }

    public function listarPorIdVeiculo($idVeiculo){
        $comando = "SELECT * from tbViagem WHERE idVeiculo = :id";
        $stm = $this->pdo->prepare($comando);

        $stm->bindValue(':id',$idVeiculo);

        $stm->execute();
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $veiculoDAO = new VeiculoDao();
            $funcDAO = new UsuarioDao();
            $gastoDAO = new GastoDao();
            $viagens = array();

            foreach ($rows as $resul){
                $veiculo = $veiculoDAO->listarPorId($resul['idVeiculo']);
                $viajante = $funcDAO->listarPorId($resul['idUsuario']);
                $gastos = $gastoDAO->listarPorIdViagem($resul['id']);
                $obj = new ViagemModel($viajante,$veiculo,$resul['origem'],$resul['destino'],$resul['dataIda'],$resul['dataVolta'],$resul['passagem'],$resul['justificativa'],$resul['observacoes'],$resul['entradaHosp'],$resul['saidaHosp'],$resul['fonte'],$resul['atividade'],$resul['tipoPassagem'],$resul['tipo'],$resul['totalGasto'],$resul['id'],$gastos);
                $viagens[] = $obj;
            }
            return $viagens;
        } else {
            return false;
        }

    }

    function atualizarTotal(ViagemModel $viagem){
        $comando = "UPDATE tbViagem SET totalGasto =:totalGasto WHERE id = :id";
        $stm = $this->pdo->prepare($comando);

        $stm->bindValue(':totalGasto',$viagem->getTotalGasto());
        $stm->bindValue(':id',$viagem->getId());

        $stm->execute();
    }

    public function descobrirIdTarefa($idViagem)
    {
        $comando = "SELECT idTarefa FROM tbViagem where id = :id";
        $stm = $this->pdo->prepare($comando);
        $stm->bindParam(':id',$idViagem);
        $stm->execute();

        $row = $stm->fetch(PDO::FETCH_ASSOC);

        return $row['idTarefa'];
    }


}
