<?php

declare(strict_types=1);
class TtnConfigurator extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
    }

    public function ApplyChanges()
    {
        //Apply filter
        parent::ApplyChanges();
		// nach MQTT-Paketen im Format des TTI V3 Stack suchen 
        $this->SetReceiveDataFilter('.*v3\/.*\/devices\/.*');
    }

    public function GetConfigurationForm()
    {
		// Formular dynamisch anpassen
        $data = json_decode(file_get_contents(__DIR__ . '/form.json'));
        $TtnDevices = $this->getTtnDevices();
        if (count($TtnDevices) > 0) {
            foreach ($TtnDevices as $device) {
                $InstanzName = '-';
                $instanceID = $this->searchTtnDevice($device);
                if ($instanceID != 0) {
                    $InstanzName = IPS_GetInstance($instanceID)['ModuleInfo']['ModuleName'];
                }
                $data->actions[0]->values[] = [

                    'DeviceId'              => $device['DeviceId'],
                    'ApplicationId'         => $device['ApplicationId'],
                    'Tenant'                => $device['Tenant'],
                    'Instanz'               => $InstanzName,
                    'instanceID'            => $instanceID,
                    'create'                => [
                            'moduleID'      => '{FF6D63B4-E6C1-C76C-5CDD-626847F3B3FA}',
                            'location'      => ['TTN',$device['ApplicationId']],
                            'name'         => $device['DeviceId'],
                            'configuration' => [
                                'Tenant'    => $device['Tenant'],
                                'ApplicationId'    => $device['ApplicationId'],
                                'DeviceId'    => $device['DeviceId']
                            ]
                    ]
                ];
            }
        }
        return json_encode($data);
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $topic = $data->Topic;

        $this->SendDebug('ReceiveData()', "Topic: ".  $topic, 0);
        // die ersten Elemente des Topics auswählen
        $elements = explode("/", $topic);
        $topic = implode("/", array($elements[0],$elements[1],$elements[2],$elements[3]));

        // Buffer an Topics auslesen
        $topics = json_decode($this->GetBuffer('Topics'));
        // Wenn noch nicht vorhanden neues Array erstellen
        if ($topics == null) {
            $topics= array();
        }

        $this->SendDebug('ReceiveData()', "Buffer: ".  $this->GetBuffer('Topics'), 0);

        // Neues Topic anlegen
        if (!in_array($topic, $topics)) {
            array_push($topics, $topic);
            $this->SetBuffer('Topics', json_encode($topics));

            $this->SendDebug('ReceiveData()', "Topic hinzugefügt: ".  $topic, 0);
        }
    }
	
	private function getTtnDeviceIds()
	{
		// Geräte im Objektbaum suchen

        $idsMQTTDevice = IPS_GetInstanceListByModuleID('{FF6D63B4-E6C1-C76C-5CDD-626847F3B3FA}');
		return $idsMQTTDevice;
		
		// Für weitere Instanzen 
		// $idsMQTTDevice = IPS_GetInstanceListByModuleID('{FF6D63B4-E6C1-C76C-5CDD-626847F3B3FA}');
        //  $ids = array_merge($idsMQTTDevice, $idsMQTTDevice);
		//return $ids;
		
	}
	

    private function searchTtnDevice($device)
    {
        $ids = $this->getTtnDeviceIds();
		
		//Geräte nach passenden Attributen durchsuchen und Geräte-ID zurückgeben
        foreach ($ids as $id) {
            if (IPS_GetProperty($id, 'Tenant') == $device['Tenant']
                && IPS_GetProperty($id, 'ApplicationId') == $device['ApplicationId']
                && IPS_GetProperty($id, 'DeviceId') == $device['DeviceId']) {
                return $id;
            }
        }
        return 0;
    }

    private function getTtnDevices()
    {
        // Geräte anhand der Topics im Buffer erstellen
        $topics = json_decode($this->GetBuffer('Topics'));
		
		// Array initialisieren falls noch nicht vorhanden
        if ($topics == null) {
            $topics= array();
            return array();
        }
        
        $ttnDevices = array();


		// Geräteatribute aus Topic erstellen
        foreach ($topics as $topic) {
            $elements = explode("/", $topic);
            $applicationrenant = explode("@", $elements[1]);

            $device['Tenant'] = $applicationrenant[1] ;
            $device['ApplicationId']=$applicationrenant[0];
            $device['DeviceId'] =$elements[3];
            array_push($ttnDevices, $device);
        }
		
        return $ttnDevices;
    }
}
