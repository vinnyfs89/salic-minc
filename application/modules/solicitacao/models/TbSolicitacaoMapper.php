<?php

/**
 * Class Solicitacao_Model_TbSolicitacaoMapper
 */
class Solicitacao_Model_TbSolicitacaoMapper extends MinC_Db_Mapper
{

    public function __construct()
    {
        $this->setDbTable('Solicitacao_Model_DbTable_TbSolicitacao');
    }

    public function isUniqueCpfCnpj($value)
    {
        return ($this->findBy(array("cnpjcpf" => $value))) ? true : false;
    }

    public function encaminhar($arrData)
    {
        $booStatus = false;
        if (!empty($arrData)) {
            unset($arrData['dsMensagem']);
            unset($arrData['IdPRONAC']);
            $model = new Solicitacao_Model_TbSolicitacao($arrData);
            try {
                $auth = Zend_Auth::getInstance(); // pega a autenticacao
                $arrAuth = array_change_key_case((array)$auth->getIdentity());
                $model->setStAtivo(1);
                if ($intId = parent::save($model)) {
                    $booStatus = 1;
                    $this->setMessage('Mensagem encaminhada com sucesso!');
                } else {
                    $this->setMessage('N&atilde;o foi possivel encaminhar mensagem!');
                }
            } catch (Exception $e) {
                $this->setMessage($e->getMessage());
            }
        }
        return $booStatus;
    }

    public function responder($arrData)
    {
        $booStatus = false;
        if (!empty($arrData)) {
            $arrData['dsMensagem'] = $arrData['dsResposta'];
            $arrData['idMensagemOrigem'] = $arrData['idMensagemProjeto'];
            unset($arrData['idMensagemProjeto']);
            $model = new Solicitacao_Model_TbSolicitacao($arrData);
            try {
                $auth = Zend_Auth::getInstance(); // pega a autenticacao
                $arrAuth = array_change_key_case((array)$auth->getIdentity());
                $grupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
                $intUsuOrgao = $grupoAtivo->codGrupo;
                $model->setStAtivo(1);
                $model->setDtMensagem(date('Y-m-d h:i:s'));
                $model->setIdRemetente($arrAuth['usu_codigo']);
                $model->setIdRemetenteUnidade($intUsuOrgao);
                $model->setCdTipoMensagem('R');
                $arrMensagemOrigem = $this->getDbTable()->findBy($arrData['idMensagemOrigem']);
                $model->setIdDestinatario($arrMensagemOrigem['idRemetente']);
                $model->setIdDestinatarioUnidade($arrMensagemOrigem['idRemetenteUnidade']);
                if (empty($model->getIdPRONAC())) {
                    $model->setIdPRONAC($arrMensagemOrigem['IdPRONAC']);
                }
                if ($intId = parent::save($model)) {
                    $booStatus = 1;
                    $this->setMessage('Solicita&ccedil;&atilde;o respondida com sucesso!');
                } else {
                    if (isset($this->getMessages()['dsMensagem'])) {
                        $this->setMessage($this->getMessages()['dsMensagem'], 'dsResposta');
                        unset($this->arrMessages['dsMensagem']);
                    }
                    $this->setMessage('N&atilde;o foi possivel responder &agrave; solicita&ccedil;&atilde;o!');
                }
            } catch (Exception $e) {
                $this->setMessage($e->getMessage());
            }
        }
        return $booStatus;
    }

    public function isValid($model)
    {
        $booStatus = true;
        $arrData = $model->toArray();
        if (empty($arrData['idMensagemProjeto'])){
            $arrRequired = array(
                'idDestinatarioUnidade',
                'IdPRONAC',
                'dsMensagem',
            );
        } else {
            $arrRequired = array(
                'idDestinatarioUnidade',
            );
        }
        foreach ($arrRequired as $strValue) {
            if (!isset($arrData[$strValue]) || empty($arrData[$strValue])) {
                $this->setMessage('Campo obrigat&oacute;rio!', $strValue);
                $booStatus = false;
            }
        }
        return $booStatus;
    }

    public function salvar($arrData)
    {
        $booStatus = false;
        if (!empty($arrData)) {
            $model = new Solicitacao_Model_TbSolicitacao($arrData);
            try {
                $auth = Zend_Auth::getInstance(); // pega a autenticacao
                $arrAuth = array_change_key_case((array)$auth->getIdentity());
                $grupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
                $intUsuOrgao = $grupoAtivo->codOrgao;
                //$intUsuOrgao = $grupoAtivo->codGrupo;
                //var_dump($intUsuOrgao, $grupoAtivo->codOrgao);die;
                $model->setDtMensagem(date('Y-m-d h:i:s'));
                $model->setIdRemetente($arrAuth['usu_codigo']);
                $model->setIdRemetenteUnidade($intUsuOrgao);
//                $model->setIdDestinatario($arrAuth['usu_codigo']);
                $model->setCdTipoMensagem('E');
                $model->setStAtivo(1);
                if ($intId = parent::save($model)) {
                    $booStatus = 1;
                    $this->setMessage('Solicita&ccedil;&atilde;o enviada com sucesso!');
                } else {
                    $this->setMessage('N&atilde;o foi possivel enviar mensagem!');
                }
            } catch (Exception $e) {
                $this->setMessage($e->getMessage());
            }
        }
        return $booStatus;
    }
}
