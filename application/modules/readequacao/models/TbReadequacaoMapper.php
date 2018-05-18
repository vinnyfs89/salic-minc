<?php

class Readequacao_Model_TbReadequacaoMapper extends MinC_Db_Mapper
{
    public function __construct()
    {
        parent::setDbTable('Readequacao_Model_DbTable_TbReadequacao');
    }

    public function save($model)
    {
        return parent::save($model);
    }

    public function salvarSolicitacaoReadequacao($arrData)
    {

        try {
            $auth = Zend_Auth::getInstance();
            $tblAgente = new Agente_Model_DbTable_Agentes();
            $rsAgente = $tblAgente->buscar(array('CNPJCPF=?' => $auth->getIdentity()->Cpf))->current();

            $dados = array();
            $dados['idReadequacao'] = $arrData['idReadequacao'];
            $dados['idPronac'] = $arrData['idPronac'];
            $dados['dtSolicitacao'] = new Zend_Db_Expr('GETDATE()');
            $dados['idSolicitante'] = $rsAgente->idAgente;
            $dados['stAtendimento'] = 'N';
            $dados['siEncaminhamento'] = Readequacao_Model_tbTipoEncaminhamento::SI_ENCAMINHAMENTO_CADASTRADA_PROPONENTE;
            $dados['stEstado'] = 0;

            if (isset($arrData['idTipoReadequacao'])) {
                $dados['idTipoReadequacao'] = $arrData['idTipoReadequacao'];
            }
            if (isset($arrData['dsJustificativa'])) {
                $dados['dsJustificativa'] = $arrData['dsJustificativa'];
            }
            if (isset($arrData['dsSolicitacao'])) {
                $dados['dsSolicitacao'] = $arrData['dsSolicitacao'];
            }
            if (isset($arrData['idDocumento'])) {
                $dados['idDocumento'] = $arrData['idDocumento'];
            }

            $id = $this->save(new Readequacao_Model_TbReadequacao($dados));
            if ($this->getMessage()) {
                throw new Exception($this->getMessage());
            }

            return $id;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function finalizarSolicitacaoReadequacao($idPronac, $idTipoReadequacao = null, $idReadequacao = null)
    {
        try {

            if (empty($idPronac)) {
                throw new Exception("Pronac &eacute; obrigat&oacute;rio");
            }

            $tbReadequacao = new Readequacao_Model_DbTable_TbReadequacao();

            $where = [];
            if ($idTipoReadequacao) {
                $where[] = $tbReadequacao->getAdapter()->quoteInto('idTipoReadequacao = ?', (int)$idTipoReadequacao);
            }

            if ($idReadequacao) {
                $where[] = $tbReadequacao->getAdapter()->quoteInto('idReadequacao = ?', (int)$idReadequacao);
            }

            $where[] = $tbReadequacao->getAdapter()->quoteInto('idPronac = ?', (int)$idPronac);
            $where[] = $tbReadequacao->getAdapter()->quoteInto('siEncaminhamento = ?', Readequacao_Model_tbTipoEncaminhamento::SI_ENCAMINHAMENTO_CADASTRADA_PROPONENTE);
            $where[] = $tbReadequacao->getAdapter()->quoteInto('stEstado = ?', 0);

            $dados = array();
            $dados['siEncaminhamento'] = Readequacao_Model_tbTipoEncaminhamento::SI_ENCAMINHAMENTO_ENVIADO_MINC;
            $dados['dtEnvio'] = new Zend_Db_Expr('GETDATE()');

            return $tbReadequacao->update($dados, $where);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
