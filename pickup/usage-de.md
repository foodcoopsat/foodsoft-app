# Abhol-App (pickup)

Die Abholapp ist eine Smartphone-taugliche App für die Abholung der Bestellungen in Foodcoops, die mit der Foodsoft gekoppelt ist. Sie erspart das Ausdrucken der **Bestelllisten** (Gruppen-PDF) indem die Bestellungen am eigenen Smartphone oder auf einem fix im Lagerraum installieren Smartphone oder Tablet angezeigt, und die einzelnen Artikel beim Abholen abgehakt werden können. Weiters ermöglicht die App es, **Abweichungen** 
- bei Stückzahl (insbesondere auch nicht gelieferte Artikel) und 
- bei Gewicht (z.B. Krautkopf Stück bestellt mit variablem Gewicht, tatsächliche Preis pro geliefertem Gewicht)

einzugeben und in die Foodsoft zu übertragen, damit diese Abweichungen bei der Abrechnung berücksichtigt werden. 

Dieses Video stammt aus der Anfangszeit von der Einführung in unserer Foodcoop, aber im Wesentlichen beschreibt es immer noch die Anwendung der App:
https://youtu.be/r9Pfzuuu6Ko

Die App ist in unserer  Foodcoop seit mittlerweile 4 Jahren erfolgreich im Einsatz (Stand 2026,) mit dieser Version soll die App auch für andere FoodCoops zur Verfügung gestellt werden.

## Voraussetzungen 
- Foodsoft wird verwendet zum Bestellen und 
- Abrechnung erfolgt über die Foodsoft: Mitglieder laden in der Foodsoft ihr Bestellguthaben auf, Bestellungen werden über Foodsoft abgerechnet, indem vom Guthaben der Mitglieder abgebucht wird. Bei einer manuellen Abrechnung über Papierlisten macht die App wenig Sinn.

Es muss dazu nichts installiert werden, die App läuft am IG Server (anders als im Video, wo die App noch am Webserver unserer Foodcoop läuft). 


## Angezeigte Bestellungen
In der App werden folgende Bestellungen angezeigt:
- **beendete Bestellungen**, unabhängig davon, ob "Lieferung in Empfang nehmen" ausgeführt wurde (Status `received`) oder die Bestellung abgerechnet wurde (Status `closed`). und unabhängig davon, ob sie eine Abholdatum haben, wobei Bestellungen ohne Abholdatum nicht empfehlenswert sind, weil dann nicht klar ist, wann sie abzuholen sind bzw. ab wann Artikel als nicht geliefert eingetragen werden sollen.
- **Lager-Bestellungen**, unabhängig ihres Status (offen, beendet, ...), wobei dort nur Abweichungen eingegeben werden können, wenn die Bestellung geschlossen ist.
- **Offene Bestellungen ohne Abholdatum**: wir verwenden das in unserer Foodcoop nur für die Leergut-Rückgabe-Bestellung.

Bei bereits abgerechneten Bestellungen können keine Änderungen in Stückzahl oder Gewicht mehr eingegeben werden. Bei noch offenen Bestellungen können derzeit ebenfalls keine Änderungen in Stückzahl oder Gewicht eingegeben werden, es wird aber ein Link auf die Foodsoft-Bestellung angezeigt, wo die Bestellung bearbeitet werden kann.

## Gewichtsangaben bei Artikeln
- Gewichtseinheiten sind in g oder kg in der Foodsoft im Artikelfeld **Einheit** hinterlegt, also z.B. `350g`, `70 g`, `1200 g`, `1 kg`, `2,5 kg`, `bis 222 g`,  `Stück < 1,3 kg` wobei kein Leerzeichen zwischen Zahl und Einheit sein muss, Komma oder Punkt als Dezimalzeichen verwendet werden kann und zusätzlicher Text vor der Zahl und hinter der Einheit (durch nicht-Buchstabenzeichen abgetrennt, z.B. `2,5 kg ca.` oder `Stück (ca. 2,5 kg)` aber nicht `2,5 kgs`) möglich sind.
- Der hinterlegte Preis bezieht sich auf das eingegebene Gewicht, also z.B. bei  `Stück < 1,3 kg` und 2,60 € Artikelpreis errechnet die App einen pro-kg-Preis von 2,60/1,3 = 2,00 €/kg
- Artikel, bei denen ein Gewicht auf jeden Fall eingegeben werden soll, haben ein \* im Namen, also zum Beispiel *Krautkopf\** mit Einheit  `Stück < 1,3 kg`. Bei diesen Artikeln kann nur das Gewicht (bei mehreren bestellten Einheiten das gesamte oder auch einzeln für jede Einheit) eingegeben werden, nicht die Anzahl der Einheiten.  Wir haben uns in unserer Foodcoop dazu entschlossen, bei solchen Artikel eher eine Obergrenze des Gewichts einzugeben, damit beim nicht-Eintragen der Mitglieder kein finanzieller Nachteil für die Foodcoop entsteht. 
- Artikel, bei denen ein abweichendes Gewicht nicht berücksichtigt werden soll, können durch ein `#`-Zeichen in der Einheit gekennzeichnet werden. Alternativ können auch im Kommentarfeld für eine Produzentin über einen Textbaustein generell für alle Artikel dieser Produzentin Gewichtsänderungen gesperrt werden. Wir verwenden das in unserer Foodcoop zum Beispiel für die Bäckerei, wo die Gewichtsangaben nur Richtwerte zur Orientierung sind, der Preis aber fix pro Stück ist.
- Bei Artikel ohne Gewicht oder mit gesperrtem Gewicht kann nur die Stückzahl verändert werden.

## Lieferantin Eigenschaften
Am Ende des Notiz Feldes einer Lieferantin in der Foodsoft können Einstellungen für diese eingegeben werden (JSON Format): 

`@pickup:{"setting1":value1, ...}`

Eigenschaften: 
- `"ignore_weight":true` wenn für alle Artikel der Lieferantin das Gewicht nicht anpassbar sein sollen

Beispiel: 

`@pickup:{"ignore_weight":true}`



## Globale Eigenschaften 

`$config` Array im `index.php` der jeweiligen Foodcoop:
- `n_weeks`: Anzahl der Wochen von jetzt an zurück, für die Bestellungen angezeigt werden sollen. Standardwert: 5
- `exclude_usernames`: Array von Strings von Benutzernamen die nicht zur Auswahl angezeigt werden sollen
- `inactive_user`: String, wenn er in Benutzername vorkommt, wird der Benutzer nicht tritt Auswahl angezeigt, Standardwert: "ZZ"
- `variable_weight`: String, der in Artikelnamen vorkommt, wenn das Gewicht variabel ist. Standard Wert: `"*"`
- `locked_weight`: Array von Strings, die in der Einheit eines Artikels angeben, dass das Gewicht nicht anpassbar ist. Standardwert:      `["#", "Glas"]`

Optionen für Entwicklung: 
- `debug`: Standardwert false;
- `comment_level`:
  - 0: save no order comments,
  - 1: only article notes,
  - 2: for all changes,
  - Standardwert 1
- `use_local_foodsoft`: Standardwert false

# Einkistln App (distribute)
Die Einkistln App ist zum Aufteilen der Bestellungen auf die Foodcoop Mitglieder im Lagerraum auf Tablets oder Smartphones. Bestelllisten müssen nicht mehr ausgedruckt werden. Abweichungen der Lieferung von der Bestellung in Stück oder Gewicht können eingegebenen und in die Foodsoft übertragen werden. Es können mehrere Geräte gleichzeitig verwendet werden, alle Eingaben werden zwischen den Geräten synchronisiert.
