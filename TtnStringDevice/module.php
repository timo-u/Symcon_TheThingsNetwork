<?php


    class TtnStringDevice extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
            $this->RegisterPropertyString('DeviceId', 'DeviceId');
			
			$this->RegisterPropertyBoolean('UseHex', false);
			
            $this->ConnectParent('{A6D53032-A228-458C-B023-8C3B1117B73B}');
			$this->RegisterVariableString('Payload', $this->Translate('Payload'), '', 1);	
						
			
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
        }

        public function ReceiveData($JSONString)
        {
			$data = json_decode($JSONString);
			$data = $data->Buffer;
			
			if($data->app_id != $this->ReadPropertyString('ApplicationId')) return;
			if($data->dev_id != $this->ReadPropertyString('DeviceId')) return;
			
			$this->SendDebug('ReceiveData()', "Application_ID & Device_ID OK", 0);
			
            $payload = base64_decode($data->payload_raw);
	
			if ($this->ReadPropertyBoolean('UseHex')) 
			{
				$payload = bin2hex($payload);
			}
			$this->SetValue('Payload', $payload);
			$this->SendDebug('ReceiveData()', "Payload: ".$payload , 0);
		}
    }
