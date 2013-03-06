<? params( $articles ) ?>

<div class="index">
    <header>
        <h1>
            Articulate
        </h1>
        <h2>lets write together</h2>

        <a href="#" class="js-login login">login / signup</a>
    </header>

    <article>
        <div class="abstract">
            <h2>Abstract</h2>
            <p>
                Do you want to write a programming article,
                but have no interest in maintaining a blog?
                Do you want your ideas accessible online,
                but aren't interested in trying to drive people towards it?
            
            <p>
                Techinical Write Up is a proposed solution to those two problems.
                You can login, write your article, publish, and then others can view straight away.
                Articles are listed together, allowing fellow users to view your ideas and solutions.

            <p>
                Our style gives a great emphasis on making the content of an article,
                the number one priority.
        </div>

        <h2 class="no-index">Articles</h2>
        <? $this->load->view( 'article/_articles_list', $articles ) ?>
    </article>
</div>
