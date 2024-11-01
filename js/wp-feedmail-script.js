function check_feed_form()
	{
		var f_email	= document.feedmail_addsub.fsub_email.value;
		var emailfilter=/^\w+[\+\.\w-]*@([\w-]+\.)*\w+[\w-]*\.([a-z]{2,4}|\d+)$/i;
		if (f_email=='')
			{
				document.getElementById("response1").style.display="block";
				return false;
			}
		else if(emailfilter.test(f_email) == false)
			{
				document.getElementById("response2").style.display="block";
				return false;
			}
		else
			{
				return true;
			}
	}

function hideMessage()
	{
		document.getElementById("response1").style.display="none";	
		document.getElementById("response2").style.display="none";
	}
	