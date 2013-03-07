<?php params( $title, $author, $content, $style, $styles ) ?>
<?
    if ( $style === '' ) {
        $style = $styles[0];
    }
?>

<div class="article-fullscreen"></div>

<div class="article-window">
    <div class="article-edit <?= $style ?>">
        <div class="article-edit-header">
            <textarea numrows="1" class="article-edit-title"><?= $title ?></textarea>

            <div class="article-edit-bar">
                <a href="#" class="js-edit-fullscreen article-edit-option fullscreen">fullscreen</a>

                <div class="article-edit-option numbers">
                    <div class="js-edit-number article-edit-option-label">number headers</div>
                    <input type="checkbox" />
                </div>

                <div class="article-edit-option style">
                    <div class="article-edit-option-label">style</div>

                    <select class="js-edit-style">
                        <? foreach ( $styles as $s ) { ?>
                            <option
                                    <?= ($s === $style) ? 'selected="true"' : '' ?>
                                    value="<?= $s ?>"
                            ><?= $s ?></option>
                        <? } ?>
                    </select>
                </div>

                <a href="#" class="article-edit-option.save js-edit-save">save</a>
            </div>

            <h3 class="article-edit-author"><?= $author ?></h3>
            <h3 class="article-edit-date"><?= date( 'jS F, Y', time() ) ?></h3>
        </div>

        <textarea class="article-edit-content"><?= $content ?></textarea>
    </div>
</div>
