<?php
require_once(__DIR__.'/../libs/TtnMqttBase.php');


class TtnMilesightWS523Multicast extends IPSModule
{
	use TtnMqttBase;
	
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
        $this->RegisterPropertyString('DeviceId', 'DeviceId');
		$this->RegisterPropertyString('Tenant', 'ttn');
		$this->RegisterPropertyString('Gateways', '');         
        
		$this->RegisterPropertyInteger('Interval', 1);
		$this->RegisterPropertyInteger('Repetitions', 1);
		
		$this->ConnectParent('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
        $this->RegisterBaseVariableProfiles();;
    }

	public function ApplyChanges()
    {
     parent::ApplyChanges();

          $this->MaintainBaseVaraibles();

        //Setze Filter für ReceiveData
        if($this->ReadPropertyString('Tenant')=="") // Leerer Tennat bei Lokalem Stack
		{
			$MQTTTopic = "v3/" .$this->ReadPropertyString('ApplicationId').'/devices/'.$this->ReadPropertyString('DeviceId').'/up';
		}
		else		
		{
			$MQTTTopic = "v3/" .$this->ReadPropertyString('ApplicationId')."@" .$this->ReadPropertyString('Tenant').'/devices/'.$this->ReadPropertyString('DeviceId').'/up';
		}
		
        $this->SetReceiveDataFilter('.*'.$MQTTTopic.'.*');
		

    }
	private function GetGatewayIds()
	{
		$arrString = $this->ReadPropertyString("Gateways");
		$array = json_decode($arrString,true);
		$gws=array();
		foreach ($array as $gw )
		{
			array_push($gws,$gw['gw-id']);
		}
		return implode(',',$gws);
	}
	private function MaintainBaseVaraibles()
	{
		// Funktion überschreiben, da hier nicht genutzt
	}
	public function ReceiveData($JSONString)
	{
		// Funktion überschreiben, da hier nicht genutzt
	}
 
	private function HandleReceivedData($data)
	{
		// Funktion überschreiben, da hier nicht genutzt
      
	}

	private function Maintain()
	{
		// Funktion überschreiben, da hier nicht genutzt
		
	}

	public function SetOutputMC(bool $output)
	{
		$this->DownlinkMulticast(85,"","08".$this->IntToHex($output,2,true)."ff"."FF28FF",$this->GetGatewayIds(),$this->ReadPropertyInteger('Interval'),$this->ReadPropertyInteger('Repetitions'));
	}
	
   	public function SetReportingIntervalMC(int $seconds)
	{
		$this->DownlinkMulticast(85,"","FF03".$this->IntToHex($seconds,2,true),$this->GetGatewayIds());
	}
	
	public function AddDelayTask(int $seconds,bool $output)
	{
		$val = "10";
		if($output)
			$val = "11";
		$this->DownlinkMulticast(85,"","FF2200".$this->IntToHex($seconds,2,true).$val,$this->GetGatewayIds());
	}
	
	public function DeleteDelayTask()
	{
		$this->DownlinkMulticast(85,"","FF23FF",$this->GetGatewayIds());
	}
	
	public function SetOvercurrentAlarm(bool $enabled,int $currentThreshold)
	{
		$val = "00";
		if($enabled)
			$val = "01";
		$this->DownlinkMulticast(85,"","ff24".$val.$this->IntToHex($currentThreshold,2,true),$this->GetGatewayIds());
	}
	
	public function SetButtonLock(bool $locked)
	{
		$val = "0000";
		if($locked)
			$val = "0080";
		$this->DownlinkMulticast(85,"","ff25".$val,$this->GetGatewayIds());
	}	
	
	public function SetPowerConsumption(bool $enabled)
	{
		$val = "00";
		if($enabled)
			$val = "01";
		$this->DownlinkMulticast(85,"","ff26".$val,$this->GetGatewayIds());
	}

	public function ResetPowerConsumption()
	{
		$this->DownlinkMulticast(85,"","FF27FF",$this->GetGatewayIds());
	}
	
	public function GetState()
	{
		$this->DownlinkMulticast(85,"","FF28FF",$this->GetGatewayIds());
	}

	public function SetPowerLed(bool $enabled)
	{
		$val = "00";
		if($enabled)
			$val = "01";
		$this->DownlinkMulticast(85,"","ff2f".$val,$this->GetGatewayIds());
	}
	public function SetOvercurrentProtection(bool $enabled,int $currentThreshold)
	{
		$val = "00";
		if($enabled)
			$val = "01";
		$this->DownlinkMulticast(85,"","ff30".$val.$this->IntToHex($currentThreshold,2,true),$this->GetGatewayIds());
	}


}
