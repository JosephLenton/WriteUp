<? params( $articles ) ?>

<ul class="articles-list">
    <? foreach ( $articles as $article ) { ?>
        <li class="articles-list-item">
            <a class="articles-list-item-username" href="<?= $article->url ?>"><?= h($article->title) ?></a>
            <a class="articles-list-item-username" href="<?= $article->user_url ?>"><?= h($article->username) ?></a>
        </li>
    <? } ?>
</ul>