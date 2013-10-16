<?php

$site_url = config::getSchemeWithServerName();
$site_name = config::getMainIni('server_name');
$site_name_bold = strtoupper($site_name);
$site_country = config::getMainIni('site_country');
$site_email = config::getMainIni('site_email');

if (!$site_country){
    $site_country = 'USA'; 
} 

$site_state = config::getMainIni('site_state');
if (!$site_state) {
    $site_state = 'Texas'; 
} 

$laws_of = $site_state . ', ' . $site_country;

?>


<h1><?=$site_name_bold?> Privacy Policy</h1>
<p>This Privacy Policy was last modified on October 16, 2013.</p>
<p><?=$site_name?> ("us", "we", or "our") operates <?=$site_url?> (the "Site"). This page informs you of our policies regarding the collection, use and disclosure of Personal Information we receive from users of the Site.</p>
<p>We use your Personal Information only for providing and improving the Site. By using the Site, you agree to the collection and use of information in accordance with this policy. Unless otherwise defined in this Privacy Policy, terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, accessible at <?=html::createLink('/account/terms/service', $site_url . "/account/terms/service")?>.</p>

<p><strong>Information Collection And Use</strong><br />While using our Site, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you. Personally identifiable information may include, but is not limited to, your name, email address, postal address and phone number ("Personal Information").</p>

<p><strong>Log Data</strong><br />Like many site operators, we collect information that your browser sends whenever you visit our Site ("Log Data"). This Log Data may include information such as your computer's Internet Protocol ("IP") address, browser type, browser version, the pages of our Site that you visit, the time and date of your visit, the time spent on those pages and other statistics.</p>

<p><strong>Cookies</strong><br />Cookies are files with small amount of data, which may include an anonymous unique identifier. Cookies are sent to your browser from a web site and stored on your computer's hard drive.</p>
<p>Like many sites, we use "cookies" to collect information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our Site.</p>

<p><strong>Security</strong><br />The security of your Personal Information is important to us, but remember that no method of transmission over the Internet, or method of electronic storage, is 100% secure. While we strive to use commercially acceptable means to protect your Personal Information, we cannot guarantee its absolute security.</p>

<p><strong>Links To Other Sites</strong><br />Our Site may contain links to other sites that are not operated by us. If you click on a third party link, you will be directed to that third party's site. We strongly advise you to review the Privacy Policy of every site you visit.</p>
<p><?=$site_name?> has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third party sites or services.</p>

<p><strong>Changes To This Privacy Policy</strong><br /><?=$site_name?> may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on the Site. You are advised to review this Privacy Policy periodically for any changes.</p>

<p><strong>Contact Us</strong><br />If you have any questions about this Privacy Policy, please <?=html::createLink("mailto:$site_email", 'contact us')?>.</p>
<p><a href="http://termsfeed.com/privacy-policy/generator/" title="TermsFeed"><img src="http://termsfeed.com/seals/ts-pp-authenticity.png" alt="TermsFeed" /></a></p>
