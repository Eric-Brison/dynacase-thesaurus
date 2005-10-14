var isNetscape = navigator.appName=="Netscape";

function viewornot(id) {
  var o=document.getElementById(id);
  if (o) {
    if (o.style.display=='none') o.style.display='';
    else o.style.display='none';
  }
}

// serach element in array
// return index found (-1 if not)
function array_search(elt,ar) {
  for (var i=0;i<ar.length;i++) {
    if (ar[i]==elt) return i;
  }
  return -1;
}

// only for mozilla
function moz_unfade(dvid) {
  var f;
  var dv=document.getElementById(dvid);  
  if (dv && dv.style.MozOpacity) {
    f=parseFloat(dv.style.MozOpacity);
    if (f < 1) {
      dv.style.MozOpacity=f+0.02;
      
      setTimeout('moz_unfade(\''+dvid+'\')',10);
    } 
  }
}




// return value in computed style
// o : the node HTML Object
// attribute name (marginLeft, top, backgroundColor)
function getCssStyle(o,a) {
  var result = 0;
  var sa='';
  var j=0;
  
  if (document.defaultView) {
    var style = document.defaultView;
    var cssDecl = style.getComputedStyle(o, "");
    for (var i=0;i<a.length;i++) {
	  if (a[i]<='Z') {
	    sa+='-';
	    sa+=a[i].toLowerCase();
	  } else {
	    sa+=a[i];
	  }

    } 
    result = cssDecl.getPropertyValue(sa);
  } else if (o.currentStyle) {
    result = o.currentStyle[a];
  } 
  return result;
}
    	
function copy_clip(meintext)
{

 if (window.clipboardData) 
   {
   
   // the IE-manier
   window.clipboardData.setData("Text", meintext);
   alert('copy :'+meintext);
   // waarschijnlijk niet de beste manier om Moz/NS te detecteren;
   // het is mij echter onbekend vanaf welke versie dit precies werkt:
   }
   else if (window.netscape) 
   { 
   
   // dit is belangrijk maar staat nergens duidelijk vermeld:
   // you have to sign the code to enable this, or see notes below 
   netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');
   
   // maak een interface naar het clipboard
   var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
   if (!clip) return;
   
   // maak een transferable
   var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
   if (!trans) return;
   
   // specificeer wat voor soort data we op willen halen; text in dit geval
   trans.addDataFlavor('text/unicode');
   
   // om de data uit de transferable te halen hebben we 2 nieuwe objecten nodig   om het in op te slaan
   var str = new Object();
   var len = new Object();
   
   var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
   
   var copytext=meintext;
   
   str.data=copytext;
   
   trans.setTransferData("text/unicode",str,copytext.length*2);
   
   var clipid=Components.interfaces.nsIClipboard;
   
   if (!clip) return false;
   
   clip.setData(trans,null,clipid.kGlobalClipboard);
   
   }
   alert("Following info was copied to your clipboard:\n\n" + meintext);
   return false;
}

function shiftPushed(event) {  
  if (!event) event=window.event;
  return event.shiftKey;
}
function altPushed(event) {  
  if (!event) event=window.event;
  return event.altKey;
}
function ctrlPushed(event) {  
  if (!event) event=window.event;
  return event.ctrlKey;
}


function trackMenuKey(event)
{
  var intKeyCode;

  if (!event) event=window.event;
  if (isNetscape) {
    intKeyCode = event.keyCode;
    altKey = event.altKey
    ctrlKey = event.ctrlKey
   }  else {
    intKeyCode = window.event.keyCode;
    altKey = window.event.altKey;
    ctrlKey = window.event.ctrlKey
   }
  window.status=intKeyCode + ':'+ event.which +':'+altKey+ ':'+ctrlKey;

  if (((intKeyCode ==  93))) {
    // Ctrl-Menu
    openMenu(event,'popupcard',1);
    
    if (event.stopPropagation) event.stopPropagation();
    else event.cancelBubble=true;
    if (event.preventDefault) event.preventDefault();
    else event.returnValue=true;
    return false;
  }
  return true;
}

// JScript source code
//Red : 0..255
//Green : 0..255
//Blue : 0..255
//Hue : 0,0..360,0<=>0..255
//Lum : 0,0..1,0<=>0..255
//Sat : 0,0..1,0<=>0..255

//Retourne un tableau de 3 valeurs : H,S,L
function RGB2HSL (r, g, b)
{
  red = Math.round (r);
  green = Math.round (g);
  blue = Math.round (b);
  var minval = Math.min (red, Math.min (green, blue));
  var maxval = Math.max (red, Math.max (green, blue));
  var mdiff = maxval - minval + 0.0;
  var msum = maxval + minval + 0.0;
  var luminance = msum / 510.0;
  var saturation;
  var hue;
  if (maxval == minval)
  {
    saturation = 0.0;
    hue = 0.0;
  }
  else
  {
    var rnorm = (maxval - red) / mdiff;
    var gnorm = (maxval - green) / mdiff;
    var bnorm = (maxval - blue) / mdiff;
    saturation = (luminance <= 0.5) ? (mdiff / msum) : (mdiff / (510.0 - msum));
    if (red == maxval)
      hue = 60.0 * (6.0 + bnorm - gnorm);
    if (green == maxval)
      hue = 60.0 * (2.0 + rnorm - bnorm);
    if (blue == maxval)
      hue = 60.0 * (4.0 + gnorm - rnorm);
    if (hue > 360.0)
      hue -= 360.0;
  }
  return new Array (Math.round (hue * 255.0 / 360.0), Math.round (saturation * 255.0), Math.round (luminance * 255.0));
}

function ColorMagic (rm1, rm2, rh)
{
  var retval = rm1;
  if (rh > 360.0)
    rh -= 360.0;
  if (rh < 0.0)
    rh += 360.0;
  if (rh < 60.0)
    retval = rm1 + (rm2 - rm1) * rh / 60.0;
  else if (rh < 180.0)
    retval = rm2;
  else if (rh < 240.0)
    retval = rm1 + (rm2 - rm1) * (240.0 - rh) / 60.0;
  return Math.round (retval * 255);
}

//Retourne un tableau de 3 valeurs : R,G,B
function HSL2RGB (h, s, l)
{
  var hue = h * 360.0 / 255.0;
  var saturation = s / 255.0;
  var luminance = l / 255.0;
  var red;
  var green;
  var blue;
  if (saturation == 0.0)
  {
    red = green = blue = Math.round (luminance * 255.0);
  }
  else
  {
    var rm1;
    var rm2;
    if (luminance <= 0.5)
      rm2 = luminance + luminance * saturation;
    else
      rm2 = luminance + saturation - luminance * saturation;
    rm1 = 2.0 * luminance - rm2;
    red = ColorMagic (rm1, rm2, hue + 120.0);
    green = ColorMagic (rm1, rm2, hue);
    blue = ColorMagic (rm1, rm2, hue - 120.0);
  }
  return new Array (red, green, blue);
}


function getAltern(c,l) {

  var ot=document.getElementById('terriblecolor');
  var r,g,b;
  var validcolor=false;
  var rgb;

  if (c.substr(0,1) == "#") {
    r=parseInt('0x'+c.substr(1,2));
    g=parseInt('0x'+c.substr(3,2));
    b=parseInt('0x'+c.substr(5,2));
    validcolor=true;    
  } else {

    if (!ot) {
      ot=document.createElement('span');
      ot.id='terriblecolor';
    }
    ot.style.backgroundColor=c;
    rgb=getCssStyle(ot,'backgroundColor');
    if (rgb && rgb.substr(0,3)=="rgb") {  
      trgb=rgb.substr(4,rgb.length-5).split(',');
      r=parseInt(trgb[0]);
      g=parseInt(trgb[1]);
      b=parseInt(trgb[2]);
      validcolor=true;    
    }
  }
  if (validcolor) {
    hsl=RGB2HSL (r, g, b);
    if (hsl[0] < 128) l=l-200; // dark color
    // trgb=HSL2RGB (hsl[0],hsl[1], hsl[2]);
    trgb=HSL2RGB (hsl[0],hsl[1], l);
    for (i=0;i<3;i++) {
      if (trgb[i]>15)  trgb[i]=trgb[i].toString(16);
      else trgb[i]='0'+trgb[i].toString(16);
    }
    return('#'+trgb.join(''));
  }
}

// altern color in a table between rows
function alterrow(tid,co,by) {
  var c1=getAltern(co,240);
  var c2=getAltern(co,250);
  var c=[c1,c2];
  var t=document.getElementById(tid);
  if (t) {
    var ttr=t.getElementsByTagName('tr');  
    if (by) by2=2*by;
    for (var i=0;i<ttr.length;i++) {
      if (!by) ttr[i].style.backgroundColor=c[(i%2)];
      else ttr[i].style.backgroundColor=c[parseInt((i % by2)/by)];
    }
  }
}

function alterfieldset(tid,co,by) {
  if (!isNetscape) return; // not nice on IE
  var c1=getAltern(co,240);
  var c2=getAltern(co,250);
  var c=[c1,c2];
  var ci=0;
  var t=document.getElementById(tid);
  var tds;
  if (t) {
    var ttr=t.getElementsByTagName('fieldset');  
    if (by) by2=2*by;
    for (var i=0;i<ttr.length;i++) {
      if (ttr[i].className=='mfield') {
	if (!by) ttr[i].style.backgroundColor=c[(ci%2)];
	else ttr[i].style.backgroundColor=c[parseInt((ci % by2)/by)];
	tds=ttr[i].getElementsByTagName('td');
	//      alert(tds.lenght);
	for (var j=0;j<tds.length;j++) {
	  if (!by) tds[j].style.backgroundColor=c[(ci%2)];
	  else tds[j].style.backgroundColor=c[parseInt((ci % by2)/by)]; 
	}
	ci++;
      }
    }
  }
}

function addBookmark(url,title) {
       if ( isNetscape ){
           window.sidebar.addPanel(title,url,"");
       }
       else {
           window.external.AddFavorite(title,url);
       }
 }
