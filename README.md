# Symcon_TheThingsNetwork
[![StyleCI Status](https://styleci.io/repos/175640288/shield)](https://styleci.io/repos/175640288)

Das Modul verbindet das The Things Network (https://www.thethingsnetwork.org/) mit IP-Symcon (https://www.symcon.de). Hierfür wird die HTTP-Integration genutzt. 
Erstellt und getestet wurde das Modul mit der HTTP Integration (v2.6.0).


## TTN HTTP Integration Gateway
Das Gatway legt einen Webhook an (Standard: /hook/ttn).
Der Name kann im Konfigurationsformular unter "Hook Name" angepasst werden.

Um den Webhook vor fremden Zugriff zu schützen wird eine Autorisation verwendet. Hierfür wird im Feld "Authorization" ein Passwort bzw. Token eingetragen. 
Der hier eingetragene Wert muss natürlich mit dem Wert in der HTTP Integration übereinstimmen. 


## Generelle Informationen zu den Devices
### Konfigurationsformular
Durch Eingabe der Application-ID und der Device-ID wird sichergestellt, dass nur Nachrichten empfangen werden, die für das Gerät bestimmt sind. 

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



## TTN Object Device
Diese Instanz eignet sich für Nachrichten die ein Valides JSON als Payload verweden oder wenn die Decodiereung bereits im TTN duchgeführt wird.
Die Decodierung kann in der TTN Console in der Application unter PAYLOAD FORMATS => Decoder eingestellt werden. 
Unterstützte Formate sind hierbei String, Boolean, Integer und Double (bzw. Float).

Durch das Feld "Auto Create Variables" werden die nötigen Variablen automatisch erstellt
