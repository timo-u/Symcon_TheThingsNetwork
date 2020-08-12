<?php

declare(strict_types=1);

class TtnGateway extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('GatewayId', 'eui-ffffffffffffffff');
        $this->RegisterPropertyInteger('UpdateInterval', 120);
        $this->RegisterPropertyInteger('ConnectionWarningInterval', 900);
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
        if ($eui == 'eui-ffffffffffffffff') {
            $this->SendDebug('Update()', '$eui == eui-ffffffffffffffff (request stopped) ', 0);

            return;
        }
        $url = 'http://noc.thethingsnetwork.org:8085/api/v2/gateways/'.$eui;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ]);

        $content = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        //echo $content;

        $this->SendDebug('Update() Content: ', $content, 0);
        $this->SendDebug('Update() HTTP statusCode: ', $statusCode, 0);

        if ($statusCode == 404) {
            $this->SetStatus(201);
            $this->SendDebug('Update() ', 'Status not found. Invalid Gateway ID?', 0);

            return;
        }

        if ($statusCode != 200) {
            return;
        }

        $data = json_decode((string) $content);

        if ($data == null) {
            $this->SendDebug('Update()', '$data==null', 0);

            return;
        }

        $this->SendDebug('Update()', 'content: '.$content, 0);

        $cutrentdate = new DateTime('now');
        $currentTimestamp = $cutrentdate->getTimestamp();
        $GwTimeStamp = (int) (($data->time) / 1000000000);
        $difference = ($currentTimestamp - $GwTimeStamp);

        $this->MaintainVariable('online', $this->Translate('Online'), 0, 'TTN_Online', 1, 1);
        $this->MaintainVariable('uplink', $this->Translate('Online'), 1, '', 1, 1);
        $this->MaintainVariable('downlink', $this->Translate('Online'), 1, '', 1, 1);
        $this->MaintainVariable('lastseenbevore', $this->Translate('Online'), 1, 'TTN_second', 1, 1);

        $this->SetValue('online', $difference < $this->ReadPropertyInteger('ConnectionWarningInterval'));

        if (property_exists($data, 'uplink')) {
            $this->SetValue('uplink', $data->uplink);
        }
        if (property_exists($data, 'downlink')) {
            $this->SetValue('downlink', $data->downlink);
        }
        $this->SetValue('lastseenbevore', $difference);
        $this->SetStatus(102);
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
