<?php
use src\Utils\I18n\I18n;
use src\Utils\Database\OcDb;
use src\Models\GeoCache\CacheAttribute;

?>
<style>
.atContainer {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    width: 514px;
}

.atDiv {
    position: relative;
    width: 35px;
    height: 34px;
    margin: 1px 2px;
}

.atImg {
    width: 35px;
    height: 34px;
    padding: 0px 2px;
}

.withOpacity {
    opacity: 0.3;
}

.atCross {
  position: absolute;
  right: 0px;
  top: 0px;
  width: 35px;
  height: 34px;
  opacity: 0.8;
  display: none;
}

.atCross:before, .atCross:after {
  position: absolute;
  left: 16px;
  content: ' ';
  height: 34px;
  width: 5px;
  background-color: red;
  opacity: 1;
}
.atCross:before {
  transform: rotate(45deg);
}
.atCross:after {
  transform: rotate(-45deg);
}

</style>

<h1>Attributes tester</h1>

<?php

function attr_jsline($tpl, $options, $id, $textlong, $iconlarge, $iconno, $iconundef, $category)
{
  $line = $tpl;

  $line = mb_ereg_replace('{id}', $id, $line);


  $line = mb_ereg_replace('{state}', 1, $line);

  $line = mb_ereg_replace('{text_long}', addslashes($textlong), $line);
  $line = mb_ereg_replace('{icon}', $iconlarge, $line);
  $line = mb_ereg_replace('{icon_no}', $iconno, $line);
  $line = mb_ereg_replace('{icon_undef}', $iconundef, $line);
  $line = mb_ereg_replace('{category}', $category, $line);

  return $line;
}

function attr_image($tpl, $options, $id, $textlong, $iconlarge, $iconno, $iconundef, $category)
{
  $line = $tpl;

  $line = mb_ereg_replace('{id}', $id, $line);
  $line = mb_ereg_replace('{text_long}', $textlong, $line);


  $line = mb_ereg_replace('{icon}', $iconlarge, $line);
  return $line;
}

?>

<?php foreach(['pl', 'nl', 'ro','uk', 'us'] as $node) { ?>

    <?php

    if($config['ocNode'] == $node){
      $attributes_jsarray = '';
      $attributes_img = '';
      $attributesCat2_img = '';

      $cache_attrib_jsarray_line = "new Array('{id}', {state}, '{text_long}', '{icon}', '{icon_no}', '{icon_undef}', '{category}')";
      $cache_attrib_img_line = '<img id="attrimg{id}" src="{icon}" title="{text_long}" alt="{text_long}" onmousedown="switchAttribute({id})" style="cursor: pointer;" /> ';

      $database = OcDb::instance();
      $query = "SELECT `id`, `text_long`, `icon_large`, `icon_no`, `icon_undef`, `category` FROM `cache_attrib` WHERE `language` LIKE :1 ORDER BY `id`";
      $s = $database->multiVariableQuery($query, strtoupper(I18n::getCurrentLang()));
      if($database->rowCount($s) <= 0) {
        $s = $database->multiVariableQuery($query, 'EN');
      }
      $rs = $database->dbResultFetchAll($s);

      foreach ($rs as $record)
      {
        $line = attr_jsline($cache_attrib_jsarray_line, false, $record['id'], $record['text_long'], '/'.$record['icon_large'], '/'.$record['icon_no'], '/'.$record['icon_undef'], $record['category']);
        if ($attributes_jsarray != '') $attributes_jsarray .= ",\n";
        $attributes_jsarray .= $line;
        $line = attr_image($cache_attrib_img_line, false, $record['id'], $record['text_long'], '/'.$record['icon_large'], '/'.$record['icon_no'], '/'.$record['icon_undef'], $record['category']);
        if ($record['category'] != 1)
          $attributesCat2_img .= $line;
        else
          $attributes_img .= $line;
      }
      $line = attr_jsline($cache_attrib_jsarray_line, false, "999", tr("with_password"), '/'.$config['search-attr-icons']['password'][0], '/'.$config['search-attr-icons']['password'][1], '/'.$config['search-attr-icons']['password'][2], 0);
      $attributes_jsarray .= ",\n".$line;

      $line = attr_image($cache_attrib_img_line, false, "999", tr("with_password"), '/'.$config['search-attr-icons']['password'][0], '/'.$config['search-attr-icons']['password'][1], '/'.$config['search-attr-icons']['password'][2], 0);
      $attributes_img .= $line;
    }
    ?>

    <hr>
    <h2>oc<?=$node?></h2>
    <div>
      <p>Screenshot from the search view:</p>
      <img src="/images/cacheAttributes/test/oc<?=$node?>.png">
    </div>
    <p></p>
    <?php if($config['ocNode'] == $node): ?>
      <p>3-states icons generated like as search view:</p>
      <div class="atContainer">
        <?php echo $attributes_img; ?>
      </div>
      <p><br/></p>
    <?php endif; ?>
    <p>3-states icons generated from new code:</p>
    <div class="atContainer">
    <?php foreach ($view->attrList[$node] as $key=>$at) { ?>
        <div class="atDiv" onclick="changeAtIcon(this)" state="selected">
          <img class="atImg" src="<?=CacheAttribute::getIcon($at, $node)?>"
               title="<?=tr(CacheAttribute::getTrKey($at))?>"
               alt="<?=tr(CacheAttribute::getTrKey($at))?>">
          <div class="atCross"></div>
        </div>
    <?php } ?>
    </div>
    <p><br/></p>
    <p>All attributes from CacheAttribute list (displays <img src="/images/blue/atten-red.png"> if icon not found):</p>
    <div class="atContainer">
    <?php foreach (CacheAttribute::getGpxAttrIds() as $at) { ?>
        <div class="atDiv">
            <img class="atImg atImgList" src="<?=CacheAttribute::getIcon($at, $node)?>"
               title="<?= $at; ?>; <?= CacheAttribute::getTrKey($at); ?>; <?= tr(CacheAttribute::getTrKey($at)); ?>"
               alt="<?= $at; ?>; <?= CacheAttribute::getTrKey($at); ?>; <?= tr(CacheAttribute::getTrKey($at)); ?>">
        </div>
    <?php } ?>
    </div>
<?php } ?>

<script>

function changeAtIcon(obj) {
  var iconDiv = $(obj);

  switch (iconDiv.attr('state')) {
  case 'selected': // -> negselected
    iconDiv.removeClass('withOpacity');
    iconDiv.children(".atCross").show();
    iconDiv.attr('state','negselected');
    break;
  case 'notselected': // -> selected
    iconDiv.removeClass('withOpacity');
    iconDiv.children(".atCross").hide();
    iconDiv.attr('state','selected');
    break;
  case 'negsel': // -> notselected
  default:
    iconDiv.addClass('withOpacity');
    iconDiv.children(".atCross").hide();
    iconDiv.attr('state','notselected');
  }
}

$('.atImgList').on("error", function() {
    $(this).attr('src', '/images/blue/atten-red.png');
});

function switchAttribute(id)
{
    var attrImg = document.getElementById("attrimg" + id);
    var nArrayIndex = 0;

    for (nArrayIndex = 0; nArrayIndex < maAttributes.length; nArrayIndex++)
    {
        if (maAttributes[nArrayIndex][0] == id)
            break;
    }

    if (maAttributes[nArrayIndex][1] == 0)
    {
        attrImg.src = maAttributes[nArrayIndex][3];
        maAttributes[nArrayIndex][1] = 1;
    }
    else if (maAttributes[nArrayIndex][1] == 1)
    {
        attrImg.src = maAttributes[nArrayIndex][4];
        maAttributes[nArrayIndex][1] = 2;
    }
    else if (maAttributes[nArrayIndex][1] == 2)
    {
        attrImg.src = maAttributes[nArrayIndex][5];
        maAttributes[nArrayIndex][1] = 0;
    }
}
var maAttributes = new Array(<?php echo $attributes_jsarray ?>);
</script>
