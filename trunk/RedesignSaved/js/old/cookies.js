function createExpireTime(timestamp)
{
	var date = new Date();
	timestamp = timestamp * 1000;
	date.setTime(timestamp);

	return date; 
}

function deleteSetCookie(name,expire)
{
	if($.cookie(name) == true) {
		$.cookie(name, null);
	} else {
		var date = createExpireTime(expire);
		$.cookie(name, '1', { expires: date });	
	}
}

function changeCookie(name,expire)
{
	var date = createExpireTime(expire);

	if($.cookie(name) == 1) {
	    mode = 0;
	} else {
	    mode = 1
	}
	
	if (expire == null) {
	    $.cookie(name, mode);
	} else {
			$.cookie(name, mode, { expires: date });
	}
}

function set_cookie(ele,expire)
{
	expireStatement = "";
	if(expire != "")
	{
		var expireTime = new Date();
		expire = expire * 1000;
		expireTime.setTime(expire);
		
		expireStatement = ' expires='+expireTime.toGMTString()+''; 
	}

	if(document.cookie)
	{
		done = false;
		cookie_object = document.cookie.replace(/ /g,"");
		cookie_explode = cookie_object.split(";");
		for(i=0;i<cookie_explode.length;i++)
		{
			data_explode = cookie_explode[i].split("=");
			if(data_explode[0] == ele)
			{
				if(data_explode[1] == 0)
				{
					document.cookie = ''+ele+'=1;'+expireStatement+''; 
				}
				else
				{
					document.cookie = ''+ele+'=0;'+expireStatement+''; 
				}
				done = true;
				break;
			}
		}
		
		if(!done)
		{
			document.cookie = ''+ele+'=0;'+expireStatement+''; 
		}
	}
	else
	{
		document.cookie = ''+ele+'=0;'+expireStatement+''; 
	}
}

function check_cookie(cookieName,ele)
{
	if(document.cookie)
	{
		done = false;
		cookie_object = document.cookie.replace(/ /g,"");
		cookie_explode = cookie_object.split(";");
		for(i=0;i<cookie_explode.length;i++)
		{
			data_explode = cookie_explode[i].split("=");
			if(data_explode[0] == cookieName)
			{
				if(data_explode[1] == 0)
				{
					cookie_isNotSet(ele);
				}
				else
				{
					cookie_isSet(ele);
				}
				done = true;
				break;
			}
		}
		
		if(!done)
		{
			cookie_isNotSet(ele);
		}
	}
	else
	{
		cookie_isNotSet(ele);
	}
}