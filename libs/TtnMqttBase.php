<?php

declare(strict_types=1);

trait TtnMqttBase
{
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

        $this->RegisterTimer('WatchdogTimer', $this->ReadPropertyInteger('WatchdogTime') * 60000, 'TTN_WatchdogTimerElapsed($_IPS[\'TARGET\']);');
        $this->RegisterBaseVariableProfiles();
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainBaseVaraibles();

        //Setze Filter für ReceiveData
        if ($this->ReadPropertyString('Tenant') == "") { // Leerer Tennat bei Lokalem Stack
            $MQTTTopic = "v3/" .$this->ReadPropertyString('ApplicationId').'/devices/'.$this->ReadPropertyString('DeviceId').'/up';
        } else {
            $MQTTTopic = "v3/" .$this->ReadPropertyString('ApplicationId')."@" .$this->ReadPropertyString('Tenant').'/devices/'.$this->ReadPropertyString('DeviceId').'/up';
        }

        $this->SetReceiveDataFilter('.*'.$MQTTTopic.'.*');

    }

    private function MaintainBaseVaraibles()
    {
        $this->MaintainVariable('Meta_Informations', $this->Translate('Meta Informations'), 3, '', 100, $this->ReadPropertyBoolean('ShowMeta'));
        $this->MaintainVariable('Meta_SpreadingFactor', $this->Translate('Spreading Factor'), 1, 'TTN_spreadingfactor', 100, $this->ReadPropertyBoolean('ShowMeta'));
        $this->MaintainVariable('Meta_RSSI', $this->Translate('RSSI'), 1, 'TTN_dBm_RSSI', 101, $this->ReadPropertyBoolean('ShowRssi'));
        $this->MaintainVariable('Meta_SNR', $this->Translate('SNR'), 2, 'TTN_dB_SNR', 102, $this->ReadPropertyBoolean('ShowSnr'));
        $this->MaintainVariable('Meta_FrameId', $this->Translate('Frame ID'), 1, '', 103, $this->ReadPropertyBoolean('ShowFrame'));
        $this->MaintainVariable('Meta_GatewayCount', $this->Translate('Gateway Count'), 1, 'TTN_count', 104, $this->ReadPropertyBoolean('ShowGatewayCount'));
        $this->MaintainVariable('State', $this->Translate('State'), 0, 'TTN_Online', 105, $this->ReadPropertyBoolean('ShowState'));
        $this->MaintainVariable('Interval', $this->Translate('Interval'), 1, 'TTN_second', 106, $this->ReadPropertyBoolean('ShowInterval'));
        $this->MaintainVariable('LastMessageTime', $this->Translate('Last Message Time'), 3, '', 107, $this->ReadPropertyBoolean('LastMessageTime'));
        $this->Maintain();
    }
    
    private function Maintain()
    {
        //für Vaiablen in den Modulen
    }

    public function ReceiveData($JSONString)
    {

        $data = json_decode($JSONString);
        $data = $data->Payload;
        $data = json_decode($data);

        // Prüfung ob V2 Stack => Abbruch
        if (! property_exists($data, 'end_device_ids')) {
            return;
        }

        // Prüfung ob keine Uplink Messsage => Abbruch
        if (! property_exists($data, 'uplink_message')) {
            return;
        }

        // Prüfung ob die Application-ID passt
        if ($data->end_device_ids->application_ids->application_id != $this->ReadPropertyString('ApplicationId')) {
            return;
        }

        // Prüfung die Device-ID passt
        if ($data->end_device_ids->device_id != $this->ReadPropertyString('DeviceId')) {
            return;
        }
        // Buffer für weitere Anwendungen via TTN_GetData();
        $this->SetBuffer('DataBuffer', json_encode($data));

        // Zurücksetzen des WatchdogTimers bei Empfang einer Nachricht
        $this->WatchdogReset();

        $this->SendDebug(__FUNCTION__, 'Application_ID & Device_ID OK', 0);

        $this->HandleReceivedData($data);

        $this->MaintainBaseVaraibles();

        // Initialisierungswerte setzten
        $rssi = -200;
        $snr = -200;
        $gatewayCount = 0;

        // Suche nach bestem RSSI und SNR und Zählen der Gateway-Anzahl
        if (property_exists($data->uplink_message, 'rx_metadata')) {
            $gateways = $data->uplink_message->rx_metadata;
            foreach ($gateways as $gateway) {

                if (property_exists($gateway, 'snr')  && $snr < $gateway->snr) {
                    $snr = $gateway->snr;
                }

                if (property_exists($gateway, 'rssi')  && $rssi < $gateway->rssi) {
                    $rssi = $gateway->rssi;
                    $this->SendDebug(__FUNCTION__, 'Gateway: '.$gateway->gateway_ids->gateway_id . " RSSI: " .$gateway->rssi, 0);
                }

            }
            $gatewayCount = count($gateways);
        }

        $this->SendDebug(__FUNCTION__, 'Best RSSI: '.$rssi, 0);
        $this->SendDebug(__FUNCTION__, 'Best SNR: '.$snr, 0);


        if ($this->ReadPropertyBoolean('ShowMeta')) {
            if (property_exists($data->uplink_message, 'settings')) {
                $infos = "";
                if (property_exists($data->uplink_message->settings, 'frequency')) {
                    $infos .= 'Freq: '.$data->uplink_message->settings->frequency / 1000000 ." MHz ";
                }

                if (property_exists($data->uplink_message->settings, 'data_rate')
                    && property_exists($data->uplink_message->settings, 'lora')
                    && property_exists($data->uplink_message->settings->lora, 'bandwidth')) {
                    $infos .= 'BW: '.$data->uplink_message->settings->data_rate->lora->bandwidth / 1000 ." kHz ";
                }

                if (property_exists($data->uplink_message->settings, 'coding_rate')) {
                    $infos .= 'Coding Rate: '.$data->uplink_message->settings->coding_rate." ";
                }

                if (property_exists($data->uplink_message, 'consumed_airtime')) {
                    $infos .= 'AirTime : ' . $data->uplink_message->consumed_airtime ." ";
                }

                $this->SetValue('Meta_Informations', $infos);

                $this->SetValue('Meta_SpreadingFactor', $data->uplink_message->settings->data_rate->lora->spreading_factor);
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
            if (property_exists($data->uplink_message, 'f_cnt')) { // wenn FrameID==0 existiert dieses Feld nicht
                $this->SendDebug(__FUNCTION__, 'Frame Counter : '.$data->uplink_message->f_cnt, 0);
                $this->SetValue('Meta_FrameId', $data->uplink_message->f_cnt);
            } else {
                $this->SetValue('Meta_FrameId', 0);
            }
        }

        if ($this->ReadPropertyBoolean('ShowGatewayCount')) {
            $this->SetValue('Meta_GatewayCount', $gatewayCount);
        }

        // Intervall berechnen
        $currentTimestamp = time();
        if ($this->ReadPropertyBoolean('ShowInterval')) {
            $lastTimestamp = $this->ReadAttributeInteger('LastMessageTimestamp');
            if ($lastTimestamp != 0) {
                $this->SetValue('Interval', $currentTimestamp - $lastTimestamp);
            }
        }
        // Timestamp der letzten Übertragung schreiben.
        $this->WriteAttributeInteger('LastMessageTimestamp', $currentTimestamp);

        if ($this->ReadPropertyBoolean('LastMessageTime')) {
            $this->SetValue('LastMessageTime', (new DateTime('NOW'))->format('Y-m-d H:i:s'));
        }
    }

    private function HandleReceivedData($data)
    {
        // Payload-Elemente auslesen sofern vorhanden
        if (property_exists($data, 'uplink_message') && property_exists($data->uplink_message, 'decoded_payload')) {
            $elements = $data->uplink_message->decoded_payload;
            $this->SendDebug(__FUNCTION__, 'Payload: '.json_encode($elements), 0);
        } else {
            $elements = null;
            $this->SendDebug(__FUNCTION__, 'Key: uplink_message->decoded_payload does not exist', 0);
        }

        if ($elements == null) {
            $this->SendDebug(__FUNCTION__, 'JSON-Decode failed', 0);
        } else {
            foreach ($elements as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Key: '.$key.' Value: '.print_r($value, true).' Type: '.gettype($value), 0);
                // Prüfung ob Variable nicht vorhannden
                if (@$this->GetIDForIdent($key) == false) {
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


    public function GetVariables()
    {
        $children = IPS_GetChildrenIDs($this->InstanceID);
        $data = [];

        foreach ($children as &$child) {
            $variable = (IPS_GetObject($child));
            if ($variable['ObjectType'] != 2) {
                continue;
            }
            if ($variable['ObjectIdent'] != "") {
                $name = $variable['ObjectIdent'];
            } else {
                $name = $variable['ObjectName'];
            }

            $data[$name] = (GetValue($child));

        }

        $data['Timestamp'] = $this->ReadAttributeInteger('LastMessageTimestamp');

        return $data;
    }

    public function Downlink(int $port, bool $confirmed, string $schedule, string $payload)
    {
        $this->SendDebug(__FUNCTION__, 'Downlink()', 0);

        if ($port < 0 || $port > 255) {
            $this->SendDebug(__FUNCTION__, 'Port must be between 0 and 255!', 0);

            return false;
        }

        $schedule = strtolower($schedule);
        if ($schedule == 'push') {
            $method = "push";
        } else {
            $method = "replace";
        }

        $this->SendDebug(__FUNCTION__, 'Payload: '. $payload, 0);

        if (!ctype_xdigit($payload) || (strlen($payload) % 2) != 0) {
            $this->SendDebug(__FUNCTION__, 'Payload Exception: Payload is not a HEX-String', 0);

            return false;
        }

        $payloadRaw = base64_encode(hex2bin($payload));

        $downlinks = [
            'f_port'        => $port,
            'confirmed'   => $confirmed,
            'priority'   => "NORMAL",
            'frm_payload' => $payloadRaw,
        ];
        $postPayloadArray = [
            'downlinks'      => array($downlinks)
        ];
        $Payload = json_encode($postPayloadArray);
        $this->SendDebug(__FUNCTION__, 'Payload: '. $Payload, 0);


        $MQTTTopic = "v3/" .$this->ReadPropertyString('ApplicationId')."@" .$this->ReadPropertyString('Tenant').'/devices/'.$this->ReadPropertyString('DeviceId').'/down/'.$method;
        $this->SendDebug(__FUNCTION__, 'Topic: '. $MQTTTopic, 0);
        $result = $this->SendMQTT($MQTTTopic, $Payload);

        $this->SendDebug(__FUNCTION__, 'Successfull: '. intval($result), 0);

        return $result;
    }
    public function DownlinkMulticast(int $port, string $schedule, string $payload, string $gatewayids, int $interval, int $repetitions)
    {
        $this->SendDebug(__FUNCTION__, 'DownlinkMulticast()', 0);

        if ($port < 0 || $port > 255) {
            $this->SendDebug(__FUNCTION__, 'Port must be between 0 and 255!', 0);

            return false;
        }
        $gatewayids = explode(',', $gatewayids);
        if (count($gatewayids) == 0) {
            $this->SendDebug(__FUNCTION__, 'For multicast, at least one gateway must be specified.', 0);
            return false;
        }

        $schedule = strtolower($schedule);
        if ($schedule == 'push') {
            $method = "push";
        } else {
            $method = "replace";
        }

        $this->SendDebug(__FUNCTION__, 'Payload: '. $payload, 0);

        if (!ctype_xdigit($payload) || (strlen($payload) % 2) != 0) {
            $this->SendDebug(__FUNCTION__, 'Payload Exception: Payload is not a HEX-String', 0);

            return false;
        }

        $payloadRaw = base64_encode(hex2bin($payload));



        for ($i = 1; $i <= $repetitions; $i++) {
            $this->SendDebug(__FUNCTION__, $i. ". Durchlauf", 0);

            foreach ($gatewayids as &$gatewayid) {
                $gatewayList = array();
                $curentgatway['gateway_ids']['gateway_id'] = $gatewayid;
                array_push($gatewayList, $curentgatway);
                $gatewayList1['gateways'] = $gatewayList;

                // Dies sollte der eigenltiche Implementierung sein. Im Test funktionierte dies jedoch nicht.
                //date_default_timezone_set('UTC');
                //$gatewayList1['absolute_time'] = date("Y-m-d\TH:i:s.000\Z");
                //$gatewayList1['absolute_time'] =date("Y-m-d\TH:i:s.000\Z",strtotime('+'.$i*$interval+10 .' seconds'));



                $downlinks = [
                    'f_port'        => $port,
                    'confirmed'   => false,
                    'priority'   => "NORMAL",
                    'frm_payload' => $payloadRaw,
                    'class_b_c' => ($gatewayList1),
                ];

                $postPayloadArray = [
                    'downlinks'      => array($downlinks)
                    ];

                $Payload = json_encode($postPayloadArray);
                $this->SendDebug(__FUNCTION__, 'Payload'. $Payload, 0);
                $this->SendDebug(__FUNCTION__, 'Gateway: '. $gatewayid, 0);


                $MQTTTopic = "v3/" .$this->ReadPropertyString('ApplicationId')."@" .$this->ReadPropertyString('Tenant').'/devices/'.$this->ReadPropertyString('DeviceId').'/down/'.$method;
                $this->SendDebug(__FUNCTION__, 'Topic: '. $MQTTTopic, 0);
                $result = $this->SendMQTT($MQTTTopic, $Payload);
                ips_sleep($interval * 1000); // zwischen den Aussendeungen der einzelnen Gateways Zeit lassen um überschneidungen der Pakete zu verhindern
            }
        }

        $this->SendDebug(__FUNCTION__, 'Successfull: '. intval($result), 0);

        return $result;
    }
    protected function SendMQTT($Topic, $Payload)
    {
        $resultServer = true;
        $resultClient = true;
        //MQTT Server
        $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Server['PacketType'] = 3;
        $Server['QualityOfService'] = 0;
        $Server['Retain'] = false;
        $Server['Topic'] = $Topic;
        $Server['Payload'] = $Payload;
        $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__, 'MQTT Server '. $ServerJSON, 0);
        $resultServer = @$this->SendDataToParent($ServerJSON);

        //MQTT Client
        $Buffer['PacketType'] = 3;
        $Buffer['QualityOfService'] = 0;
        $Buffer['Retain'] = false;
        $Buffer['Topic'] = $Topic;
        $Buffer['Payload'] = $Payload;
        $BufferJSON = json_encode($Buffer, JSON_UNESCAPED_SLASHES);

        $Client['DataID'] = '{97475B04-67C3-A74D-C970-E9409B0EFA1D}';
        $Client['Buffer'] = $BufferJSON;

        $ClientJSON = json_encode($Client);
        $this->SendDebug(__FUNCTION__, 'MQTT Client '. $ClientJSON, 0);
        $resultClient = @$this->SendDataToParent($ClientJSON);

        return $resultServer === false && $resultClient === false;
    }

    private function RegisterBaseVariableProfiles()
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
            IPS_SetVariableProfileIcon("TTN_dBm_RSSI", "Gauge");
        }

        if (!IPS_VariableProfileExists('TTN_dB_SNR')) {
            IPS_CreateVariableProfile('TTN_dB_SNR', 2);
            IPS_SetVariableProfileDigits('TTN_dB_SNR', 1);
            IPS_SetVariableProfileText('TTN_dB_SNR', '', ' dB');
            IPS_SetVariableProfileValues('TTN_dB_SNR', -25, 15, 0.1);
            IPS_SetVariableProfileIcon("TTN_dB_SNR", "Gauge");
        }
        if (!IPS_VariableProfileExists('TTN_second')) {
            IPS_CreateVariableProfile('TTN_second', 1);
            IPS_SetVariableProfileText('TTN_second', '', ' s');
            IPS_SetVariableProfileIcon("TTN_second", "Clock");
        }
        if (!IPS_VariableProfileExists('TTN_count')) {
            IPS_CreateVariableProfile('TTN_count', 1);
            IPS_SetVariableProfileValues('TTN_count', 0, 10, 1);
            IPS_SetVariableProfileText('TTN_count', '', '');
            IPS_SetVariableProfileIcon("TTN_count", "Snow");
        }
        if (!IPS_VariableProfileExists('TTN_spreadingfactor')) {
            IPS_CreateVariableProfile('TTN_spreadingfactor', 1);
            IPS_SetVariableProfileValues('TTN_spreadingfactor', 7, 12, 1);
            IPS_SetVariableProfileText('TTN_spreadingfactor', 'SF', '');
            IPS_SetVariableProfileIcon("TTN_spreadingfactor", "Intensity");
        }
        $this->RegisterVariableProfiles();
    }

    private function RegisterVariableProfiles()
    {
        //für Vaiablenprofile in den Modulen
    }

    public function WatchdogTimerElapsed()
    {
        if ($this->ReadPropertyBoolean('ShowState')) {
            $this->SetValue('State', false);
        }
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

    public function GetInstanceState()
    {
        if ($this->ReadPropertyBoolean('ShowState')) {
            return $this->GetValue('State');
        } else {
            return false;
        }
    }

    private function IntToHex(int $value, int $bytes, bool $toLittleEndian = false)
    {
        $binary = dechex($value);
        if (strlen($binary) > $bytes * 2) {
            $this->SendDebug(__FUNCTION__, 'Der Wert '. $value ." ist größer als ".$bytes." Bytes", 0);
            return null;
        }
        $binary = str_pad($binary, $bytes * 2, "0", STR_PAD_LEFT);

        if ($toLittleEndian && $bytes > 1) {
            $binary = bin2hex(implode(array_reverse(str_split(hex2bin($binary)))));
        }
        return $binary;
    }


}
