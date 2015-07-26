<?php

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

<h1><?=$site_name_bold?> Terms and Conditions ("Agreement")</h1>
<p>This Agreement was last modified on October 16, 2013.</p>

<p>Please read these Terms and Conditions ("Agreement", "Terms and Conditions") carefully before using <?=$site_url?> ("the Site") operated by <?=$site_name_bold?> ("us", "we", or "our"). This Agreement sets forth the legally binding terms and conditions for your use of the Site at <?=$site_url?>.</p>
<p>By accessing or using the Site in any manner, including, but not limited to, visiting or browsing the Site or contributing content or other materials to the Site, you agree to be bound by these Terms and Conditions. Capitalized terms are defined in this Agreement.</p>

<p><strong>Intellectual Property</strong><br />The Site and its original content, features and functionality are owned by <?=$site_name_bold?> The licence for content is this site corresponds with the following license:  <?=html::createLink('http://creativecommons.org/licenses/by-sa/3.0/', 'Attribution-ShareAlike 3.0 ')?> </p>

<p><strong>Termination</strong><br />We may terminate your access to the Site, without cause or notice, which may result in the forfeiture and destruction of all information associated with you. All provisions of this Agreement that by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity, and limitations of liability.</p>

<p><strong>Links To Other Sites</strong><br />Our Site may contain links to third-party sites that are not owned or controlled by <?=$site_name_bold?>.</p>
<p><?=$site_name_bold?> has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third party sites or services. We strongly advise you to read the terms and conditions and privacy policy of any third-party site that you visit.</p>

<p><strong>Governing Law</strong><br />This Agreement (and any further rules, polices, or guidelines incorporated by reference) shall be governed and construed in accordance with the laws of <?=$laws_of?> without giving effect to any principles of conflicts of law.</p>

<p><strong>Changes To This Agreement</strong><br />We reserve the right, at our sole discretion, to modify or replace these Terms and Conditions by posting the updated terms on the Site. Your continued use of the Site after any such changes constitutes your acceptance of the new Terms and Conditions.</p>
<p>Please review this Agreement periodically for changes. If you do not agree to any of this Agreement or any changes to this Agreement, do not use, access or continue to access the Site or discontinue any use of the Site immediately.</p>

<p><strong>Contact Us</strong><br />If you have any questions about this Agreement, please contact us.</p>
<p style="font-size: 85%; color: #999;">Published and generated with permission from <a href="http://termsfeed.com/terms-conditions/generator/" style="color: #999; text-decoration: none;">www.termsfeed.com</a>.</p>
