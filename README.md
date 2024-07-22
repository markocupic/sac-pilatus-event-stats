![Alt text](docs/logo.png?raw=true "logo")

# Welcome to SAC Pilatus Event Statistics

This bundle is still under construction.

Im Backend eine **Event-, Leiter- und Teilnehmer-Statistik als Übersicht** einführen.

- Z.B. als neue Seite (evtl. Modul oder notfalls auf Startseite) `SAC Event-Statistik` im Contao.
- Inkl. neuer Benutzergruppe `Statistik`, welche z.B. dem ganzen Vorstand gegeben werden kann.

Die **Darstellung** soll als Text vom jeweiligen ganzen Jahr (Startdatum des Events) mit folgenden drei Zahlen als Vergleichs-Ansicht dargestellt werden:

- `aktuelles Jahr | letztes Jahr | vorletztes Jahr`
- `2023 | 2022 | 2021`
- `Total Touren:   135 | 125 | 115`

Folgende **Statistikzahlen** sollen aus den verschiedenen Datenbanken aufbereitet werden:

---

Ausgeschriebene Touren (ab FS3):

- Total Touren
- Anzahl Touren ohne Bergführer/in
- Anzahl Touren mit Bergführer/in
- Anzahl Veranstaltungen (inkl. Trainings)

Ausgeschriebene Kurse (ab FS3):

- Total Kurse
- Anzahl Kurse mit Bergführer/in
- Anzahl Kurse ohne Bergführer/in

Ausgeschriebene Events (ab FS3):

- Total Events
- Events Stammsektion Jugend
- Events Stammsektion Aktive
- Events Stammsektion Senioren
- Events OG Hochdorf
- Events OG Napf
- Events OG Rigi
- Events OG Surental
- Events Gruppenübergreifend

Leitende (aus ausgeschriebenen Events):

- Total Leitende
- Anzahl Tourenleitende
- Anzahl Bergführer/innen

Anmeldungen von Teilnehmenden (aus ausgeschriebenen Events):

- Total Anmeldungen
- Anzahl Anmeldeanfrage bestätigt
- Anzahl Anmeldeanfrage auf Warteliste
- Anzahl Anmeldeanfrage unbeantwortet
- Anzahl Anmeldeanfrage abgelehnt
- Anzahl Anmeldeanfrage storniert

Bestätigte Teilnehmende (aus ausgeschriebenen Events):

- Total teilgenommene Teilnehmende
- männlich
- weiblich
- Alterkategorien
    - bis 20-jährig
    - 21 bis 30-jährig
    - 31 bis 40-jährig
    - 41 bis 60-jährig
    - 61 bis 80-jährig
    - 81 Jahre und älter

Event-Status aus ausgeschriebenen Touren (ab FS3) [Feld eventState]:

- Events stattgefunden [nur mit Tourrapport : executionState != "" AND eventState = ""]
- Events ausgebucht [eventState = "Event ausgebucht"]
- Events verschoben [eventState = "Event verschoben"]
- Events abgesagt [eventState = "Event abgesagt"]
- Unbekannt (kein Tourrapport vorhanden) [else-Fall : executionState = "" AND eventState = ""]
- Tour wie ausgeschrieben durchgeführt? Ja [executionState = "ja"]
- Tour wie ausgeschrieben durchgeführt? Nein [executionState = "nein"]
- Tour wie ausgeschrieben durchgeführt? Unbekannt (kein Tourrapport vorhanden) [executionState = ""]
