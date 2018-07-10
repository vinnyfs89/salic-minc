<?php

class Projeto_IncentivoController extends Projeto_GenericController
{
    protected $blnProponente  = false;
    protected $blnProcurador  = false;
    protected $intFaseProjeto = 0;
    protected $intTamPag 	    = 10;
    protected $idResponsavel  = 0;
    protected $bln_readequacao = "false";
    protected $idPreProjeto   = 0;
    protected $projeto;
    protected $idPronac;

    /**
     * Reescreve o metodo init()
     * @access public
     * @param void
     * @return void
     */
    public function init()
    {
        // verifica as permissoes
        $PermissoesGrupo = array();
        $PermissoesGrupo[] = 91; // Protocolo recebimento

        $auth = Zend_Auth::getInstance();
        $this->view->usuarioInterno = false;

        //Da permissao de acesso a todos os grupos do usuario logado afim de atender o UC75
        if (isset($auth->getIdentity()->usu_codigo)) {
            $this->view->usuarioInterno = true;

            //Recupera todos os grupos do Usuario
            $Usuario = new Autenticacao_Model_DbTable_Usuario(); // objeto usuario
            $grupos = $Usuario->buscarUnidades($auth->getIdentity()->usu_codigo, 21);
            foreach ($grupos as $grupo) {
                $PermissoesGrupo[] = $grupo->gru_codigo;
            }
        }

        if (isset($auth->getIdentity()->usu_codigo)) { // autenticacao novo salic
            parent::perfil(1, $PermissoesGrupo);
            $this->getIdUsuario = UsuarioDAO::getIdUsuario($auth->getIdentity()->usu_codigo);
            $this->getIdUsuario = ($this->getIdUsuario) ? $this->getIdUsuario["idAgente"] : 0;
        } else { // autenticacao scriptcase
            $this->blnProponente = true;
            parent::perfil(4, $PermissoesGrupo);
            
            $this->getIdUsuario = $this->_request->getParam("idPronac", 0);

            /* =============================================================================== */
            /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
            /* =============================================================================== */
            $this->verificarPermissaoAcesso(false, true, false);
        }
        parent::init();

        //SE CAIU A SECAO REDIRECIONA
        if (!$auth->hasIdentity()) {
            $url = Zend_Controller_Front::getInstance()->getBaseUrl();
            $this->redirect($url);
        }

        $this->idPronac = $this->_request->getParam("idPronac");

        if (strlen($this->idPronac) > 7) {
            $this->idPronac = Seguranca::dencrypt($this->idPronac);
        }

        if (empty($this->idPronac)) {
            parent::message("Pronac &eacute; obrigat&oacute;rio!", "listarprojetos/listarprojetos", "ERROR");
        }

        $this->view->idPronac = $this->idPronac;
        $this->view->idPronacHash = Seguranca::encrypt($this->idPronac);
        $this->view->urlMenu = [
            'module' => 'projeto',
            'controller' => 'menu',
            'action' => 'obter-menu-ajax',
            'idPronac' => $this->view->idPronacHash
        ];
        
        if (!isset($auth->getIdentity()->usu_codigo)) {
            $this->view->blnProponente = $this->blnProponente;

            $proj = new Projetos();
            $this->projeto = $proj->buscar(array('IdPRONAC = ?' => $this->idPronac))->current();

            if (empty($this->projeto)) {
                parent::message("Nenhum projeto encontrado com o n&uacute;mero de Pronac informado.", "listarprojetos/listarprojetos", "ERROR");
            }

        }
    }

    private function carregarScripts() {
//        $this->view->headScript()->offsetSetFile(100,'https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js', 'text/javascript', array('language' => 'javascript'));
        $this->view->headScript()->offsetSetFile(99,'/public/dist/js/manifest.js',  'text/javascript', array('language' => 'javascript'));
        $this->view->headScript()->offsetSetFile(100,'/public/dist/js/vendor.js',  'text/javascript', array('language' => 'javascript'));
        $this->view->headScript()->offsetSetFile(101,'/public/dist/js/projeto_visualizar.js',  'text/javascript', array('language' => 'javascript'));
    }

    public function visualizarAction()
    {
        $params = $this->getRequest()->getParams();

        $this->carregarScripts();

        try {

            $dbTableProjetos = new Projeto_Model_DbTable_Projetos();
            $projetoCompleto = $dbTableProjetos->obterProjetoIncentivoCompleto($this->idPronac);
            $this->view->projeto = $projetoCompleto;
                    
            if (count($projetoCompleto) <= 0) {
                throw new Exception("Nenhum projeto encontrado com o n&uacute;mero de Pronac informado.");
            }

            $dbTableInabilitado = new Inabilitado();
            $proponenteInabilitado = $dbTableInabilitado->BuscarInabilitado($projetoCompleto->CgcCPf);
            $this->view->ProponenteInabilitado = ($proponenteInabilitado->Habilitado == 'I');

            $Parecer = new Parecer();
            $parecerAnaliseCNIC = $Parecer->verificaProjSituacaoCNIC($projetoCompleto->Pronac);
            $this->view->emAnaliseNaCNIC= (count($parecerAnaliseCNIC) > 0) ? 1 : 0;

        } catch (Exception $e) {
            parent::message($e->getMessage(), "listarprojetos/listarprojetos", "ERROR");
        }
    }
}