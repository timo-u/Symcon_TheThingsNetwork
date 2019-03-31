<?php

declare(strict_types=1);
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

        $this->RegisterPropertyBoolean('ShowMeta', false);
        $this->RegisterPropertyBoolean('ShowRssi', false);
        $this->RegisterPropertyBoolean('ShowGatewayCount', false);
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
        $elements = json_decode($payload);

        if ($elements == null) {
            $this->SendDebug('ReceiveData()', 'JSON-Decode failed', 0);
        } else {
            foreach ($elements as $key => $value) {
                $this->SendDebug('ReceiveData()', 'Key: ' . $key . ' Value: ' . $value . ' Type: ' . gettype($value), 0);
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

   
		$this->MaintainVariable('Meta_Informations', $this->Translate('Meta Informations'), 3, '', 100, $this->ReadPropertyBoolean('ShowMeta'));
        $this->MaintainVariable('Meta_RSSI', $this->Translate('RSSI'), 1, '', 101, $this->ReadPropertyBoolean('ShowRssi'));
        $this->MaintainVariable('Meta_GatewayCount', $this->Translate('Gateway Count'), 1, '', 102, $this->ReadPropertyBoolean('ShowGatewayCount'));


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

        $this->SendDebug('ReceiveData()', 'Payload: ' . $payload, 0);
    }
}
