<?php
require_once(__DIR__.'/../libs/TtnMqttBase.php');


class TtnKlax2 extends IPSModule
{
	use TtnMqttBase;
	
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
        $this->RegisterPropertyString('DeviceId', 'DeviceId');
		$this->RegisterPropertyString('Tenant', 'ttn');
        
		$this->RegisterPropertyBoolean('WriteArchiveValues', true);
		$this->RegisterPropertyInteger('Interval', 5);

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


        $this->RegisterTimer('WatchdogTimer', $this->ReadPropertyInteger('WatchdogTime') * 60000, 'TTN_WatchdogTimerElapsed($_IPS[\'TARGET\']);');
        $this->RegisterBaseVariableProfiles();
    }

	private function HandleReceivedData($data)
	{
		// Payload-Elemente auslesen sofern vorhanden
        if (property_exists($data, 'uplink_message') && property_exists($data->uplink_message, 'decoded_payload')) 
		{
               $elements = $data->uplink_message->decoded_payload;
               $this->SendDebug(__FUNCTION__ . '()', 'Payload: '.json_encode($elements), 0);
        } else
		{
                $elements = null;
                $this->SendDebug(__FUNCTION__ . '()', 'Key: uplink_message->decoded_payload does not exist', 0);
        }
        
        if ($elements == null) 
		{
            $this->SendDebug(__FUNCTION__ . '()', 'JSON-Decode failed', 0);
        } 
		else 
		{
			// Batteriestatus auslesen
			if (property_exists($elements, 'header')) 
			{
				if (property_exists($elements->header, 'batteryPerc')) 
					$battLevel = $elements->header->batteryPerc;
					$this->SetValue('batterylevel',$battLevel); 
					$this->SendDebug(__FUNCTION__ . '()', 'Batt Level '.$battLevel.'%' , 0);
			}			
			
			if (property_exists($elements, 'payloads')) 
			{
				if (property_exists($elements->payloads[1], 'register')) 
				{
					if (property_exists($elements->payloads[1]->register, 'values')) 
					{
						$values = $elements->payloads[1]->register->values;
						$count = count($values); 
						$this->SendDebug(__FUNCTION__ . '()', 'Values Count: '.$count, 0);
						
						if($count>2 && $this->ReadPropertyBoolean('WriteArchiveValues'))
						{
							$archiveId = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
							$varid  = $this->GetIDForIdent("powerconsumption");
							$this->SendDebug(__FUNCTION__ . '()', 'VarID '.$varid  , 0);
								
							$intervall = $this->ReadPropertyInteger('Interval')*60;
							$timestamp = strtotime($data->uplink_message->rx_metadata[0]->time);
							$this->SendDebug(__FUNCTION__ . '() Current Timestamp: ', $timestamp, 0);
							if(AC_GetLoggingStatus($archiveId, $varid))
							{
								$this->SendDebug(__FUNCTION__ . '()', 'Variablenlogging aktiv'  , 0);
								for ($i = $count - 1; $i > 0; $i--) 
								{
									AC_AddLoggedValues($archiveId, $varid,[['TimeStamp' => ($timestamp -($i*$intervall)),'Value' => (($values[$i]->value)/1000)]]);
									$this->SendDebug(__FUNCTION__ . '()', 'Value '.$i .' '.(($values[$i]->value)/1000) .'Wh Timestamp: '.($timestamp -($i*$intervall)) , 0);
								}
								@AC_ReAggregateVariable($archiveId, $varid);
							}	
							else
							{
								$this->LogMessage("Archivierung für die Variable Zählerwert muss aktiv sein um Schreiben der Archiverte nutzen zu können!", KL_WARNING);
							}
							
						}
						if($count>0)
						{
							$this->SetValue('powerconsumption',($values[0]->value)/1000); 
						}
					}
				}
			}	
        }
	}


	public function UpdateMeasuringInterval()
	{
		$minutes = $this->ReadPropertyInteger('Interval');
		$this->Downlink(100,false,"",$this->IntToHex($minutes,2,false));
	}
	
	
	private function Maintain()
	{
		$this->MaintainVariable('batterylevel', $this->Translate('Battery level'), 1, '~Intensity.100', 1, true);
		$this->MaintainVariable('powerconsumption', $this->Translate('Power Consumption'), 2, '~Electricity', 2, true);		
	}

	public function RequestAction($Ident, $Value)
    {
        $this->SendDebug(__FUNCTION__ . ' Ident', $Ident, 0);
        $this->SendDebug(__FUNCTION__ . ' Value', $Value, 0);
    }

}

