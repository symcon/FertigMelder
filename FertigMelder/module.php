<?
class FertigMelder extends IPSModule {
	
	public function Create() {
		//Never delete this line!
		parent::Create();
		
		//Properties
		$this->RegisterPropertyInteger("SourceID", 0);
		$this->RegisterPropertyInteger("Period", 15);
		$this->RegisterPropertyFloat("BorderValue", 0);
		
		//Timer
		$this->RegisterTimer("CheckIfDoneTimer", 0, 'FM_NewDone($_IPS[\'TARGET\']);');
		
		if (!IPS_VariableProfileExists("FM.Status")) {
			IPS_CreateVariableProfile("FM.Status", 1);
			IPS_SetVariableProfileValues("FM.Status", 0, 2, 1);
			IPS_SetVariableProfileAssociation("FM.Status", 0, "Off", "Sleep", -1);
			IPS_SetVariableProfileAssociation("FM.Status", 1, "Running", "Motion", -1);
			IPS_SetVariableProfileAssociation("FM.Status", 2, "Done", "Ok", -1);
		}
		
		$this->RegisterVariableInteger("Status", "Status", "FM.Status");
		$this->RegisterVariableBoolean("Active", "Active", "~Switch");
		$this->EnableAction("Active");
		
	}

	public function Destroy(){
		//Never delete this line!
		parent::Destroy();
		
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
		
		$this->RegisterMessage($this->ReadPropertyInteger("SourceID"), VM_UPDATE);
	
	}

	public function SetActive(bool $Active) {
		
		if ($this->ReadPropertyInteger("SourceID") == 0) {
			SetValue($this->GetIDForIdent("Status"), 0);
			
			//Modul Deaktivieren
			SetValue($this->GetIDForIdent("Active"), false);
			echo "No variable selected";
			return false;
		}
		
		if ($Active) {
			if (GetValue($this->ReadPropertyInteger("SourceID")) >= $this->ReadPropertyFloat("BorderValue")) {
				SetValue($this->GetIDForIdent("Status"), 1);
			} else {
				SetValue($this->GetIDForIdent("Status"), 2);
			}
		} else {
			SetValue($this->GetIDForIdent("Status"), 0);
		}
		
		//Modul aktivieren
		SetValue($this->GetIDForIdent("Active"), $Active);
		return true;
	}

	public function RequestAction($Ident, $Value) {
		
		switch ($Ident) {
			case "Active":
				$this->SetActive($Value);
				break;
			
			default:
				throw new Exception("Invalid Ident");
		}
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
	{
        if (GetValue(GetIDForIdent("Status"))) {
            if (($Data[0] < $this->ReadPropertyFloat("BorderValue")) && (GetValue(GetIDForIdent("Status") == 1))) {
                $this->SendDebug("Status", "BorderReached", 0);
                $this->SetTimerInterval("CheckIfDoneTimer", $this->ReadPropertyInteger("Period") * 1000);
            } else {
                SetValue($this->GetIDForIdent("Status"), 1);
            }
        }
	}

	public function NewDone()
	{
		$this->SendDebug("Done", "Ready", 0);
		SetValue($this->GetIDForIdent("Status"), 2);
		$this->SetTimerInterval("CheckIfDoneTimer", 0);
	}
}
?>