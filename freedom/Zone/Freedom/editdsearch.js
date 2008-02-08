
// use when submit to avoid first unused item
function deletenew() {
  resetInputs('newcond');
  var na=document.getElementById('newcond');
  if (na) na.parentNode.removeChild(na); 
  na=document.getElementById('newstate');
  if (na) na.parentNode.removeChild(na);
  
  
}
  

function trackCR(event) {
  var intKeyCode;

  if (!event) event=window.event;
  intKeyCode=event.keyCode;
  if (intKeyCode == 13) return true;

  return false;
}
function sendsearch(faction,rtarget) {
  var fedit = document.fedit;
  resetInputs('newcond');
  
  with (document.modifydoc) {
    var editAction=action;
    var editTarget=target;

    enableall();  
    var na=document.getElementById('newcond');
    if (na) {
      disabledInput(na,true);        
      var nt=document.getElementById('newstate');
      if (nt)   disabledInput(nt,true);
    }
    if ((!rtarget) &&  (window.parent.fvfolder)) rtarget='fvfolder';
    else  if (!rtarget) rtarget='_blank';
    target=rtarget;
    action=faction;
    submit();
    target=editTarget;
    action=editAction;

    
    if (na) {
      disabledInput(na,false);            
       if (nt) disabledInput(nt,false);
    }
    
  }
}
function callFunction(event,th) {
  var pnode=getPrevElement(th.parentNode);
  var ex=document.getElementById('example');
  if (pnode) {
    pnode.innerHTML='<input  type="text"  size="20" name="_se_keys[]">';
    pnode.appendChild(ex);
    ex.style.display='';
  }
  
}
function setKey(event,th) {
  var pnode;

  pnode=th.previousSibling;
  while (pnode && ((pnode.nodeType != 1) || (pnode.name != '_se_keys[]'))) pnode = pnode.previousSibling;

  pnode.value = th.options[th.selectedIndex].value;

  
}

function getNextElement(th) {
  var pnode;
  pnode=th.nextSibling;
  while (pnode && (pnode.nodeType != 1)) pnode = pnode.nextSibling;
  return pnode;
  
}

function getPrevElement(th) {
  var pnode;
  pnode=th.previousSibling;
  while (pnode && (pnode.nodeType != 1)) pnode = pnode.previousSibling;
  return pnode;
  
}

function filterfunc(th) {
  var p=th.parentNode;
  var opt=th.options[th.selectedIndex];
  var atype=opt.getAttribute('atype');
  var ctypes,i;
  var pnode,so=false;
  var aid=opt.value;
  var sec,se;
  var needresetselect=false,ifirst=0;
  var ex=document.getElementById('example');
  var lc=document.getElementById('lastcell');

  // move to tfoot to not be removed
  ex.style.display='none';
  lc.appendChild(ex);

  // search brother select input
  pnode=p.nextSibling;
  while (pnode && ((pnode.nodeType != 1) || (pnode.tagName != 'TD'))) pnode = pnode.nextSibling;

 
  for (i=0;i<pnode.childNodes.length;i++) {
    if (pnode.childNodes[i].tagName=='SELECT') {
      so=pnode.childNodes[i];
    }
  }

  // display only matches
  ifirst=-1;
  for (i=0;i<so.options.length;i++) {
    opt=so.options[i];
    ctype=opt.getAttribute('ctype');
    if ((ctype=='') || (ctype.indexOf(atype)>=0)) {
      if (ifirst == -1) ifirst=i;
      opt.style.display='';
      opt.disabled=false;
    } else {
      opt.style.display='none';
      if (opt.selected) needresetselect=true;
      opt.selected=false;
      opt.disabled=true;
    }
  }
  if (needresetselect) {
    so.options[ifirst].selected=true;
  }

  // find key cell
  pnode=pnode.nextSibling;
  while (pnode && ((pnode.nodeType != 1) || (pnode.tagName != 'TD'))) pnode = pnode.nextSibling;
  // now enum
  if ((atype=='enum') || (atype=='enumlist')) {   
     se=document.getElementById('selenum'+aid);
    if (se) {      
      if (pnode) {	
	pnode.innerHTML='';
	sec=se.cloneNode(true);
	sec.name='_se_keys[]';
	sec.id='';
	pnode.appendChild(sec);
      }            
    }
  } else {    
    se=document.getElementById('thekey');
    if (se) {            
	sec=se.cloneNode(true);
	sec.name='_se_keys[]';
	sec.id='';
	if (pnode) {            
	  pnode.innerHTML='';
	  pnode.appendChild(sec);	  
	}
    }
  }
  
}
