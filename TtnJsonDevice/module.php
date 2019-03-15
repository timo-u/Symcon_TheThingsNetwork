<?php
    class TtnJsonDevice extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
            $this->RegisterPropertyString('DeviceId', 'DeviceId');
			
			$this->RegisterPropertyBoolean('AutoCreateVariable', false);
			
            $this->ConnectParent('{A6D53032-A228-458C-B023-8C3B1117B73B}');
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
			$elements = json_decode($payload);
			if($elements == null )
			{
				$this->SendDebug('ReceiveData()', "JSON-Decode failed", 0);
			}
			
			foreach ($elements as $key => $value) {
				
				$this->SendDebug('ReceiveData()' , "Key: " . $key. " Value: ". $value ." Type: " .gettype($value ) , 0);
				$id = @$this->GetIDForIdent($key);
				if ($id == false) 
				{
					if (! $this->ReadPropertyBoolean('AutoCreateVariable')) 
						continue;
					$type = gettype($value);
					if($type == "integer")
						$id  = $this->RegisterVariableInteger($key,$key , '', 1);
					else if ($type == "boolean")
						$id  = $this->RegisterVariableBoolean($key,$key , '', 1);
					else if ($type == "string")
						$id  = $this->RegisterVariableString($key,$key , '', 1);
					else if ($type == "double")
						$id  = $this->RegisterVariableFloat($key,$key , '', 1);
					else	
						continue;
				}	
				
				SetValue($id , $value);
			}
			$this->SendDebug('ReceiveData()', "Payload: ".$payload , 0);
		}
    }
