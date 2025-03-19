<?php

require_once(__DIR__.'/../libs/TtnMqttBase.php');


class DntRadiatorThermostat extends IPSModule
{
    use TtnMqttBase;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('ApplicationId', 'ApplicationId');
        $this->RegisterPropertyString('DeviceId', 'DeviceId');
        $this->RegisterPropertyString('Tenant', 'ttn');

        //$this->RegisterPropertyBoolean('ShowOutage', true);
        //$this->RegisterPropertyBoolean('ShowPerformanceParameters', true);


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
        $this->RegisterAttributeString('DownlinkUrl', '');

        $this->RegisterTimer('WatchdogTimer', $this->ReadPropertyInteger('WatchdogTime') * 60000, 'TTN_WatchdogTimerElapsed($_IPS[\'TARGET\']);');
        $this->RegisterVariableProfiles();
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



            if (property_exists($elements, 'battery_voltage')) {
                $this->SetValue('battery_voltage', $elements->battery_voltage->value / 1000);
            }

            if (property_exists($elements, 'heating_control')) {
                if (property_exists($elements->heating_control, 'room_temperature')) {
                    $this->SetValue('room_temperature', $elements->heating_control->room_temperature->value);
                    //$this->SendDebug(__FUNCTION__, 'room_temperature: '.$elements->heating_control->room_temperature->value, 0);
                }
                if (property_exists($elements->heating_control, 'set_point_temperature')) {
                    $this->SetValue('set_point_temperature', $elements->heating_control->set_point_temperature->value);
                    //$this->SendDebug(__FUNCTION__, 'set_point_temperature: '.$elements->heating_control->set_point_temperature->value, 0);
                }
                if (property_exists($elements->heating_control, 'mode') && property_exists($elements->heating_control->mode, 'active_mode') && property_exists($elements->heating_control->mode->active_mode, 'value')) {
                    switch ($elements->heating_control->mode->active_mode->value) {
                        case "Manu Temp":
                            $this->SetValue('thermostat_mode', 0);
                            break;
                        case "Manu Temp":
                            $this->SetValue('thermostat_mode', 0);
                            break;
                        case "Manu_Pos":
                            $this->SetValue('thermostat_mode', 1);
                            break;
                        case "Auto":
                            $this->SetValue('thermostat_mode', 2);
                            break;
                        case "Emergency":
                            $this->SetValue('thermostat_mode', 3);
                            break;
                        case "Frost Protection":
                            $this->SetValue('thermostat_mode', 4);
                            break;
                        case "Boost":
                            $this->SetValue('thermostat_mode', 5);
                            break;
                        case "Window Open":
                            $this->SetValue('thermostat_mode', 6);
                            break;
                        case "Holiday":
                            $this->SetValue('thermostat_mode', 7);
                            break;
                    }
                }
            }

        }

    }

    private function Maintain()
    {
        $this->MaintainVariable('room_temperature', $this->Translate('room temperature'), 2, '~Temperature.Room', 1, true);
        $this->MaintainVariable('set_point_temperature', $this->Translate('set point temperature'), 2, '~Temperature.Room', 2, true);
        $this->MaintainVariable('thermostat_mode', $this->Translate('thermostat mode'), 1, 'TTN_dnt_thermostat_mode', 3, true);


        $this->MaintainVariable('battery_voltage', $this->Translate('battery voltage'), 2, '~Volt', 10, true);

        $this->EnableAction("set_point_temperature");

    }


    public function SetReportingInterval(int $seconds, bool $confirmed = false)
    {
        $value = intval(($seconds - 30) / 30);
		if($value<0 || $value>127)
		{
			$this->LogMessage("SetReportingInterval muss zwiscchen 30 und 3840 Sekunden liegen", KL_WARNING);
			return;
		}
		$value = $value + 128; // Setzte das höchste Bit für sofortige Antwort
        $this->Downlink(10, $confirmed, "", "01".$this->IntToHex($value, 1, true));
    }

    public function SetPointTemperature(float $temperature)
    {
        $value = intval(($temperature) * 2);
        $this->Downlink(10, false, "", "22".$this->IntToHex($value, 1, true)."21");
    }

    public function RequestAction($Ident, $Value)
    {
        $this->SendDebug(__FUNCTION__ . ' Ident', $Ident, 0);
        $this->SendDebug(__FUNCTION__ . ' Value', $Value, 0);

        if ($Ident == 'set_point_temperature') {
            $this->SetPointTemperature($Value);
            return true;
        }

    }

    private function RegisterVariableProfiles()
    {
        if (!IPS_VariableProfileExists('TTN_dnt_thermostat_mode')) {
            IPS_CreateVariableProfile('TTN_dnt_thermostat_mode', 1);
            IPS_SetVariableProfileValues('TTN_dnt_thermostat_mode', 0, 7, 1);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_mode", 0, $this->Translate("manual temperature mode"), '', -1);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_mode", 1, $this->Translate("manual positioning mode"), '', -1);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_mode", 2, $this->Translate("automatic mode"), '', -1);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_mode", 3, $this->Translate("emergency mode"), '', -1);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_mode", 4, $this->Translate("antifreeze mode"), '', -1);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_mode", 5, $this->Translate("Boost Modus"), '', -1);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_mode", 6, $this->Translate("window open mode"), '', -1);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_mode", 7, $this->Translate("vacation mode"), '', -1);
            IPS_SetVariableProfileIcon("TTN_dnt_thermostat_mode", "Gauge");
        }

    }

}
