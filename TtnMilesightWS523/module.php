<?php
require_once(__DIR__.'/../libs/TtnMqttBase.php');


class TtnMilesightWS523 extends IPSModule
{
	use TtnMqttBase;
	
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
        $this->RegisterPropertyString('DeviceId', 'DeviceId');
		$this->RegisterPropertyString('Tenant', 'ttn');
        
		$this->RegisterPropertyBoolean('ShowOutage', true);
		$this->RegisterPropertyBoolean('ShowPerformanceParameters', true);
		

        $this->RegisterPropertyBoolean('ShowState', false);
        $this->RegisterPropertyInteger('WatchdogTime', 0);
        $this->ConnectParent('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');

        $this->RegisterPropertyBoolean('ShowMeta', false);
        $this->RegisterPropertyBoolean('ShowRssi', false);
        $this->RegisterPropertyBoolean('ShowSnr', false);
        $this->RegisterPropertyBoolean('ShowGatewayCount', false);
        $this->RegisterPropertyBoolean('ShowFrame', false);
        $this->RegisterPropertyBoolean('ShowInterval', false);
		$this->RegisterPropertyBoolean('LastMessageTime', false);

        $this->RegisterAttributeInteger('LastMessageTimestamp', 0);
        $this->RegisterAttributeString('DownlinkUrl', '');

        $this->RegisterTimer('WatchdogTimer', $this->ReadPropertyInteger('WatchdogTime') * 60000, 'TTN_WatchdogTimerElapsed($_IPS[\'TARGET\']);');
        $this->RegisterBaseVariableProfiles();
    }

	private function HandleReceivedData($data)
	{
		// Payload-Elemente auslesen sofern vorhanden
        if (property_exists($data, 'uplink_message') && property_exists($data->uplink_message, 'decoded_payload')) 
		{
               $elements = $data->uplink_message->decoded_payload;
               $this->SendDebug('HandleReceivedData()', 'Payload: '.json_encode($elements), 0);
        } else
		{
                $elements = null;
                $this->SendDebug('HandleReceivedData()', 'Key: uplink_message->decoded_payload does not exist', 0);
        }
        
        if ($elements == null) 
		{
            $this->SendDebug('HandleReceivedData()', 'JSON-Decode failed', 0);
        } 
		else 
		{

			if (property_exists($elements, 'output')) 
				$this->SetValue('output',$elements->output); 
			if (property_exists($elements, 'outage') && $this->ReadPropertyBoolean('ShowOutage')) 
				$this->SetValue('outage',$elements->outage);
			if($this->ReadPropertyBoolean('ShowPerformanceParameters')){				
			if (property_exists($elements, 'current')) 
				$this->SetValue('current',$elements->current);
			if (property_exists($elements, 'factor')) 
				$this->SetValue('factor',$elements->factor);
			if (property_exists($elements, 'power')) 
				$this->SetValue('power',$elements->power);
			if (property_exists($elements, 'power_sum')) 
				$this->SetValue('power_sum',$elements->power_sum);
			if (property_exists($elements, 'voltage')) 
				$this->SetValue('voltage',$elements->voltage);
			}
        }
		
	}

	private function Maintain()
	{
		$this->MaintainVariable('output', $this->Translate('Switchstate'), 0, '~Switch', 1, true);
		$this->EnableAction('output');
		$this->MaintainVariable('outage', $this->Translate('Outage'), 0, '~Alert', 2,  $this->ReadPropertyBoolean('ShowOutage'));
		$this->MaintainVariable('current', $this->Translate('Current'), 2, '~Ampere.16', 3,  $this->ReadPropertyBoolean('ShowPerformanceParameters'));
		$this->MaintainVariable('power', $this->Translate('Power'), 2, '~Watt.3680', 4,  $this->ReadPropertyBoolean('ShowPerformanceParameters'));
		$this->MaintainVariable('factor', $this->Translate('Factor'), 1, '~Intensity.100', 5,  $this->ReadPropertyBoolean('ShowPerformanceParameters'));
		$this->MaintainVariable('voltage', $this->Translate('Voltage'), 2, '~Volt.230', 6,  $this->ReadPropertyBoolean('ShowPerformanceParameters'));
		$this->MaintainVariable('power_sum', $this->Translate('Power Consumption'), 2, '~Electricity', 7,  $this->ReadPropertyBoolean('ShowPerformanceParameters'));		
		
	}

	public function SetOutput(bool $output, bool $confirmed=true)
	{
		$this->Downlink(85,$confirmed,"","08".$this->IntToHex($output,2,true)."ff");
	}
	
   	public function SetReportingInterval(int $seconds, bool $confirmed=true)
	{
		$this->Downlink(85,$confirmed,"","FF03".$this->IntToHex($seconds,2,true));
	}
	
	public function AddDelayTask(int $seconds,bool $output)
	{
		$val = "10";
		if($output)
			$val = "11";
		$this->Downlink(85,false,"","FF2200".$this->IntToHex($seconds,2,true).$val);
	}
	
	public function DeleteDelayTask()
	{
		$this->Downlink(85,false,"","FF23FF");
	}
	
	public function SetOvercurrentAlarm(bool $enabled,int $currentThreshold)
	{
		$val = "00";
		if($enabled)
			$val = "01";
		$this->Downlink(85,false,"","ff24".$val.$this->IntToHex($currentThreshold,2,true));
	}
	
	public function SetButtonLock(bool $locked)
	{
		$val = "0000";
		if($locked)
			$val = "0080";
		$this->Downlink(85,false,"","ff25".$val);
	}	
	
	public function SetPowerConsumption(bool $enabled)
	{
		$val = "00";
		if($enabled)
			$val = "01";
		$this->Downlink(85,false,"","ff26".$val);
	}

	public function ResetPowerConsumption()
	{
		$this->Downlink(85,false,"","FF27FF");
	}
	
	public function GetState()
	{
		$this->Downlink(85,false,"","FF28FF");
	}

	public function SetPowerLed(bool $enabled)
	{
		$val = "00";
		if($enabled)
			$val = "01";
		$this->Downlink(85,false,"","ff2f".$val);
	}
	public function SetOvercurrentProtection(bool $enabled,int $currentThreshold)
	{
		$val = "00";
		if($enabled)
			$val = "01";
		$this->Downlink(85,false,"","ff30".$val.$this->IntToHex($currentThreshold,2,true));
	}

	public function RequestAction($Ident, $Value)
    {
        $this->SendDebug(__FUNCTION__ . ' Ident', $Ident, 0);
        $this->SendDebug(__FUNCTION__ . ' Value', $Value, 0);
		
		if ($Ident == 'output') {
            $this->SetOutput($Value);
            return true;
        }

    }

}
