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
Legt eine Variable "RSSI" aus und gibt den höchsten RSSI-Wert aus.

#### Show Gateway Count
Legt eine Variable "Gateway Count" an und gibt die Anzahl der Gateways aus, die die Nachticht empfangen haben aus.



## TTN Device
Der Payload der eingehenden Nachricht wird in die Variable "Payload" geschrieben. 
Durch das Feld "Type" kann der Datentyp ausgewählt werden, der verwendet werden soll. 
Zur Auswahl hierfür steht: 
- String 
- HEX (Daten werden Hexadezimal in eine String-Variable geschrieben)
- Boolean
- Integer
- Float

## TTN JSON Device
Eingehende Nachrichten im JSON-Format werden decodiert und in Variablen umgewandelt. Hierbei wird nur die erste Ebene des JSON unterstützt.
Unterstützte Formate sind hierbei String, Boolean, Integer und Double (bzw. Float).

Durch das Feld "Auto Create Variables" werden die nötigen Variablen automatisch erstellt

## TTN String Device
Die Funktion wird durch das TTN Device abgedeckt. Diese Instanz wird zukünfrig entfernt. 
