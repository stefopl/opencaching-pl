<?php
use src\Utils\Uri\SimpleRouter;
?>
<div class="content2-container">
  <div class="content2-pagetitle">
    <img src="/images/blue/newspaper.png" class="icon22" alt="Newspaper icon">
    <?=tr('news')?>
    <a href="/rss/newnews.xml">
      <img src="/images/misc/rss.svg" class="icon16" alt="RSS icon">
    </a>
  </div>

  <?php foreach ($view->newsList as $news) { ?>

      <div class="callout callout-news callout-<?=$news->getStatusBootstrapName()?>">
      <div class="callout-news-status">
        <?=tr('news_lbl_datepub')?>: <a href="<?=SimpleRouter::getLink('News.NewsList', 'show', $news->getId())?>" class="links"><?=$news->getDatePublication(true)?></a> |
        <?=tr('news_lbl_author')?>: <strong><?php if ($news->isAuthorHidden()) { echo tr('news_OCTeam'); } else { ?><a href="<?=$news->getAuthor()->getProfileUrl()?>" class="links"><?=$news->getAuthor()->getUserName()?></a><?php } ?></strong>
      </div>
      <div class="callout-news-title"><?=$news->getTitle()?></div>
      <div class="callout-news-content"><?=$news->getContent()?></div>
    </div>
  <?php } //foreach ?>

  <?php $view->callChunk('pagination', $view->paginationModel); ?>
</div>
