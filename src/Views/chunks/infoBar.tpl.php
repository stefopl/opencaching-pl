<?php

use src\Utils\Uri\Uri;

/**
 * This chunk is purpose is to display info/error bar at the top of the page
 */

return function ($reloadUrl=null, $infoMsg=null, $errorMsg=null) {

    $chunkCSS = Uri::getLinkWithModificationTime('/views/chunks/infoBar.css');

// begining of chunk
?>

    <script>
        // load infoBar chunk css
        var linkElement = document.createElement("link");
        linkElement.rel = "stylesheet";
        linkElement.href = "<?=$chunkCSS?>";
        linkElement.type = "text/css";
        document.head.appendChild(linkElement);
    </script>

    <script>
        function infoBarReload(){
          window.location = "<?=$reloadUrl?>";
        }

        function infoBarHide(){
          $('.infoBar-message').hide();
        }
    </script>

    <?php if (!empty($infoMsg)) { ?>
        <div class="infoBar-message infoBar-messageInfo">
          <div class="infoBar-messageText">
              <?=$infoMsg?>
          </div>
          <div class="infoBar-closeBtnContainer">
            <?php if ($reloadUrl) { ?>
                <span class="infoBar-closeBtn" onclick="infoBarReload()"></span>
            <?php } else { //if-reloadUrl ?>
                <span class="infoBar-closeBtn" onclick="infoBarHide()"></span>
            <?php } //if-reloadUrl ?>
          </div>
        </div>
    <?php } ?>

    <?php if (!empty($errorMsg)) { ?>
        <div class="infoBar-message infoBar-messageErr">
          <div class="infoBar-messageText">
            <?=$errorMsg?>
          </div>
          <div class="infoBar-closeBtnContainer">
            <?php if ($reloadUrl) { ?>
                <span class="infoBar-closeBtn" onclick="infoBarReload()"></span>
            <?php } else { //if-reloadUrl ?>
                <span class="infoBar-closeBtn" onclick="infoBarHide()"></span>
            <?php } //if-reloadUrl ?>
          </div>
        </div>
    <?php } ?>

<link rel="prefetch" href="/images/misc/btn_close_hover.svg">

<?php
};

// end of chunk - nothing should be added below
