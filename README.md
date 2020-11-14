# Symcon_TheThingsNetwork
[![StyleCI Status](https://styleci.io/repos/175640288/shield)](https://styleci.io/repos/175640288)

Das Modul verbindet das The Things Network (https://www.thethingsnetwork.org/) mit IP-Symcon (https://www.symcon.de). Hierfür wird die HTTP-Integration genutzt. 
Erstellt und getestet wurde das Modul mit der HTTP Integration (v2.6.0).

## Vorraussetzungen 
* natürlich IP-Symcon
	* Symcon ab V5.0
		* IP-Symcon muss vom Internt über HTTP(S) erreichbar sein  
		* Symcon Connect dienst 
   		* Aktive Subscription erforderlich
 		* Proxy-Server (HTTPS) <br>   
ODER 	
	* Symcon ab V5.5 mit MQTT Client
* The Things Network Account 


## TTN HTTP Integration Gateway
Das Gateway (IO-Instanz) ist der Zentrale Empfänger in Symcon, in dem alle Nachrichten von TTN eingehen. 
Das TTN HTTP Integration Gateway hat nichts mit einem Physikalischen Gateway zu tun sondern bietet nur die Schnittstelle zwischen TTN und Symcon. 
Im Geatewy wird die Autorisierung eingetragen und der WebHook angelegt (Standard: /hook/ttn).
Der Name kann im Konfigurationsformular unter "Hook Name" angepasst werden.
Um den Webhook vor fremden Zugriff zu schützen wird ein Autorisierungstoken erstellt. Der Token wird beim erstellen der Instanz generiert und kann so direkt verwendet werden. 
Der hier eingetragene Wert muss natürlich mit dem Wert in der HTTP Integration übereinstimmen. (Weitere Infos in der Einrichtung) 

![Instanz erstellen](imgs/Symcon_TTN_HttpIntegrationGateway.png?raw=true "Instanz erstellen")

### WebHook Registriern 
Der Button "WebHook Registriern" legt selbstständig einen Webhook (Standard: /hook/ttn) an, der Instanz mit dem WebHook Control verbindet. Wenn ein WebHook mit gleichem Namen existiert wird dieser überschrieben!

### WebHook URL Abrufen 
Der Button gibt die URL aus, unter der die eingehenden Daten empfangen werden können. 

### WebHook Öffnen
Durch Drücken auf diesen Button wird die WebHook-URL in einem neuen Tab geöffnet. 
Wenn die Einrichtung bis jetzt erfolgreich war, wird "Unauthorized" angezeigt.

### open TTN Console
Durch drücken auf den Button wird die TTN-Console in einem neuen Tab geöffnet.


## Generelle Informationen zu den Devices
### Konfigurationsformular
Durch Eingabe der Application-ID und der Device-ID wird sichergestellt, dass nur Nachrichten empfangen werden, die für das Gerät bestimmt sind. 
Die Devices verbinden sich selbstständig mit einem Gateway. Wenn kein Gateway existiert wird ein Gateway erstellt. 

#### Status anzueigen
Zeigt den Status (Online/Offline) mit Hilfe eines Watchdogs. Beim Empfang einer Nachricht wird der Watchdog zurückgesetzt. Ist der Watchdog-Timer abgelaufen wird der Status als Offline angezeigt. 

#### Watchdog Zeit
Hier kann die Zeitdauer für den Watchdog in Minuten eingestellt werden. 

#### Show Meta Informations
Leigt eine Variable "Meta Informatios" an und gibt Infos zu Frequenz und Modulationsverfahren aus.

#### Show RSSI
Legt eine Variable "RSSI" aus und gibt den höchsten RSSI-Wert(bei mehrern Gateways) des letzten LoRa-Pakets aus.

#### Show SNR
Legt eine Variable "SNR" aus und gibt den höchsten SNR-Wert(bei mehrern Gateways) des letzten LoRa-Pakets aus.

#### Show Gateway Count
Legt eine Variable "Gateway Count" an und gibt die Anzahl der Gateways aus, die die Nachticht empfangen haben aus.

#### Show Frame Counter
Zeigt die Frame ID des letzten Lora-Pakets an 

#### Show Intervall
Zeigt das Sendeinterall der empfangen packete in Sekunden an. 

## TTN Device
Der Payload der eingehenden Nachricht wird in die Variable "Payload" geschrieben. 
Durch das Feld "Type" kann der Datentyp ausgewählt werden, der verwendet werden soll. 
Zur Auswahl hierfür steht: 
- String 
- HEX (Daten werden Hexadezimal in eine String-Variable geschrieben)
- Boolean
- Integer
- Float
Die Instanz liest die Daten aus dem Feld "Payload" aus. Diese Instanz macht dann Sinn, wenn die Daten nicht bei TTN Decodiert werden sondern in Symcon.
In der Regel kann der Payload direkt bei TTN decodiert werden. Dann kann das TTN Object Device verwedet werden.  
#### Downlink

Mit TTN_Downlink() kann ein Downlink erzeugt werden. Hierfür

Beispiel:
$result =  TTN_Downlink(Instanz-ID,Port,Confirmed,Schedule,HEX-Daten);
* Instanz-ID:
Die Instanz-ID des TTN Object Device. (andere Instanzen werden z.Z. nicht unterstützt) 
* Port:
Hier kann der Port angegeben werden, der verwendet werden soll. Hier sind Werte von 0 bis 255 möglich

* Confirmed:
Hier wird festgelegt ob das Packet vom Node bestätigt werden soll oder nicht. 

* Schedule:
Hier wird festgelegt wie das Packet in der Cue behandelt werden soll. "" oder "replace" ersetzt das vorherige Packet. Außerdem kann das Packet mit "first" oder "last" in die Cue einsortiert werden. 

* HEX-Daten:
Hier werden die HEX-Daten als String übergeben. Z.B. "00AAFF"



## TTN Object Device
Diese Instanz eignet sich für Nachrichten die ein Valides JSON als Payload verweden oder wenn die Decodiereung bereits im TTN duchgeführt wird.
Die Decodierung kann in der TTN Console in der Application unter PAYLOAD FORMATS => Decoder eingestellt werden. 
Unterstützte Formate sind hierbei String, Boolean, Integer und Double (bzw. Float).

Durch das Feld "Auto Create Variables" werden die nötigen Variablen automatisch erstellt.
Hinweis: Wenn der Wert einer Float-Variable beim ersten Empfang eine ganze Zahl ist wird diese als Integer erstellt. Hier einfach die Variable löschen bis die Empfangene Variabe eine Nachkommastelle enthält.


# Einrichtung via HTTP Integration
Zunächst muss Symcon mit aktivem Connect-Dienst sowie ein Acount bei The Things Network zur verfüngung stehen

## Einrichtung des TTN HTTP Integration Gateways
In der Symcon-Console unter IO-Instanzen eine Instanz vom Typ "TTN HTTP Integration Gateway" erstellen. 
Durch den Button "WebHook Öffnen" wird die komplette URL im Browser aufgerufen. 
Nun ist das HTTP-Gateway betriebsbereit.

## Einrichtung der Verbindung im TTN 
Mit dem Button "Open TTN Console" öffnet sich die Console direkt im Browser. 
Nun navigiert man zu der Application, die hier verwendet werden soll (Die Einrichtung muss für jede Application erfolgen!).
Alternativ kann eine eigene Application erstellt werden: 
![Application erstellen](imgs/Symcon_TTN_Create_Application.png?raw=true "Application erstellen")

In der Application kann man unter "Integrations"  und "Add Integration" eine "HTTP Integration" hinzufügen. 
* Process ID:
Hier muss man zunächst eine Eindeutige ID für diese Integration angegeben werden. Diese wird im weitern Verlauf nicht mehr benötigt. 

* Access Key:
Hier muss der TTN-Key ausgewählt werden, der verwendet werden soll. Hier kann man den "default key" verwenden, oder sich einen neuen für die Integration aktivieren. Der Key wird im weiteren Verlauf auch nicht benötigt. 

* URL:
Hier wird die URL eingetragen die im TTN HTTP Integration Gateway durch den Button "WebHook Öffnen" im Browser aufgerufen wird.
Bei verwendung eines Proxy-Dienstes oder einer IP-Adresse muss diese mit der erweiterung für den webhook (/hook/ttn/) eingetragen werden. 

* Method:
Hier bleibt es bei POST

* Authorization: 
Hier wird der Token eingetragen, der im TTN HTTP Integration Gateway als "Autorisierung" angegeben bzw automatisch erstellt wird. 

* Custom Header Name
Wird nicht verwendet.

* Custom Header Value
Wird nicht verwendet.

![HTTP Integration erstellen](imgs/Symcon_TTN_Create_Webhook.png?raw=true "HTTP Integration erstellen")

## Einrichtung der Geräte in Symcon
Bei der Auswahl der Geräte kommt es natürlich darauf an, welche Geräte mit dem TTN verwebunden werden. 

Hier verwenede ich das TTN-Device, welches die empfangenen HEX-Werte in einen String schreibt.

Hiierfür wird im Symcon ein "TTN_Device" erstellt. Parallel wird in der TTN-Console die das Device aufgerufen was eingebunden werden soll. 

Die Anwendungs-ID / Application-ID wird aus der TTN-Console in das Symcon Device kopiert. 
Die Geräte-ID / Device-ID wird aus der TTN-Console in das Symcon Device kopiert. 

In Symcon wird "HEX-String" als Datentyp ausgewählt. 

![Instanz erstellen](imgs/Symcon_TTN_Device_in_TTN_console.png?raw=true "Instanz erstellen")

![Geräte-Instanz erstellen](imgs/Symcon_TTN_Create_TTN_Device.png?raw=true "Geräte-Instanz erstellen")


Nun ist das Gerät mit Symcon verbunden. 


# Einrichtung via MQTT (TTN MQTT Device)

## Client Socket 
![MQTT Client Socket](imgs/Symcon_TTN_MQTT_Client_Socket.png?raw=true "MQTT Client Socket")

## MQTT Client 
![MQTT Client](imgs/Symcon_TTN_MQTT_Client.png?raw=true "MQTT Client")
* Benutzername: 
Application Name 
* Passwort:
ACCESS KEY

## TTN MQTT Device
Die Einstellungen im TTN MQTT Device sind analog zu dem TTN Object Device.


# Test der Verbindung 

In der TTN-Console kann kann unter dem Device mit "SIMULATE UPLINK" eine Übertragung simuliert werden. Hierfür müssen die Daten hier im HEX-Format eingegeben werden. z.B. "AA BB CC"  
![Simulate Uplink](imgs/Symcon_TTN_Simulate_Uplink.png?raw=true "Simulate Uplink")

Nach dem Senden werden die Daten in der Symcon-Console beim TTN-Device angezeigt. 
![Incomming_Data](imgs/Symcon_TTN_Incomming_Data.png?raw=true "Incomming_Data")

# Feedback
Bitte gebt mir Feedback zu dem Modul. 
Dies betrifft sowohl Bugs als auch Funktionswünsche. 

