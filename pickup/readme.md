# Foodsoft Pickup App

Intended to be installed at https://pickup.foodcoops.at/

requires an additional foodsoft API controller: https://github.com/foodcoopsat/foodsoft/pull/17/

## Activation for Foodcoops
For each foodcoop, a copy of the `template-foodcoop` directory has to be generated and named with the foodcoop's name identically like in the foodsoft url https://app.foodcoops.at/(fc-name). The permissions of the directory have to be `0777` te enable the php-daemon to make directories and write data.
The app can then be called via https://pickup.foodcoops.at/(fc-name)

The index.php file in this folder has to contain the oauth access credentials for the foodsoft of the foodcoop and optional configuration parameters.

## Local Test
The app can be tested in combination with a local foodsoft installation.

Hier vorab ein paar Eigenschaften der App, die überprüft und eventuell angepasst werden sollten, bevor sie in einer Foodcoop eingesetzt wird. 

# Anleitung

## Angezeigte Bestellungen
In der App werden folgende Bestellungen angezeigt:
- **beendete Bestellungen**, unabhängig davon, ob "Lieferung in Empfang nehmen" ausgeführt wurde (Status `received`) oder die Bestellung abgerechnet wurde (Status `closed`). und unabhängig davon, ob sie eine Abholdatum haben, wobei Bestellungen ohne Abholdatum nicht empfehlenswert sind, weil dann nicht klar ist, wann sie abzuholen sind bzw. ab wann Artikel als nicht geliefert eingetragen werden sollen.
- **Lager-Bestellungen**, unabhängig ihres Status (offen, beendet, ...), wobei dort nur Abweichungen eingegeben werden können, wenn die Bestellung geschlossen ist.
- **Offene Bestellungen ohne Abholdatum**: wir verwenden das in unserer Foodcoop nur für die Leergut-Rückgabe-Bestellung.

Bei bereits abgerechneten Bestellungen können keine Änderungen in Stückzahl oder Gewicht mehr eingegeben werden. Bei noch offenen Bestellungen können derzeit ebenfalls keine Änderungen in Stückzahl oder Gewicht eingegeben werden, es wird aber ein Link auf die Foodsoft-Bestellung angezeigt, wo die Bestellung bearbeitet werden kann.

## Gewichtsangaben bei Artikeln
- Gewichtseinheiten sind in g oder kg in der Foodsoft im Artikelfeld **Einheit** hinterlegt, also z.B. `350g`, `70 g`, `1200 g`, `1 kg`, `2,5 kg`, `bis 222 g`,  `Stück < 1,3 kg` wobei kein Leerzeichen zwischen Zahl und Einheit sein muss, Komma oder Punkt als Dezimalzeichen verwendet werden kann und zusätzlicher Text vor der Zahl und hinter der Einheit (durch nicht-Buchstabenzeichen abgetrennt, z.B. `2,5 kg ca.` oder `Stück (ca. 2,5 kg)` aber nicht `2,5 kgs`) möglich sind.
- Der hinterlegte Preis bezieht sich auf das eingegebene Gewicht, also z.B. bei  `Stück < 1,3 kg` und 2,60 € Artikelpreis errechnet die App einen pro-kg-Preis von 2,60/1,3 = 2,00 €/kg
- Artikel, bei denen ein Gewicht auf jeden Fall eingegeben werden soll, haben ein * im Namen, also zum Beispiel `Krautkopf*` mit Einheit  `Stück < 1,3 kg`. Bei diesen Artikeln kann nur das Gewicht (bei mehreren bestellten Einheiten das gesamte oder auch einzeln für jede Einheit) eingegeben werden, nicht die Anzahl der Einheiten.  Wir haben uns in unserer Foodcoop dazu entschlossen, bei solchen Artikel eher eine Obergrenze des Gewichts einzugeben, damit beim nicht-Eintragen der Mitglieder kein finanzieller Nachteil für die Foodcoop entsteht. 
- Artikel, bei denen ein abweichendes Gewicht nicht berücksichtigt werden soll, können durch ein `#`-Zeichen in der Einheit gekennzeichnet werden. Alternativ können auch im Kommentarfeld für eine Produzentin über einen Textbaustein generell für alle Artikel dieser Produzentin Gewichtsänderungen gesperrt werden. Wir verwenden das in unserer Foodcoop zum Beispiel für die Bäckerei, wo die Gewichtsangaben nur Richtwerte zur Orientierung sind, der Preis aber fix pro Stück ist.
- Bei Artikel ohne Gewicht oder mit gesperrtem Gewicht kann nur die Stückzahl verändert werden.

## Lieferantin Eigenschaften
...
