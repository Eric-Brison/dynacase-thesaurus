/*
 **
 **
 **
*/

function ChangeAlarm() {
  chk = document.getElementById('AlarmCheck');
  alrm = document.getElementById('AlarmVis');
  chk.checked =  (chk.checked ? "" : "checked" );
  if (chk.checked) {
    alrm.style.visibility = 'visible';
    document.getElementById('evalarmst').value = 1;
  } else {
    alrm.style.visibility = 'hidden';
    document.getElementById('evalarmst').value = 0;
  }
  return;
}

function changeAlarmT() {
  var dd = document.getElementById('alarmday');
  var hh = document.getElementById('alarmhour');
  var mm = document.getElementById('alarmmin');

  document.getElementById('evalarmd').value = dd.options[dd.selectedIndex].value;
  document.getElementById('evalarmh').value = hh.options[hh.selectedIndex].value;
  document.getElementById('evalarmm').value = mm.options[mm.selectedIndex].value;

  return;
}

function ComputeDateFromStart() {

  var o_stime = parseInt(document.getElementById('TsStart').value);
  var od_stime = new Date();
  od_stime.setTime(o_stime);

  var o_etime = parseInt(document.getElementById('TsEnd').value);
  var od_etime = new Date();
  od_etime.setTime(o_etime);

  var tdiff = (o_etime - o_stime);

  // Compute new start time
  var tsS = parseInt(document.getElementById('DayTsStart').value) * 1000;
  var hts = new Date();
  hts.setTime(tsS);
  var Hstart = parseInt(document.getElementById('Hstart').options[document.getElementById('Hstart').selectedIndex].value);
  var Mstart = parseInt(document.getElementById('Mstart').options[document.getElementById('Mstart').selectedIndex].value);
  var nS = new Date(hts.getFullYear(), hts.getMonth(), hts.getDate(), Hstart, Mstart, 0, 0);
  var nE = new Date();
  nE.setTime(nS.getTime() + tdiff);


  // Updating all fields....
  document.getElementById('TsStart').value = nS.getTime();
  document.getElementById('DayTsStart').value = (nS.getTime() / 1000);
  document.getElementById('DayStart').innerHTML = nS.print('%a %d %b %Y');

  document.getElementById('TsEnd').value = nE.getTime();
  document.getElementById('DayTsEnd').value = (nE.getTime() / 1000);
  document.getElementById('DayEnd').innerHTML = nE.print('%a %d %b %Y');
  document.getElementById('Hend').selectedIndex = nE.getHours();
  UpdateEndMinutes(nE.getMinutes());
  
}

function UpdateEndMinutes(min) {
  var init = -1;
  var sb = document.getElementById('Mend');
  for (var ib=(sb.options.length-1); ib>=0 && init==-1; ib--) {
    if (parseInt(min)>=parseInt(sb.options[ib].value)) init=ib;
  }
  sb.selectedIndex = init;
}

function ComputeDateFromEnd() {

  var o_stime = parseInt(document.getElementById('TsStart').value);
  var od_stime = new Date();
  od_stime.setTime(o_stime);

  var o_etime = parseInt(document.getElementById('TsEnd').value);
  var od_etime = new Date();
  od_etime.setTime(o_etime);


  // Compute new old time
  var tsE = parseInt(document.getElementById('DayTsEnd').value) * 1000;
  var hte = new Date();
  hte.setTime(tsE);
  var Hend = parseInt(document.getElementById('Hend').options[document.getElementById('Hend').selectedIndex].value);
  var Mend = parseInt(document.getElementById('Mend').options[document.getElementById('Mend').selectedIndex].value);
  var nE = new Date(hte.getFullYear(), hte.getMonth(), hte.getDate(), Hend, Mend, 0, 0);

  if (nE.getTime()<=od_stime.getTime()) {
    alert('La date demand�e est ant�rieure � celle de d�but');
    nE.setTime(od_etime.getTime());
  }
  document.getElementById('TsEnd').value = nE.getTime();
  document.getElementById('DayTsEnd').value = (nE.getTime() / 1000);
  document.getElementById('DayEnd').innerHTML = nE.print('%a %d %b %Y');
  document.getElementById('Hend').selectedIndex = nE.getHours();
  UpdateEndMinutes(nE.getMinutes());
  return;
}


function InitStartCalendar() {
  var e = document.getElementById('DayTsStart');
  if (!e) return;
  var cts = parseInt(e.value)*1000;
  var cd = new Date;
  cd.setTime(cts);
  var calO = { 
    date:cd, 
    firstDay:1, 
    inputField:'DayTsStart', 
    ifFormat:'%s', 
    button:'ButStart',
    date:cd };
  Calendar.setup( calO );
  return;
}

function InitEndCalendar() {
  var e = document.getElementById('DayTsEnd');
  if (!e) return;
  var cts = parseInt(e.value)*1000;
  var cd = new Date;
  cd.setTime(cts);
  var calO = { 
    date:cd, 
    firstDay:1, 
    inputField:'DayTsEnd', 
    ifFormat:'%s', 
    button:'ButEnd',
    date:cd
  };
  Calendar.setup( calO );
  return;
}

function checkrdays(ic) {
  var ix=0;
  for (ix=0; ix<7; ix++) {
    if (document.getElementById('rweekday'+ix)) {
      if (document.getElementById('rweekday'+ix).checked) return true;
    } else {
      alert('Err: elt rweekday'+ix);
    }
  }
  document.getElementById('rweekday'+ic).checked = 'checked';
  return true;
}
    
  
function checkEndTime(cd) {
  var st = document.getElementById('TsEnd').value;
  if (cd.getTime()>=st) return false;
  return true;
}

function ChangeAllDay() {

  var nohour = document.getElementById('nohour');
  var tnohour = document.getElementById('tnohour');
  var allday = document.getElementById('allday');
  var hstart = document.getElementById('start_hour');
  var hend1 = document.getElementById('end_hour1');
  var hend2 = document.getElementById('end_hour2');
  var hend3 = document.getElementById('end_hour3');

  if (allday.checked) {
    nohour.checked = false;
    tnohour.style.visibility = 'hidden';
    hend1.style.visibility = 'hidden';
    hend2.style.visibility = 'hidden';
    hend3.style.visibility = 'hidden';
    hstart.style.visibility = 'hidden';
  } else {
    tnohour.style.visibility = 'visible';
    hend1.style.visibility = 'visible';
    hend2.style.visibility = 'visible';
    hend3.style.visibility = 'visible';
    hstart.style.visibility = 'visible';
  }
  return;
}


function ChangeNoHour() {

  var nohour = document.getElementById('nohour');
  var allday = document.getElementById('allday');
  var tallday = document.getElementById('tallday');
  var hstart = document.getElementById('start_hour');
  var hend1 = document.getElementById('end_hour1');
  var hend2 = document.getElementById('end_hour2');
  var hend3 = document.getElementById('end_hour3');

  if (nohour.checked) {
    allday.checked = false;
    tallday.style.visibility = 'hidden';
    hend1.style.visibility = 'hidden';
    hend2.style.visibility = 'hidden';
    hend3.style.visibility = 'hidden';
    hstart.style.visibility = 'hidden';
  } else {
    tallday.style.visibility = 'visible';
    hend1.style.visibility = 'visible';
    hend2.style.visibility = 'visible';
    hend3.style.visibility = 'visible';
    hstart.style.visibility = 'visible';
  }
  return;
}
function SetSelectedItem(from, to) {
   var f = document.getElementById(from);
   var to = document.getElementById(to);
   for (i=0; i<f.options.length; i++) {
     if (f.options[i].selected) {
       to.value = f.options[i].value;
     }
   }
   if (from=='rvcalendar') {
     if (to.value>0)  deleteAttendee(-1);
   }
   if (from=='rvconfid') {
     bg = document.getElementById('butevgroups');
     zg = document.getElementById('evgroups');
     if (to.value==2) {
       bg.style.visibility = 'visible';
       SwitchZone('evgroups'); 
     } else {
       bg.style.visibility = 'hidden';
       SwitchZone('evattendees');
     }
   }
}


function SwitchZone(view) {

  var zones = new Array ( 'evattendees', 'evrepeatzone', 'evgroups');

  for (zx in zones) {
    z = zones[zx];
    document.getElementById(z).style.display = 'none';
    document.getElementById('but'+z).className = 'WGCalZoneDefault';
    if (z == view) {
      document.getElementById(view).style.display = '';
      document.getElementById('but'+view).className = 'WGCalZoneSelected';
    }
  }

}


// Attendees management --------------------------------------------

var attendeesList = new Array();


function refreshAttendees() {

  var nTr;
  var tab = document.getElementById('tabress');
  var showtab = false;

  for (idx=0; idx<attendeesList.length; idx++) {
    if (attendeesList[idx].id!=-1) {
      showtab = true;
      if (attendeesList[idx].status == 0) {
        attendeesList[idx].status = 1;
        with (document.getElementById('trsample')) {
	  nTr = cloneNode(true);
	  style.display = 'none';
        }
        nTr.id = 'tr'+attendeesList[idx].id;
        mynodereplacestr(nTr, '%RID%', attendeesList[idx].id);
        mynodereplacestr(nTr, '%RICON%', attendeesList[idx].icon);
        mynodereplacestr(nTr, '%RDESCR%', attendeesList[idx].title);
        mynodereplacestr(nTr, '%RSTATE%', attendeesList[idx].state);
	nTr.style.display = '';
        tab.appendChild(nTr);
// 	if (attendeesList[idx].select) 	  document.getElementById(attendeesList[idx].id).className = classSelected;
// 	else  document.getElementById(attendeesList[idx].id).className = classUnSelected;
        document.getElementById(attendeesList[idx].id).className = classSelected;
	capp = document.getElementById('cp'+attendeesList[idx].id);
        capp.style.backgroundColor = attendeesList[idx].bgcolor;
      }
    }
  }
  refreshAttendeesCtx();
}

function refreshAttendeesCtx() {

  var vress = document.getElementById('tabress');
  var vdispo = document.getElementById('viewplan');
  var vdelall = document.getElementById('delall');
//   var viparticipe = document.getElementById('diparticipe');
  var vconvocation = document.getElementById('dconvocation');

  var showtab = false;

  for (idx=0; idx<attendeesList.length; idx++) {
    if (attendeesList[idx].id!=-1) showtab = true;
  }

  if (showtab) {
    vress.style.display = '';
    vdispo.style.display = '';
    vdelall.style.display = '';
//     viparticipe.style.display = '';
    vconvocation.style.display = '';
  }  else {
    vress.style.display = 'none';
    vdispo.style.display = 'none';
    vdelall.style.display = 'none';
//     viparticipe.style.display = 'none';
    vconvocation.style.display = 'none';
//     document.getElementById('withMe').checked = true;
  }
  return; 
}

function getAttendeeIdx(aid) {
  var idx = -1;
  for (i=0; i<attendeesList.length; i++) {
    if (attendeesList[i]!=null && attendeesList[i].id == aid) idx = i;
  } 
  return idx;
}
      
function addRessource(rid, rtitle, ricon, rstate, rsLabel, rsColor, rselect) {
  if (getAttendeeIdx(rid)!=-1) return;
  var idx = attendeesList.length;
  attendeesList[idx] = new Object();
  attendeesList[idx].id = rid;
  attendeesList[idx].title = rtitle;
  attendeesList[idx].icon = ricon;
  attendeesList[idx].state = rstate; /* confirmation status */
  attendeesList[idx].status = 0;     /* displayed status    */
  attendeesList[idx].label = rsLabel;
  attendeesList[idx].bgcolor = rsColor;
  attendeesList[idx].select = true;
  refreshAttendees();
}

function  deleteAttendee(aid) {
  var i = 0;
  for (i=(attendeesList.length-1); i>=0; i--) {
    if (aid==-1 || aid == attendeesList[i].id) {
      eltA = document.getElementById('tr'+ attendeesList[i].id);
      if (!eltA) continue;
      eltA.parentNode.deleteRow(eltA.sectionRowIndex);
      attendeesList[i].id = -1;
    }
  }
  refreshAttendeesCtx();
}


var attpicker;
function attkillwins() {
  if (attpicker != null) attpicker.close();
}


var DocumentSaved = false;

function saveEvent(event, checkconflict) {
  var fs = document.getElementById('editevent');
  var ti = document.getElementById('rvtitle');
  var refi = document.getElementById('editevent');

  if (event && ti.value=='') {
    ti.style.background = 'red';
    document.getElementById('errTitle').style.display='';
    return false;
  }

  if (event && checkconflict) {
    var nbe=normalEditCheckConflict(event, false);
    if (nbe>0) {
      if (!confirm('Des �v�nements sont en conflit ('+nbe+').\n'
		   +'Cliquez [OK] pour sauver le rendez-vous.\n'
		   +'        [Annuler] pour le modifier')) return false;
    }
  }
	
  if (EventSelectAll(fs)) fs.submit();
  DocumentSaved = true;
  window.close();
  return false;
}

function GetTitle(evt, checkconflict) {
  evt = (evt) ? evt : ((event) ? event : null );
  var cc = (evt.keyCode) ? evt.keyCode : evt.charCode;
  var ftitle = document.getElementById('rvtitle');
  if ((cc == 13)  && (ftitle.value != "")) {
    saveEvent(checkconflict,evt);
    return false;
  }
  return true;
}

function forceSaveEvent() {
  ok = confirm('forceSaveEvent::'+saveForceMsg); 
  if (ok) saveEvent(false, false);
  return false;
}
  
function deleteEvent(text) {
  ok = confirm(text); 
  if (!ok) return;
  var fs = document.getElementById('deleteevent');
  fs.submit();
  window.close();
}

function delExclDate() {
  var list = document.getElementById('listexcldate');
  for (i=(list.options.length-1); i>=0; i--) {
    if (list.options[i].selected) list.options[i] = null;
  }
}




function ViewElement(eCheck, eDisplay) {
  chk = document.getElementById(eCheck);
  zon = document.getElementById(eDisplay);
  if (chk.checked == true) {
    zon.style.visibility = 'visible';
  } else {
    zon.style.visibility = 'hidden';
  }
}

function everyInfo() {
  var checkone = -1;
  evr = document.getElementsByName('repeattype');
  for (i=0; i<evr.length; i++) {
    if (evr[i].checked) checkone = i;
  }

  document.getElementById('d_rweekday').style.display = 'none';
  document.getElementById('d_rmonth').style.display = 'none';
  document.getElementById('d_until').style.display = 'none';

  if (checkone>0) document.getElementById('d_until').style.display = '';
  if (checkone==2) document.getElementById('d_rweekday').style.display = '';
  if (checkone==3 || checkone==4) document.getElementById('d_rmonth').style.display = '';

}


function EventSelectAll(f) {

  var excdate = document.getElementById('excludedate');
  excdate.value = '';
  if (document.getElementById('listexcldate')) {
    var list = document.getElementById('listexcldate');
    var n = "";
    for (i=(list.options.length-1); i>=0; i--) {
      list.options[i].select = true;
      sep = (n==''?'':'|');
      n += sep + list.options[i].value;
    }
    excdate.value = n;
  }
  alist = document.getElementById('attendees');
  nlist = '';
  for (att=0; att<attendeesList.length; att++) {
    if (attendeesList[att].id==-1 || !attendeesList[att].select) continue;
    sep = (nlist==''?'':'|');
    nlist = nlist+sep+attendeesList[att].id;
  }
  alist.value = nlist;
  return true;
}


function viewattdispo(url, rlist) {

//   var withme = document.getElementById('withMe');
  var me = document.getElementById('ownerid').value;
  var rvs = document.getElementById('TsStart').value;
  var js;
  var je;

  if (!rlist) {
    rll = "";
    for (att=0; att<attendeesList.length; att++) {
      if (attendeesList[att].id==-1 || !attendeesList[att].select) continue;
      if (rll!='') rll += '|';
      rll += attendeesList[att].id;
    }
    if (rll!='') rll += '|';
    rll += me;
  } else {
    rll = rlist;
  }
  var td = new Date();
  td.setTime(rvs);
  var ye  = parseFloat(td.getFullYear());
  var mo  = parseFloat(td.getMonth()) + 1.0;
  var da  = parseFloat(td.getDate());
  var ho  = 0; // parseFloat(td.getHours());
  var mn  = 0; // parseFloat(td.getMinutes());
  var se  = 0; // parseFloat(td.getSeconds());
  
  var sdb =  '';
  js = cal_to_jd( "CEST", ye, mo, da, ho, mn, se);
  je = parseFloat(js) + 14.0;
  subwindow(500, 600, 'ViewDispo', url+rll);
}

function ShowHideStatus(s, v) {
  var evch = document.getElementById(s);
  evch.checked = (evch.checked ? "" : "checked" );
  document.getElementById(v).value = (evch.checked ? 1 : 0);
}
  
function setStatus(st, cst) {
  evst = document.getElementById('evstatus');
  evst.value = status;
//   document.getElementById('spall').style.border = '1px solid '+cst;
}

function ViewGroup(ev,st) {
  var ge = document.getElementById('grp'+ev.id);
  if (!ge) return;
  ge.style.display = (st?"":"none"); 
  if (st) {
    ww = getFrameWidth();
    wh = getFrameHeight();
    ge.style.position = 'absolute';
    ge.style.width = 'auto';
    ge.style.height = 'auto';
    ge.style.left = (ww/2)+'px';
    ge.style.top = (wh/2)+'px';
  }
  return;
}


function ImportRessources(elt, tress) {
  var i;
  for (i=0; i<tress.length; i++) {
    $col = 'red';
    if (tress[i][3]==-1) $col = 'transparent';
    addRessource(tress[i][0], tress[i][1], tress[i][2], tress[i][3], 'nouveau', $col, true, false);
  }
}

var classSelected = 'WGCRessSelected';
var classUnSelected = 'WGCRessDefault';

function RessourceSelect(idr) {
  var idx = -1;

  idx = getAttendeeIdx(idr);
  if (idx==-1) return;
  attendeesList[idx].select = ( attendeesList[idx].select ? false : true );
  relt = document.getElementById(idr);
  if (attendeesList[idx].select) {
    document.getElementById(attendeesList[idx].id).className = classSelected;
    document.getElementById(attendeesList[idx].id).style.textDecoration = 'none';
  } else {
    document.getElementById(attendeesList[idx].id).className = classUnSelected;
    document.getElementById(attendeesList[idx].id).style.textDecoration = 'line-through';
  }
}


// RV group visibility

function checkOneSelect(gowner) {
  var gsel = 0;
  for (i=0; i<gownerlist[gowner].length;i++) {
    if (gownerlist[gowner][i] != -1) gsel++;
  }
  if (gsel<=1) {
   alert(alertMsgOne);
   return false;
  }
  return true;
}

function SetGroupsForOwner(own) 
{
  for (i=0; i<owners.length; i++) {
    if (document.getElementById('showg'+owners[i])) {
      document.getElementById('showg'+owners[i]).style.display = 'none';
      if (own==owners[i]) document.getElementById('showg'+own).style.display = '';
    }
  }
}

function  changeVisGrp(gowner, grp) 
{
  var grpList = '';
  var cGrp = document.getElementById('o'+gowner+'d'+grp);
  var i;
  if (!cGrp) return;
  if (cGrp.className!='WGCRessSelected') {
    gownerlist[gowner][gownerlist[gowner].length] = grp;
    cGrp.className = 'WGCRessSelected';
  } else {
    if (!checkOneSelect(gowner)) return;
    for (i=0; i<gownerlist[gowner].length;i++) {
      if (gownerlist[gowner][i] == grp)  gownerlist[gowner][i] = -1;
    }
    cGrp.className = 'WGCRessDefault';
  }
  SetGroupsList(gowner);
  return false;
}

function  SetGroupsList(gowner) 
{
  if (gownerlist.length==0) return;
  var grpList = '';
  for (i=0; i<gownerlist[gowner].length;i++) {
    if (gownerlist[gowner][i] != -1) {
      grpList += gownerlist[gowner][i]+'|';
    }
  }
  document.getElementById('evconfgroups').value = grpList;
  return false;
}

function showHideElt(elt) {
  var elt = document.getElementById(elt);
  if (!elt) return false;
  if (arguments.length==1) undisplay='none';
  else undisplay = arguments[1];
  if (elt.style.display == '') {
    elt.style.display = undisplay;
  } else {
    elt.style.display = '';
  }
  return true;
}

function normalEditCheckConflict(ev, displayZero) {
  ev || (ev = window.event);
  var rll="";
  globalcursor('progress');
  var evid = document.getElementById('eventid').value;
  var me = document.getElementById('ownerid').value;
  for (att=0; att<attendeesList.length; att++) {
    if (attendeesList[att].id==-1 || !attendeesList[att].select) continue;
    if (rll!='') rll += '|';
    rll += attendeesList[att].id;
  }
  if (rll!='') rll += '|';
  rll += me;
  
  var ts = parseInt(document.getElementById('DayTsStart').value) + 60;
  var te = parseInt(document.getElementById('DayTsEnd').value) - 60;
  var urlsend = "index.php?sole=Y&app=WGCAL&action=WGCAL_GVIEW&stda=1&rvfs_pexc="+evid+"&rvfs_ts="+ts+"&rvfs_te="+te+"&rvfs_ress="+rll;
  var rq;
  if (window.XMLHttpRequest) rq = new XMLHttpRequest();
  else rq = new ActiveXObject("Microsoft.XMLHTTP");
  rq.open("GET", urlsend, false);
  rq.send('');
  document.getElementById('conflictcontent').innerHTML = rq.responseText;
  var nbc = parseInt(eltId('eventCount').innerHTML);
  if ((displayZero && nbc==0) || nbc>0) { 
    document.getElementById('conflict').style.display = 'inline';
    document.getElementById('conflict').style.visibility = 'visible';
    CenterDiv('conflict');
  }
  unglobalcursor();
  return nbc;
}
