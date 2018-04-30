<?php

class Readequacao_LocalRealizacaoController extends MinC_Controller_Action_Abstract
{
    public function init()
    {
        parent::init();
    }

    public function obterLocaisReadequacaoAjaxAction() {
        $this->_helper->layout->disableLayout();

        $idPronac = $this->_request->getParam('idPronac');

        $arrBusca = array();
        $arrBusca['idprojeto'] = $idPronac;
        $arrBusca['stabrangencia'] = 1;
        $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $locais = $tblAbrangencia->buscar($arrBusca);

        foreach ($locais as $key => $dado) {
            $locais[$key] = array_map('utf8_encode', $dado);
        }

        $this->_helper->json(array('data' => $locais, 'success' => 'true'));
    }

    /*
     * Criada em 19/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun��o � usada para carregar os dados do locais de realiza��o do projeto.
     */
    public function carregarLocaisDeRealizacaoAction()
    {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo
        $this->view->idPerfil = $GrupoAtivo->codGrupo;

        if (isset($_POST['iduf'])) {
            $iduf = $_POST['iduf'];

            $mun = new Agente_Model_DbTable_Municipios();
            $cidade = $mun->listar($iduf);
            $a = 0;
            $cidadeArray = array();
            foreach ($cidade as $DadosCidade) {
                $cidadeArray[$a]['idCidade'] = $DadosCidade->id;
                $cidadeArray[$a]['nomeCidade'] = utf8_encode($DadosCidade->Descricao);
                $a++;
            }
            $this->_helper->json($cidadeArray);
            die;
        }

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }
        $tbAbrangencia = new Readequacao_Model_DbTable_TbAbrangencia();
        $locais = $tbAbrangencia->buscarLocaisParaReadequacao($idPronac, 'tbAbrangencia');
        if (count($locais)==0) {
            $locais = $tbAbrangencia->buscarLocaisParaReadequacao($idPronac, 'Abrangencia');
        }

        $tbPais = new Pais();
        $this->view->Paises = $tbPais->buscar(array(), array(3));

//        $buscarEstado = EstadoDAO::buscar();
        $uf = new Agente_Model_DbTable_UF();
        $buscarEstado = $uf->buscar();
        $this->view->UFs = $buscarEstado;

        $get = Zend_Registry::get('get');
        $link = isset($get->link) ? true : false;

        $this->montaTela(
            'local-realizacao/carregar-locais-de-realizacao.phtml',
            array(
                'idPronac' => $idPronac,
                'locaisDeRealizacao' => $locais,
                'link' => $link
            )
        );
    }

    /*
     * Essa fun��o � usada para carregar os dados do locais de realiza��o do projeto.
     */
    public function carregarLocaisDeRealizacaoReadequacoesAction()
    {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo
        $this->view->idPerfil = $GrupoAtivo->codGrupo;

        $idPronac = $this->_request->getParam("idPronac");
        $idReadequacao = $this->_request->getParam("idReadequacao");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbAbrangencia = new Readequacao_Model_DbTable_TbAbrangencia();
        $locais = $tbAbrangencia->buscarLocaisConsolidadoReadequacao($idReadequacao);

        $tbReadequacao = new Readequacao_Model_tbReadequacao();
        $dadosReadequacao = $tbReadequacao->buscar(array('idReadequacao=?'=>$idReadequacao))->current();
        $siEncaminhamento = $dadosReadequacao->siEncaminhamento;

        $tbPais = new Pais();
        $this->view->Paises = $tbPais->buscar(array(), array(3));


        $uf = new Agente_Model_DbTable_UF();
        $this->view->UFs = $uf->buscar();
        $get = Zend_Registry::get('get');
        $link = isset($get->link) ? true : false;

        $this->montaTela(
            'local-realizacao/carregar-locais-de-realizacao.phtml',
            array(
                'idPronac' => $idPronac,
                'locaisDeRealizacao' => $locais,
                'link' => $link,
                'idReadequacao' => $idReadequacao,
                'siEncaminhamento' => $siEncaminhamento
            )
        );
    }

    public function incluirLocalDeRealizacaoAction()
    {
        $this->_helper->layout->disableLayout();
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        //VERIFICA SE JA POSSUI AS ABRANGENCIAS NA TABELA tbAbrangencia (READEQUACAO), SE N�O TIVER, COPIA DA ORIGINAL, E DEPOIS INCLUI O ITEM DESEJADO.
        $tbAbrangencia = new Readequacao_Model_DbTable_TbAbrangencia();
        $readequacaoLR = $tbAbrangencia->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S'));
        $locaisAtivos = $tbAbrangencia->buscarLocaisParaReadequacao($idPronac);

        if (count($readequacaoLR)==0) {
            $locaisCopiados = array();
            foreach ($locaisAtivos as $value) {
                $locaisCopiados['idReadequacao'] = null;
                $locaisCopiados['idPais'] = $value->idPais;
                $locaisCopiados['idUF'] = $value->idUF;
                $locaisCopiados['idMunicipioIBGE'] = $value->idCidade;
                $locaisCopiados['tpSolicitacao'] = 'N';
                $locaisCopiados['stAtivo'] = 'S';
                $locaisCopiados['idPronac'] = $idPronac;
                $tbAbrangencia->inserir($locaisCopiados);
            }
        }

        if ($_POST['newPaisLR'] == 31) {
            if (empty($_POST['newUFLR']) && empty($_POST['newMunicipioLR'])) {
                $msg = utf8_encode('Ao escolher o Brasil, os campos de UF e Munic�pio se tornam obrigat�rios no cadastro!');
                $this->_helper->json(array('resposta'=>false, 'msg'=>$msg));
                $this->_helper->viewRenderer->setNoRender(true);
            }
            $verificaLocalRepetido = $tbAbrangencia->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S', 'idPais=?'=>$_POST['newPaisLR'], 'idUF=?'=>$_POST['newUFLR'], 'idMunicipioIBGE=?'=>$_POST['newMunicipioLR']));
        } else {
            $verificaLocalRepetido = $tbAbrangencia->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S', 'idPais=?'=>$_POST['newPaisLR']));
        }

        if (count($verificaLocalRepetido)==0) {
            /* DADOS DO ITEM PARA INCLUS�O DA READEQUA��O */
            $dadosInclusao = array();
            $dadosInclusao['idReadequacao'] = null;
            $dadosInclusao['idPais'] = $_POST['newPaisLR'];
            $dadosInclusao['idUF'] = isset($_POST['newUFLR']) ? $_POST['newUFLR'] : 0;
            $dadosInclusao['idMunicipioIBGE'] = isset($_POST['newMunicipioLR']) ? $_POST['newMunicipioLR'] : 0;
            $dadosInclusao['tpSolicitacao'] = 'I';
            $dadosInclusao['stAtivo'] = 'S';
            $dadosInclusao['idPronac'] = $idPronac;
            $insert = $tbAbrangencia->inserir($dadosInclusao);
            if ($insert) {
                //$jsonEncode = json_encode($dadosPlanilha);
                $this->_helper->json(array('resposta'=>true));
            } else {
                $this->_helper->json(array('resposta'=>false));
            }
        } else {
            $msg = utf8_encode('Esse local de realiza��o j� foi cadastrado!');
            $this->_helper->json(array('resposta'=>false, 'msg'=>$msg));
        }
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /*
     * Esse fun��o � usada pelo proponente para solicitar a exclus�o de um local de realiza��o.
     */
    public function excluirLocalDeRealizacaoAction()
    {
        $this->_helper->layout->disableLayout();
        $idAbrangencia = $this->_request->getParam("idAbrangencia");
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbAbrangencia = new Readequacao_Model_DbTable_TbAbrangencia();
        $readequacaoLR = $tbAbrangencia->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S'));

        //VERIFICA SE JA POSSUI AS ABRANGENCIAS NA TABELA tbAbrangencia (READEQUACAO), SE N�O TIVER, COPIA DA ORIGINAL, E DEPOIS INCLUI O ITEM DESEJADO.
        $locaisAtivos = $tbAbrangencia->buscarLocaisParaReadequacao($idPronac);
        if (count($readequacaoLR)==0) {
            $locaisCopiados = array();
            foreach ($locaisAtivos as $value) {
                $locaisCopiados['idReadequacao'] = null;
                $locaisCopiados['idPais'] = $value->idPais;
                $locaisCopiados['idUF'] = $value->idUF;
                $locaisCopiados['idMunicipioIBGE'] = $value->idCidade;
                $locaisCopiados['tpSolicitacao'] = 'N';
                $locaisCopiados['stAtivo'] = 'S';
                $locaisCopiados['idPronac'] = $idPronac;
                $tbAbrangencia->inserir($locaisCopiados);
            }
        }

        /* DADOS DO ITEM PARA EXCLUSAO L�GICA DO ITEM DA READEQUACAO */
        $dados = array();
        $dados['tpSolicitacao'] = 'E';

        $itemLR = $tbAbrangencia->buscar(array('idAbrangencia=?'=>$idAbrangencia))->current();
        if ($itemLR) {
            if ($itemLR->tpSolicitacao == 'I') {
                $exclusaoLogica = $tbAbrangencia->delete(array('idAbrangencia = ?'=>$idAbrangencia));
            } else {
                $where = "stAtivo = 'S' AND idAbrangencia = $idAbrangencia";
                $exclusaoLogica = $tbAbrangencia->update($dados, $where);
            }
        } else {
            $Abrangencia = new Proposta_Model_DbTable_Abrangencia();
            $itemLR = $Abrangencia->find(array('idAbrangencia=?'=>$idAbrangencia))->current();
            $dadosArray = array(
                'idPais =?' => $itemLR->idPais,
                'idUF =?' => $itemLR->idUF,
                'idMunicipioIBGE =?' => $itemLR->idMunicipioIBGE,
                'idPronac =?' => $idPronac,
                'stAtivo =?' => 'S',
            );
            $itemLR = $tbAbrangencia->buscar($dadosArray)->current();
            $where = "stAtivo = 'S' AND idAbrangencia = $itemLR->idAbrangencia";
            $exclusaoLogica = $tbAbrangencia->update($dados, $where);
        }

        if ($exclusaoLogica) {
            //$jsonEncode = json_encode($dadosPlanilha);
            $this->_helper->json(array('resposta'=>true));
        } else {
            $this->_helper->json(array('resposta'=>false));
        }
        $this->_helper->viewRenderer->setNoRender(true);
    }
}