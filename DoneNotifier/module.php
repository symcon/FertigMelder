<?php

declare(strict_types=1);
class DoneNotifier extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyInteger('SourceID', 0);
        $this->RegisterPropertyInteger('Period', 15);
        $this->RegisterPropertyFloat('BorderValue', 0);

        //Timer
        $this->RegisterTimer('CheckIfDoneTimer', 0, 'FM_Done($_IPS[\'TARGET\']);');

        if (!IPS_VariableProfileExists('FM.Status')) {
            IPS_CreateVariableProfile('FM.Status', 1);
            IPS_SetVariableProfileAssociation('FM.Status', 0, $this->Translate('Off'), 'Sleep', -1);
            IPS_SetVariableProfileAssociation('FM.Status', 1, $this->Translate('Running'), 'Motion', -1);
            IPS_SetVariableProfileAssociation('FM.Status', 2, $this->Translate('Done'), 'Ok', -1);
        }

        //Updating legacy profiles to also beeing associative
        IPS_SetVariableProfileValues('FM.Status', 0, 0, 0);

        $this->RegisterVariableInteger('Status', $this->Translate('Status'), 'FM.Status');
        $this->RegisterVariableBoolean('Active', $this->Translate('Active'), '~Switch');
        $this->EnableAction('Active');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Deleting outdated events
        $eventID = @$this->GetIDForIdent('EventUp');
        if ($eventID) {
            IPS_DeleteEvent($eventID);
        }

        $eventID = @$this->GetIDForIdent('EventDown');
        if ($eventID) {
            IPS_DeleteEvent($eventID);
        }

        //Unregister all messages in order to readd them
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }

        $this->RegisterMessage($this->ReadPropertyInteger('SourceID'), VM_UPDATE);

        //Add references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }
        if ($this->ReadPropertyInteger('SourceID') != 0) {
            $this->RegisterReference($this->ReadPropertyInteger('SourceID'));
        }
    }

    public function SetActive(bool $Active)
    {
        if ($this->ReadPropertyInteger('SourceID') == 0) {
            SetValue($this->GetIDForIdent('Status'), 0);

            //Modul Deaktivieren
            SetValue($this->GetIDForIdent('Active'), false);
            echo 'No variable selected';
            return false;
        }

        if ($Active) {
            if (GetValue($this->ReadPropertyInteger('SourceID')) >= $this->ReadPropertyFloat('BorderValue')) {
                SetValue($this->GetIDForIdent('Status'), 1);
                $this->SetBuffer('StatusBuffer', 'Running');
            } else {
                SetValue($this->GetIDForIdent('Status'), 0);
            }
        } else {
            SetValue($this->GetIDForIdent('Status'), 0);
        }

        //Modul aktivieren
        SetValue($this->GetIDForIdent('Active'), $Active);
        return true;
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->SetActive($Value);
                break;

            default:
                throw new Exception('Invalid Ident');
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        if (GetValue($this->GetIDForIdent('Active'))) {
            if (($Data[0] < $this->ReadPropertyFloat('BorderValue')) && (GetValue($this->GetIDForIdent('Status')) == 1) && ($this->GetBuffer('StatusBuffer') == 'Running')) {
                $this->SetTimerInterval('CheckIfDoneTimer', $this->ReadPropertyInteger('Period') * 1000);
                $this->SetBuffer('StatusBuffer', 'Done');
            } elseif ($Data[0] > $this->ReadPropertyFloat('BorderValue')) {
                SetValue($this->GetIDForIdent('Status'), 1);
                $this->SetTimerInterval('CheckIfDoneTimer', 0);
                $this->SetBuffer('StatusBuffer', 'Running');
            }
        }
    }

    public function Done()
    {
        SetValue($this->GetIDForIdent('Status'), 2);
        $this->SetTimerInterval('CheckIfDoneTimer', 0);
    }
}