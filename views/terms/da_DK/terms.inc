<?php

use diversen\conf;
use diversen\html;

$site_url = conf::getSchemeWithServerName();
$site_name = conf::getMainIni('server_name');
$site_name_bold = strtoupper($site_name);
$site_country = conf::getMainIni('site_country');

if (!$site_country){
    $site_country = 'USA'; 
} 

$site_state = conf::getMainIni('site_state');
if (!$site_state) {
    $site_state = 'Texas'; 
} 

$laws_of = $site_state . ', ' . $site_country;

?>

<h1> <?=$site_name_bold?> Betingelser og vilkår ( "Aftalen" ) </h1 >
<p> Aftalen blev senest ændret den 16. oktober 2013. </p>

<p> Læs venligst disse vilkår og betingelser ("Aftalen" , " Vilkår og betingelser ") grundigt igennem , før du bruger <?=$site_url?> ( " Webstedet "), der drives af <?=$site_name_bold?> ( "os", "vi" eller "vores" ) . Denne aftale indeholder de juridisk bindende vilkår og betingelser for din brug af sitet ved <?=$site_url ?> . </p>
<p> Ved at tilgå eller bruge hjemmesiden på nogen måde, herunder , men ikke begrænset til , besøger eller surfe på sitet eller bidrage indhold eller andre materialer til webstedet , accepterer du at være bundet af disse vilkår og betingelser. Aktiverede er defineret i denne aftale. </p>

<p> <strong>Intellektuel Ejendomsret</strong > <br /> 
    stedet og dets oprindelige indhold, funktioner og funktionalitet er ejet af <?=$site_name_bold?>. Licensen for indhold er <?=html::createLink('http://creativecommons.org/licenses/by-sa/3.0/', 'Attribution-ShareAlike 3.0 ')?> </p>

<p> <strong> Opsigelse </strong > <br /> Vi kan opsige din adgang til webstedet, uden årsag eller varsel , hvilket kan resultere i konfiskation og tilintetgørelse af alle oplysninger i forbindelse med dig. Alle bestemmelserne i denne aftale , som efter deres natur skal overleve opsigelse skal overleve opsigelse , herunder, uden begrænsning , ejerskab bestemmelser , garanti ansvarsfraskrivelser , erstatning og ansvarsbegrænsninger . </p>

<p> <strong> Links til andre sider </strong > <br /> 
    Vores hjemmeside kan indeholde links til tredjeparts websteder, der ikke ejes eller kontrolleres af <?=$site_name_bold?> . </p>
<p> <?=$site_name_bold?> har ingen kontrol over , og påtager sig intet ansvar for , at indholdet , privatliv politikker eller praksis tredjeparters websteder eller tjenester. Vi anbefaler dig at læse de vilkår og betingelser og privacy policy af enhver tredjeparts websted, du besøger. </p>

<p> <strong> Gældende lov </strong > <br /> 
    Denne Aftale (og eventuelle yderligere regler , politikker eller retningslinjer indarbejdet ved reference) er underlagt og fortolkes i overensstemmelse med lovene i <?=$laws_of ?> uden hensyntagen til eventuelle principper for lovkonflikter . </p>

<p> <strong> Ændringer til denne aftale </strong > <br /> 
    Vi forbeholder os ret til efter eget skøn at ændre eller erstatte disse vilkår og betingelser ved at udgive de opdaterede vilkår på sitet . Din fortsatte brug af hjemmesiden efter sådanne ændringer udgør din accept af de nye vilkår og betingelser. </p>
<p> venligst denne aftale for ændringer. Hvis du ikke accepterer nogen af ​​aftalen eller eventuelle ændringer til denne aftale , må du ikke bruge , få adgang til eller fortsætte med at få adgang til sitet eller afbryde enhver brug af sitet med det samme. </p>

<p> <strong> Kontakt os </strong> <br /> 
    Hvis du har spørgsmål om denne aftale , bedes du kontakte os . 
</p>
<p style="font-size: 85%; color: #999;">
    Udgivet med tilladelse fra <a href = " http://termsfeed.com/terms-conditions/generator/ "> www.termsfeed.com </a>
</p>