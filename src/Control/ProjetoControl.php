<?php

namespace Lasse\LPM\Control;

use DateInterval;
use DatePeriod;
use DateTime;
use InvalidArgumentException;
use Lasse\LPM\Dao\AtividadeDao;
use UnexpectedValueException;
use Lasse\LPM\Dao\ProjetoDao;
use Lasse\LPM\Erros\NotFoundException;
use Lasse\LPM\Erros\PermissionException;
use Lasse\LPM\Model\ProjetoModel;

class ProjetoControl extends CrudControl
{
    public function __construct($url)
    {
        $this->requisitor = UsuarioControl::autenticar();
        $this->DAO = new ProjetoDao();
        parent::__construct($url);
    }

    public function processaRequisicao()
    {
        if (!is_null($this->url)) {
            $requisicaoEncontrada = false;
            switch ($this->metodo) {
                case 'POST':
                    $body = json_decode(@file_get_contents("php://input"));
                    // /api/projetos
                    if (count($this->url) == 2) {
                        $requisicaoEncontrada = true;
                        $this->cadastrar($body);
                        $this->respostaSucesso("Projeto Cadastrado com sucesso",null,$this->requisitor);
                    }
                    // /api/projetos/adicionar
                    elseif (count($this->url) == 3 && $this->url[2] == "adicionar") {
                        $requisicaoEncontrada = true;
                        if (isset($body->idProjeto) && isset($body->idUsuario)) {
                            $this->addFuncionario($body->idProjeto,$body->idUsuario);
                            $this->respostaSucesso("Usuario adicionado",null,$this->requisitor);
                        } else {
                            throw new UnexpectedValueException("Parametros insuficientes ou mal estruturados");
                        }
                    }
                    break;
                case 'GET':
                    // /api/projetos
                    if (count($this->url) == 2) {
                        $requisicaoEncontrada = true;
                        $projetos = $this->listar();
                        if ($projetos != false ) {
                            $donosReais =  array();
                            foreach ($projetos as $projeto) {
                                $idDono = $this->DAO->descobreDono($projeto->getId());
                                $donosReais[$projeto->getId()] = $idDono;
                            }
                            $this->requisitor["donosReais"] = $donosReais;
                            $this->respostaSucesso("Listado Projetos",$projetos,$this->requisitor);
                        } else {
                            $this->respostaSucesso("Nenhum projeto encontrado",null,$this->requisitor);
                            http_response_code(202);
                        }
                    // /api/projetos/{idProjeto}
                    } elseif (count($this->url) == 3 && $this->url[2] == (int)$this->url[2]) {
                        $requisicaoEncontrada = true;
                        $projeto = $this->listarPorId($this->url[2]);
                        if ($this->procuraFuncionario($this->url[2],$this->requisitor['id']) || $this->requisitor['admin'] == "1") {
                            $dono = $this->verificaDono($projeto->getId(),$this->requisitor['id']);
                            $this->requisitor["dono"] = $dono;
                            $this->respostaSucesso("Listando Projeto",$projeto,$this->requisitor);
                        } else {
                            throw new PermissionException("Você precisa participar deste projeto para ter acesso à suas informações","Acessar Projeto que não está inserido");
                        }
                    }
                    // /api/projetos/user/{idUsuario}
                    elseif (count($this->url) == 4 && $this->url[3] == (int)$this->url[3] && $this->url[2] == 'user') {
                        $requisicaoEncontrada = true;
                        if ($this->requisitor['id'] == $this->url[3] || $this->requisitor['admin'] == "1") {
                            $projetos = $this->listarPorIdUsuario($this->url[3]);
                            if ($projetos != false) {
                                $donosReais =  array();
                                foreach ($projetos as $projeto) {
                                    $idDono = $this->DAO->descobreDono($projeto->getId());
                                    $donosReais[$projeto->getId()] = $idDono;
                                }
                                $this->requisitor["donosReais"] = $donosReais;
                                $this->respostaSucesso("Listando Projetos por Usuário",$projetos,$this->requisitor);
                            } else {
                                $this->respostaSucesso("Nenhum projeto encontrado!",null,$this->requisitor);
                                http_response_code(201);
                            }
                        } else {
                            throw new PermissionException("Você não possui acesso aos projetos deste usuario","Acessar projetos de outro usuário");
                        }
                    }
                    break;
                case 'PUT':
                    $body = json_decode(@file_get_contents("php://input"));
                    // /api/projetos/gerarGrafico
                    if (count($this->url) == 3 && $this->url[2] == "gerarGrafico") {
                        $requisicaoEncontrada = true;
                        if ($this->requisitor["admin"] == "1") {
                            if (isset($body->mes) && isset($body->ano) && isset($body->idProjeto)) {
                                $dados = $this->gerarGrafico($body);
                                if ($dados != false) {
                                    $this->respostaSucesso("Dados para geração de gráfico",$dados,$this->requisitor);
                                } else {
                                    $this->respostaSucesso("Nenhum dado encontrado",null,$this->requisitor);
                                    http_response_code(202);
                                }
                            } else {
                                throw new UnexpectedValueException("Parametros insuficientes ou mal estruturados");
                            }
                        } else {
                            throw new PermissionException("Você precisa ser Administrador para ter acesso a esta funcionalidade","Gerar Graficos de administrador");
                        }
                    }
                    // /api/projetos/{idProjeto}
                    elseif (count($this->url) == 3 && $this->url[2] == (int)$this->url[2]) {
                        $requisicaoEncontrada = true;
                        $projeto = $this->atualizar($body,$this->url[2]);
                        $this->respostaSucesso("Projeto atualizado com sucesso",null,$this->requisitor);
                    }
                    // /api/projetos/transferirDominio/{idProjeto}
                    elseif (count($this->url) == 4 && $this->url[2] == "transferirDominio" && $this->url[3] == (int)$this->url[3]) {
                        $requisicaoEncontrada = true;
                        $this->listarPorId($this->url[3]);
                        if ($this->verificaDono($this->url[3],$this->requisitor['id'])) {
                            $this->transferirDominio($this->url[3],$body);
                            $this->respostaSucesso("Dominio transferido com sucesso",null,$this->requisitor);
                        } else {
                            throw new PermissionException("Você precisa ser dono deste projeto","Transferir dominio de projeto que não é dono");
                        }
                    }
                    break;
                case 'DELETE':
                    // /api/projetos/{idProjeto}
                    if (count($this->url) == 3 && $this->url[2] == (int)$this->url[2] ) {
                        $requisicaoEncontrada = true;
                        $this->excluir($this->url[2]);
                        $this->respostaSucesso("Projeto excluido com sucesso.",null,$this->requisitor);
                    }
                    // /api/projetos/sair/${idProjeto}
                    elseif (count($this->url) == 4 && $this->url[3] == (int)$this->url[3] && $this->url[2] == "sair" ) {
                        $requisicaoEncontrada = true;
                        $this->removerUsuario($this->url[3]);
                        $this->respostaSucesso("Usuário removido do sistema com sucesso.",null,$this->requisitor);
                    }
                    break;
            }
            if (!$requisicaoEncontrada) {
                throw new NotFoundException("URL não encontrada");
            }
        }
    }

    public function gerarGrafico($body)
    {
        $projeto = $this->listarPorId($body->idProjeto);
        if ($body->mes >= 1 && $body->mes <=12 && $body->ano >= 2005 && $body->ano <= date("Y")) {
            $primeiroDia = new DateTime("$body->ano-$body->mes-01");
            $ultimoDia = new DateTime($primeiroDia->format("Y-m-t"));
            $ultimoDia = $ultimoDia->modify("+1 day");

            $intervalo = new DateInterval("P1D");
            $periodo = new DatePeriod($primeiroDia,$intervalo,$ultimoDia);

            $atividadeDao = new AtividadeDao();
            $atividadesUsuario = $atividadeDao->listarAtividadesUsuariosPeriodo($body->idProjeto,$primeiroDia->format("Y-m-d"));
            $usuarios = $atividadeDao->listarUsuariosComAtividadesNoProjeto($body->idProjeto);
            $participantes = array();
            foreach ($projeto->getParticipantes() as $participante) {
                $participantes[] = $participante->getLogin();
            }
            $participantes = array_unique (array_merge ($usuarios, $participantes));

            if ($atividadesUsuario != false) {
                $labels = [];
                $datasets = [];
                foreach ($participantes as $participante) {
                    $dataset = ["label" => $participante,"data"=>[]];
                    foreach ($periodo as $data) {
                        $tempoGasto = 0;
                        foreach ($atividadesUsuario as $atividadeUsuario) {
                            if ($atividadeUsuario[0] == $data->format("d/m/Y") && $atividadeUsuario["login"] == $participante ) {
                                $tempoGasto += $atividadeUsuario["tempoGasto"];
                            }
                        }
                        $dataset["data"][] = $tempoGasto;
                    }
                    $datasets[]=$dataset;
                }
                foreach ($periodo as $data) {
                    $labels[] = $data->format("d/m/Y");
                }
                return [$labels,$datasets];
            } else {
                return false;
            }
        } else {
            throw new InvalidArgumentException("Mês ou ano inválido");
        }
    }

    public function transferirDominio($idProjeto,$body)
    {
        if (isset($body->idNovoDono)) {
            $usuarioControl = new UsuarioControl(null);
            $usuario = $usuarioControl->listarPorId($body->idNovoDono);
            if ($this->procuraFuncionario($idProjeto,$usuario->getId())) {
                $this->DAO->transferirDominio($idProjeto,$usuario->getId(),$this->requisitor['id']);
            } else {
                throw new InvalidArgumentException("Novo dono não encontrado no projeto");
            }
        } else {
            throw new UnexpectedValueException("Paramentros insuficientes ou mal estruturados");
        }
    }
    
    protected function cadastrar($body)
    {
        if (isset($body->dataFinalizacao) && isset($body->dataInicio) && isset($body->descricao) && isset($body->nome) && isset($body->centroCusto)) {
            $usuarioControl = new UsuarioControl(null);
            $dono = $usuarioControl->listarPorId($this->requisitor['id']);
            $projeto = new ProjetoModel($body->dataFinalizacao, $body->dataInicio, $body->descricao, $body->nome,$body->centroCusto, null, null, null, $dono);
            $this->DAO->cadastrar($projeto);
        } else {
            throw new UnexpectedValueException("Parametros insuficientes ou mal estruturados");
        }
    }

    protected function excluir($id)
    {
        if ($this->verificaDono($id,$this->requisitor['id'])) {
            $this->DAO->excluir($id);
        } else {
            throw new PermissionException("Você precisa ser dono deste projeto para deleta-lo.","Excluir projeto que não é dono");
        }
    }

    public function listar()
    {
        if ($this->requisitor['admin'] == '1') {
            $projetos = $this->DAO->listar();
            return $projetos;
        } else {
            throw new PermissionException("Você precisa ser administrador para acessar essa funcionalidade","Listar todos projetos");
        }
    }

    protected function atualizar($body,$id)
    {
        if (isset($body->dataFinalizacao) && isset($body->dataInicio) && isset($body->descricao) && isset($body->nome) && isset($body->centroCusto)) {
            if ($this->verificaDono($id, $this->requisitor['id'])) {
                $projetoAntigo = $this->listarPorId($id);
                $projeto = new ProjetoModel($body->dataFinalizacao, $body->dataInicio, $body->descricao, $body->nome,$body->centroCusto, $id, null, null, null);
                if (is_array($projetoAntigo->getTarefas())) {
                    foreach ($projetoAntigo->getTarefas() as $tarefa) {
                        if ($tarefa->getDataConclusao() > $projeto->getDataInicio() && $tarefa->getDataConclusao() < $projeto->getDataFinalizacao() && $tarefa->getDataInicio() > $projeto->getDataInicio() && $tarefa->getDataInicio() < $projeto->getDataFinalizacao() ) {
                            continue;
                        } else {
                            throw new InvalidArgumentException("Périodo de existência do projeto incoerente com o périodo de existência de suas tarefas");
                        }
                    }
                }
                $this->DAO->alterar($projeto);
            } else {
                throw new PermissionException("Permissão negada para alterar este projeto","Atualizar projeto que não é dono");
            }
        } else {
            throw new UnexpectedValueException("Parâmetros insuficientes ou mal estruturados");
        }
    }

    public function listarPorIdUsuario($id)
    {
        $projetos = $this->DAO->listarPorIdUsuario($id);
        return $projetos;
    }

    public function listarPorId($id)
    {
        $projeto = $this->DAO->listarPorId($id);
        if ($projeto != false) {
            return $projeto;
        } else {
            throw new NotFoundException("Projeto não encontrado no sistema");
        }
    }

    public function removerUsuario($idProjeto)
    {
        if (!$this->verificaDono($idProjeto,$this->requisitor['id'])) {
            $projeto = $this->listarPorId($idProjeto);
            if ($this->DAO->procuraFuncionario($idProjeto,$this->requisitor['id'])) {
                $this->DAO->removerUsuario($this->requisitor['id'],$idProjeto);
            } else {
                throw new InvalidArgumentException("Usuário não participa do projeto");
            }
        } else {
            throw new InvalidArgumentException("O dono do projeto não pode sair de seu projeto, transfira o dominio do projeto ou exclua-o");
        }
    }

    public function procuraFuncionario($idProjeto, $idUsuario)
    {
        $result = $this->DAO->procuraFuncionario($idProjeto, $idUsuario);
        if ($result > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function atualizaTotal($idProjeto)
    {
        $projeto = $this->DAO->listarPorId($idProjeto);
        $this->DAO->atualizarTotal($projeto);
    }

    public function verificaDono($idProjeto,$idUsuario)
    {
        $numRows = $this->DAO->verificaDono($idProjeto,$idUsuario);
        if ($numRows > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function addFuncionario($idProjeto,$idUsuario)
    {
        $this->listarPorId($idProjeto);
        $usuarioControl = new UsuarioControl(null);
        $usuarioControl->listarPorId($idUsuario);
        if ($this->verificaDono($idProjeto,$this->requisitor['id'])) {
            if (!$this->procuraFuncionario($idProjeto,$idUsuario)) {
                $this->DAO->adicionarFuncionario($idUsuario, $idProjeto);
            } else {
                throw new InvalidArgumentException('Funcionário já inserido');
            }
        } else {
            throw new PermissionException("Você não possui permissão para adicionar funcionários nesse projeto.","Inserir novos funcionários em projeto que não é dono");
        }
    }
}
