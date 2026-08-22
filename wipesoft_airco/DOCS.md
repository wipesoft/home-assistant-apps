# Gebruik

Vul vóór de eerste start op het tabblad **Configuratie** de Home Assistant-entiteiten in voor Studio en Slaapkamer. Deze beginnen met `climate.`.

Na het starten verschijnt **Airco** in de Home Assistant-zijbalk. De app draait volledig lokaal: Home Assistant verzorgt de aanmelding en geeft de app tijdens het draaien toegang tot de interne API.

## Bediening

- Tik op **Aan** of **Uit** om een binnenunit te schakelen.
- Stel de gewenste temperatuur in stappen van 0,5 °C in.
- Kies rechtstreeks een bedrijfsstand en ventilatorsnelheid.
- Kies onder **Luchtstroom** de verticale en horizontale richting van de lamellen.
- Gebruik **Op & neer**, **Links & rechts** of **3D automatisch** voor bewegende luchtverdeling.

De beschikbare keuzes komen live uit de Mitsubishi WF-RAC-integratie. Daardoor toont de app
alleen standen die de betreffende binnenunit daadwerkelijk ondersteunt.

## Externe bediening (optioneel)

De app kan zelf via HTTPS verbinding maken met een externe WIPEsoft Climate-installatie. Hiervoor hoeven geen poorten in de thuisrouter te worden geopend.

Vul op het tabblad **Configuratie** in:

- `remote_url`: de HTTPS-URL zonder afsluitende slash;
- `bridge_token`: de eenmalig door de externe installatie getoonde sleutel;
- `poll_interval`: het aantal seconden tussen controles, aanbevolen waarde `3`.

Herstart de app na het opslaan. In het logboek verschijnt daarna **WIPEsoft remote bridge is gestart**. Laat de URL en sleutel leeg wanneer alleen lokale bediening gewenst is.
