<?php

declare(strict_types=1);
    class TtnHttpGateway extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('Authorization', $this->generateRandomString());
            $this->RegisterPropertyString('HookName', 'ttn');
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            $this->SendDebug('ApplyChanges()', 'OK', 0);
        }

        public function RegisterHook()
        {
            $WebHook = '/hook/'.$this->ReadPropertyString('HookName');
            $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
            if (count($ids) > 0) {
                $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
                $found = false;
                foreach ($hooks as $index => $hook) {
                    if ($hook['Hook'] == $WebHook) {
                        if ($hook['TargetID'] == $this->InstanceID) {
                            return;
                        }
                        $hooks[$index]['TargetID'] = $this->InstanceID;
                        $found = true;
                    }
                }
                if (!$found) {
                    $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
                    $this->SendDebug('RegisterHook()', 'Hook: '.$WebHook.' mit TargetID: '.$this->InstanceID.' angelegt', 0);
                }
                IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
                IPS_ApplyChanges($ids[0]);
            } else {
                $this->SendDebug('RegisterHook()', 'Keine WebHook-Instanz verfügbar');
            }
        }

        public function GetUrl()
        {
            $ids = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}');
            $WebHook = '/hook/'.$this->ReadPropertyString('HookName');
            if (count($ids) > 0) {
                $url = CC_GetConnectURL($ids[0]);
                if ($url != '') {
                    echo $this->Translate('The Connect-Service WebHook-URL is: ').$url.$WebHook;
                } else {
                    echo $this->Translate('The WebHook-URL is: ').$url.$WebHook;
                }
            }
        }

        public function OpenWebHook()
        {
            $ids = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}');
            $WebHook = '/hook/'.$this->ReadPropertyString('HookName');
            if (count($ids) > 0) {
                $url = CC_GetConnectURL($ids[0]);
                if ($url != '') {
                    echo $url.$WebHook;
                } else {
                    echo $this->Translate('Connect-Service not active.');
                }
            }
        }

        public function generateRandomString()
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < 30; $i++) {
                if ($i % 5 == 0 && $i != 0) {
                    $randomString .= '-';
                }
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }

            return $randomString;
        }

        protected function ProcessHookData()
        {
            if (!(isset($_SERVER['HTTP_AUTHORIZATION'])) || $_SERVER['HTTP_AUTHORIZATION'] != $this->ReadPropertyString('Authorization')) {
                http_response_code(401);
                echo 'Unauthorized';
                $this->SendDebug('ProcessHookData()', 'Response: 401 Unauthorized', 0);

                return;
            }

            $content = file_get_contents('php://input');
            $this->SendDebug('ProcessHookData()', $content, 0);

            //Try to decode Data
            $data = json_decode((string) $content);
            if ($data == null) {
                http_response_code(400);
                $this->SendDebug('ProcessHookData()', 'Response: 400 Bad Request', 0);
                $this->SendDebug('ProcessHookData()', "JSON Decode Failed. ($data== null)", 0);

                return;
            }

            try {
                $this->SendDataToChildren(json_encode(['DataID' => '{474DDD47-79C2-4B83-AE33-79326BF07B2B}', 'Buffer' => $data]));
            } catch (Exception $e) {
                $this->SendDebug('ProcessHookData()', 'Exception: '.$e, 0);
            }

            http_response_code(200);
        }
    }
