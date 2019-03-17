<?php

declare(strict_types=1);

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

            $this->RegisterPropertyBoolean('ShowMeta', false);
            $this->RegisterPropertyBoolean('ShowRssi', false);
            $this->RegisterPropertyBoolean('ShowGatewayCount', false);
            $this->SetStatus(201);
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

            if ($data->app_id != $this->ReadPropertyString('ApplicationId')) {
                return;
            }
            if ($data->dev_id != $this->ReadPropertyString('DeviceId')) {
                return;
            }

            $this->SendDebug('ReceiveData()', 'Application_ID & Device_ID OK', 0);

            $payload = base64_decode($data->payload_raw);

            if ($this->ReadPropertyBoolean('UseHex')) {
                $payload = bin2hex($payload);
            }

            $this->MaintainVariable('Meta_Informations', 'Meta Informations', 3, '', 100, $this->ReadPropertyBoolean('ShowMeta'));
            $this->MaintainVariable('Meta_RSSI', 'RSSI', 1, '', 101, $this->ReadPropertyBoolean('ShowRssi'));
            $this->MaintainVariable('Meta_GatewayCount', 'Gateway Count', 1, '', 102, $this->ReadPropertyBoolean('ShowGatewayCount'));

            $metadata = $data->metadata;
            $gateways = $metadata->gateways;

            $rssi = -200;

            foreach ($gateways as $gateway) {
                if ($rssi < $gateway->rssi) {
                    $rssi = $gateway->rssi;
                }
            }
            $this->SendDebug('ReceiveData()', 'Best RSSI: ' . $rssi, 0);
            if ($this->ReadPropertyBoolean('ShowMeta')) {
                $this->SetValue('Meta_Informations', 'Freq: ' . $metadata->frequency . ' Modulation: ' . $metadata->modulation . ' Data Rate: ' . $metadata->data_rate . ' Coding Rate: ' . $metadata->coding_rate);
            }
            if ($this->ReadPropertyBoolean('ShowRssi')) {
                $this->SetValue('Meta_RSSI', $rssi);
            }
            if ($this->ReadPropertyBoolean('ShowGatewayCount')) {
                $this->SetValue('Meta_GatewayCount', count($gateways));
            }

            $this->SetValue('Payload', $payload);

            $this->SendDebug('ReceiveData()', 'Payload: ' . $payload, 0);
        }
    }
