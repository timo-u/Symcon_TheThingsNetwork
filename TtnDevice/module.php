<?php

declare(strict_types=1);

    class TtnDevice extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
            $this->RegisterPropertyString('DeviceId', 'DeviceId');

            $this->RegisterPropertyInteger('DataType', 0);

            $this->ConnectParent('{A6D53032-A228-458C-B023-8C3B1117B73B}');

            $this->RegisterPropertyBoolean('ShowMeta', false);
            $this->RegisterPropertyBoolean('ShowRssi', false);
            $this->RegisterPropertyBoolean('ShowGatewayCount', false);
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            $this->Maintain();
        }

        private function Maintain()
        {
            $this->MaintainVariable('Meta_Informations', 'Meta Informations', 3, '', 100, $this->ReadPropertyBoolean('ShowMeta'));
            $this->MaintainVariable('Meta_RSSI', 'RSSI', 1, '', 101, $this->ReadPropertyBoolean('ShowRssi'));
            $this->MaintainVariable('Meta_GatewayCount', 'Gateway Count', 1, '', 102, $this->ReadPropertyBoolean('ShowGatewayCount'));

            $type = $this->ReadPropertyInteger('DataType');
            $this->MaintainVariable('Payload_Boolean', 'Payload', 0, '', 1, $type == 2);
            $this->MaintainVariable('Payload_Integer', 'Payload', 1, '', 1, $type == 3);
            $this->MaintainVariable('Payload_Float', 'Payload', 2, '', 1, $type == 4);
            $this->MaintainVariable('Payload_String', 'Payload', 3, '', 1, $type <= 1);
        }

        public function ReceiveData($JSONString)
        {
            $this->Maintain();

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

            $type = $this->ReadPropertyInteger('DataType');

            if ($type == 1) {
                $payload = bin2hex($payload);
            }

            if ($type <= 1) {
                $this->SetValue('Payload_String', $payload);
            }
            if ($type == 2) {
                $this->SetValue('Payload_Boolean', $payload);
            }
            if ($type == 3) {
                $this->SetValue('Payload_Integer', $payload);
            }
            if ($type == 4) {
                $this->SetValue('Payload_Float', $payload);
            }

            $this->SendDebug('ReceiveData()', 'Payload: ' . $payload, 0);
        }
    }
