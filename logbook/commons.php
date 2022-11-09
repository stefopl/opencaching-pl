<?php
function run_in_bg($Command, $Priority = 0)
{
shell_exec('export _INKSCAPE_GC="disable"');
if($Priority)
$PID = shell_exec("export _INKSCAPE_GC=\"disable\"; nohup nice -n $Priority $Command 2> /dev/null & echo $!");
else
$PID = shell_exec("export _INKSCAPE_GC=\"disable\"; nohup $Command 2> /dev/null & echo $!");
return($PID);
}

function is_running($PID)
{
exec("ps $PID", $ProcessState);
return(count($ProcessState) >= 2);
}

function wait_for_pid($pid)
{
while(is_running($pid)) usleep(100000);
}

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


function decrypt($msg_encrypted_bundle, $password){

    $msg_encrypted_bundle = base64_decode($msg_encrypted_bundle);

    $password = sha1($password);

    $components = explode( ':', $msg_encrypted_bundle );
    $iv            = $components[0];
    $salt          = hash('sha256', $password.$components[1]);
    $encrypted_msg = $components[2];

    $decrypted_msg = openssl_decrypt(
        $encrypted_msg, 'aes-256-cbc', $salt, null, $iv
    );

    if ( $decrypted_msg === false )
        return false;
    return $decrypted_msg;
}

function validate_msg($cookietext)
{


if(!preg_match("/[0-9]+ This is a secret message/", $cookietext))
return false;

$num=0;
sscanf($cookietext, "%d", $num);
return $num;
}
