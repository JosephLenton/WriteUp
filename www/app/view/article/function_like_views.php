<!DOCTYPE html>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">

<link rel="stylesheet" href="./reset.css">
<link href='http://fonts.googleapis.com/css?family=Neuton:200,300,400,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="./style.css">

<title></title>

<header>
	<h1>
		Function Like Views in PHP
	</h1>
	<h2>Joseph Lenton</h2>
	<h2>May 14th, 2012</h2>
</header>

<div class="abstract">
	<h2>Abstract</h2>

	<p>
		A big part of any web framework is loading 'views', snippets of HTML, which render you're data. A vital, and obvious part, is passing data into that view. There are multiple APIs designed to achieve this in PHP, however I feel that many of them suck.

	<p>
		This article describes the two most common ways to pass data into a view, and then offers a third, which I prefer.
</div>

<article>
	<h2>Introduction</h2>

	<p>
		I'll be blunt, I just don't like how views are called in most frameworks. It might sound odd, but it's something I've always found too verbose and too awkward. I have variables in one file, and data in the other, so why can't the interface between them be as direct as possible?

	<p>
		I recently re-wrote the way I call views in my own MVC framework, and this is pretty much the thought process I went through.

	<h2>Existing Approaches</h2>

	<p>
		There are two main ways of passing values into views.

	<h3>Assigning to $this</h3>

	<p>
		A common way is to assign variables to a controller or view object, and then load the view within the scope of that object. This allows you to access the variables within the view by accessing '$this'.

	<code>
		$view = new View( 'blog_poost' );

		$view->title   = 'Welcome to my blog';
		$view->content = 'blah blah blah blah blah ...';

		$view->render();
	</code>

	<p>
		The view can then access these values like so:

	<code>
		// todo
	</code>

	<h3>Associate Array</h3>

	<p>
		A common alternative is to name variables as indexes in an associate array:
	
	<code>
		$this->view( 'blog_post', array(
				'title'   => 'Welcome to my blog',
				'content' => 'blah blah blah blah blah ...'
		));
	</code>

	<p>
		The array indexes then become local variables within the view:

	<code>
		// todo
	</code>

	<h3>Problems</h3>

	<p>
		There are two main issues I have with this. On any large site, you quickly get a lot of small views, and when using them I want to know quickly what data they take. So I open up a view, and then have to read through the whole thing to work this out.

	<p>
		This is if you're views are both documented, and that documentation is kept up to date!

	<p>
		The other problem is that there are no checks. What happens if I pass in not enough values? If I have strict errors turned on, then I will get an error that relates to the issue, but this happens later in the view. Note that I will not get an error relating to calling the view, I get an error resulting to using the variable. However without strict errors this can easily lead to silent bugs, which aren't discovered straight away.

	<p>
		That flies in the face of good debugging where errors should be reported as close to the source as possible.

	<p>
		There is also an opposite issue, passing in too much data. I have found real life examples where a view required data from the database, and displayed it, but the displaying logic was then later removed. The call to the database however did not get removed, and so the page silently requested data that was never used. Enforcing a 'view signature' would have found this problem the moment this signature was changed.

	<h2>Take a step back</h2>

	<p>
		There is already a far more popular style to solving this problem. Programmers already pass data into a section of re-usable code: functions!

	<p>
		In the majority of programming languages, parameters are not named by the caller, and instead are only named by the callee. This allows ...

	<ul>
		<li>
			it to have a signature, it states what parameters it expects.
		</li>
		<li>
			the caller to be terse; it states who it is calling, and the data it is passing in, and nothing more.
		</li>
		<li>
			there to be added checks, such as enforcing the number of parameters passed in.
		</li>
	</ul>

	<p>
		So why don't we apply this to views?

	<h3>Because it's ugly</h3>

	<p>
		A first approach would be to define views within functions, or methods. For example:

	<code>
		// todo
		// define a view inside a function
	</code>

	<p>
		Views are typically stand along HTML files, without any kind of wrapping needed. If views are functions, it leads to creating hundreds of functions, which could easily clash with existing ones. If they are methods, then I am now keeping multiple view in one file, and having to define a class to use them.

	<p>
		I don't like having to use either of them due to the added boilerplate involved. It also stops me from keeping views in seeprate files.

	<h3>The 'params' function</h3>

	<p>
		So lets mix the two; the file based approach of HTML views, and the signature of functions. So I call the view like:

	<code>
		$this->view( 'blog_post', "Welcome to my blog", 'blah blah blah blah blah ...' );
	</code>

	<p>
		... and the values are automatically passed to the view for me. The syntax is like a function (and could be closer using magic methods). Now to bind that data to variables in the view, we use a 'params' function at the top of the view file.

	<p>
		In the view this looks like:

	<code>
		&lt;?
			params( $title, $content );
		?&gt;
		// todo, the rest
	</code>

	<p>
		That's it! Much shorter, and we get some checks in for free. For example it can check if too few, or too many, values are passed into the view, and throw an error if that is the case.

	<p>
		The variables don't need to be pre-defined, or exist. They are defined when they are used as paraeters, as PHP allows this. This forces you to document your code at the top, and if you're view's parameters change, you must update this call.

	<h3>Implementation Details</h3>

	<p>
		When the view is loaded, we first grab up all the values, and place them into a variable somewhere. In my own implementation this is a global variable, as it allows params to remain as a function call.

	<p>
		When called, the params function grabs references to the variables using the ampersand operator. To do this for every possible parameter, we must explicitely define each of those parameters to be like this. So if we want a view with 5 parameters, params must support 5 explicit parameters. We can't use 'func_get_args' to achieve this. For example:

	<code>
		function params( &amp;$param0, &amp;$param1, &amp;$param2, &amp;$param3, &amp;$param4 ) {
	</code>

	<p>
		PHP allows you to define variables on the fly, so we just use the variables as parameters to get them passed in.

	<p>
		To avoid errors for views with less than 5 parameters, we can use default parameters:

	<code>
		function params( &amp;$param0=null, &amp;$param1=null, &amp;$param2=null, &amp;$param3=null, &amp;$param4=null ) {
	</code>

	<p>
		Finally we can use 'func_num_args' to check how many were actually passed in, compare this against how many are being passed to the view, and assign values to just those parameters.

	<p>
		This is the only issue with this approach, having to manually define each possible parameter. However you can easily generate the code for a long list of parameters, and the code to efficiently set values to it based on a switch statement. The vast majority of views will have less then 10 values passed in, and so I personally only support up to 20. However you could easily scale this up to 30, 40 or 50 parameters (if you have a view requiring more then that, you should maybe rethink you're code).

	<h2 class="no-index">References</h2>

	<div class="reference">
		<div class="num"></div>
		Spratt, L.
		Analytic Assessment of Visual Programming Languages.
		1996.
		<a href="http://homepage.mac.com/lspratt/papers/Assessing_SPARCL.dir/Assessing%20SPARCL.pdf"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		IBM.
		OpenDX.
		<a href="http://www.opendx.org/"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		Roque, R. V.
		OpenBlocks: An Extendable Framework for Graphical Block Programming Systems.
		May 2007.
		<a href="http://dspace.mit.edu/bitstream/handle/1721.1/41550/220927290.pdf"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		Montague, K. Hanson, V. and Cobley, A.
		Evaluation of Adaptive Interaction with Mobile Touch-Screen Devices.
		October 2011.
		<a href="http://de2011.computing.dundee.ac.uk/wp-content/uploads/2011/10/Evaluation-of-Adaptive-Interaction-with-Mobile-Touch-Screen-Devices.pdf"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		Sunset Lake Software.
		Pi Cubed.
		2012.
		<a href="http://www.sunsetlakesoftware.com/picubed"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		Nickerson, J. V.
		Visual Programming: Limits of Graphic Representation.
		1994.
		<a href="http://www.stevens.edu/jnickerson/visprog.pdf"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		Schaefer, R.
		Criticisms of Visual Programming Languages.
		ACM SIGSOFT Software Engineering Notes,
		Volume 36 No. 2,
		March 2011,
		7-8.
	</div>
	<div class="reference">
		<div class="num"></div>
		Blackwell, A. F, Whitley, K. N, Good. J, and Petre, M.
		Cognitive Factors in Programming with Diagrams.
		Artificial Intelligence Review 15, 2001, 95-114.
		<a href="http://www.sussex.ac.uk/Users/judithg/papers/AIRev_Black_Whit_Good_Petre.pdf"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		Green, T. R. G. and Petre, M.

		Usability analysis of visual programming environments: a ‘cognitive dimensions’ framework J.
		Visual Languages and Computing, 7, 1996, 131-174,

		<a href="http://citeseerx.ist.psu.edu/viewdoc/download?doi=10.1.1.54.3584&amp;rep=rep1&amp;type=pdf"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		Clarke, S.
		Evaluating a new programming language.
		<a href="http://www.ppig.org/papers/13th-clarke.pdf"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		Raisamo, R.
		Evaluating different touched-based interaction techniques in a public information kiosk.
		1999.
		<a href="http://www.cs.uta.fi/~tko/Reports/pdf/A-1999-11.pdf"></a>
	</div>

	<div class="reference">
		<div class="num"></div>
		Lenton, J. and Lenton, S.
		Play My Code.
		<a href="http://www.playmycode.com"></a>
	</div>
	<div class="reference">
		<div class="num"></div>
		University of Kent.
		Greenfoot.
		<a href="http://www.greenfoot.org"></a>
	</div>
</article>