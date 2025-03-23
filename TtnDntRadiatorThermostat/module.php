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

        $this->RegisterPropertyBoolean('ShowAdvancedOptions', false);



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
                if (property_exists($elements->heating_control, 'mode') && property_exists($elements->heating_control->mode, 'manu_temp') && property_exists($elements->heating_control->mode->manu_temp, 'value')) {

                    $this->SetValue('set_point_temperature', $elements->heating_control->mode->manu_temp->value);
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
        $this->MaintainVariable('battery_voltage', $this->Translate('battery voltage'), 2, 'TTN_dnt_thermostat_battery', 10, true);

        $this->EnableAction("set_point_temperature");

        /*
        $ShowAdvancedOptions = $this->ReadPropertyboolean('ShowAdvancedOptions');

        $this->MaintainVariable('window_open_detection', $this->Translate('window open detection'), 0, '', 100, $ShowAdvancedOptions);
        $this->MaintainVariable('holiday', $this->Translate('holiday'), 0, '', 101, $ShowAdvancedOptions);
        $this->MaintainVariable('display_color_inversion', $this->Translate('display_color_inversion'), 0, '', 101, $ShowAdvancedOptions);
        $this->MaintainVariable('display_legacy_temp_scale', $this->Translate('display_color_inversion'), 0, '', 101, $ShowAdvancedOptions);
        $this->MaintainVariable('display_orientation', $this->Translate('display_color_inversion'), 0, '', 101, $ShowAdvancedOptions);
        $this->MaintainVariable('display_color_inversion', $this->Translate('display_color_inversion'), 0, '', 101, $ShowAdvancedOptions);
*/


    }


    public function DownlinkSetReportingInterval(int $seconds, bool $confirmed = false)
    {
        $value = intval(($seconds - 30) / 30);
        if ($value < 0 || $value > 127) {
            $this->LogMessage("SetReportingInterval muss zwiscchen 30 und 3840 Sekunden liegen", KL_WARNING);
            return;
        }
        $this->Downlink(10, $confirmed, "", $this->GetCommandByte("01", true) . $this->IntToHex($value, 1, true));
    }

    public function DownlinkSetPointTemperature(float $temperature)
    {
        $value = intval(($temperature) * 2);
        $this->Downlink(10, false, "", $this->GetCommandByte("22", true).$this->IntToHex($value, 1, true).$this->GetCommandByte("21", true));
    }

    public function DownlinkSetBoostConfig(int $duration, int $boostPosition)
    {


        $this->Downlink(
            10,
            false,
            "",
            $this->GetCommandByte("15", false).	// Boost-Config Setzen
            $this->IntToHex(intval($duration / 15), 1, true).	// Dauer 0-255 *15 Sekunden
            $this->IntToHex(intval($boostPosition * 2), 1, true).	// Ventilposition 0-200 => 0-100%
            $this->GetCommandByte("14", true)	//Boost-Config abfragen
        );
    }

    public function DownlinkSetBoostMode()
    {

        $this->Downlink(10, false, "", $this->GetCommandByte("11", false).$this->GetCommandByte("04", true));
    }

    public function DownlinkSetDisplayConfiguration(int $orientation, bool $colorInverion, bool $legacyTempScale)
    {
        $commandValue = hexdec($orientation % 4);   // Orientierung des Displays in 90° Schritten 0 bis 3
        if ($colorInverion) {                       // Schwarzer Hintergrund und weiße Schrift
            $commandValue += bindec("00000100");
        }
        if ($legacyTempScale) {                     // Anzeige von Scala 1 bis 5 analog zu alten Heizkörperthermostaten
            $commandValue += bindec("00001000");
        }
        $value =  str_pad(dechex($commandValue), 1 * 2, "0", STR_PAD_LEFT);

        $this->Downlink(10, false, "", $this->GetCommandByte("3C", false).$value);
    }

    public function DownlinkGetAllConfig()
    {
        $this->Downlink(10, false, "", $this->GetCommandByte("7C", true));
    }


    private function GetCommandByte($commandId, $instantResponse = false)
    {
        $commandValue = hexdec($commandId);
        if ($commandValue < 0 || $commandValue > 127) {
            $this->SendDebug(__FUNCTION__, 'Die Command ID darf nur 7 Bit betragen ("00" bis "7F")'. $commandValue ." ist größer als ".$bytes." Bytes", 0);
            exit();
        }
        if ($instantResponse) {
            $commandValue += bindec("10000000");
        }
        return str_pad(dechex($commandValue), 1 * 2, "0", STR_PAD_LEFT);
    }

    public function RequestAction($Ident, $Value)
    {
        $this->SendDebug(__FUNCTION__ . ' Ident', $Ident, 0);
        $this->SendDebug(__FUNCTION__ . ' Value', $Value, 0);

        if ($Ident == 'set_point_temperature') {
            $this->DownlinkSetPointTemperature($Value);
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
        if (!IPS_VariableProfileExists('TTN_dnt_thermostat_battery')) {
            IPS_CreateVariableProfile('TTN_dnt_thermostat_battery', 2);
            IPS_SetVariableProfileDigits('TTN_dnt_thermostat_battery', 2);
            IPS_SetVariableProfileText('TTN_dnt_thermostat_battery', '', ' V');
            IPS_SetVariableProfileValues('TTN_dnt_thermostat_battery', 2.0, 3.5, 0.1);
            IPS_SetVariableProfileIcon("TTN_dnt_thermostat_battery", "battery");
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_battery", 0, "%.2f", "battery-exclamation", 0xFF0000);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_battery", 2.4, "%.2f", "battery-low", 0xFFFF00);
            IPS_SetVariableProfileAssociation("TTN_dnt_thermostat_battery", 2.8, "%.2f", "battery-full", 0x00FF00);
        }

    }

}
