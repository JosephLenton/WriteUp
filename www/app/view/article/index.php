<!DOCTYPE html>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">

<link rel="stylesheet" href="./reset.css">
<link href='http://fonts.googleapis.com/css?family=Neuton:200,300,400,700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="./style.css">

<title>Joseph Lenton | Ph.D. Thesis Proposal | Research into multi-touch visual programming language design</title>

<header>
	<h1>
		Ph.D. Thesis Proposal:<br>
		Research into Multi-Touch visual programming language design
	</h1>
	<h2>Joseph Lenton</h2>
	<h2>May 14th, 2012</h2>
</header>

<div class="abstract">
	<h2>Abstract</h2>

	<p>
		Today over half of the devices used as personal computers are touch based. This includes smart phones, tablet PCs and touch screens on laptops and desktop computers. The use of touch screens is growing, and over the next few years it is expected to be a standard feature on home PCs.

	<p>
		Despite their popularity, we are still teaching and building software using programming languages designed solely with old keyboard and mouse interfaces in mind. This is even though touch devices are rapidly becoming the norm.

	<p>
		These devices allow users to interact with software using new UI paradigms; new ways of interacting physically with software. Visual programming languages naturally fit the domain of touch devices. However, there is very little research evaluating how we can take best advantage of them for programming with a touch device.

	<p>
		Many visual languages also cause cognitive issues for users. Inputting tasks is often slow compared to textual input; some tasks can become repetitive, and often require complex user interfaces in order to support a broad set of features. This is typified by the lack of visual languages in commercial software development. Touch based interfaces allow research to take a fresh approach in resolving these issues.

	<p>
		Research has demonstrated other criticisms of visual languages that do not directly relate to the interface. These include issues with modularizing code, users being able to express problems, and navigation.

	<p>
		This is a proposal to research the issues with visual programming, and investigating ways in which they can be addressed through taking advantage of modern multi-touch based interfaces.
</div>

<article>
	<h2>Aims</h2>

	<p>
		The basis behind this project is that there is almost no research or development, which looks into building a programming language which is touch first. What is meant by 'touch first', is that it is developed solely with multi-touch devices in mind. This would include looking at interfaces would only work on a touch device. The types of devices I am primarily focusing on are tablet devices, and high-end smart phones.

	<p>
		For example many existing programming editors that do work on a touch device are predominantly based on offering a touch alternative to an existing desktop version. A virtual keyboard replaces a real keyboard, and a single-finger touch interface replaces the mouse. This approach is emulating a keyboard and mouse interface on a touch device; this project is about rethinking that, and coming from a multi-touch direction.

	<p>
		The goal is to research how multi-touch can be used to improve visual programming languages, where touch is seen as the primary interface. The interface is built specifically for multi-touch, rather than built as a keyboard or mouse substitute.

	<p>
		Work involves researching different programming and UI models, and developing prototypes for a visual programming language, as well as an IDE around it.

	<p>
		This will allow new methods to be developed for interaction, for developers to express solutions to problems, and to improve the programming experience on a touch device. It asks if multi-touch can make visual language easier to use, easier to learn, and as productive as their text based alternatives.

	<p>
		By the end of this project I will have a multi-touch visual programming language developed, which shows the results of this research.

	<h2>Research Areas</h2>

	<p>
		As a part of researching a new touch-oriented language, this can be broken down into multiple project areas.

	<h3>Programming Model</h3>

	<p>
		How the actual programming model works will have a large impact on the UI, and in turn, the UI will have a big impact on the programming model.

	<p>
		For example an imperative programming model may need to visualize how the program executes statements in order, whereas this is less important with a pure logic programming language. In short the UI will need to reflect the underlying programing model. There are many other paradigms which would alter the UI in other ways.

	<p>
		So one core aspect is to research and evaluate how different programming models would need to be represented, and how user friendly they might be. Finding ways to visualize these paradigms, developing how touch interaction can improve them, and evaluating which works the best.

	<p>
		There exist many evaluations of specific visual programming languages, and visual programming in general, and many visual language implementations also exist. These include logical Prolog-like languages<span class="ref"></span>, data-flow visual language<span class="ref"></span>, and the Open Blocks<span class="ref"></span> system used in Google App Inventor. Combined I can draw on these to aid with researching and evaluating how different models will perform, on a touch device.

	<h3>Multi-Touch based IDE</h3>
	
	<p>
		To interact with a touch language, a corner stone is to develop an IDE for the developer to be able to develop applications in this language. This offers a new avenue to develop ideas for not only a touch based IDE, but also to improve visual programming as a whole.

	<p>
		The key idea is that by researching new touch-first UI models, we can develop methods to make programming more natural for the end user. Develop ways in which the user can express themselves with their hands, and use this to write a programming solution.

	<p>
		More intelligent adaptive UIs, which take advantage of a touch interface, have been developed and evaluated<span class="ref"></span>. In this example they have shown to improve application usability, and I can use this as one avenue when developing the IDE interface.

	<h3>Improving Specific Input Methods</h3>

	<p>
		With programming there are many types of expression. These include inputting numbers and text into source code, writing out mathematical equations, building control structures, creating definitions, and more. Many of these types of input cut across all, or most, programming models. 

	<p>
		So this aspect of research is about finding ways to solve, or improve, specific user interface issues, and find how they can be incorporated into these programming models. It will look at academic research, and also real world examples such as Pi Cubed for the iPhone<span class="ref"></span>.

	<p>
		Many visual languages are successful at creating high level designs, but are more awkward when it comes to inputting specifics (like multiplying a value by 2). So by researching ways to improve those specific aspects, this will provide improvements to visual programming as a whole.

	<h3>Improvements to Visual Programming</h3>

	<p>
		Visual programming languages are not a new idea, but they have never successfully caught on into mainstream programming. Problems are regularly cited as to why they have failed, by various academics, and limits on their representations have been analysed<span class="ref"></span>.

		What are highlighted are basic issues, such as creating an 'if' statement and problems with UI<span class="ref"></span>, to greater issues looking at how applications can scale and solutions are represented<span class="ref"></span>.
		
	<p>
		I believe these issues boil down to two problems. First are simple badly tested designs and inadequate features. Researching and experimenting with existing visual languages, and the academic material around them, will help to highlight these issues and avoid them.
		The other problem is that text based input is still vastly superior with a full keyboard, then a visual language with a mouse. In short, with a touch interface I believe we can overcome the barrier when using a visual programming language.

	<h2>Importance</h2>

	<p>
		Personal computing has been redefined over the last 5 years due to the introduction of smart phones with multi-touch interfaces, and more recently tablet devices. Personal computers are also gaining touch controls; both through additions in the monitor, and through accompanying touch pads. Multi-touch is becoming the common way that we interact with personal computers.

	<p>
		Today, programming is still predominantly text based, focused around keyboard interaction. A touch based approach to programming is the next natural step.

	<h3>Improving the programming experience</h3>
	<p>
		Multi-touch controls allow you to express yourself with a greater variety of input methods, which can match the task at hand. For example to zoom, you commonly pinch the screen, with the scale aligning to finger movements. The key here is that the zoom is matching our interaction.

	<p>
		Programming however is typically built around us learning programming languages, and then having to match a compiler or interpreter when expressing our solutions.

	<p>
		A touch approach would allow us to look at how programming could be expressed in a more natural way. Through a wide variety of interaction, we could improve how we build problems, where interaction better matches the solutions at hand. This can potentially offer benefits with programming productivity, and learning.

	<h3>In Education</h3>
	<p>
		Improving the education of computer science is current a major topic in the UK, as well as elsewhere around the world, and a major factor of this is getting students to learn to program. Visual programming languages allow you to prevent the syntax issues you would normally receive with a text based language. With a well implemented visual programming language, this can make the learning experience more enjoyable.

	<p>
		In an age where it is becoming common for most students to own a touch device, this research would help to provide new tools for teaching students using their own devices. In turn, this streamlines teaching in the classroom.

	<h2>Research Plan</h2>
	<p>
		This is an outline on some of the practical aspects of researching the PHD.

	<h3>Timeframe</h3>

	<p>
		To complete the dissertation I plan to split the work into three overlapping phases.

	<p>
		First is to research, compare and evaluate the different programming models I could use. This includes looking at how these can be expressed with touch controls, how the user would interact with each model, and researching distinct components (such as UI widgets) to make interaction more natural for the user.

	<p>
		As a part of this phase I will also be developing a set of example programs that would be built using this language. This will help to evaluate how well, or badly, different programming models fair. 

	<p>
		The second phase is to start developing prototypes, and evaluating these as they are produced. UI models will be designed by this stage, and begin being implemented.

	<p>
		A big aspect of this phase is to research how I will be testing these models on users, and using this to evaluating the prototypes in practice. How well their usage fairs for the end user, what models work best, and to find places where these can be improved.

	<p>
		Finally in the third stage, I will begin to formalize which ideas are successful, and why. I personally believe that how well the prototypes are implemented will play a major factor on how well they are liked when used. This stage will allow me to highlight usability issues with the most successful ideas, resolve those issues, and through testing show if there is improvement.

	<h3>Technologies Used</h3>
	<p>
		The implementations will be first targeting iOS. However the research will aim to use common and open standards, allowing Windows 8 and Android support to be added later.

	<p>
		To achieve this I will implement prototypes using HTML5, JavaScript and CSS. Web browsers on tablet devices support recent standards, and are implemented to a high standard. These technologies allow the bulk of any implementations to remain cross-platform. HTML5 is also great for rapidly prototyping new ideas.

	<p>
		Due to the power of CSS, and the modern rendering engines behind them, HTML5 and CSS are regularly used for building beautiful, modern, and natural interfaces for users. By using this technology I can produce UIs which are more pleasing, and in turn should help to overcome some of the out dated feel that many visual programming languages suffer from.

	<p>
		Another benefit of HTML5 and JavaScript is that I already have a large amount of experience working with both. This includes building very large JavaScript projects, with complex user interfaces. This means I will spend less time learning any new technologies, and devote more of my time to the research at hand.

	<h3>Testing for success</h3>

	<p>
		There are many papers that exist which evaluate the usability of programming languages. These include evaluation visual languages using a 'cognitive dimensions' framework by Thomas Green<span class="ref"></span>, and examples of this framework used on other mainstream programming languages<span class="ref"></span>. These will be used to help research how I will evaluate the different programming models.

	<p>
		It will be useful to research how multi-touch interfaces are evaluated in other application domains, and see what methods can be gained from their approaches. One example is looking at the evaluation of touch information kiosks<span class="ref"></span>.

	<p>
		This touch visual language research would also need to be evaluated against other existing programming languages. This would include both other visual languages, and against conventional text based languages. A challenge for this is to attempt to find ways to test this research against conventional programming languages, so that it can be successfully compared and evaluated.

	<p>
		One approach could be to teach the same problems in our language, against another language, and then record the success of solving problems with those languages. Other methods for real world testing will also be developed, evaluated, and used.

	<p>
		I personally believe that the UI does need to reflect the underlying programming model, as otherwise I would expect the developer would have difficulty using the language. So part of the end user testing is to see how much the end user succeeds in understanding our language. It will also try to evaluate how much that understanding actually matters, to test if my presumption is correct or not.

	<h2>My Background</h2>

	<p>
		There are a range of reasons why I will be a success with this research project. First I have shown a very strong undergraduate academic record, receiving a first class degree in Computer Science with a year in industry. I also graduated with the highest mark in my year, with an average of 84%.

	<p>
		In regards to programming language theory, I not only completed modules on programming languages at University, but have also built my own programming language. On my game building platform, Play My Code<span class="ref"></span>, users use a Ruby-like language built by myself to build games. The site currently has hundreds of published games and experiments, proving real life success with the language.
	
	<p>
		For this project I can draw on my own programming language development experience to help research solutions.

	<p>
		I have experience teaching new programmers; this includes leading classes to students ranging to 12 to 16 years old, to writing teaching material used in schools. I also have existing experience working with the Greenfoot IDE,<span class="ref"></span> an educational environment for teaching Java, used by 100s of teachers. My own game platform is also used by many teachers in the UK as a way of helping to get kids interested in programming.

	<p>
		These factors gives me a foundation I can use for researching UIs and programming models which are intuitive, natural and user friendly.

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