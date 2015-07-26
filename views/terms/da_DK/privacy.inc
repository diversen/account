<?php
$site_url = conf::getSchemeWithServerName();
$site_name = conf::getMainIni('server_name');
$site_name_bold = strtoupper($site_name);
$site_country = conf::getMainIni('site_country');
$site_email = conf::getMainIni('site_email');

if (!$site_country) {
    $site_country = 'USA';
}

$site_state = conf::getMainIni('site_state');
if (!$site_state) {
    $site_state = 'Texas';
}

$laws_of = $site_state . ', ' . $site_country;
?>


<h1> <?= $site_name_bold ?> Fortrolighedspolitik </h1>
<p> Denne fortrolighedspolitik blev senest ændret den 16. oktober 2013. </p>
<p> <?=$site_name ?> ( "os", "vi" eller "vores" ) driver <?=$site_url?> ("Webstedet ").
    Denne side informerer dig om vores politikker vedrørende indsamling , brug og videregivelse af personlige oplysninger , vi modtager fra brugere af sitet .
</p>
<p>
    Vi bruger dine personlige oplysninger kun til at tilvejebringe og forbedre sitet .
    Ved at bruge webstedet accepterer du til indsamling og brug af oplysninger i overensstemmelse med denne politik.
    Medmindre andet er defineret i denne fortrolighedspolitik, udtryk, der anvendes i denne fortrolighedspolitik har de samme betydninger som i vores vilkår og betingelser , der er tilgængelige på
<?= html::createLink('/account/terms/service', $site_url . "/account/terms/service") ?>.
</p>

<p>
    <strong> Indsamling og brug </strong > <br />
    Mens du bruger vores hjemmeside , kan vi bede dig om at give os visse personligt identificerbare oplysninger , der kan bruges til at kontakte eller identificere dig.
    Personligt identificerbare oplysninger kan omfatte, men er ikke begrænset til , dit navn , email -adresse , postadresse og telefonnummer ( "Personlige oplysninger ").
</p>

<p>
    <strong> Log Data </strong > <br />
    Ligesom mange site operatører , indsamler vi oplysninger, som din browser sender, når du besøger vores hjemmeside ( " Log Data ").
    Denne log data kan indeholde oplysninger såsom din computers Internet Protocol ( "IP " ) adresse , browser type , browser -version, de sider af vores websted , du besøger , tidspunkt og dato for dit besøg, den tid brugt på disse sider og andre statistik.
</p>

<p> <strong> Cookies </strong > <br />
    Cookies er filer med lille mængde data , som kan omfatte et anonymt, entydigt id. Cookies sendes til din browser fra en hjemmeside og gemmes på din computers harddisk .
</p>
<p>
    Ligesom mange steder bruger vi " cookies " til at indsamle oplysninger . Du kan instruere din browser til at afvise alle cookies eller indikere hvornår en cookie bliver sendt . Men hvis du ikke accepterer cookies, kan du ikke være i stand til at bruge nogle dele af vores site .
</p>

<p> <strong> Sikkerhed </strong > <br />
    Sikkerheden af dine personlige oplysninger er vigtigt for os , men husk, at ingen metode til transmission via internettet , eller metode til elektronisk opbevaring , er 100% sikker . Mens vi stræber efter at bruge kommercielt acceptabelt middel til at beskytte dine personlige oplysninger , kan vi ikke garantere dets absolutte sikkerhed.
</p>

<p> <strong> Links til andre sider </strong > <br />
    Vores hjemmeside kan indeholde links til andre websteder , der ikke drives af os. Hvis du klikker på en tredjeparts link, vil du blive dirigeret til denne tredjepart websted. Vi anbefaler dig at gennemgå fortrolighedspolitikken af ethvert websted , du besøger .
</p>
<p> <?=$site_name?> har ingen kontrol over , og påtager sig intet ansvar for, at indholdet , privatliv politikker eller praksis tredjeparters websteder eller tjenester.
</p>

<p> <strong> ændringer i denne Privacy Policy </strong > <br />
        <?=$site_name?> kan opdatere denne fortrolighedspolitik fra tid til anden.
        Vi vil underrette dig om eventuelle ændringer ved at offentliggøre den nye fortrolighedspolitik på hjemmesiden.
        Du rådes til at gennemgå denne fortrolighedspolitik jævnligt for eventuelle ændringer.
</p>

<p> <strong> Kontakt os </strong > <br />
    Hvis du har spørgsmål til denne fortrolighedspolitik, kan du <?=html::createLink ( "mailto:$site_email", 'kontakte os' ) ?> . </p>
<p style="font-size: 85%; color: #999;"> Udgivet med tilladelse fra <a href = "http://termsfeed.com/privacy-policy/generator/"> www.termsfeed.com </a> </p>
