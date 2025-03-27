#include <EthernetENC.h>
#include <U8g2lib.h>
#include <TimeLib.h>
#include <MFRC522.h>
#include <Ethernet.h>
#include <EthernetUdp.h>
#include <LittleFS.h>
#include <SSLClient.h>
#include <CSV_Parser.h>
#include <map>

#include "trust_anchors.h"
const int rand_pin = 12;
const char* server = "portal.metalesmakine.com";
EthernetClient base_client;
SSLClient client(base_client, TAs, (size_t)TAs_NUM, rand_pin);

// Pin Tanımlamaları
#define RST_PIN 4      // RFID Reset Pini
#define SS_PIN 5       // RFID SPI SS Pini
#define RED_PIN 0     // RGB LED Kırmızı
#define GREEN_PIN 2    // RGB LED Yeşil
#define BLUE_PIN 15     // RGB LED Mavi
#define ENC28J60_CS 17 // ENC28J60 CS Pin
#define BUZZER_PIN 25  // Buzzer Pin

// OLED Ekran Ayarları
U8G2_SH1106_128X64_NONAME_F_SW_I2C u8g2(U8G2_R0, 22, 21, U8X8_PIN_NONE);

// Ağ Ayarları
byte mac[] = {0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED};
/*
IPAddress ip(192, 168, 1, 200);
IPAddress gateway(192, 168, 1, 1);
IPAddress subnet(255, 255, 255, 0);
IPAddress dnsServer(8, 8, 8, 8);
*/

// NTP Ayarları
const char* ntpServer = "1.tr.pool.ntp.org";
const int timeZone = 3;
EthernetUDP Udp;
unsigned int localPort = 123;

// SPIFFS Ayarları
#define MAX_ENTRIES 50
#define ENTRY_SIZE 30

// Nesneler
MFRC522 mfrc522(SS_PIN, RST_PIN);

// Fonksiyon Prototipleri
void displayMessage(const char* msg);
void displayError(const char* err);
void displayTime();
void displayNetworkInfo();
time_t getNtpTime();
void saveToSPIFFS(const char* cardId, const char* timeStr);
void clearSPIFFS();
void sendPendingCardsInBackground();
void drawEthernetSymbol(bool connected);
void beep(int duration);
void blinkLEDRed(int duration);
void blinkLEDGreen(int duration);
void blinkLEDBlue(int duration);
void sendIPAddressToServer();
void downloadCSVFile();
bool checkForUpdate();
void updateServerValueToZero();

unsigned long redLedStartTime = 0;
unsigned long greenLedStartTime = 0;
unsigned long blueLedStartTime = 0;
unsigned long buzzerStartTime = 0;
bool redLedOn = false;
bool greenLedOn = false;
bool blueLedOn = false;
bool buzzerOn = false;
int buzzerDuration = 0;
int redLedDuration = 0;
int greenLedDuration = 0;
int blueLedDuration = 0;

unsigned long messageStartTime = 0;
bool messageDisplayed = false;
const int messageDuration = 1000;

unsigned long errorStartTime = 0;
bool errorDisplayed = false;
const int errorDuration = 500;

unsigned long lastCardScanTime = 0;
int cardScanCount = 0;

unsigned long lastDownloadTime = 0;
const unsigned long downloadInterval = 24 * 60 * 60 * 1000; // 24 hours in milliseconds




void setup() {
  Serial.begin(115200);
  Serial.println("Setup basladi");

  // Pin Modları
  pinMode(RED_PIN, OUTPUT);
  pinMode(GREEN_PIN, OUTPUT);
  pinMode(BLUE_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  Serial.println("Pin modlari ayarlandi");

  digitalWrite(RED_PIN, HIGH);

  // OLED Başlatma
  u8g2.begin();
  u8g2.setFont(u8g2_font_ncenB08_tr);
  displayMessage("SISTEM BASLIYOR");
  Serial.println("OLED baslatildi");
  
  // RFID Başlatma
  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("RFID baslatildi");

  // LittleFS Başlatma
  if (!LittleFS.begin()) {
    Serial.println("LittleFS baslatilamadi, formatlaniyor...");
    LittleFS.format();
    if (!LittleFS.begin()) {
      displayError("LittleFS Basarisiz");
      Serial.println("LittleFS baslatilamadi");
      while (true) {
        delay(1); // Sonsuz döngüde kal
      }
    }
  }
  Serial.println("LittleFS baslatildi");

  // Ethernet Bağlantısı
  Ethernet.init(ENC28J60_CS);
  while (Ethernet.linkStatus() == LinkOFF) {
    displayMessage("KABLO BEKLENIYOR");
    Serial.println("Ethernet kablosu bekleniyor...");
    delay(1000); // Bekleme süresi ekleyin
  }
  
  Serial.println("Ethernet kablosu baglandi, DHCP baslatiliyor...");
  if (Ethernet.begin(mac /*, ip, dnsServer, gateway, subnet */) == 0) {
    displayError("DHCP BASARISIZ");
    Serial.println("DHCP basarisiz");
    while (true) {
      delay(1); // Sonsuz döngüde kal
    }
  }
  Serial.println("Ethernet baglantisi basarili");
  Serial.print("IP Adresi: ");
  Serial.println(Ethernet.localIP());
  
  Udp.begin(localPort);
  Serial.println("UDP baslatildi");

  downloadCSVFile();

  //displayNetworkInfo();
  sendIPAddressToServer();
  Serial.println("IP adresi sunucuya gonderildi");

  sendPendingCardsInBackground();
  Serial.println("Bekleyen kartlar gonderildi");
  
  // Zaman Senkronizasyonu
  setSyncProvider(getNtpTime);
  setSyncInterval(3600);
  Serial.println("Zaman senkronizasyonu ayarlandi");

  drawEthernetSymbol(Ethernet.linkStatus() == LinkON);
  Serial.println("Ethernet sembolu cizildi");

  digitalWrite(RED_PIN, LOW);
  Serial.println("Setup tamamlandi");
}
void loop() {
  static unsigned long lastUpdate = 0;
  static bool lastConnectionStatus = true;
  static unsigned long lastRetryTime = 0;
  static bool retryPending = false;

  if (messageDisplayed && millis() - messageStartTime >= messageDuration) {
    messageDisplayed = false;
  }

  if (errorDisplayed && millis() - errorStartTime >= errorDuration) {
    errorDisplayed = false;
  }

  if (redLedOn && millis() - redLedStartTime >= redLedDuration) {
    digitalWrite(RED_PIN, LOW);
    redLedOn = false;
  }

  if (greenLedOn && millis() - greenLedStartTime >= greenLedDuration) {
    digitalWrite(GREEN_PIN, LOW);
    greenLedOn = false;
  }

  if (blueLedOn && millis() - blueLedStartTime >= blueLedDuration) {
    digitalWrite(BLUE_PIN, LOW);
    blueLedOn = false;
  }

  // Buzzer'ı kontrol et
  if (buzzerOn && millis() - buzzerStartTime >= buzzerDuration) {
    digitalWrite(BUZZER_PIN, LOW);
    buzzerOn = false;
  }

  // Zaman Güncelleme
  if (millis() - lastUpdate >= 1000) {
    lastUpdate = millis();
    displayTime();
  }
  

  // Bağlantı Durumu Kontrolü
  bool currentStatus = (Ethernet.linkStatus() == LinkON);
  if (currentStatus != lastConnectionStatus) {
    if (currentStatus) {
      displayMessage("BAGLANTI SAGLANDI");
      sendPendingCardsInBackground();
    } else {
      displayError("BAGLANTI KESILDI");
      u8g2.clearBuffer();
    }
    drawEthernetSymbol(currentStatus);
    lastConnectionStatus = currentStatus;
  }

  // Sunucuya tekrar bağlanmayı dene
  if (retryPending && millis() - lastRetryTime >= 60000) { // 1 dakika sonra tekrar dene
    retryPending = false;
    sendPendingCardsInBackground();
  }
  
  // RFID Okuma
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    char cardId[20] = "";
    //timestr tanimlama
    char timeStr[20];
    sprintf(timeStr, "%04d-%02d-%02d %02d:%02d:%02d", year(), month(), day(), hour(), minute(), second());
    for (byte i = 0; i < mfrc522.uid.size; i++) {
      sprintf(cardId + (i * 2), "%02X", mfrc522.uid.uidByte[i]);
    }
    
    blinkLEDBlue(200);

    // Ekranın üst kısmındaki 10 satırı koruyarak geri kalan kısmı temizle
    u8g2.setDrawColor(0);
    u8g2.drawBox(0, 20, 128, 54);
    u8g2.setDrawColor(1);
    u8g2.sendBuffer();

    //displayMessage(cardId); // Kartın UID'sini OLED ekranda göster
    Serial.println(cardId);
    
    if (strlen(cardId) > 0) {
      if (Ethernet.linkStatus() == LinkON) {
        saveToSPIFFS(cardId, timeStr);
      } else {
        saveToSPIFFS(cardId, timeStr);
      }
      mfrc522.PICC_HaltA();
      lastCardScanTime = millis();
    }
  }

  if (millis() - lastCardScanTime > 30000 && cardScanCount > 0) {
    sendPendingCardsInBackground();
    lastCardScanTime = millis();
  }

  // Check if it's time to download the CSV file again
  if (millis() - lastDownloadTime >= downloadInterval) {
    downloadCSVFile();
    lastDownloadTime = millis();
  }

}

String getNameByCardId(const char* cardId) {
  File csvFile = LittleFS.open("/data.csv", FILE_READ);
  if (!csvFile) {
    displayError("CSV ACILAMADI");
    return "";
  }

  while (csvFile.available()) {
    String line = csvFile.readStringUntil('\n');
    line.trim(); // Remove any trailing whitespace or newline characters

    int commaIndex = line.indexOf(',');
    if (commaIndex != -1) {
      String name = line.substring(1, commaIndex - 1); // Remove quotes
      String id = line.substring(commaIndex + 2, line.length() - 1); // Remove quotes
      id.trim(); // Remove any trailing whitespace or newline characters

      if (id.equalsIgnoreCase(cardId)) {
        csvFile.close();
        return name;
      }
    }
  }
  csvFile.close();
  return "";
}

void sendIPAddressToServer() {
  IPAddress ip = Ethernet.localIP();
  char ipStr[16];
  sprintf(ipStr, "%d.%d.%d.%d", ip[0], ip[1], ip[2], ip[3]);

  if (client.connect(server, 443)) {
    client.println("POST /api/get_esp.php HTTP/1.1");
    client.println("Host: " + String(server));
    client.println("Content-Type: application/x-www-form-urlencoded");
    client.println("Connection: close");
    client.print("Content-Length: ");
    client.println(String("ip=" + String(ipStr)).length());
    client.println();
    client.println("ip=" + String(ipStr));

    unsigned long timeout = millis();
    while (client.connected() && millis() - timeout < 10000) {
      if (client.available()) {
        String line = client.readStringUntil('\n');
        if (line == "\r") {
          String response = client.readStringUntil('\n');
          response.trim();
          break;
        }
      }
    }
    client.stop();
  } else {
    displayError("IPERR");
    blinkLEDRed(1000);
    beep(1000);
  }
}

// Kart ID'si ve son basılma zamanını saklamak için bir harita (map) kullanın
std::map<String, unsigned long> cardLastScanTime;

void saveToSPIFFS(const char* cardId, const char* timeStr) {
  String cardIdStr = String(cardId);
  unsigned long currentTime = millis();

  // Kartın son basılma zamanını kontrol edin
  if (cardLastScanTime.find(cardIdStr) != cardLastScanTime.end()) {
    unsigned long lastScanTime = cardLastScanTime[cardIdStr];
    if (currentTime - lastScanTime < 300000) { // 1 dakika (60,000 milisaniye)
      displayMessage("5 DAKIKA GECMEDI");
      return;
    }
  }

  // Kartı kaydedin
  File file = LittleFS.open("/data.txt", FILE_APPEND);
  if (!file) {
    displayError("DOSYA ACILAMADI");
    return;
  }

  file.print(cardId);
  file.print(",");
  file.println(timeStr);
  file.close();

  // Kartın son basılma zamanını güncelleyin
  cardLastScanTime[cardIdStr] = currentTime;

  String name = getNameByCardId(cardId);
  if (name.length() > 0) {
    digitalWrite(BLUE_PIN, LOW);
    beep(300);
    blinkLEDGreen(500);
    displayMessage(name.c_str());
  } else {
    displayMessage("KART KAYDEDILDI");
  }

  // Increment the card scan count
  cardScanCount++;
}

void clearSPIFFS() {
  // Dosyanın içeriğini temizlemek için dosyayı yazma modunda açın ve kapatın
  File file = LittleFS.open("/data.txt", FILE_WRITE);
  if (!file) {
    displayError("DOSYA ACILAMADI");
    Serial.println("Error: Unable to open file for clearing.");
    return;
  }
  file.close();

  cardScanCount = 0;
  Serial.println("Dosya iceriği temizlendi.");
}


// Görüntüleme Fonksiyonları
void displayMessage(const char* msg) {
  //u8g2.clearBuffer();
  // Ekranın üst kısmındaki 10 satırı koruyarak geri kalan kısmı temizle
  u8g2.setDrawColor(0);
  u8g2.drawBox(0, 20, 128, 54);
  u8g2.setDrawColor(1);

  int strWidth = u8g2.getStrWidth(msg);
  int x = (128 - strWidth) / 2;
  int y = 40;
  u8g2.drawStr(x, y, msg);
  u8g2.sendBuffer();

  messageStartTime = millis();
  messageDisplayed = true;
}

void displayError(const char* err) {
  // Ekranın üst kısmındaki 10 satırı koruyarak geri kalan kısmı temizle
  u8g2.setDrawColor(0);
  u8g2.drawBox(0, 20, 128, 54);
  u8g2.setDrawColor(1);
  
  int strWidth = u8g2.getStrWidth(err);
  int x = (128 - strWidth) / 2;
  int y = 40;
  u8g2.drawStr(x, y, err);
  u8g2.sendBuffer();
  
  errorStartTime = millis();
  errorDisplayed = true;
}

void displayNetworkInfo() {
  IPAddress ip = Ethernet.localIP();
  char ipStr[16];
  sprintf(ipStr, "%d.%d.%d.%d", ip[0], ip[1], ip[2], ip[3]);

  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_ncenB08_tr);
  int strWidth = u8g2.getStrWidth(ipStr);
  int x = (128 - strWidth) / 2;
  int y = 32;
  u8g2.drawStr(x, y, ipStr);
  u8g2.sendBuffer();
}

void displayTime() {
  char timeStr[20];
  sprintf(timeStr, "%02d:%02d:%02d", hour(), minute(), second());
  char dateStr[20];
  sprintf(dateStr, "%02d/%02d/%04d", day(), month(), year());

  // Ekranın üst kısmındaki 10 satırı koruyarak geri kalan kısmı temizle
  u8g2.setDrawColor(0);
  u8g2.drawBox(0, 20, 128, 54);
  u8g2.setDrawColor(1);


  u8g2.setFont(u8g2_font_logisoso16_tn);
  int timeWidth = u8g2.getStrWidth(timeStr);
  int dateWidth = u8g2.getStrWidth(dateStr);
  int timeX = (128 - timeWidth) / 2;
  int dateX = (128 - dateWidth) / 2;
  u8g2.drawStr(timeX, 45, timeStr);
  u8g2.setFont(u8g2_font_ncenB08_tr);
  u8g2.drawStr(35, 60, dateStr);
  u8g2.sendBuffer();
}

void drawEthernetSymbol(bool connected) {
  u8g2.setDrawColor(1);
  if (connected) {
    u8g2.clearBuffer();
    // WiFi sembolü çiz
    u8g2.drawCircle(120, 10, 5); // Dış daire
    u8g2.drawCircle(120, 10, 3); // İç daire
    u8g2.drawDisc(120, 10, 1);   // Merkez nokta
  } else {
    u8g2.clearBuffer();
    // WiFi sembolü ve üstüne çizgi çiz
    u8g2.drawCircle(120, 10, 5); // Dış daire
    u8g2.drawDisc(120, 10, 1);   // Merkez nokta
    u8g2.drawLine(115, 5, 125, 15); // Üstüne çizgi
  }
  u8g2.sendBuffer();
}

void blinkLEDRed(int duration) {
  digitalWrite(RED_PIN, HIGH);
  redLedStartTime = millis();
  redLedOn = true;
  redLedDuration = duration;
}

void blinkLEDGreen(int duration) {
  digitalWrite(GREEN_PIN, HIGH);
  greenLedStartTime = millis();
  greenLedOn = true;
  greenLedDuration = duration;
}

void blinkLEDBlue(int duration) {
  digitalWrite(BLUE_PIN, HIGH);
  blueLedStartTime = millis();
  blueLedOn = true;
  blueLedDuration = duration;
}

void beep(int duration) {
  digitalWrite(BUZZER_PIN, HIGH);
  buzzerStartTime = millis();
  buzzerOn = true;
  buzzerDuration = duration;
}

// NTP Zaman Sorgulama
time_t getNtpTime() {
  byte packetBuffer[48];
  memset(packetBuffer, 0, 48);
  packetBuffer[0] = 0b11100011;

  if (!Udp.beginPacket(ntpServer, 123)) return 0;
  Udp.write(packetBuffer, 48);
  Udp.endPacket();

  uint32_t beginWait = millis();
  while (millis() - beginWait < 1500) {
    if (Udp.parsePacket() >= 48) {
      Udp.read(packetBuffer, 48);
      unsigned long secsSince1900 = (unsigned long)packetBuffer[40] << 24 | 
                                   packetBuffer[41] << 16 | 
                                   packetBuffer[42] << 8 | 
                                   packetBuffer[43];
      return secsSince1900 - 2208988800UL + timeZone * 3600;
    }
  }
  return 0;
}

void sendPendingCardsInBackground() {
  File file = LittleFS.open("/data.txt", FILE_READ);
  if (!file) {
    displayError("DOSYA ACILAMADI");
    return;
  }
  digitalWrite(RED_PIN, HIGH);

  String payload = "[";  // JSON formatında veri hazırlamak için başlangıç

  while (file.available()) {
    String line = file.readStringUntil('\n');
    line.trim();
    if (line.length() > 0) {
      int commaIndex = line.indexOf(',');
      String cardId = line.substring(0, commaIndex);
      String timeStr = line.substring(commaIndex + 1);

      // JSON formatında veriyi ekle
      payload += "{\"card_id\":\"" + cardId + "\",\"time\":\"" + timeStr + "\"}";
      if (file.available()) {
        payload += ",";
      }
    }
  }

  payload += "]";  // JSON formatında veri hazırlamak için bitiş
  file.close();

  byte mac[6];
  Ethernet.MACAddress(mac);
  char macStr[18];
  sprintf(macStr, "%02X:%02X:%02X:%02X:%02X:%02X", mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);

  const char* updateUrl = "/api/rfid_handler.php"; // Sunucudaki PHP dosyasının yolu

  if (client.connect(server, 443)) {
    client.println("POST " + String(updateUrl) + " HTTP/1.1");
    client.println("Host: " + String(server));
    client.println("Content-Type: application/json");
    client.print("MAC-Address: ");
    client.println(macStr);
    client.println("Connection: close");
    client.print("Content-Length: ");
    client.println(payload.length());
    client.println();
    client.println(payload);

    unsigned long timeout = millis();
    while (client.connected() && millis() - timeout < 10000) {
      if (client.available()) {
        String line = client.readStringUntil('\n');
        if (line == "\r") {
          String response = client.readStringUntil('\n');
          response.trim();
          displayMessage(response.c_str());
          break;
        }
      }
    }
    client.stop();

    clearSPIFFS();
    blinkLEDGreen(500);
    beep(500);
    displayMessage("GONDERILDI");
    digitalWrite(RED_PIN, LOW);
  } else {
    displayError("BAGLANAMADI");
    digitalWrite(RED_PIN, LOW);
    blinkLEDRed(1000);
    beep(1000);
  }
}

bool checkForUpdate() {
  const char* updateCheckUrl = "/api/check_mesbil_update.php";

  if (client.connect(server, 443)) { // HTTPS bağlantısı
    client.print("GET ");
    client.print(updateCheckUrl);
    client.println(" HTTP/1.1");
    client.print("Host: ");
    client.println(server);
    client.println("Connection: close");
    client.println();

    unsigned long timeout = millis();
    while (client.connected() && millis() - timeout < 10000) {
      if (client.available()) {
        String line = client.readStringUntil('\n');
        if (line.startsWith("{")) { // JSON yanıtı kontrol et
          int valueIndex = line.indexOf("\"value\":");
          if (valueIndex != -1) {
            int value = line.substring(valueIndex + 8).toInt();
            client.stop();
            return value == 1; // 1 ise güncelleme yapılacak
          }
        }
      }
    }
    client.stop();
  } else {
    Serial.println("Error: Unable to connect to update server");
  }
  return false; // Varsayılan olarak güncelleme yapılmasın
}

void updateServerValueToZero() {
  const char* updateUrl = "/api/update_mesbil_value.php"; // Sunucudaki PHP dosyasının yolu

  if (client.connect(server, 443)) {
    String postData = "type=mesbil_data&value=0"; // Gönderilecek veri

    client.println("POST " + String(updateUrl) + " HTTP/1.1");
    client.println("Host: " + String(server));
    client.println("Content-Type: application/x-www-form-urlencoded");
    client.println("Connection: close");
    client.print("Content-Length: ");
    client.println(postData.length());
    client.println();
    client.println(postData);

    unsigned long timeout = millis();
    while (client.connected() && millis() - timeout < 10000) {
      if (client.available()) {
        String line = client.readStringUntil('\n');
        Serial.println(line); // Sunucudan gelen yanıtı yazdır
      }
    }
    client.stop();
    Serial.println("Server value updated to 0.");
  } else {
    Serial.println("Error: Unable to connect to server to update value.");
  }
}

void downloadCSVFile() {
  if (!checkForUpdate()) {
    Serial.println("No update required. Skipping CSV download.");
    return; // Güncelleme gerekmediği için çık
  }

  const char* url = "/uploads/data.csv";

  // Delete the old data.csv file if it exists
  if (LittleFS.exists("/data.csv")) {
    LittleFS.remove("/data.csv");
  }

  int retryCount = 0; // Deneme sayacı
  const int maxRetries = 5; // Maksimum deneme sayısı

  while (retryCount < maxRetries) { // Maksimum 5 kez dene
    if (client.connect(server, 443)) {
      client.print("GET ");
      client.print(url);
      client.println(" HTTP/1.1");
      client.print("Host: ");
      client.println(server);
      client.println("Connection: close");
      client.println();

      unsigned long timeout = millis();
      bool headersEnded = false;

      File file = LittleFS.open("/data.csv", FILE_WRITE);
      if (!file) {
        displayError("CSV KAYDEDILEMEDI");
        Serial.println("Error: Unable to open file for writing.");
        return;
      }

      while (client.connected() && millis() - timeout < 10000) {
        if (client.available()) {
          String line = client.readStringUntil('\n');
          if (!headersEnded) {
            if (line == "\r") {
              headersEnded = true;
            }
          } else {
            file.println(line);
          }
        }
      }

      file.close();
      displayMessage("CSV GUNCELLENDI");
      Serial.println("CSV file updated successfully.");
      client.stop();

      // CSV indirildikten sonra sunucuda değeri 0 yap
      updateServerValueToZero();
      return; // Başarılı olursa fonksiyondan çık
    } else {
      displayError("HTTP GET failed");
      Serial.println("Error: HTTP GET failed");
      client.stop();
      retryCount++; // Deneme sayısını artır
      delay(5000); // 5 saniye bekle ve tekrar dene
    }
  }

  // Maksimum deneme sayısına ulaşıldıysa hata mesajı göster
  displayError("CSV INDIRILEMEDI");
  Serial.println("Error: Maximum retries reached. CSV download failed.");
}