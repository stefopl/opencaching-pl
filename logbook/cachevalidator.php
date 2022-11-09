<?php
$secret = "opencaching2022";
include('commons.php');
header('Content-Type: application/xhtml+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?'.">\n";
echo '<?xml-stylesheet type="text/css" href="style.css"?'.">\n";

function stripslashes_from_strings_only( $value ) {
    return is_string( $value ) ? stripslashes( $value ) : $value;
}

$_POST = array_map('stripslashes_from_strings_only', $_POST);
$_GET = array_map('stripslashes_from_strings_only', $_GET);
$_COOKIE = array_map('stripslashes_from_strings_only', $_COOKIE);
$_REQUEST = array_map('stripslashes_from_strings_only', $_REQUEST);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl">
    <head>
        <title>Naprawiacz opisu</title>
        <script src="ajax.js" charset="utf-8"></script>
    </head>


    <body>
        <script>
            //<![CDATA[

            var cururl;
            var curpage = 1;
            var numpages = 1;

            function startCallback() {
                // make something useful before submit (onStart)
                return true;
            }

            function bindArgument(fn, arg)
            {
                return function () {
                    return fn(arg);
                };
            }

            function removeChildrenFromNode(node)
            {
                var len = node.childNodes.length;
                while (node.hasChildNodes()) {
                    node.removeChild(node.firstChild);
                }
            }

            //]]>
        </script>
        <div id="logoblock">
            <img src="geocaching.jpg" id="logo" />
        </div>
        <div id="navibar">
    <!--<span><a href="">Strona Główna</a></span>-->
            <?php
            include("menu.inc");
            ?>
        </div>
        <p>

            <?php

            $text = isset($_POST['text']) ?? "";

            $tidy =  htmlspecialchars($text);

            function iterate_over($node)
            {
            $removed = array();

            print "<br />Iterating over " .  $node->tagName ."\n";

            if($node->tagName == "span") {
            print "deleting\n";
            array_push($removed, $node);
            }
            if(!$node)
            return;

            if($node->hasAttributes()) {
            $attributes = $node->attributes;
            if(!is_null($attributes))
            foreach ($attributes as $index=>$attr)
            echo $attr->name ." = " . htmlspecialchars($attr->value) . "\n";
            }
            if($node->hasChildNodes()) {
            $children = $node->childNodes;
            foreach($children as $child) {
            $removed = array_merge($removed, iterate_over($child, $array));
            }
            }
            return $removed;
            }

            function appendSibling(DOMNode $newnode, DOMNode $ref)
            {
            if ($ref->nextSibling) {
            // $ref has an immediate brother : insert newnode before this one
            return $ref->parentNode->insertBefore($newnode, $ref->nextSibling);
            } else {
            // $ref has no brother next to him : insert newnode as last child of his parent
            return $ref->parentNode->appendChild($newnode);
            }
            }

            function remove_node($domElement)
            {
            if($domElement->hasChildNodes()) {
            $children = $domElement->childNodes;
            $toAppend = array();
            foreach($children as $child)
            array_unshift($toAppend, $child);
            foreach($toAppend as $child)
            appendSibling($child, $domElement);
            }
            //  $domElement->parentNode->removeChild($domElement);
            }


            $str = (string)$tidy;
            if($str) {
            //  $str = str_replace("&amp;", "&", $str);
            $doc = DOMDocument::loadXML("<cache_description>".$str."</cache_description>");
            $doc->encoding = "utf-8";
            $main = $doc->documentElement;

            if($main) {
            $for_removal = iterate_over($main);
            foreach($for_removal as $domElement) {
            echo "<br/>removing ..\n";
            remove_node($domElement);
            $domElement->parentNode->removeChild($domElement);
            }
            }

            $str = $doc->saveXML();
            $str = str_replace('<?xml version="1.0" encoding="utf-8"?>'."\n", "", $str);
            $str = str_replace('<cache_description>', "", $str);
                $str = str_replace('</cache_description>', "", $str);
            }

            ?>
            <form method="post" action="index.php?page=cachevalidator">
                <textarea name="text" id="validatorarea"><?php

echo htmlspecialchars($str, ENT_NOQUOTES, "UTF-8");

?></textarea>
                <br/>
                <input type="submit" name="submit" value="Poprawiaj!" />
            </form>
        </p>
        <div id="textpreview">
            <?php
            echo $str;




            ?>
        </div>
    </body>
</html>
