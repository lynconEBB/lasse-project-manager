<?php
require_once '../Services/Autoload.php';

class CondutorControl extends CrudControl {

    public function __construct(){
        $this->DAO = new CondutorDao();
        parent::__construct();
    }

    public function defineAcao($acao){
        switch ($acao){
            case 1:
                $this->cadastrar();
                header('Location:../View/CondutorView.php');
                break;
            case 2:
                $this->excluir($_POST['id']);
                header('Location:../View/CondutorView.php');
                break;
            case 3:
                $this->atualizar();
                header('Location:../View/CondutorView.php');
                break;
        }
    }

    public function cadastrar(){
        $condutor = new CondutorModel($_POST['nomeCondutor'],$_POST['cnh'],$_POST['validadeCNH']);
        $this->DAO->cadastrar($condutor);
    }

    protected function excluir($id){
        $this -> DAO -> excluir($id);
    }

    public function listar(){
        return $this -> DAO -> listar();
    }

    public function listarPorId($id){
        return $this -> DAO -> listarPorId($id);
    }

    protected function atualizar(){
        $condutor = new CondutorModel($_POST['nomeCondutor'],$_POST['cnh'],$_POST['validadeCNH'],$_POST['id']);
        $this -> DAO -> atualizar($condutor);;
    }
}

$class = new CondutorControl();
