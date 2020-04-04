<?php

declare(strict_types=1);
class TtnEnvironmentSensor extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
        $this->RegisterPropertyString('DeviceId', 'DeviceId');

        $this->RegisterPropertyBoolean('ShowState', false);
        $this->RegisterPropertyInteger('WatchdogTime', 0);
        $this->ConnectParent('{A6D53032-A228-458C-B023-8C3B1117B73B}');

        $this->RegisterPropertyBoolean('ShowTemperature', false);
        $this->RegisterPropertyBoolean('ShowHumidity', false);
        $this->RegisterPropertyBoolean('ShowPressure', false);
        $this->RegisterPropertyBoolean('ShowBatteryVoltage', false);
        $this->RegisterPropertyBoolean('ShowSolarVoltage', false);
        $this->RegisterPropertyBoolean('ShowErrorState', false);

        $this->RegisterPropertyBoolean('ShowMeta', false);
        $this->RegisterPropertyBoolean('ShowRssi', false);
        $this->RegisterPropertyBoolean('ShowSnr', false);
        $this->RegisterPropertyBoolean('ShowGatewayCount', false);
        $this->RegisterPropertyBoolean('ShowFrame', false);
        $this->RegisterPropertyBoolean('ShowInterval', false);

        $this->RegisterAttributeInteger('LastMessageTimestamp', 0);
        $this->RegisterAttributeString('DownlinkUrl', '');

        $this->RegisterTimer('WatchdogTimer', $this->ReadPropertyInteger('WatchdogTime') * 60000, 'TTN_WatchdogTimerElapsed($_IPS[\'TARGET\']);');
        $this->RegisterVariableProfiles();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->Maintain();
        //$this->WatchdogReset();
    }

    private function Maintain()
    {
        $this->MaintainVariable('Temperature', $this->Translate('Temperature'), 2, '~Temperature', 2, $this->ReadPropertyBoolean('ShowTemperature'));
        $this->MaintainVariable('Humidity', $this->Translate('Humidity'), 2, '~Humidity.F', 2, $this->ReadPropertyBoolean('ShowHumidity'));
        $this->MaintainVariable('Pressure', $this->Translate('Pressure'), 2, '~AirPressure.F', 2, $this->ReadPropertyBoolean('ShowPressure'));
        $this->MaintainVariable('BatteryVoltage', $this->Translate('Battery Voltage'), 2, '~Volt', 4, $this->ReadPropertyBoolean('ShowBatteryVoltage'));
        $this->MaintainVariable('SolarVoltage', $this->Translate('SolarVoltage'), 2, '~Volt', 5, $this->ReadPropertyBoolean('ShowSolarVoltage'));
        $this->MaintainVariable('ErrorState', $this->Translate('Error State'), 1, '', 6, $this->ReadPropertyBoolean('ShowErrorState'));

        $this->MaintainVariable('Meta_Informations', $this->Translate('Meta Informations'), 3, '', 100, $this->ReadPropertyBoolean('ShowMeta'));
        $this->MaintainVariable('Meta_RSSI', $this->Translate('RSSI'), 1, 'TTN_dBm_RSSI', 101, $this->ReadPropertyBoolean('ShowRssi'));
        $this->MaintainVariable('Meta_SNR', $this->Translate('SNR'), 2, 'TTN_dB_SNR', 102, $this->ReadPropertyBoolean('ShowSnr'));
        $this->MaintainVariable('Meta_FrameId', $this->Translate('Frame ID'), 1, '', 103, $this->ReadPropertyBoolean('ShowFrame'));
        $this->MaintainVariable('Meta_GatewayCount', $this->Translate('Gateway Count'), 1, '', 104, $this->ReadPropertyBoolean('ShowGatewayCount'));
        $this->MaintainVariable('State', $this->Translate('State'), 0, 'TTN_Online', 105, $this->ReadPropertyBoolean('ShowState'));
        $this->MaintainVariable('Interval', $this->Translate('Interval'), 1, 'TTN_second', 106, $this->ReadPropertyBoolean('ShowInterval'));
    }

    public function WatchdogTimerElapsed()
    {
        $this->SetValue('State', false);
        $this->SetTimerInterval('WatchdogTimer', 0);
    }

    private function WatchdogReset()
    {
        if ($this->ReadPropertyBoolean('ShowState')) {
            $this->SetTimerInterval('WatchdogTimer', $this->ReadPropertyInteger('WatchdogTime') * 60000);
            $this->SetValue('State', true);
        } else {
            $this->SetTimerInterval('WatchdogTimer', 0);
        }
    }

    public function EnableLogging()
    {
        $archiveId = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        $arr = ['Temperature', 'Humidity', 'Pressure', 'BatteryVoltage', 'SolarVoltage', 'ErrorState',
            'Meta_Informations', 'Meta_RSSI', 'Meta_SNR', 'Meta_FrameId', 'Meta_GatewayCount', 'State', 'Interval', ];

        foreach ($arr as &$ident) {
            $id = @$this->GetIDForIdent($ident);

            if ($id == 0) {
                continue;
            }
            AC_SetLoggingStatus($archiveId, $id, true);
            AC_SetAggregationType($archiveId, $id, 0); // 0 Standard, 1 Zähler
            AC_SetGraphStatus($archiveId, $id, true);
        }

        IPS_ApplyChanges($archiveId);
    }

    public function DisableLogging()
    {
        $archiveId = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        $arr = ['Temperature', 'Humidity', 'Pressure', 'BatteryVoltage', 'SolarVoltage', 'ErrorState',
            'Meta_Informations', 'Meta_RSSI', 'Meta_SNR', 'Meta_FrameId', 'Meta_GatewayCount', 'State', 'Interval', ];

        foreach ($arr as &$ident) {
            $id = $this->GetIDForIdent($ident);
            if ($id == 0) {
                continue;
            }
            AC_SetLoggingStatus($archiveId, $id, false);
            AC_SetGraphStatus($archiveId, $id, false);
        }

        IPS_ApplyChanges($archiveId);
    }

    public function GetData()
    {
        return json_decode($this->GetBuffer('DataBuffer'));
    }

    public function GetSensorData()
    {
        $arr = ['Temperature', 'Humidity', 'Pressure', 'BatteryVoltage', 'SolarVoltage', 'ErrorState', 'State'];

        $data = [];
        foreach ($arr as &$ident) {
            if (@$this->GetIDForIdent($ident) != 0) {
                $data[$ident] = (@$this->GetValue($ident));
            }
        }

        $data['Timestamp'] = $this->ReadAttributeInteger('LastMessageTimestamp');

        return $data;
    }

    public function GetState()
    {
        return $this->GetValue('State');
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $data = $data->Buffer;

        if ($data->app_id != $this->ReadPropertyString('ApplicationId')) {
            return;
        }
        if ($data->dev_id != $this->ReadPropertyString('DeviceId')) {
            return;
        }
        $this->SetBuffer('DataBuffer', json_encode($data));

        $this->WatchdogReset();

        $this->SendDebug('ReceiveData()', 'Application_ID & Device_ID OK', 0);

        if (array_key_exists('payload_fields', $data)) {
            $elements = $data->payload_fields;
            $this->SendDebug('ReceiveData()', 'Payload: '.json_encode($elements), 0);
        } else {
            $elements = null;
            $this->SendDebug('ReceiveData()', 'Key: payload_fields does not exist', 0);
        }

        if ($elements == null) {
            $this->SendDebug('ReceiveData()', 'JSON-Decode failed', 0);
        } else {
            //1 Temperature
            if ($this->ReadPropertyBoolean('ShowTemperature') && (array_key_exists('temperature_1', $elements))) {
                $this->SetValue('Temperature', $elements->temperature_1);
            }
            //2	Battery Voltege
            if ($this->ReadPropertyBoolean('ShowBatteryVoltage') && (array_key_exists('analog_in_2', $elements))) {
                $this->SetValue('BatteryVoltage', $elements->analog_in_2);
            }
            //3 Solar Voltage
            if ($this->ReadPropertyBoolean('ShowSolarVoltage') && (array_key_exists('analog_in_3', $elements))) {
                $this->SetValue('SolarVoltage', $elements->analog_in_3);
            }
            //4 ErrorState
            if ($this->ReadPropertyBoolean('ShowErrorState') && (array_key_exists('digital_in_4', $elements))) {
                $this->SetValue('ErrorState', $elements->digital_in_4);
            }
            //5 Humidity
            if ($this->ReadPropertyBoolean('ShowHumidity') && (array_key_exists('relative_humidity_5', $elements))) {
                $this->SetValue('Humidity', $elements->relative_humidity_5);
            }
            //6 Pressure
            if ($this->ReadPropertyBoolean('ShowPressure') && (array_key_exists('barometric_pressure_6', $elements))) {
                $this->SetValue('Pressure', $elements->barometric_pressure_6);
            }
        }

        $this->Maintain();

        $metadata = $data->metadata;

        $rssi = -200;
        $snr = -200;
        $gatewayCount = 0;

        if (array_key_exists('gateways', $metadata)) {
            $gateways = $metadata->gateways;
            foreach ($gateways as $gateway) {
                if ($snr < $gateway->snr) {
                    $snr = $gateway->snr;
                }
                if ($rssi < $gateway->rssi) {
                    $rssi = $gateway->rssi;
                }
            }
            $gatewayCount = count($gateways);
        }
        $this->SendDebug('ReceiveData()', 'Best RSSI: '.$rssi, 0);
        $this->SendDebug('ReceiveData()', 'Best SNR: '.$snr, 0);
        $this->SendDebug('ReceiveData()', 'Frame Counter : '.$data->counter, 0);

        if ($this->ReadPropertyBoolean('ShowMeta')) {
            if (array_key_exists('frequency', $metadata)) {
                $this->SetValue('Meta_Informations', 'Freq: '.$metadata->frequency.
                ' Modulation: '.$metadata->modulation.
                ' Data Rate: '.$metadata->data_rate.
                ' Coding Rate: '.$metadata->coding_rate);
            } else {
                $this->SetValue('Meta_Informations', 'no data');
            }
        }

        if ($this->ReadPropertyBoolean('ShowRssi')) {
            $this->SetValue('Meta_RSSI', $rssi);
        }
        if ($this->ReadPropertyBoolean('ShowSnr')) {
            $this->SetValue('Meta_SNR', $snr);
        }
        if ($this->ReadPropertyBoolean('ShowFrame')) {
            $this->SetValue('Meta_FrameId', $data->counter);
        }
        if ($this->ReadPropertyBoolean('ShowGatewayCount')) {
            $this->SetValue('Meta_GatewayCount', $gatewayCount);
        }

        $currentTimestamp = time();
        if ($this->ReadPropertyBoolean('ShowInterval')) {
            $lastTimestamp = $this->ReadAttributeInteger('LastMessageTimestamp');
            if ($lastTimestamp != 0) {
                $this->SetValue('Interval', $currentTimestamp - $lastTimestamp);
            }
        }

        if (array_key_exists('downlink_url', $data)) {
            $this->WriteAttributeString('DownlinkUrl', $data->downlink_url);
        }

        $this->WriteAttributeInteger('LastMessageTimestamp', $currentTimestamp);
    }

    public function Downlink(int $port, bool $confirmed, string $schedule, string $payload)
    {
        $this->SendDebug('Downlink()', 'Downlink()', 0);

        $url = $this->ReadAttributeString('DownlinkUrl');
        $this->SendDebug('Downlink() URL', $url, 0);

        if ($url == '') {
            $this->SendDebug('Downlink()', 'URL empty', 0);

            return false;
        }

        if ($port < 0 || $port > 255) {
            $this->SendDebug('Downlink()', 'Port must be between 0 and 255!', 0);

            return false;
        }

        $schedule = strtolower($schedule);
        if ($schedule != 'first' || $schedule != 'last') {
            $schedule = 'replace';
        }

        $this->SendDebug('Downlink() Payload', $payload, 0);

        if (!ctype_xdigit($payload) || (strlen($payload) % 2) != 0) {
            $this->SendDebug('Downlink() Payload Exception', 'Payload is not a HEX-String', 0);

            return false;
        }

        $payloadRaw = base64_encode(hex2bin($payload));

        $postPayloadArray = [
            'dev_id'      => $this->ReadPropertyString('DeviceId'),
            'port'        => $port,
            'confirmed'   => $confirmed,
            'schedule'    => $schedule,
            'payload_raw' => $payloadRaw,
        ];

        $postPayload = json_encode($postPayloadArray);
        $this->SendDebug('Downlink() PostPayload', $postPayload, 0);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $postPayload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_SAFE_UPLOAD    => true,
        ]);

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $this->SendDebug('Downlink() Statuscode', $statusCode, 0);

        return $statusCode == 202;
    }

    private function RegisterVariableProfiles()
    {
        $this->SendDebug('RegisterVariableProfiles()', 'RegisterVariableProfiles()', 0);

        if (!IPS_VariableProfileExists('TTN_Online')) {
            IPS_CreateVariableProfile('TTN_Online', 0);
            IPS_SetVariableProfileAssociation('TTN_Online', 0, $this->Translate('Offline'), '', 0xFF0000);
            IPS_SetVariableProfileAssociation('TTN_Online', 1, $this->Translate('Online'), '', 0x00FF00);
        }
        if (!IPS_VariableProfileExists('TTN_dBm_RSSI')) {
            IPS_CreateVariableProfile('TTN_dBm_RSSI', 1);
            IPS_SetVariableProfileText('TTN_dBm_RSSI', '', ' dBm');
            IPS_SetVariableProfileValues('TTN_dBm_RSSI', -150, 0, 1);
        }

        if (!IPS_VariableProfileExists('TTN_dB_SNR')) {
            IPS_CreateVariableProfile('TTN_dB_SNR', 2);
            IPS_SetVariableProfileDigits('TTN_dB_SNR', 1);
            IPS_SetVariableProfileText('TTN_dB_SNR', '', ' dB');
            IPS_SetVariableProfileValues('TTN_dB_SNR', -25, 15, 0.1);
        }
        if (!IPS_VariableProfileExists('TTN_second')) {
            IPS_CreateVariableProfile('TTN_second', 1);
            IPS_SetVariableProfileText('TTN_second', '', ' s');
        }
    }
}
