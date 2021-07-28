<?php

class TtnGateway extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('GatewayId', 'ffffffffffffffff');
        $this->RegisterPropertyInteger('UpdateInterval', 120);
        $this->RegisterPropertyInteger('ConnectionWarningInterval', 900);
		$this->RegisterPropertyString('ApiKey', 'xxx');
        $this->RegisterTimer('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000, 'TTN_Update($_IPS[\'TARGET\']);');
        $this->RegisterVariableProfiles();

        $this->RegisterVariableInteger('uplink', $this->Translate('uplink messages'), '', 1);
        $this->RegisterVariableInteger('downlink', $this->Translate('downlink messages'), '', 2);
        $this->RegisterVariableInteger('lastseenbevore', $this->Translate('last seen bevore'), 'TTN_second', 3);
        $this->RegisterVariableBoolean('online', $this->Translate('online'), '', 4);
    }

    public function ApplyChanges()
    {
        $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);

        parent::ApplyChanges(); //Never delete this line!
    }

    public function EnableLogging()
    {
        $archiveId = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        AC_SetLoggingStatus($archiveId, $this->GetIDForIdent('online'), true);
        AC_SetAggregationType($archiveId, $this->GetIDForIdent('online'), 0); // 0 Standard, 1 Zähler
        AC_SetGraphStatus($archiveId, $this->GetIDForIdent('online'), true);

        AC_SetLoggingStatus($archiveId, $this->GetIDForIdent('uplink'), true);
        AC_SetAggregationType($archiveId, $this->GetIDForIdent('uplink'), 0); // 0 Standard, 1 Zähler
        AC_SetGraphStatus($archiveId, $this->GetIDForIdent('uplink'), true);

        AC_SetLoggingStatus($archiveId, $this->GetIDForIdent('downlink'), true);
        AC_SetAggregationType($archiveId, $this->GetIDForIdent('downlink'), 0); // 0 Standard, 1 Zähler
        AC_SetGraphStatus($archiveId, $this->GetIDForIdent('downlink'), true);

        AC_SetLoggingStatus($archiveId, $this->GetIDForIdent('lastseenbevore'), true);
        AC_SetAggregationType($archiveId, $this->GetIDForIdent('lastseenbevore'), 0); // 0 Standard, 1 Zähler
        AC_SetGraphStatus($archiveId, $this->GetIDForIdent('lastseenbevore'), true);

        IPS_ApplyChanges($archiveId);
    }

    public function DisableLogging()
    {
        $archiveId = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        AC_SetLoggingStatus($archiveId, $this->GetIDForIdent('online'), false);
        AC_SetGraphStatus($archiveId, $this->GetIDForIdent('online'), false);

        AC_SetLoggingStatus($archiveId, $this->GetIDForIdent('uplink'), false);
        AC_SetGraphStatus($archiveId, $this->GetIDForIdent('uplink'), false);

        AC_SetLoggingStatus($archiveId, $this->GetIDForIdent('downlink'), false);
        AC_SetGraphStatus($archiveId, $this->GetIDForIdent('downlink'), false);

        AC_SetLoggingStatus($archiveId, $this->GetIDForIdent('lastseenbevore'), false);
        AC_SetGraphStatus($archiveId, $this->GetIDForIdent('lastseenbevore'), false);

        IPS_ApplyChanges($archiveId);
    }

    public function Update()
    {
        $eui = strtolower($this->ReadPropertyString('GatewayId'));
        if ($eui == '') {
            $this->SendDebug('Update()', 'empty gateway ID ', 0);
            return;
        }
		
		$apikey = $this->ReadPropertyString('ApiKey');
		
        $curl = curl_init();

       

		curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://eu1.cloud.thethings.network/api/v3/gs/gateways/'.$eui.'/connection/stats',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$apikey),
		));

        $content = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        //echo $content;

        $this->SendDebug('Update() Content: ', $content, 0);
        $this->SendDebug('Update() HTTP statusCode: ', $statusCode, 0);

		
		if ($statusCode == 403) {
            $this->SetStatus(203);
            $this->SendDebug('Update() ', 'Forbidden', 0);
			$this->SendDebug('Update() ', 'Invalid gateway ID?', 0);
        }
		
		if ($statusCode == 401) {
            $this->SetStatus(201);
            $this->SendDebug('Update() ', 'API-Key not found', 0); 
        }
		if ($statusCode == 400) {
            $this->SetStatus(202);
            $this->SendDebug('Update() ', 'Invalid Token', 0);
        }

        

        $data = json_decode((string) $content);

        if ($data == null) {
            $this->SendDebug('Update()', '$data==null', 0);
            return;
        }
		
		if (property_exists($data, 'message')) 
		{
			$this->SendDebug('Message:', $data->message, 0);
        }

		if ($statusCode != 200) 
		{
            return;
        }


        $this->SendDebug('Update()', 'content: '.$content, 0);

		$this->MaintainVariable('online', $this->Translate('Online'), 0, 'TTN_Online', 1, 1);
		$this->MaintainVariable('uplink', $this->Translate('Online'), 1, '', 1, 1);
		$this->MaintainVariable('downlink', $this->Translate('Online'), 1, '', 1, 1);
		$this->MaintainVariable('lastseenbevore', $this->Translate('Online'), 1, 'TTN_second', 1, 1);
			
			
        if (property_exists($data, 'uplink_count')) 
		{
            $this->SetValue('uplink', $data->uplink_count);
        }
        if (property_exists($data, 'downlink_count')) 
		{
            $this->SetValue('downlink', $data->downlink_count);
        }
			
		if (property_exists($data, 'last_uplink_received_at')) 
		{
			$cutrentdate = new DateTime('now');
		
			$gwLastUplink = $data->last_uplink_received_at;
			$gwLastUplink = str_replace("T", " ",$gwLastUplink );
			$gwLastUplink = substr($gwLastUplink,0,27);
			$GwTimeStamp = new DateTime($gwLastUplink,new \DateTimeZone("UTC"));
		
			$this->SendDebug('$cutrentdate', date_format($cutrentdate, 'Y-m-d H:i:s'), 0);
			$this->SendDebug('last_status_received_at', date_format($GwTimeStamp, 'Y-m-d H:i:s'), 0);
		
			$difference = ($cutrentdate->getTimestamp() - $GwTimeStamp->getTimestamp());
		
			$this->SetValue('online', $difference < $this->ReadPropertyInteger('ConnectionWarningInterval'));
			$this->SetValue('lastseenbevore', $difference);
			
			$this->SetStatus(102);
		}
		
		
        
        
    }

    private function RegisterVariableProfiles()
    {
        $this->SendDebug('RegisterVariableProfiles()', 'RegisterVariableProfiles()', 0);

        if (!IPS_VariableProfileExists('TTN_Online')) {
            IPS_CreateVariableProfile('TTN_Online', 0);
            IPS_SetVariableProfileAssociation('TTN_Online', 0, $this->Translate('Offline'), '', 0xFF0000);
            IPS_SetVariableProfileAssociation('TTN_Online', 1, $this->Translate('Online'), '', 0x00FF00);
        }

        if (!IPS_VariableProfileExists('TTN_second')) {
            IPS_CreateVariableProfile('TTN_second', 1);
            IPS_SetVariableProfileText('TTN_second', '', ' s');
        }
    }
}
