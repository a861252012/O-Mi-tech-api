/*!	SWFObject v2.2 <http://code.google.com/p/swfobject/> 
	is released under the MIT License <http://www.opensource.org/licenses/mit-license.php> 
*/



var swfobject = function() {

	var UNDEF = "undefined",
		OBJECT = "object",
		SHOCKWAVE_FLASH = "Shockwave Flash",
		SHOCKWAVE_FLASH_AX = "ShockwaveFlash.ShockwaveFlash",
		FLASH_MIME_TYPE = "application/x-shockwave-flash",
		EXPRESS_INSTALL_ID = "SWFObjectExprInst",
		ON_READY_STATE_CHANGE = "onreadystatechange",
		
		win = window,
		doc = document,
		nav = navigator,
		
		plugin = false,
		domLoadFnArr = [main],
		regObjArr = [],
		objIdArr = [],
		listenersArr = [],
		storedAltContent,
		storedAltContentId,
		storedCallbackFn,
		storedCallbackObj,
		isDomLoaded = false,
		isExpressInstallActive = false,
		dynamicStylesheet,
		dynamicStylesheetMedia,
		autoHideShow = true,
	
	/* Centralized function for browser feature detection
		- User agent string detection is only used when no good alternative is possible
		- Is executed directly for optimal performance
	*/	
	ua = function() {
		var w3cdom = typeof doc.getElementById != UNDEF && typeof doc.getElementsByTagName != UNDEF && typeof doc.createElement != UNDEF,
			u = nav.userAgent.toLowerCase(),
			p = nav.platform.toLowerCase(),
			windows = p ? /win/.test(p) : /win/.test(u),
			mac = p ? /mac/.test(p) : /mac/.test(u),
			webkit = /webkit/.test(u) ? parseFloat(u.replace(/^.*webkit\/(\d+(\.\d+)?).*$/, "$1")) : false, // returns either the webkit version or false if not webkit
			ie = !+"\v1", // feature detection based on Andrea Giammarchi's solution: http://webreflection.blogspot.com/2009/01/32-bytes-to-know-if-your-browser-is-ie.html
			playerVersion = [0,0,0],
			d = null;
		if (typeof nav.plugins != UNDEF && typeof nav.plugins[SHOCKWAVE_FLASH] == OBJECT) {
			d = nav.plugins[SHOCKWAVE_FLASH].description;
			if (d && !(typeof nav.mimeTypes != UNDEF && nav.mimeTypes[FLASH_MIME_TYPE] && !nav.mimeTypes[FLASH_MIME_TYPE].enabledPlugin)) { // navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin indicates whether plug-ins are enabled or disabled in Safari 3+
				plugin = true;
				ie = false; // cascaded feature detection for Internet Explorer
				d = d.replace(/^.*\s+(\S+\s+\S+$)/, "$1");
				playerVersion[0] = parseInt(d.replace(/^(.*)\..*$/, "$1"), 10);
				playerVersion[1] = parseInt(d.replace(/^.*\.(.*)\s.*$/, "$1"), 10);
				playerVersion[2] = /[a-zA-Z]/.test(d) ? parseInt(d.replace(/^.*[a-zA-Z]+(.*)$/, "$1"), 10) : 0;
			}
		}
		else if (typeof win.ActiveXObject != UNDEF) {
			try {
				var a = new ActiveXObject(SHOCKWAVE_FLASH_AX);
				if (a) { // a will return null when ActiveX is disabled
					d = a.GetVariable("$version");
					if (d) {
						ie = true; // cascaded feature detection for Internet Explorer
						d = d.split(" ")[1].split(",");
						playerVersion = [parseInt(d[0], 10), parseInt(d[1], 10), parseInt(d[2], 10)];
					}
				}
			}
			catch(e) {}
		}
		return { w3:w3cdom, pv:playerVersion, wk:webkit, ie:ie, win:windows, mac:mac };
	}(),
	
	/* Cross-browser onDomLoad
		- Will fire an event as soon as the DOM of a web page is loaded
		- Internet Explorer workaround based on Diego Perini's solution: http://javascript.nwbox.com/IEContentLoaded/
		- Regular onload serves as fallback
	*/ 
	onDomLoad = function() {

		return (function fn(){
      if (!ua.w3) { return; }
      if ((typeof doc.readyState != UNDEF && doc.readyState == "complete") || (typeof doc.readyState == UNDEF && (doc.getElementsByTagName("body")[0] || doc.body))) { // function is fired after onload, e.g. when script is inserted dynamically
        callDomLoadFunctions();
      }
      if (!isDomLoaded) {
        if (typeof doc.addEventListener != UNDEF) {
          doc.addEventListener("DOMContentLoaded", callDomLoadFunctions, false);
        }
        if (ua.ie && ua.win) {
          doc.attachEvent(ON_READY_STATE_CHANGE, function() {
            if (doc.readyState == "complete") {
              doc.detachEvent(ON_READY_STATE_CHANGE, fn);
              callDomLoadFunctions();
            }
          });
          if (win == top) { // if not inside an iframe
            (function(){
              if (isDomLoaded) { return; }
              try {
                doc.documentElement.doScroll("left");
              }
              catch(e) {
                setTimeout(fn, 0);
                return;
              }
              callDomLoadFunctions();
            })();
          }
        }
        if (ua.wk) {
          (function(){
            if (isDomLoaded) { return; }
            if (!/loaded|complete/.test(doc.readyState)) {
              setTimeout(fn, 0);
              return;
            }
            callDomLoadFunctions();
          })();
        }
        addLoadEvent(callDomLoadFunctions);
      }
		})();

	}();
	
	function callDomLoadFunctions() {
		if (isDomLoaded) { return; }
		try { // test if we can really add/remove elements to/from the DOM; we don't want to fire it too early
			var t = doc.getElementsByTagName("body")[0].appendChild(createElement("span"));
			t.parentNode.removeChild(t);
		}
		catch (e) { return; }
		isDomLoaded = true;
		var dl = domLoadFnArr.length;
		for (var i = 0; i < dl; i++) {
			domLoadFnArr[i]();
		}
	}
	
	function addDomLoadEvent(fn) {
		if (isDomLoaded) {
			fn();
		}
		else { 
			domLoadFnArr[domLoadFnArr.length] = fn; // Array.push() is only available in IE5.5+
		}
	}
	
	/* Cross-browser onload
		- Based on James Edwards' solution: http://brothercake.com/site/resources/scripts/onload/
		- Will fire an event as soon as a web page including all of its assets are loaded 
	 */
	function addLoadEvent(fn) {
		if (typeof win.addEventListener != UNDEF) {
			win.addEventListener("load", fn, false);
		}
		else if (typeof doc.addEventListener != UNDEF) {
			doc.addEventListener("load", fn, false);
		}
		else if (typeof win.attachEvent != UNDEF) {
			addListener(win, "onload", fn);
		}
		else if (typeof win.onload == "function") {
			var fnOld = win.onload;
			win.onload = function() {
				fnOld();
				fn();
			};
		}
		else {
			win.onload = fn;
		}
	}
	
	/* Main function
		- Will preferably execute onDomLoad, otherwise onload (as a fallback)
	*/
	function main() { 
		if (plugin) {
			testPlayerVersion();
		}
		else {
			matchVersions();
		}
	}
	
	/* Detect the Flash Player version for non-Internet Explorer browsers
		- Detecting the plug-in version via the object element is more precise than using the plugins collection item's description:
		  a. Both release and build numbers can be detected
		  b. Avoid wrong descriptions by corrupt installers provided by Adobe
		  c. Avoid wrong descriptions by multiple Flash Player entries in the plugin Array, caused by incorrect browser imports
		- Disadvantage of this method is that it depends on the availability of the DOM, while the plugins collection is immediately available
	*/
	function testPlayerVersion() {
		var b = doc.getElementsByTagName("body")[0];
		var o = createElement(OBJECT);
		o.setAttribute("type", FLASH_MIME_TYPE);
		var t = b.appendChild(o);
		if (t) {
			var counter = 0;
			(function fn(){
				if (typeof t.GetVariable != UNDEF) {
					var d = t.GetVariable("$version");
					if (d) {
						d = d.split(" ")[1].split(",");
						ua.pv = [parseInt(d[0], 10), parseInt(d[1], 10), parseInt(d[2], 10)];
					}
				}
				else if (counter < 10) {
					counter++;
					setTimeout(fn, 10);
					return;
				}
				b.removeChild(o);
				t = null;
				matchVersions();
			})();
		}
		else {
			matchVersions();
		}
	}
	
	/* Perform Flash Player and SWF version matching; static publishing only
	*/
	function matchVersions() {
		var rl = regObjArr.length;
		if (rl > 0) {
			for (var i = 0; i < rl; i++) { // for each registered object element
				var id = regObjArr[i].id;
				var cb = regObjArr[i].callbackFn;
				var cbObj = {success:false, id:id};
				if (ua.pv[0] > 0) {
					var obj = getElementById(id);
					if (obj) {
						if (hasPlayerVersion(regObjArr[i].swfVersion) && !(ua.wk && ua.wk < 312)) { // Flash Player version >= published SWF version: Houston, we have a match!
							setVisibility(id, true);
							if (cb) {
								cbObj.success = true;
								cbObj.ref = getObjectById(id);
								cb(cbObj);
							}
						}
						else if (regObjArr[i].expressInstall && canExpressInstall()) { // show the Adobe Express Install dialog if set by the web page author and if supported
							var att = {};
							att.data = regObjArr[i].expressInstall;
							att.width = obj.getAttribute("width") || "0";
							att.height = obj.getAttribute("height") || "0";
							if (obj.getAttribute("class")) { att.styleclass = obj.getAttribute("class"); }
							if (obj.getAttribute("align")) { att.align = obj.getAttribute("align"); }
							// parse HTML object param element's name-value pairs
							var par = {};
							var p = obj.getElementsByTagName("param");
							var pl = p.length;
							for (var j = 0; j < pl; j++) {
								if (p[j].getAttribute("name").toLowerCase() != "movie") {
									par[p[j].getAttribute("name")] = p[j].getAttribute("value");
								}
							}
							showExpressInstall(att, par, id, cb);
						}
						else { // Flash Player and SWF version mismatch or an older Webkit engine that ignores the HTML object element's nested param elements: display alternative content instead of SWF
							displayAltContent(obj);
							if (cb) { cb(cbObj); }
						}
					}
				}
				else {	// if no Flash Player is installed or the fp version cannot be detected we let the HTML object element do its job (either show a SWF or alternative content)
					setVisibility(id, true);
					if (cb) {
						var o = getObjectById(id); // test whether there is an HTML object element or not
						if (o && typeof o.SetVariable != UNDEF) { 
							cbObj.success = true;
							cbObj.ref = o;
						}
						cb(cbObj);
					}
				}
			}
		}
	}
	
	function getObjectById(objectIdStr) {
		var r = null;
		var o = getElementById(objectIdStr);
		if (o && o.nodeName == "OBJECT") {
			if (typeof o.SetVariable != UNDEF) {
				r = o;
			}
			else {
				var n = o.getElementsByTagName(OBJECT)[0];
				if (n) {
					r = n;
				}
			}
		}
		return r;
	}
	
	/* Requirements for Adobe Express Install
		- only one instance can be active at a time
		- fp 6.0.65 or higher
		- Win/Mac OS only
		- no Webkit engines older than version 312
	*/
	function canExpressInstall() {
		return !isExpressInstallActive && hasPlayerVersion("6.0.65") && (ua.win || ua.mac) && !(ua.wk && ua.wk < 312);
	}
	
	/* Show the Adobe Express Install dialog
		- Reference: http://www.adobe.com/cfusion/knowledgebase/index.cfm?id=6a253b75
	*/
	function showExpressInstall(att, par, replaceElemIdStr, callbackFn) {
		isExpressInstallActive = true;
		storedCallbackFn = callbackFn || null;
		storedCallbackObj = {success:false, id:replaceElemIdStr};
		var obj = getElementById(replaceElemIdStr);
		if (obj) {
			if (obj.nodeName == "OBJECT") { // static publishing
				storedAltContent = abstractAltContent(obj);
				storedAltContentId = null;
			}
			else { // dynamic publishing
				storedAltContent = obj;
				storedAltContentId = replaceElemIdStr;
			}
			att.id = EXPRESS_INSTALL_ID;
			if (typeof att.width == UNDEF || (!/%$/.test(att.width) && parseInt(att.width, 10) < 310)) { att.width = "310"; }
			if (typeof att.height == UNDEF || (!/%$/.test(att.height) && parseInt(att.height, 10) < 137)) { att.height = "137"; }
			doc.title = doc.title.slice(0, 47) + " - Flash Player Installation";
			var pt = ua.ie && ua.win ? "ActiveX" : "PlugIn",
				fv = "MMredirectURL=" + win.location.toString().replace(/&/g,"%26") + "&MMplayerType=" + pt + "&MMdoctitle=" + doc.title;
			if (typeof par.flashvars != UNDEF) {
				par.flashvars += "&" + fv;
			}
			else {
				par.flashvars = fv;
			}
			// IE only: when a SWF is loading (AND: not available in cache) wait for the readyState of the object element to become 4 before removing it,
			// because you cannot properly cancel a loading SWF file without breaking browser load references, also obj.onreadystatechange doesn't work
			if (ua.ie && ua.win && obj.readyState != 4) {
				var newObj = createElement("div");
				replaceElemIdStr += "SWFObjectNew";
				newObj.setAttribute("id", replaceElemIdStr);
				obj.parentNode.insertBefore(newObj, obj); // insert placeholder div that will be replaced by the object element that loads expressinstall.swf
				obj.style.display = "none";
				(function(){
					if (obj.readyState == 4) {
						obj.parentNode.removeChild(obj);
					}
					else {
						setTimeout(arguments.callee, 10);
					}
				})();
			}
			createSWF(att, par, replaceElemIdStr);
		}
	}
	
	/* Functions to abstract and display alternative content
	*/
	function displayAltContent(obj) {
		if (ua.ie && ua.win && obj.readyState != 4) {
			// IE only: when a SWF is loading (AND: not available in cache) wait for the readyState of the object element to become 4 before removing it,
			// because you cannot properly cancel a loading SWF file without breaking browser load references, also obj.onreadystatechange doesn't work
			var el = createElement("div");
			obj.parentNode.insertBefore(el, obj); // insert placeholder div that will be replaced by the alternative content
			el.parentNode.replaceChild(abstractAltContent(obj), el);
			obj.style.display = "none";
			(function(){
				if (obj.readyState == 4) {
					obj.parentNode.removeChild(obj);
				}
				else {
					setTimeout(arguments.callee, 10);
				}
			})();
		}
		else {
			obj.parentNode.replaceChild(abstractAltContent(obj), obj);
		}
	} 

	function abstractAltContent(obj) {
		var ac = createElement("div");
		if (ua.win && ua.ie) {
			ac.innerHTML = obj.innerHTML;
		}
		else {
			var nestedObj = obj.getElementsByTagName(OBJECT)[0];
			if (nestedObj) {
				var c = nestedObj.childNodes;
				if (c) {
					var cl = c.length;
					for (var i = 0; i < cl; i++) {
						if (!(c[i].nodeType == 1 && c[i].nodeName == "PARAM") && !(c[i].nodeType == 8)) {
							ac.appendChild(c[i].cloneNode(true));
						}
					}
				}
			}
		}
		return ac;
	}
	
	/* Cross-browser dynamic SWF creation
	*/
	function createSWF(attObj, parObj, id) {
		var r, el = getElementById(id);
		if (ua.wk && ua.wk < 312) { return r; }
		if (el) {
			if (typeof attObj.id == UNDEF) { // if no 'id' is defined for the object element, it will inherit the 'id' from the alternative content
				attObj.id = id;
			}
			if (ua.ie && ua.win) { // Internet Explorer + the HTML object element + W3C DOM methods do not combine: fall back to outerHTML
				var att = "";
				for (var i in attObj) {
					if (attObj[i] != Object.prototype[i]) { // filter out prototype additions from other potential libraries
						if (i.toLowerCase() == "data") {
							parObj.movie = attObj[i];
						}
						else if (i.toLowerCase() == "styleclass") { // 'class' is an ECMA4 reserved keyword
							att += ' class="' + attObj[i] + '"';
						}
						else if (i.toLowerCase() != "classid") {
							att += ' ' + i + '="' + attObj[i] + '"';
						}
					}
				}
				var par = "";
				for (var j in parObj) {
					if (parObj[j] != Object.prototype[j]) { // filter out prototype additions from other potential libraries
						par += '<param name="' + j + '" value="' + parObj[j] + '" />';
					}
				}
				el.outerHTML = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"' + att + '>' + par + '</object>';
				objIdArr[objIdArr.length] = attObj.id; // stored to fix object 'leaks' on unload (dynamic publishing only)
				r = getElementById(attObj.id);	
			}
			else { // well-behaving browsers
				var o = createElement(OBJECT);
				o.setAttribute("type", FLASH_MIME_TYPE);
				for (var m in attObj) {
					if (attObj[m] != Object.prototype[m]) { // filter out prototype additions from other potential libraries
						if (m.toLowerCase() == "styleclass") { // 'class' is an ECMA4 reserved keyword
							o.setAttribute("class", attObj[m]);
						}
						else if (m.toLowerCase() != "classid") { // filter out IE specific attribute
							o.setAttribute(m, attObj[m]);
						}
					}
				}
				for (var n in parObj) {
					if (parObj[n] != Object.prototype[n] && n.toLowerCase() != "movie") { // filter out prototype additions from other potential libraries and IE specific param element
						createObjParam(o, n, parObj[n]);
					}
				}
				el.parentNode.replaceChild(o, el);
				r = o;
			}
		}
		return r;
	}
	
	function createObjParam(el, pName, pValue) {
		var p = createElement("param");
		p.setAttribute("name", pName);	
		p.setAttribute("value", pValue);
		el.appendChild(p);
	}
	
	/* Cross-browser SWF removal
		- Especially needed to safely and completely remove a SWF in Internet Explorer
	*/
	function removeSWF(id) {
		var obj = getElementById(id);
		if (obj && obj.nodeName == "OBJECT") {
			if (ua.ie && ua.win) {
				obj.style.display = "none";
				(function(){
					if (obj.readyState == 4) {
						removeObjectInIE(id);
					}
					else {
						setTimeout(arguments.callee, 10);
					}
				})();
			}
			else {
				obj.parentNode.removeChild(obj);
			}
		}
	}
	
	function removeObjectInIE(id) {
		var obj = getElementById(id);
		if (obj) {
			for (var i in obj) {
				if (typeof obj[i] == "function") {
					obj[i] = null;
				}
			}
			obj.parentNode.removeChild(obj);
		}
	}
	
	/* Functions to optimize JavaScript compression
	*/
	function getElementById(id) {
		var el = null;
		try {
			el = doc.getElementById(id);
		}
		catch (e) {}
		return el;
	}
	
	function createElement(el) {
		return doc.createElement(el);
	}
	
	/* Updated attachEvent function for Internet Explorer
		- Stores attachEvent information in an Array, so on unload the detachEvent functions can be called to avoid memory leaks
	*/	
	function addListener(target, eventType, fn) {
		target.attachEvent(eventType, fn);
		listenersArr[listenersArr.length] = [target, eventType, fn];
	}
	
	/* Flash Player and SWF content version matching
	*/
	function hasPlayerVersion(rv) {
		var pv = ua.pv, v = rv.split(".");
		v[0] = parseInt(v[0], 10);
		v[1] = parseInt(v[1], 10) || 0; // supports short notation, e.g. "9" instead of "9.0.0"
		v[2] = parseInt(v[2], 10) || 0;
		return (pv[0] > v[0] || (pv[0] == v[0] && pv[1] > v[1]) || (pv[0] == v[0] && pv[1] == v[1] && pv[2] >= v[2])) ? true : false;
	}
	
	/* Cross-browser dynamic CSS creation
		- Based on Bobby van der Sluis' solution: http://www.bobbyvandersluis.com/articles/dynamicCSS.php
	*/	
	function createCSS(sel, decl, media, newStyle) {
		if (ua.ie && ua.mac) { return; }
		var h = doc.getElementsByTagName("head")[0];
		if (!h) { return; } // to also support badly authored HTML pages that lack a head element
		var m = (media && typeof media == "string") ? media : "screen";
		if (newStyle) {
			dynamicStylesheet = null;
			dynamicStylesheetMedia = null;
		}
		if (!dynamicStylesheet || dynamicStylesheetMedia != m) { 
			// create dynamic stylesheet + get a global reference to it
			var s = createElement("style");
			s.setAttribute("type", "text/css");
			s.setAttribute("media", m);
			dynamicStylesheet = h.appendChild(s);
			if (ua.ie && ua.win && typeof doc.styleSheets != UNDEF && doc.styleSheets.length > 0) {
				dynamicStylesheet = doc.styleSheets[doc.styleSheets.length - 1];
			}
			dynamicStylesheetMedia = m;
		}
		// add style rule
		if (ua.ie && ua.win) {
			if (dynamicStylesheet && typeof dynamicStylesheet.addRule == OBJECT) {
				dynamicStylesheet.addRule(sel, decl);
			}
		}
		else {
			if (dynamicStylesheet && typeof doc.createTextNode != UNDEF) {
				dynamicStylesheet.appendChild(doc.createTextNode(sel + " {" + decl + "}"));
			}
		}
	}
	
	function setVisibility(id, isVisible) {
		if (!autoHideShow) { return; }
		var v = isVisible ? "visible" : "hidden";
		if (isDomLoaded && getElementById(id)) {
			getElementById(id).style.visibility = v;
		}
		else {
			createCSS("#" + id, "visibility:" + v);
		}
	}

	/* Filter to avoid XSS attacks
	*/
	function urlEncodeIfNecessary(s) {
		var regex = /[\\\"<>\.;]/;
		var hasBadChars = regex.exec(s) != null;
		return hasBadChars && typeof encodeURIComponent != UNDEF ? encodeURIComponent(s) : s;
	}
	
	/* Release memory to avoid memory leaks caused by closures, fix hanging audio/video threads and force open sockets/NetConnections to disconnect (Internet Explorer only)
	*/
	var cleanup = function() {
		if (ua.ie && ua.win) {
			window.attachEvent("onunload", function() {
				// remove listeners to avoid memory leaks
				var ll = listenersArr.length;
				for (var i = 0; i < ll; i++) {
					listenersArr[i][0].detachEvent(listenersArr[i][1], listenersArr[i][2]);
				}
				// cleanup dynamically embedded objects to fix audio/video threads and force open sockets and NetConnections to disconnect
				var il = objIdArr.length;
				for (var j = 0; j < il; j++) {
					removeSWF(objIdArr[j]);
				}
				// cleanup library's main closures to avoid memory leaks
				for (var k in ua) {
					ua[k] = null;
				}
				ua = null;
				for (var l in swfobject) {
					swfobject[l] = null;
				}
				swfobject = null;
			});
		}
	}();
	
	return {
		/* Public API
			- Reference: http://code.google.com/p/swfobject/wiki/documentation
		*/ 
		registerObject: function(objectIdStr, swfVersionStr, xiSwfUrlStr, callbackFn) {
			if (ua.w3 && objectIdStr && swfVersionStr) {
				var regObj = {};
				regObj.id = objectIdStr;
				regObj.swfVersion = swfVersionStr;
				regObj.expressInstall = xiSwfUrlStr;
				regObj.callbackFn = callbackFn;
				regObjArr[regObjArr.length] = regObj;
				setVisibility(objectIdStr, false);
			}
			else if (callbackFn) {
				callbackFn({success:false, id:objectIdStr});
			}
		},
		
		getObjectById: function(objectIdStr) {
			if (ua.w3) {
				return getObjectById(objectIdStr);
			}
		},
		
		embedSWF: function(swfUrlStr, replaceElemIdStr, widthStr, heightStr, swfVersionStr, xiSwfUrlStr, flashvarsObj, parObj, attObj, callbackFn) {
			var callbackObj = {success:false, id:replaceElemIdStr};
			if (ua.w3 && !(ua.wk && ua.wk < 312) && swfUrlStr && replaceElemIdStr && widthStr && heightStr && swfVersionStr) {
				setVisibility(replaceElemIdStr, false);
				addDomLoadEvent(function() {
					widthStr += ""; // auto-convert to string
					heightStr += "";
					var att = {};
					if (attObj && typeof attObj === OBJECT) {
						for (var i in attObj) { // copy object to avoid the use of references, because web authors often reuse attObj for multiple SWFs
							att[i] = attObj[i];
						}
					}
					att.data = swfUrlStr;
					att.width = widthStr;
					att.height = heightStr;
					var par = {}; 
					if (parObj && typeof parObj === OBJECT) {
						for (var j in parObj) { // copy object to avoid the use of references, because web authors often reuse parObj for multiple SWFs
							par[j] = parObj[j];
						}
					}
					if (flashvarsObj && typeof flashvarsObj === OBJECT) {
						for (var k in flashvarsObj) { // copy object to avoid the use of references, because web authors often reuse flashvarsObj for multiple SWFs
							if (typeof par.flashvars != UNDEF) {
								par.flashvars += "&" + k + "=" + flashvarsObj[k];
							}
							else {
								par.flashvars = k + "=" + flashvarsObj[k];
							}
						}
					}
					if (hasPlayerVersion(swfVersionStr)) { // create SWF
						var obj = createSWF(att, par, replaceElemIdStr);
						if (att.id == replaceElemIdStr) {
							setVisibility(replaceElemIdStr, true);
						}
						callbackObj.success = true;
						callbackObj.ref = obj;
					}
					else if (xiSwfUrlStr && canExpressInstall()) { // show Adobe Express Install
						att.data = xiSwfUrlStr;
						showExpressInstall(att, par, replaceElemIdStr, callbackFn);
						return;
					}
					else { // show alternative content
						setVisibility(replaceElemIdStr, true);
					}
					if (callbackFn) { callbackFn(callbackObj); }
				});
			}
			else if (callbackFn) { callbackFn(callbackObj);	}
		},
		
		switchOffAutoHideShow: function() {
			autoHideShow = false;
		},
		
		ua: ua,
		
		getFlashPlayerVersion: function() {
			return { major:ua.pv[0], minor:ua.pv[1], release:ua.pv[2] };
		},
		
		hasFlashPlayerVersion: hasPlayerVersion,
		
		createSWF: function(attObj, parObj, replaceElemIdStr) {
			if (ua.w3) {
				return createSWF(attObj, parObj, replaceElemIdStr);
			}
			else {
				return undefined;
			}
		},
		
		showExpressInstall: function(att, par, replaceElemIdStr, callbackFn) {
			if (ua.w3 && canExpressInstall()) {
				showExpressInstall(att, par, replaceElemIdStr, callbackFn);
			}
		},
		
		removeSWF: function(objElemIdStr) {
			if (ua.w3) {
				removeSWF(objElemIdStr);
			}
		},
		
		createCSS: function(selStr, declStr, mediaStr, newStyleBoolean) {
			if (ua.w3) {
				createCSS(selStr, declStr, mediaStr, newStyleBoolean);
			}
		},
		
		addDomLoadEvent: addDomLoadEvent,
		
		addLoadEvent: addLoadEvent,
		
		getQueryParamValue: function(param) {
			var q = doc.location.search || doc.location.hash;
			if (q) {
				if (/\?/.test(q)) { q = q.split("?")[1]; } // strip question mark
				if (param == null) {
					return urlEncodeIfNecessary(q);
				}
				var pairs = q.split("&");
				for (var i = 0; i < pairs.length; i++) {
					if (pairs[i].substring(0, pairs[i].indexOf("=")) == param) {
						return urlEncodeIfNecessary(pairs[i].substring((pairs[i].indexOf("=") + 1)));
					}
				}
			}
			return "";
		},
		
		// For internal usage only
		expressInstallCallback: function() {
			if (isExpressInstallActive) {
				var obj = getElementById(EXPRESS_INSTALL_ID);
				if (obj && storedAltContent) {
					obj.parentNode.replaceChild(storedAltContent, obj);
					if (storedAltContentId) {
						setVisibility(storedAltContentId, true);
						if (ua.ie && ua.win) { storedAltContent.style.display = "block"; }
					}
					if (storedCallbackFn) { storedCallbackFn(storedCallbackObj); }
				}
				isExpressInstallActive = false;
			} 
		}
	};
}();
/*
 * @description 贵族体系
 * @auth Young
 */

(function (__noble, window) {

  //获取贵族道具信息
  var tmpDialogGetProps = ["<div class='noble-d-prop'>",
    "<div class='noble-d-prop_left'>",
      "<img src='" + window.CDN_HOST + "/flash/" + window.PUBLISH_VERSION + "/image/gift_material/#{gid}.png'/>",
      "</div>",
    "<div class='noble-d-prop_right'>",
    "<h2>#{title}</h2>",
    "<p>获取资格：#{level_title}</p>",
    "<p>获取方式：贵族坐骑，顾名思义是贵族的象征。当你开通#{level_title}后，该坐骑就会随着#{level_title}一起出现。</p>",
    "<p>坐骑描述：#{desc}</p>",
    "</div>",
    "</div>"].join("");

  __noble = function () {

    /**
     * @description 构造函数
     * @author young
     * @return null
     */

    this.currentGid = 30;
    this.roomId = 0;

    this.init = function () {
      Noble.initChargeDialog();
    };

    this.getCurrentGid = function () {
      return this.currentGid;
    };

    this.setCurrentGid = function (gid) {
      this.currentGid = gid;
    }

    this.getRoomId = function () {
      return this.roomId;
    }

    this.setRoomId = function (roomId) {
      this.roomId = roomId;
    }

    this.init();

  }

  /**
   * @author Young
   * @description 静态方法
   * @return null
   */
  $.extend(__noble, {

    //最新dialog对象
    currentChargeDialog: null,
    //实例对象
    ins: null,
    /**
     * 获取坐骑
     * @param  {json} data 坐骑id
     * @param  {function} func 请求成功后回调
     * @return {[type]}      [description]
     */
    getProp: function (data, func) {
      $.ajax({
        url: "/getvipmount",
        type: "POST",
        dataType: "json",

        //data: {
        //    mid: int[坐骑id]
        //}
        data: data,

        success: function (data) {
          if (func) {
            func(data)
          }
          ;
        },

        error: function () {
          Utility.log("get vip mount error!");
        }
      });
    },

    /**
     * 转换接口输出的数据
     * @auth Yvan
     * @param infoCell
     * @returns {*|Array}
     */
    changeNobleData: function (infoCell) {
      infoCell = infoCell || [];
      if (!infoCell.length) {
        var infoPermission = infoCell['permission'],
            infoSystem = infoCell['system'];

        //贵族等级icon
        infoCell['level_id_icon'] = '<span class="hotListImg basicLevel' + infoCell['level_id'] + '"></span>';

        //赠送爵位跟礼包钱
        for (var k in infoSystem) {
          if (k == "keep_level") {
            infoSystem[k] = infoSystem[k] + "钻";
          }
          else {
            infoSystem[k] = k == "gift_level" ? '<span class="hotListImg basicLevel' + infoSystem[k] + '"></span>' : infoSystem[k] + '<span class="noble-table-diamond"></span>';
          }
        }

        //是否限制访问房间
        infoPermission['allowvisitroom'] = infoPermission['allowvisitroom'] == 0 ? '不受限' : '限制';

        //是否允许修改昵称
        switch (infoPermission['modnickname'].toString()) {
          case '-1' :
            infoPermission['modnickname'] = '不受限';
            break;
          case '0' :
            infoPermission['modnickname'] = '限制';
            break;
          default :
            var mnnArr = infoPermission['modnickname'].split("|"),
                type = "";
            if (mnnArr[1] == 'month') {
              type = "月";
            }
            else if (mnnArr[1] == 'week') {
              type = "周";
            }
            else if (mnnArr[1] == 'year') {
              type = "年";
            }
            infoPermission['modnickname'] = mnnArr[0] + "次/" + type;
        }

        //是否有进房欢迎语
        infoPermission['haswelcome'] = infoPermission['haswelcome'] == 0 ? '无' : '有';

        //聊天文字时间限制
        infoPermission['chatsecond'] = infoPermission['chatsecond'] == 0 ? '' : infoPermission['chatsecond'] + '/次';

        //是否有聊天特效
        infoPermission['haschateffect'] = infoPermission['haschateffect'] == 0 ? '无' : '有';

        //聊天文字长度限制
        switch (infoPermission['chatlimit'].toString()) {
          case '-1' :
            infoPermission['chatlimit'] = '不限制';
            break;
          case '0' :
            infoPermission['chatlimit'] = '禁言';
            break;
          default :
            infoPermission['chatlimit'] = infoPermission['chatlimit'] + '字';
        }

        //房间是否有贵宾席
        infoPermission['hasvipseat'] = infoPermission['hasvipseat'] == 0 ? '无' : '有';

        //防止被禁言
        switch (infoPermission['nochat'].toString()) {
          case '1' :
            infoPermission['nochat'] = '防止房主';
            break;
          case '0' :
            infoPermission['nochat'] = '无';
            break;
          case '2' :
            infoPermission['nochat'] = '防止管理员';
            break;
          case '1|2' :
            infoPermission['nochat'] = '防止房主、管理员';
            break;
          default :
        }

        //禁言别人的权限
        infoPermission['nochatlimit'] = infoPermission['nochatlimit'] == 0 ? '无' : infoPermission['nochatlimit'] + '普通用户/天';

        //防被踢
        switch (infoPermission['avoidout'].toString()) {
          case '1' :
            infoPermission['avoidout'] = '防房主';
            break;
          case '0' :
            infoPermission['avoidout'] = '无';
            break;
          case '2' :
            infoPermission['avoidout'] = '防止管理员';
            break;
          case '1|2' :
            infoPermission['avoidout'] = '防止房主、管理员';
            break;
          default :
        }

        //踢人的权限
        infoPermission['letout'] = infoPermission['letout'] == 0 ? '无' : infoPermission['letout'] + '普通用户/天';

        //是否允许隐身
        infoPermission['allowstealth'] = infoPermission['allowstealth'] == 0 ? '无' : '有';
      }
      return infoCell;
    },
    /**
     * 贵族充值
     * @param data: func:回调 data:[传入ajax 含有两个参数，参数gid为贵族等级id，roomid为房间id，如果roomid为空则不给佣金]
     * @return null [description]
     */
    chargeNoble: function (data) {

      var that = this;

      $.ajax({
        url: "/openvip",
        type: 'get',
        dataType: "jsonp",
        jsonp: "callback",
        jsonpCallback: "cb",

        // data: {
        // 	gid: "", groupid [群组id]
        // 	roomid: "" 如果在房间内开通[房间id]
        // },

        data: data,

        success: function (res) {

          that.currentChargeDialog.close();

          switch (res.code) {
            case 0:

              //开通成功后的前置方法
              that.chargeNoblePreSuccessCB(res);

              $.tips("贵族开通成功！您现在就可以使用您的专属坐骑啦！", function () {

                //开通贵族成功后，点击成功按钮后的回调
                that.chargeNobleSuccessCB(res);

              });
              break;

            case 102:

              $.dialog({
                title:"提示",
                content:"您的钻石不足, 充值即可开通贵族喔!",
                okValue:"立即充值",
                ok:function () {
                  location.href = "/charge/order"
                }
              }).show();
              break;

            default:
              $.tips(res.msg);
              break;

          }
        },

        error: function () {
          Utility.log("charge noble error!");
          that.chargeNobleErrorCB();
        }

      });

    },

    //充值回调
    chargeNobleSuccessCB: function (res) {
      //todo
    },

    //充值成功前回调
    chargeNoblePreSuccessCB: function (res) {
      //todo
    },

    //充值错误回调
    chargeNobleErrorCB: function () {

    },
    //初始化弹窗
    initChargeDialog: function () {
      var that = this;

      var tmpDialogChargeNoble = ["<div class='noble-d-charge'>",
        "<ul class='noble-d_menu clearfix'></ul>",

        "<div class='noble-d_main'></div>",
        "</div>"].join("");

      //init
      that.currentChargeDialog = $.dialog({
        title: "开通贵族",
        content: tmpDialogChargeNoble,
        onshow: function () {

          var gid = that.ins.getCurrentGid();

          //弹窗list
          that.appendNobleDialogList(gid);

          //绑定tab事件
          that.bindNobleSwitchEvent();

        }

      });
    },

    //添加开通贵族弹窗列表
    appendNobleDialogList: function (gid) {
      var that = this;
      var cgid = 0;

      that.getNobleAllInfo(function (data) {

        var tmp = "";
        var info = data.info;

        $.each(info, function (i, e) {
          tmp = tmp + "<li class='noble-d_tab' data-gid='" + info[i].gid + "'><span class='hotListImg basicLevel" + info[i].level_id + "'></span></li>";
        });

        var $tmp = $(tmp);
        $tmp.eq(0).addClass("active");
        $(".noble-d_menu").html($tmp);

        //触发初始化的tab
        $(".noble-d_menu").find(".noble-d_tab").filter("[data-gid=" + gid + "]").trigger("click");
      });

    },

    //绑定充值弹窗事件
    showChargeDialog: function () {

      //show
      this.currentChargeDialog.show();

    },

    //获取道具信息
    getPropInfo: function ($target) {

      return {
        "gid": $target.data("gid"),
        "title": $target.data("title"), //坐骑名字
        "level_title": $target.data("lvtitle"), //贵族等级
        "desc": $target.data("desc") //坐骑描述
      }

    },

    //绑定获取道具事件
    showGetPropsDialog: function ($target, func) {

      var that = this;

      //dialog模板
      var tmp = Utility.template(tmpDialogGetProps, that.getPropInfo($target));

      var dialogGetProp = $.dialog({
        title: "贵族专属",
        content: tmp,
        onshow: function () {

        },

        ok: function () {
          dialogGetProp.close();

          if (func) {
            func()
          }
          ;

        },
        okValue: "开通贵族身份"
      }).show();

    },

    /**
     * 通过id获取贵族信息
     * @param { int } gid 贵族id
     * @param  { function } successCB 成功后的回调
     * @return null
     */
    getNobleInfo: function (successCB) {

      var that = this;

      $.ajax({
        url: "/getgroup",
        type: 'get',
        dataType: "jsonp",
        jsonp: "callback",
        jsonpCallback: "cb",

        data: {"gid": that.ins.getCurrentGid()},

        success: function (res) {
          //Utility.log(res);
          if (res.code == 0) {
            if (successCB && res.info) {
              successCB(res.info);
            }
            ;
          } else {
            $.tips(res.msg);
          }
          ;
        },

        error: function () {
          Utility.log("get noble group info failure.");
        }

      });

    },

    /**
     * 获取贵族所有信息
     * @return {[type]} [description]
     */
    getNobleAllInfo: function (successCB) {
      $.ajax({
        url: '/getgroupall',
        type: 'get',
        dataType: "jsonp",
        jsonp: "callback",
        jsonpCallback: "cb",
        data: "",
        success: function (data) {
          if (!data.code) {
            if (successCB) {
              successCB(data);
            }
            ;
          } else {
            Utility.log(data.msg);
          }
          ;
        },
        error: function () {
          Utility.log("ajax request error");
        }
      });
    },

    /**
     * 更新贵族弹窗字符串
     * @param  {json} data 数据输入对象
     * @return {string} 返回数据模板
     */
    flushNobleInfo: function (data) {

      data.nobleLink = "/noble";

      var tmp = ["<div class='noble-d-charge_head'>",
        "<h2>" + data.level_name + "</h2>",
        "</div>",
        "<div class='noble-d-charge_content'>",
        "<table>",
        "<tr><td>首开礼包：</td><td>" + data.system.gift_money + "</td><td>改名限制：</td><td>" + data.permission.modnickname + "</td></tr>",
        //"<tr><td>赠送爵位：</td><td>" + data.system.gift_level + "</td><td>房间特效欢迎语：</td><td>" + data.permission.haswelcome + "</td></tr>",
        "<tr><td>坐骑：</td><td>" + data.g_mount.name + "</td><td>房间特效欢迎语：</td><td>" + data.permission.haswelcome + "</td></tr>",
        "<tr><td>贵族标识：</td><td>" + data.level_id_icon + "</td><td>聊天特效：</td><td>" + data.permission.haschateffect + "</td></tr>",
        "<tr><td>房间限制：</td><td>" + data.permission.allowvisitroom + "</td><td>发送文字特权：</td><td>" + data.permission.chatlimit + "</td></tr>",
        "<tr><td>贵宾席位置：</td><td>" + data.permission.hasvipseat + "</td><td>隐身人：</td><td>" + data.permission.allowstealth + "</td></tr>",
        "</table>",
        "<h3>开通价格：" + data.system.open_money + "</h3>",
        "<p>次月保级条件：贵族等级有效期内，累计充值达" + data.system.keep_level + "。</p>",
        "</div>",
        "<div class='noble-d-charge_btnbox'>",
        "<button class='noble-d_charge_btn btn btn-center' data-gid='" + data.gid + "'>立即开通贵族</button>",
        "<a href='" + data.nobleLink + "' target='_blank' class='noble-d-link'>了解更多</a>",
        "</div>"].join("");

      $(".noble-d_main").html(tmp);

    },

    /**
     * 绑定获取贵族信息事件
     * @return {null} [description]
     */
    bindNobleSwitchEvent: function () {

      var that = this;

      $(".noble-d_menu").on("click", ".noble-d_tab", function () {
        var $this = $(this);

        that.ins.setCurrentGid($this.data("gid"));

        $this.siblings(".noble-d_tab").removeClass("active").end().addClass("active");

        //刷新贵族信息
        that.getNobleInfo(function (data) {

          that.bindNobleDialogEvent(data);

        });

      });
    },

    /**
     * 绑定开通贵族弹窗
     */
    bindNobleDialogEvent: function (data) {
      var that = this;
      //转换数据
      data = that.changeNobleData(data);
      //刷新信息
      that.flushNobleInfo(data);

      //点击充值按钮
      $(".noble-d_charge_btn").on("click", function (e) {

        //disabled
        $(this).prop("disabled", true);

        var gid = $(this).data("gid");
        //调用开通贵族接口
        that.chargeNoble({
          "gid": gid,
          "roomId": window.ROOMID
        });

        that.chargeNobleErrorCB = function () {
          $(this).prop("disabled", false);
        }

      });
    }


  });

  window.Noble = __noble;

})(typeof Noble !== "undefined" ? Noble : {}, window);
$(function(){

	//初始化
	initFlash();

	window.Fla = {};

	//Fla开头用于flash内部方法调用
	Fla.showNobleDialog = function(rid){
	      //未登录
        if(!User.isLogin()){
            //是否在第三方平台
            if(!window.OpenAPI.link){
              showLoginDialog(function(){
                location.href="/"
              });
            }else{
              showLoginDialog(function() {
                history.go(-1);
              });
            }
        }else{
            var nb = new Noble();

            Noble.ins = nb;

            Noble.ins.setRoomId(rid);

            //调用开通成功后的前置方法
            Noble.chargeNoblePreSuccessCB = function (json) {
                var str = "";
                // for( var a in json.data ){
                // 	str = str + json.data[a] + ",";
                // }
                str = json.data.roomid + "," + json.data.uid + "," + json.data.name + "," + json.data.vip + "," + json.data.cashback;

                document.getElementById("videoRoom").openVipSuccess(str);
            };

            //开通成功后的后置方法
            Noble.chargeNobleSuccessCB = function (json) {
                location.reload();
            }

            Noble.showChargeDialog();
        }
	}

    //邮箱检查
    //checkSafeEmail();

});

function showLoginDialog(cancelFunc) {
  $.dialog({
    title: "提示",
    content: "请先登录后再开通贵族",
    okValue: "去登陆",
    ok: function(){
      User.showLoginDialog();
    },
    cancelValue: "回到首页",
    cancel: function(){
      if(cancelFunc){
        cancelFunc();
      }
    }
  }).show();
}

//安全邮箱检查
function checkSafeEmail(){
    var mailCheck=window.SAFE_MAIL_STATE;
    //拼接弹窗的内容
    var checkSafeMail="<div class='mail-check-live'>"+
        "<table class='needCheck'>" +
        "<tbody>" +
        "<tr >" +
        "<td colspan='2'>安全访问条件:</td>"+
        "</tr>"+
        "<tr class='mail-check-status'>" +
        "<td>邮箱验证</td>"+
        "<td class='needCheck-item '><span class='needCheck-item-yes' >是</span></td>"+
        "</tr>"+
        "<tr>" +
        "<td>账户余额</td>"+
        "<td class='needCheck-item'>"+mailCheck.in_limit_points+"钻</td>"+
        "</tr>"+
        "</tbody>"+
        "</table>"+
        "<table class='nowCheck'>" +
        "<tbody>" +
        "<tr>" +
        "<td colspan='2'>您的当前状态:</td>"+
        "</tr>"+
        "<tr class='mail-check-status'>" +
        "<td>邮箱验证</td>"+
        "<td class='needCheck-item nowCheck-item'></td>"+
        "</tr>"+
        "<tr>" +
        "<td>账户余额</td>"+
        "<td class='needCheck-item'>"+mailCheck.points+"钻</td>"+
        "</tr>"+
        "</tbody>"+
        "</table>"+
        "<div class='mail-check-live-level'>" +
        "<span></span>"+
        "<span>当前账号安全级别:</span>"+
        "<span class='mail-check-live-level-text'></span>"+
        "<div class='p-bar'><span class='progress'></span></div>"+
        "</div>"+
        "<div class='mail-warn'>" +
        "账号提醒：您的账户"+
        "<span class='mail-warn-points'>低于"+mailCheck.in_limit_points+"钻,</span>"+
        "存在安全危机，请充值或验证邮箱提高账户安全度" +
        "</div>" +
        "</div>";
    //调用dialog
    var mailSafeDialog = $.dialog({
        title: "账号安全提醒",
        content: checkSafeMail,
        okValue: "验证邮箱",
        closeButtonDisplay: true,
        ok: function(){
            //跳转邮箱验证路径
            location.href = "/mailverific";
        },
        cancelValue: "充值",
        cancel: function(){
            showPay();
        }
    });

    //如果是游客，无弹窗
    if(mailCheck.roled=== "" ){
        mailSafeDialog.remove();
    }else{
        mailSafeDialog.show();
    }
    //判断用户当前状态是否验证邮箱，如果是，则显示“是”，否则显示“否”
    if( mailCheck.safemail === "" ){
        $(".nowCheck-item").html("否")
    }else{
        $(".nowCheck-item").html("是")
    }

    //判断用户账号级别
    var checkNumber=0;//0：账号级别低；1：账号级别中；2：账号级别高

    var needPoints=parseInt(mailCheck.in_limit_points);

    var nowPoints=parseInt(mailCheck.points);

    if( mailCheck.in_limit_safemail === "0" ){
        //如果in_limit_safemail===0，则不进行邮箱验证，只验证钻石数
        $(".mail-check-status").remove();

        if( needPoints > nowPoints ){
            $('.mail-warn-points').show();
            checkNumber=0;//如果用户钻石数低于要求钻石数，则账号安全级别为低
        }else{
            checkNumber=2;//如果用户钻石数低于要求钻石数，则账号安全级别为高
        }
    }else{
        //如果in_limit_safemail===1，则需要同时进行邮箱跟钻石数的验证
        if(needPoints>nowPoints){//如果用户钻石数低于要求钻石数

            //提示中出现钻石数
            $('.mail-warn-points').show();

            if(mailCheck.safemail ===""){
                checkNumber=0;

            }else{
                checkNumber=1;
            }
        }else{
            if( mailCheck.safemail === "" ){
                checkNumber=1;
                if(  mailCheck.new_user === "0" ){//老用户
                    setTimeout(function () {//钻石够，显示10s消失
                        mailSafeDialog.remove();
                    },10000);
                }else{
                    mailSafeDialog.show();
                }
            }else{
                checkNumber=2;
            }
        }
    }
    switch (checkNumber){
        case 0:
            $(".progress").addClass("progress-l");
            $(".mail-check-live-level-text").html("低").css("color","red");
            break;
        case 1:
            $(".progress").addClass("progress-m");
            $(".mail-check-live-level-text").html("中").css("color","#ECB43A");
            break;
        default:
            mailSafeDialog.remove();
    }
}
function initFlash() {

	var swfVersionStr = "11.1.0";
	var xiSwfUrlStr = "playerProductInstall.swf";
	//  var _flashVars={"room_data":"${resultMd5}"}

	var params = {};
	params.quality = "high";
	params.bgcolor = "#000";
	params.wmode = "window";
	//params.wmode="window";
	params.allowscriptaccess = "always";
	params.allowfullscreen = "true";

	var attributes = {};
	attributes.id = "videoRoom";
	attributes.name = "videoRoom";
	attributes.align = "middle";

	//flash初始化失败
  var initContent = "抱歉, Flash无法显示, 可能是您没有安装flash插件或flash版本过低.<a style='color:#247dda' target='_blank' href='http://www.adobe.com/go/getflashplayer'>立即获取最新flash</a><br/>完成安装后, 请关闭浏览器, 重新进入直播间即可观看.";

  document.getElementById("flashContent").innerHTML = initContent;

  //flash初始化
	swfobject.embedSWF(window._flashVars.httpRes + "videoRoom.swf", "flashContent", "100%", "100%", swfVersionStr, xiSwfUrlStr, window._flashVars, params, attributes);
}

var gserver = window.location.protocol + "//" + window.location.hostname;

//获取url参数中的rid
function getRid() {
	var url = window.location.href.replace(/[><'"]/g, "");
	var paraString = url.substring(url.indexOf("?") + 1, url.length).split("&");
	var paraObj = {};
	var i;
	var j;

	for (i = 0; j = paraString[i]; i++) {
		paraObj[j.substring(0, j.indexOf("=")).toLowerCase()] = j.substring(j.indexOf("=") + 1, j.length);
	}

	var returnValue = paraObj["rid"];

	if (typeof(returnValue) == "undefined") {
		return "";
	} else {
		return returnValue;
	}

}

//获取用户PHPSESSID
function getUserKey() {
	var cookieVal = window.document.cookie;
	var user_key = "";
	//alert(cookieVal);
	if (cookieVal != undefined) {
		var cookies = cookieVal.split(";");
		for (var i = 0; i < cookies.length; i++) {
			var cookieOne = cookies[i];
			var pos = cookieOne.indexOf("=");
			if (pos > -1) {
				var key = cookieOne.substring(0, pos);
				key = key.replace(/^\s+|\s+$/g, "");
				var value = cookieOne.substring(pos + 1);
				value = value.replace(/^\s+|\s+$/g, "");
				if (key == "PHPSESSID") {
					user_key = value;
				}
			}
		}
	}
	return user_key;
}

//获取room key
function getRoomKey() {
	var cookie = window.document.cookie, t = "";
	if (void 0 != cookie) {
		for (var cookieSpl = cookie.split(";"), i = 0; i < cookieSpl.length; i++) {
			var o = cookieSpl[i], r = o.indexOf("=");
			if (r > -1) {
				var key = o.substring(0, r);
				key = key.replace(/^\s+|\s+$/g, "");
				var value = o.substring(r + 1);
				value = value.replace(/^\s+|\s+$/g, "").replace(/\"/g, ""), "room_host" == key && (t = value);
			}
		}
	}
	return t;
}


//获取flash
function getSWF(movieName) {
	if (navigator.appName.indexOf("Microsoft") != -1) {
		return window[movieName];
	} else {
		return document[movieName];
	}
}

function showUserCenter() {//个人中心
	if( $.isEmptyObject(window.OpenAPI.link) ){
    window.open(gserver + "/member/index", "_blank")
	}else {
    window.open(window.OpenAPI.link.usercenter, "_blank");
	}

}

function gohall() {//大厅
  if( $.isEmptyObject(window.OpenAPI.link) ){
    window.open(gserver + "/", "_blank");
  }else{
    window.open(window.OpenAPI.link.hall, "_blank");
  }

}
function gomarket() {//商场
	if( $.isEmptyObject(window.OpenAPI.link) ){
    window.open(gserver + "/shop", "_blank")
	}else {
    window.open(window.OpenAPI.link.shop, "_blank");
	}
}

function uattention() {//我的关注
	window.open(gserver + "/member/attention", "_blank")
}

function uprops() {//道具
	window.open(gserver + "/member/scene", "_blank")
}

function uconsRecords() {//消费记录
	window.open(gserver + "/member/consumerd", "_blank")
}

function userMsg() {//私信
	window.open(gserver + "/member/msglist/2", "_blank")
}

function systemMsg() {//系统消息
  if($.isEmptyObject(window.OpenAPI.link)){
    window.open(gserver + "/member/msglist/1", "_blank")
  }else{
    window.open(window.OpenAPI.link.msg, "_blank");
  }
}

function showPay() { //提示充值
	if($.isEmptyObject(window.OpenAPI.link)){
        window.open(gserver + "/charge/order", "_blank");
	}else{
        window.open(window.OpenAPI.link.pay, "_blank");
	}

}

function showReg(){
    //跳到注册页面
    //window.open(gserver +"/op/index.php","_blank")
    User.showRegDialog();
}
function showLogin(){
    //跳到登录页面
    User.showLoginDialog();
}

//退出登录
function showLogout() {
    window.location.href = gserver + "/logout";
}

function callEmailFun(){
    if($.isEmptyObject(window.OpenAPI.link)){
      window.open(gserver + "/member/msglist/1", "_blank")
    }else{
      window.open(window.OpenAPI.link.msg, "_blank");
    }
}

function gotoRoom(_rid) {//跳转到指定房间
	window.top.location.href = window._flashVars.httpDomain + "/" + _rid;
}

function reportVideo(_uid) {//举报
	alert("已经举报了")
}

//用户断开刷新或者跳转操作关闭rtmp
window.onbeforeunload = function () {
	getSWF("videoRoom").closeRtmp();
	console.log("on before unload");
}