<div class="topbar">
    <a href="/" class="topbar-link">Articulate</a>
    <a href="/articles" class="topbar-link">articles</a>

    <? if ( $this->session->isLoggedIn() ) { ?>
        <?
                $this->view->users->_avatarSmall(
                        $this->users->getUserByID(
                                $this->session->id
                        )
                );
        ?>
        <a href="#" class="topbar-link login js-login">login / signup</a>
    <? } else { ?>
        <a href="#" class="topbar-link login js-login">login / signup</a>
    <? } ?>
</div>
