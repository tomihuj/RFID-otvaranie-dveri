<!DOCTYPE html>
<html lang="en">
  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Webaplikácia apartmánu</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

  </head>

  <body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark static-top">
      <div class="container">
        <a class="navbar-brand" href="index.php">Webaplikácia apartmánu</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="index.php">Prehľad
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="pridat.php">Pridať kartu</a>
			               <span class="sr-only">(current)</span>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="odobrat.php">Odobrať kartu</a>
            </li>
			<li class="nav-item active">
              <a class="nav-link" href="program.php">Program</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <div class="container">
	 <hr><h2>Wiring</h2><hr>
	 	 <img src="https://i.stack.imgur.com/e1ewN.png" style="display: block; max-width: 100%; height: auto;">
	   <hr><h2>Projekt</h2><hr>
	   <li><a href="https://github.com/esp8266/Arduino/">Návod na inštaláciu NodeMCU do ArduinoIDE</a></li><br>
	   <li><a href="http://www.handsontec.com/pdf_learn/esp8266-V10.pdf">NodeMCU v1.0 (v3, v2) datasheet</a></li><br>
	   <li>Knižnica pre čítačku RC522 je obsiahnutá v repozitári</li><br>
	   <li><a href="https://www.nxp.com/docs/en/data-sheet/MFRC522.pdf">RC522 datasheet</a></li><br>
	   <li><a href="https://github.com/martinius96/RFID-otvaranie-dveri/">Repozitár free verzie projektu pod MIT licenciou</a></li>
	   <hr><h2>Zdrojový kód - HTTPS</h2><hr>
<pre style="background-color:#4cd137;">
/*|----------------------------------------------------------|*/
/*|SKETCH PRE RFID SYSTEM S WEB ADMINISTRACIOU               |*/
/*|VYHOTOVIL: MARTIN CHLEBOVEC                               |*/
/*|EMAIL: martinius96@gmail.com                              |*/
/*|Doska: NodeMCU v3 Lolin (v2 compatible)                   |*/
/*|CORE: 2.3.0                                               |*/
/*|WEB: https://arduino.php5.sk                              |*/
/*|----------------------------------------------------------|*/
#include &lt;ESP8266WiFi.h&gt;
#include &lt;WiFiClientSecure.h&gt;
#include &lt;SPI.h&gt;
#include &lt;RFID.h&gt;
const char * ssid = "MenoWifiSiete";
const char * password = "HesloWifiSiete";
const char * host = "arduino.php5.sk"; //bez https a www
const int httpsPort = 443; //https port
const int rele = 16; //GPIO16 == D0
const char * fingerprint = "‎a6 02 4d e1 32 b0 0b fe 56 85 0f 84 03 ec b2 18 23 09 f0 63"; // odtlacok HTTPS cert
#define SS_PIN 4
#define RST_PIN 5
RFID rfid(SS_PIN, RST_PIN); 
unsigned long kod;
WiFiClientSecure client; //HTTPS client
void setup(){ 
	Serial.begin(9600);
  	SPI.begin(); 
  	rfid.init();
  	pinMode(rele, OUTPUT);
	WiFi.begin(ssid, password);
  	while (WiFi.status() != WL_CONNECTED) {
    		delay(500);
    		Serial.print(".");
  	}
	Serial.println("");
  	Serial.println("WiFi uspesne pripojene");
  	Serial.println("IP adresa: ");
  	Serial.println(WiFi.localIP());
  	Serial.println("Ready");
}

void loop(){
  	if (WiFi.status() != WL_CONNECTED) {
    		WiFi.begin(ssid, password);
  	}
  	while (WiFi.status() != WL_CONNECTED) {
    		delay(500);
    		Serial.print(".");
  	}
  	if (rfid.isCard()) {
    		if (rfid.readCardSerial()) {
      			Serial.println(" ");
      			Serial.println("Card found");
      			kod = 10000*rfid.serNum[4]+1000*rfid.serNum[3]+100*rfid.serNum[2]+10*rfid.serNum[1]+rfid.serNum[0];
      			Serial.println(kod);
      			String kodik = String(kod);
      			client.stop();      
      			if (client.connect(host, httpsPort)) {
        			String url = "/rfid/karta.php?kod="+kodik;
        			client.print(String("GET ") + url + " HTTP/1.0\r\n" + "Host: " + host + "\r\n" + "User-Agent: NodeMCU\r\n" + "Connection: close\r\n\r\n");
      				while (client.connected()) {
        				String line = client.readStringUntil('\n');
        				if (line == "\r") {
          					break;
        				}	
      				}
  				String line = client.readStringUntil('\n');
  				if (line == "OK"){
	 				digitalWrite(rele, LOW); //invertovane spinane rele active LOW
	 				delay(5500);              //cas otvorenia dveri
  				}else if (line == "NO") {
    					digitalWrite(rele,HIGH);
				}
  			}
          }
    	}
	rfid.halt();
}
</pre>
	  <hr><h2>Zdrojový kód - HTTP</h2><hr>
<pre style="background-color:#e84118;">
/*|----------------------------------------------------------|*/
/*|SKETCH PRE RFID SYSTEM S WEB ADMINISTRACIOU               |*/
/*|VYHOTOVIL: MARTIN CHLEBOVEC                               |*/
/*|EMAIL: martinius96@gmail.com                              |*/
/*|Doska: NodeMCU v3 Lolin (v2 compatible)                   |*/
/*|CORE: 2.3.0                                               |*/
/*|WEB: https://arduino.php5.sk                              |*/
/*|----------------------------------------------------------|*/
#include &lt;ESP8266WiFi.h&gt;
#include &lt;SPI.h&gt;
#include &lt;RFID.h&gt;
const char * ssid = "MenoWifiSiete";
const char * password = "HesloWifiSiete";
const char * host = "www.arduino.php5.sk"; 
const int httpPort = 80; //http port
const int rele = 16; //GPIO16 == D0
#define SS_PIN 4
#define RST_PIN 5
RFID rfid(SS_PIN, RST_PIN); 
unsigned long kod;
WiFiClient client;
void setup(){ 
    Serial.begin(9600);
    SPI.begin(); 
    rfid.init();
    pinMode(rele, OUTPUT);
  WiFi.begin(ssid, password);
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("");
    Serial.println("WiFi uspesne pripojene");
    Serial.println("IP adresa: ");
    Serial.println(WiFi.localIP());
    Serial.println("Ready");
}

void loop(){
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.begin(ssid, password);
    }
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    if (rfid.isCard()) {
        if (rfid.readCardSerial()) {
            Serial.println(" ");
            Serial.println("Card found");
            kod = 10000*rfid.serNum[4]+1000*rfid.serNum[3]+100*rfid.serNum[2]+10*rfid.serNum[1]+rfid.serNum[0];
            Serial.println(kod);
            String kodik = String(kod);
            client.stop();
            if (client.connect(host, httpPort)) {
              String url = "/rfid/karta.php?kod="+kodik;
              client.print(String("GET ") + url + " HTTP/1.0\r\n" + "Host: " + host + "\r\n" + "User-Agent: NodeMCU\r\n" + "Connection: close\r\n\r\n");
              while (client.connected()) {
                String line = client.readStringUntil('\n');
                if (line == "\r") {
                    break;
                }
              }
          String line = client.readStringUntil('\n');
          if (line == "OK"){
          digitalWrite(rele, LOW); //invertovane spinane rele active LOW
          delay(5500);              //cas otvorenia dveri
            }else if (line == "NO") {
                digitalWrite(rele,HIGH);
          }
        }
            }
      }

    rfid.halt();
}
	</pre>
    
      </div>
    </div>

    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  </body>

</html>
