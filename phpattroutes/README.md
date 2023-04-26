# Attribute routes module

This modules lets you to use php attributes (annotations) to define routes for your callback function.
You can place annotations before your "loc_" prefixed function.
This annotation will automatically define the routes in the codkep system.

Because this module uses php8 attributes it requires at least php 8.0 version!

Sample codes:
	#[ckpath("start")]
	function loc_samplepage()
	{
	    ob_start();
	    print "<h2>I am a sample page!</h2>";
	    print "<div id=\"colorph\">RED</div>";
	    print lx("Change color","colchange");
	    print "<br/>";
	    print l("Go to page with parameter","secondpage/something");
	    return ob_get_clean();
	}
	
	#[ckpath("colchange"),cktype("ajax")]
	function loc_colorchanger()
	{
	    ajax_add_html('#colorph',"BLUE");
	}
	
	#[ckpath("secondpage/{str}")]
	function loc_secondpage()
	{
	    par_def('str','text2');
	    ob_start();
	    print "<h2>I am a page with parameter!</h2>";
	    $p = par('str');
	    print "The parameter value is: $p";
	    return ob_get_clean();
	}





