<?php

class TtnObjectDeviceV2 extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
        $this->RegisterPropertyString('DeviceId', 'DeviceId');
        $this->RegisterPropertyBoolean('GetContentFromRawPayload', false);
        $this->RegisterPropertyBoolean('AutoCreateVariable', false);

        $this->RegisterPropertyBoolean('ShowState', false);
        $this->RegisterPropertyInteger('WatchdogTime', 0);
        $this->ConnectParent('{A6D53032-A228-458C-B023-8C3B1117B73B}');

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

    public function GetData()
    {
        return json_decode($this->GetBuffer('DataBuffer'));
    }

    public function GetState()
    {
        return $this->GetValue('State');
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $data = $data->Buffer;

        if (! property_exists($data, 'app_id')) { // Prüfung ob V3 Stack => Abbruch
            return;
        }

        if ($data->app_id != $this->ReadPropertyString('ApplicationId')) {
            return;
        }
        if ($data->dev_id != $this->ReadPropertyString('DeviceId')) {
            return;
        }
        $this->SetBuffer('DataBuffer', json_encode($data));

        $this->WatchdogReset();

        $this->SendDebug('ReceiveData()', 'Application_ID & Device_ID OK', 0);
        if ($this->ReadPropertyBoolean('GetContentFromRawPayload')) {
            $payload = base64_decode($data->payload_raw);
            $elements = json_decode($payload);
            $this->SendDebug('ReceiveData()', 'Payload: '.$payload, 0);
        } else {
            if (property_exists($data, 'payload_fields')) {
                $elements = $data->payload_fields;
                $this->SendDebug('ReceiveData()', 'Payload: '.json_encode($elements), 0);
            } else {
                $elements = null;
                $this->SendDebug('ReceiveData()', 'Key: payload_fields does not exist', 0);
            }
        }
        if ($elements == null) {
            $this->SendDebug('ReceiveData()', 'JSON-Decode failed', 0);
        } else {
            foreach ($elements as $key => $value) {
                $this->SendDebug('ReceiveData()', 'Key: '.$key.' Value: '.$value.' Type: '.gettype($value), 0);
                $id = @$this->GetIDForIdent($key);
                if ($id == false) {
                    if (!$this->ReadPropertyBoolean('AutoCreateVariable')) {
                        continue;
                    }
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

                SetValue($id, $value);
            }
        }

        $this->Maintain();

        $metadata = $data->metadata;

        $rssi = -200;
        $snr = -200;
        $gatewayCount = 0;

        if (property_exists($metadata, 'gateways')) {
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
            if (property_exists($metadata, 'frequency')) {
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

        if (property_exists($data, 'downlink_url')) {
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
