<?php
require_once(__DIR__.'/../libs/TtnMqttBase.php');

class TtnMqttDevice extends IPSModule
{
	use TtnMqttBase;
	
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
        $this->RegisterPropertyString('DeviceId', 'DeviceId');
		$this->RegisterPropertyString('Tenant', 'ttn');
        $this->RegisterPropertyBoolean('AutoCreateVariable', false);

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
            foreach ($elements as $key => $value) {
                $this->SendDebug('HandleReceivedData()', 'Key: '.$key.' Value: '.print_r($value,true).' Type: '.gettype($value), 0);
				// Prüfung ob Variable nicht vorhannden
                if (@$this->GetIDForIdent($key) == false) 
				{
					// Bei deaktiviertem AutoCreateVariable deiese Variable überspringen
                    if (!$this->ReadPropertyBoolean('AutoCreateVariable')) {
                        continue;
                    }
					//Variable anlegen
                    $type = gettype($value);
                    if ($type == 'integer') {
                        $id = $this->RegisterVariableInteger($key, $key, '', 1);
                    } elseif ($type == 'boolean') {
                        $id = $this->RegisterVariableBoolean($key, $key, '', 1);
                    } elseif ($type == 'string') {
                        $id = $this->RegisterVariableString($key, $key, '', 1);
                    } elseif ($type == 'double') {
                        $id = $this->RegisterVariableFloat($key, $key, '', 1);
                    } else {
                        continue;
                    }
                }
				$this->SetValue($key, $value);
            }
        }
		
	}
	
}
