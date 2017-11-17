<?php

class Proposta_VisualizarController extends Proposta_GenericController
{
    public function init()
    {
        parent::init();

    }

    public function indexAction()
    {
    }

    public function identificacaoAction()
    {
        $this->_helper->layout->disableLayout();

        try {
            $dados = $this->_proposta;

            if (is_array($dados)) {
                $dados['stdatafixa'] = ($dados['stdatafixa']) ? 'Sim' : 'N&atilde;o';
                $dados['areaabrangencia'] = ($dados['areaabrangencia']) ? 'Sim' : 'N&atilde;o';
                $dados['tpprorrogacao'] = ($dados['tpprorrogacao']) ? 'Sim' : 'N&atilde;o';
                $dados['stproposta'] = ($dados['stproposta']) ? 'Sim' : 'N&atilde;o';
            }

            $dados = array_map('utf8_encode', $dados);
            $dados = array_map('html_entity_decode', $dados);

            $this->_helper->json(array('success' => 'true', 'msg' => '', 'data' => $dados));
        } catch (Exception $e) {
            $this->_helper->json(array('success' => 'false', 'msg' => $e->getMessage(), 'data' => []));
        }
    }

    public function historicoAvaliacoesAction()
    {
        $this->_helper->layout->disableLayout();

        try {
            $dados = Proposta_Model_AnalisarPropostaDAO::buscarHistorico($this->idPreProjeto);
            $json = [];
            $newArray = [];
            foreach ($dados as $key => $dado) {
                $newArray[$key]['tipo'] = $dado->tipo;
                $newArray[$key]['Avaliacao'] = $dado->Avaliacao;
            }

            $json['lines'] = $newArray;
            $json['cols'] = ['#', 'Avalia&ccedil;&atilde;o'];
            $json['title'] = 'Hist&oacute;rico de avalia&ccedil;&otilde;es';

            $this->_helper->json(array('success' => 'true', 'msg' => '', 'data' => $json));
        } catch (Exception $e) {
            $this->_helper->json(array('success' => 'false', 'msg' => $e->getMessage(), 'data' => []));
        }
    }

    public function dadosProponenteAction()
    {
        $this->_helper->layout->disableLayout();

        $idAgente = $this->_request->getParam('idagente');

        $dados = [];
        $matriz = [];

        $tbAgentes = new Agente_Model_DbTable_Agentes();
        $dados['identificacao'] = array_change_key_case(array_map('utf8_encode', $tbAgentes->buscarAgenteENome(['a.idAgente = ?' => $idAgente])->current()->toArray()));

        $tbEndereco = new Agente_Model_DbTable_EnderecoNacional();
        $matriz['enderecos'] = $tbEndereco->buscarEnderecos($idAgente)->toArray();

        $tbInternet = new Agente_Model_DbTable_Internet();
        $matriz['emails'] = $tbInternet->buscarEmails($idAgente)->toArray();

        $tbTelefones = new Agente_Model_DbTable_Telefones();
        $matriz['telefones'] = $tbTelefones->buscarFones($idAgente)->toArray();


        $matriz['dirigentes'] = [];
        if (strlen($dados['proponente']['CNPJCPF']) > 11) {
            $matriz['dirigentes'] = $tbAgentes->buscarDirigentes(array('a.idAgente = ?' => $idAgente))->toArray();
        }

        foreach ($matriz as $key => $array) {
            foreach ($array as $key2 => $dado) {
                $dados[$key][$key2] = array_change_key_case(array_map('utf8_encode', $dado));
            }
        }

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

    public function documentosAnexadosAction($idPreProjeto)
    {
        $this->_helper->layout->disableLayout();

        $dados = [];

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

    public function localDeRealizacaoAction()
    {
        $this->_helper->layout->disableLayout();

        $arrBusca = array();
        $arrBusca['idprojeto'] = $this->idPreProjeto;
        $arrBusca['stabrangencia'] = 1;
        $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $rsAbrangencia = $tblAbrangencia->buscar($arrBusca);

        $dados = [];

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

    public function deslocamentoAction($idPreProjeto)
    {
        $this->_helper->layout->disableLayout();

        $deslocamentos = new Proposta_Model_TbDeslocamentoMapper();
        $dados = $deslocamentos->getDbTable()->buscarDeslocamento($idPreProjeto, $id);

        $dados = [];

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

    public function planilhaOrcamentariaPropostaAction($idPreProjeto)
    {
        $this->_helper->layout->disableLayout();

        $dados = [];

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

    public function planoDeDivulgacaoAction($idPreProjeto)
    {
        $this->_helper->layout->disableLayout();

        $dados = [];

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

    public function planoDistribuicacaoAction($idPreProjeto)
    {
        $this->_helper->layout->disableLayout();

        $dados = [];

        $this->_helper->layout->disableLayout();

        $arrBusca = array();
        $arrBusca['idprojeto'] = $this->idPreProjeto;

        $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $rsAbrangencia = $tblAbrangencia->buscar($arrBusca);
        $this->view->abrangencias = $rsAbrangencia;

        $tblPlanoDistribuicao = new PlanoDistribuicao();

        $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar(
            array("a.idprojeto = ?" => $this->idPreProjeto, "a.stplanodistribuicaoproduto = ?" => 1),
            array("idplanodistribuicao DESC")
        );

        $this->view->planosDistribuicao = $rsPlanoDistribuicao;
        $this->abrangencias = $rsAbrangencia;

        //@todo enviar montado

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

    public function detalhamentoPlanoDistribuicao($idPlanoDistribuicacao)
    {
        $this->_helper->layout->disableLayout();

        $dados = [];

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }
}
