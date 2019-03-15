<?php

    class TtnHttpGateway extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('Authorization', '');
            $this->RegisterPropertyString('HookName', 'ttn');
 
		}

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
			
			$this->RegisterHook("/hook/".$this->ReadPropertyString('HookName'));
			$this->SendDebug('ApplyChanges()', 'OK', 0);
		}

		private function RegisterHook($WebHook) {
			$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
			if(sizeof($ids) > 0) {
				$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
				$found = false;
				foreach($hooks as $index => $hook) {
					if($hook['Hook'] == $WebHook) {
						if($hook['TargetID'] == $this->InstanceID)
							return;
						$hooks[$index]['TargetID'] = $this->InstanceID;
						$found = true;
					}
				}
				if(!$found) {
					$hooks[] = Array("Hook" => $WebHook, "TargetID" => $this->InstanceID);
				}
				IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
				IPS_ApplyChanges($ids[0]);
			}
		}
		
		
		
		protected function ProcessHookData() {
			
			if(!(isset($_SERVER['HTTP_AUTHORIZATION']))||$_SERVER['HTTP_AUTHORIZATION'] != $this->ReadPropertyString('Authorization'))
			{
				http_response_code(401);
				echo "Unauthorized";
				$this->SendDebug('ProcessHookData()', "Response: 401 Unauthorized", 0);
				return;				
			}
			
			$content = file_get_contents("php://input");
			$this->SendDebug('ProcessHookData()', $content, 0);
			
			//Try to decode Data 
			$data =  json_decode((string)$content);
			if($data == null) 
			{
				http_response_code(400);
				$this->SendDebug('ProcessHookData()', "Response: 400 Bad Request", 0);
				$this->SendDebug('ProcessHookData()', "JSON Decode Failed. ($data== null)", 0);
				return; 
			}
			
            $this->SendDataToChildren(json_encode(['DataID' => '{474DDD47-79C2-4B83-AE33-79326BF07B2B}', 'Buffer' => $data]));
            
			http_response_code(200);
		}
		

      
    }
