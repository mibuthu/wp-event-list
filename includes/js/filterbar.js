// Javascript functions for event-list admin_main page

// Confirmation for event deletion
function eventlist_redirect(name, value, sc_id) {
	window.location.assign(updateUrlParameter(window.location.href, name, value, sc_id));
}

function updateUrlParameter(url, paramName, paramVal, sc_id) {
	// extrude anchor
	var urlArray = url.split("#");
	var anchor = urlArray[1] ? "#" + urlArray[1] : "";
	// split base url and parameters
	urlArray = urlArray[0].split("?");
	var baseUrl = urlArray[0];
	var oldParams = urlArray[1] ? urlArray[1] : null;
	// create new parameter list
	var newParams = "";
	var seperator = "?";
	var paramNameAdded = false;
	if(null != oldParams) {
		urlArray = oldParams.split("&");
		for(i=0; i<urlArray.length; i++) {
			if(urlArray[i].split("=")[0] == "event_id"+sc_id) {
				// do nothing
			}
			else if(urlArray[i].split("=")[0] == paramName) {
				newParams += seperator + paramName + "=" + paramVal;
				paramNameAdded = true;
			}
			else {
				newParams += seperator + urlArray[i];
			}
			seperator = "&";
		}
	}
	// add paramName if not already done
	if(!paramNameAdded) {
		newParams += seperator + paramName + "=" + paramVal;
	}
	return baseUrl + newParams + anchor;
}
