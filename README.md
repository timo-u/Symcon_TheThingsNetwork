# Symcon_TheThingsNetwork
[![StyleCI Status](https://styleci.io/repos/175640288/shield)](https://styleci.io/repos/175640288)

Das Modul verbindet das The Things Network (https://www.thethingsnetwork.org/) mit IP-Symcon (https://www.symcon.de). Hierfür wird die HTTP-Integration genutzt. 
Erstellt und getestet wurde das Modul mit der HTTP Integration (v2.6.0).


## TTN HTTP Integration Gateway
Das Gatway legt einen Webhook an (Standard: /hook/ttn).
Der Name kann im Konfigurationsformular unter "Hook Name" angepasst werden.

Um den Webhook vor fremden Zugriff zu schützen wird eine Autorisation verwendet. Hierfür wird im Feld "Authorization" ein Passwort bzw. Token eingetragen. 
Der hier eingetragene Wert muss natürlich mit dem Wert in der HTTP Integration übereinstimmen. 


## TTN String Device
Durch Eingabe der Application-ID und der Device-ID wird sichergestellt, dass nur Nachrichten empfangen werden, die für das Gerät bestimmt sind. 

Der Payload der eingehenden Nachricht wird in die Variable "Payload" geschrieben. 
Über das Feld "Use HEX format" kann die Ausgabe im HEX Format erfolgen.

## TTN JSON Device
Durch Eingabe der Application-ID und der Device-ID wird sichergestellt, dass nur Nachrichten empfangen werden, die für das Gerät bestimmt sind. 

Eingehende Nachrichten im JSON-Format werden decodiert und in Variablen umgewandelt. Hierbei wird nur die erste Ebende des JSON unterstützt.
Unterstützte Formate sind hierbei String, Boolean, Integer und Double (bzw. Float).

Durch das Feld "Auto Create Variables" werden die nötigen Variablen automatisch erstellt
