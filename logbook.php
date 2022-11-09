<?php

use src\Models\ApplicationContainer;

// logbook generator...

require_once (__DIR__.'/lib/common.inc.php');

//user logged in?
if (!ApplicationContainer::GetAuthorizedUser()) {
    $target = urlencode(tpl_get_current_page());
    tpl_redirect('login.php?target=' . $target);
} else {
    $tplname = 'logbook';
}

$secret = "opencaching2022";
tpl_set_var('encrypted_message', encrypt($_GET['logbook_type'] . " This is a secret message", $secret));

tpl_BuildTemplate();




function encrypt($data, $password){
    $iv = substr(sha1(mt_rand()), 0, 16);
    $password = sha1($password);

    $salt = sha1(mt_rand());
    $saltWithPassword = hash('sha256', $password.$salt);

    $encrypted = openssl_encrypt(
        "$data", 'aes-256-cbc', "$saltWithPassword", null, $iv
    );
    $msg_encrypted_bundle = "$iv:$salt:$encrypted";
    return base64_encode($msg_encrypted_bundle);
}
