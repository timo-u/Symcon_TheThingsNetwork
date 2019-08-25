### TTN JSON device

Nimmt den Payload der Nachticht als JSON entgegen und legt automatisch die Variablen an. 
Mit aktviertem "Get Content From Raw-Payload" werden die Daten aus dem Paylod(HEX) in einen String umgewaldelt und als JSON ausgelesen. 
Ist "Get Content From Raw-Payload" deaktiviert werden die Daten direkt aus "payload_fields" entnommen. Hierfür müssen die Daten beireits bei TTN decodiert werden.