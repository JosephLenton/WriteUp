<? params( $articles ) ?>

<div class="index-back"><div class="index-cutout"></div></div>

<div class="index">
    <header>
        <h1>
            Articulate
        </h1>
    </header>

        <div class="index-about">
            <p>
                Write articles, share online.

            <p>
                Hosts blogs, 
                Do you want to write a programming article,
                but have no interest in maintaining a blog?
                Do you want your ideas accessible online,
                but aren't interested in trying to drive people towards it?
            
            <p>
                Articulate is a proposed solution to those two problems.
                You can login, write your article, publish, and then others can view straight away.
                Articles are listed together, allowing fellow users to view your ideas and solutions.

            <p>
                Our style gives a great emphasis on making the content of an article,
                the number one priority.
        </div>

        <div class="index-buttons">
            <a href="#" class="guest">guest</a>
            <a href="#" class="js-login login">login / signup</a>
        </div>

        <div class="index-rightbar">
            <h2>latest</h2>

            <? $this->load->view( 'article/_articles_list', $articles ) ?>
        </div>

        <div class="index-rightbar">
            <h2>Tags</h2>
        </div>
</div>
