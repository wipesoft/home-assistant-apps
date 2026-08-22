# Wijzigingen

## 0.3.2

- Horizontale lamellen gebruiken nu de standaard Home Assistant-actie `climate.set_swing_horizontal_mode` en het bijbehorende veld `swing_horizontal_mode`, zodat ook deze bediening geen HTTP 400 meer veroorzaakt.

## 0.3.1

- Verticale lamellen gebruiken nu de standaard Home Assistant-actie `climate.set_swing_mode`, zodat een ontbrekende custom actieregistratie geen HTTP 400 meer veroorzaakt.

## 0.3.0

- Optionele beveiligde uitgaande bridge voor bediening via een externe PHP-app.
- Opdrachten worden opgehaald zonder poorten in de thuisrouter te openen.
- Werkelijke Home Assistant-status wordt teruggekoppeld naar de externe app.

## 0.2.1

- De aan/uitknop toont nu ondubbelzinnig de actuele status van de binnenunit.
- Bediening reageert direct en synchroniseert daarna op de achtergrond met Home Assistant.
- Subtiele tikfeedback en een compacte voortgangsanimatie vervangen het blokkeren van de volledige kaart.

## 0.2.0

- Volledig vernieuwde premium mobiele vormgeving met live statuskleuren en animaties.
- Visuele thermostaat met dynamische temperatuurboog.
- Tikbare bediening voor bedrijfsstand en ventilatorsnelheid.
- Verticale en horizontale lamellenbediening, inclusief automatische en 3D-standen.
- Live weergave van de luchtstroom en actuele HVAC-activiteit.
- Temperatuurgrenzen worden dynamisch uit Home Assistant overgenomen.

## 0.1.1

- Vindt Studio en Slaapkamer automatisch op hun zichtbare Home Assistant-naam wanneer een technische entiteits-ID is gewijzigd.

## 0.1.0

- Eerste versie met bediening van de airco's in Studio en Slaapkamer.
- Aan/uit, temperatuur, bedrijfsstand en ventilatorstand.
- Volledig lokaal via Home Assistant Ingress en de interne API.
- Airco-entiteiten worden uitsluitend lokaal in Home Assistant ingesteld.
