var BOUNDARY='--------Eric TYYOUPLABOOM7893';

var _documentForm=null; // le formulaire contenant notre champ texte
var _inputField=null; // le champ texte lui-même
var _buttonField=null; // search button
var _submitButton=null; // le bouton submit de notre formulaire
var _completeDiv=null; // division to choose values
var _ciblesListe=null; // attribute cible
var _forceone=false; // auto set if only one suggestion
var _autoisinit=false;

var _oldInputFieldValue=""; // valeur précédente du champ texte
var _currentInputFieldValue=""; // valeur actuelle du champ texte
var _resultCache=new Object(); // mécanisme de cache des requetes
var _weareonselection=false; // to prevent unwanted blur event
function sendAutoChoice(event,docid,  choiceButton,attrid ) {


  inp=document.getElementById(attrid);


  if ((! inp)||(inp==null)) {
    alert('[TEXT: input not found]'+attrid);
  } else {    
    activeAutoInit(event,docid,  inp );    
    _forceone=true;
    callSuggestions(inp.value);
  }
}
function activeAutoInit(event,docid,  inp ) {
  var CORE_STANDURL='?sole=Y'; 
  var index='';
  var domindex=''; // needed to set values in arrays
  var iskey=inp.id
  addEvent(document,"keydown",onKeyDownHandler);
  _inputField=inp;
  _buttonField=document.getElementById('ic_'+inp.id);
  _documentForm=inp.form;
  _inputField.autocomplete="off";
  _inputField.onkeyup=onKeyUpHandler;
  _inputField.onblur=onBlurHandler;
  _currentInputFieldValue=_inputField.value;
  _oldInputFieldValue=_currentInputFieldValue;
  _resultCache=new Object(); // reinit cache
  cacheResults("",new Array())
  creeAutocompletionDiv();

  if (inp.name.substr(inp.name.length-2,2) == '[]') {
    // it is an attribute in array
    attrid=inp.name.substr(1,inp.name.length-3);
    index=getRowNumber(inp);
    domindex = inp.id.substring(attrid.length);    
  } else {
    attrid=inp.name.substr(1,inp.name.length-1);;
  }
  _adresseRecherche = CORE_STANDURL+'&app=FDL&action=AUTOCOMPLETION&docid='+docid+'&attrid='+attrid+'&index='+index+'&domindex='+domindex;
  
}

function activeAuto(event,docid,  inp ) {
  activeAutoInit(event,docid,  inp );
  if (! _autoisinit) setTimeout("mainLoop()",200);
  _autoisinit=true;
}

function addPostValue(post,thename,thevalue) {
  var bs = new String("\r\n--" + BOUNDARY + "\r\n");
  bs += "Content-Disposition: form-data; name=\""+thename+"\"\r\n\r\n";
  bs += thevalue;
  bs += "\r\n";

  return post+bs;
}

function completeChoiceAuto(index) {
  var allvalues = _xmlHttp.responseXML.getElementsByTagName('values');
  
  var values=allvalues[index].getElementsByTagName('val');
  
  var tval = new Array();
  for (var i=0; i < values.length; i++) {
    if (values[i].firstChild)    tval.push(values[i].firstChild.data);
    else tval.push('');
  }

  var tvals = new Array();
  tvals.push(tval);
  completechoice(0,_ciblesListe,tvals,window);
  hideCompleteDiv();
  _currentInputFieldValue=_inputField.value;
  _oldInputFieldValue=_currentInputFieldValue;
}


//~~~~~~~~~~~~~~~~~~~~~~~~ ORiginal Code ~~~~~~~~
// retourne un objet xmlHttpRequest.
// méthode compatible entre tous les navigateurs (IE/Firefox/Opera)
function getXMLHTTP(){
  var xhr=null;
  if(window.XMLHttpRequest) // Firefox et autres
  xhr = new XMLHttpRequest();
  else if(window.ActiveXObject){ // Internet Explorer
    try {
      xhr = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        xhr = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e1) {
        xhr = null;
      }
    }
  }
  else { // XMLHttpRequest non supporté par le navigateur
    alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
  }
  return xhr;
}


// tourne en permanence pour suggerer suite à un changement du champ texte
function mainLoop(){
  if(_oldInputFieldValue!=_currentInputFieldValue){
    var valeur=escapeURI(_currentInputFieldValue);
   

    _forceone=false;;
    
    callSuggestions(valeur); // appel distant
    
    _inputField.focus()
  }
  _oldInputFieldValue=_currentInputFieldValue;
   setTimeout("mainLoop()",200); // la fonction se redéclenchera dans 200 ms
 
  return true
}

// echappe les caractère spéciaux
function escapeURI(La){
  if(encodeURIComponent) {
    return encodeURIComponent(La);
  }
  if(escape) {
    return escape(La)
  }
}

var _xmlHttp = null; //l'objet xmlHttpRequest utilisé pour contacter le serveur
var _adresseRecherche = "options.php" //l'adresse à interroger pour trouver les suggestions

function callSuggestions(valeur){
  var bs='';
  if(_xmlHttp&&_xmlHttp.readyState!=0){
    _xmlHttp.abort();
    if (_buttonField) _buttonField.style.backgroundColor='blue';
  }
  _xmlHttp=getXMLHTTP();
  if(_xmlHttp){
    if (_buttonField) _buttonField.style.backgroundColor='yellow';
    //appel à l'url distante
    _xmlHttp.open("POST",_adresseRecherche+'&skey='+valeur,true);
    _xmlHttp.setRequestHeader("Content-Type", "multipart/form-data; boundary=" + BOUNDARY);
    var ie=_documentForm.elements.length;
    for (var i=0;i<ie;i++) {
      if (_documentForm.elements[i].name && _documentForm.elements[i].name!='')
      bs=addPostValue(bs,_documentForm.elements[i].name,_documentForm.elements[i].value);
      
    }

    _xmlHttp.onreadystatechange=function() {
      if(_xmlHttp.readyState==4) {
	if (_xmlHttp.responseXML) {
	//	alert(_xmlHttp.responseText);
        var liste = traiteXmlSuggestions(_xmlHttp.responseXML);
        //cacheResults(valeur,liste);
	if (liste.length >0) {
	  metsEnPlace(valeur,liste);
	  if (_forceone && (liste.length == 1)) {
	    completeChoiceAuto(0);
	  }
	
	}
	} else if (_xmlHttp.responseText)	alert(_xmlHttp.responseText);
      }
    
    };
    // envoi de la requete
    _xmlHttp.send(bs);
  }
}

// Mecanisme de caching des réponses
function cacheResults(debut,suggestions){
  _resultCache[debut]=suggestions
}

// Transformation XML en tableau
function traiteXmlSuggestions(xmlDoc) {
    if (_buttonField) _buttonField.style.backgroundColor='';
  var options = xmlDoc.getElementsByTagName('title');
  var optionsListe = new Array();
  for (var i=0; i < options.length; i++) {
    optionsListe.push(options[i].firstChild.data);
  }
  if (options.length==0) {    
    var status = xmlDoc.getElementsByTagName('status');
    if (status.length==1) {
      var msg=status[0].getAttribute('warning');
      displayWarning(msg);
    }
  } else {
    var cibles = xmlDoc.getElementsByTagName('cible');
    _ciblesListe = new Array();
    
    for (i=0; i < cibles.length; i++) {
      _ciblesListe.push(cibles[i].firstChild.data);
    }
  }
  return optionsListe;
}

//insère une règle avec son nom
function insereCSS(nom,regle){
  if (document.styleSheets) {
    var I=document.styleSheets[0];
    if(I.addRule){ // méthode IE
      I.addRule(nom,regle)
    }else if(I.insertRule){ // méthode DOM
      I.insertRule(nom+" { "+regle+" }",I.cssRules.length)
    }
  }
}

function initStyle(){
  var AutoCompleteDivListeStyle="font-size: 13px; font-family: arial,sans-serif; word-wrap:break-word; ";
  var AutoCompleteDivStyle="display: block; padding-left: 3; padding-right: 3; height: 16px; overflow: hidden; background-color: white;";
  var AutoCompleteDivActStyle="background-color: #3366cc; color: white ! important; ";
  insereCSS(".AutoCompleteDivListeStyle",AutoCompleteDivListeStyle);
  insereCSS(".AutoCompleteDiv",AutoCompleteDivStyle);
  insereCSS(".AutoCompleteDivAct",AutoCompleteDivActStyle);
}

function setStylePourElement(c,name){
  c.className=name;
}

// calcule le décalage à gauche
function calculateOffsetLeft(r){
  return calculateOffset(r,"offsetLeft")
}

// calcule le décalage vertical
function calculateOffsetTop(r){
  return calculateOffset(r,"offsetTop")
}

function calculateOffset(r,attr){
  var kb=0;
  while(r){
    kb+=r[attr];
    r=r.offsetParent
  }
  return kb
}

// calcule la largeur du champ
function calculateWidth(){
  return _inputField.offsetWidth-2*1
}

function setCompleteDivSize(){
  if(_completeDiv){
    _completeDiv.style.left=calculateOffsetLeft(_inputField)+"px";
    _completeDiv.style.top=calculateOffsetTop(_inputField)+_inputField.offsetHeight-1+"px";
    _completeDiv.style.width=calculateWidth()+"px"
  }
}

function creeAutocompletionDiv() {
  if (_completeDiv) {
    //while(_completeDiv.childNodes.length>0) {
    //  _completeDiv.removeChild(_completeDiv.childNodes[0]);
    // }
    return;
  }
  //initStyle();
  _completeDiv=document.createElement("DIV");
  _completeDiv.id="completeDiv";
  var borderLeftRight=1;
  var borderTopBottom=1;
  setCompleteDivSize();
  _completeDiv.style.visibility="hidden";
  _completeDiv.style.position="absolute";
  _completeDiv.onmouseout=completeDivOnMouseOut;
  _completeDiv.onmouseover=completeDivOnMouseOver;
  document.body.appendChild(_completeDiv);
  setStylePourElement(_completeDiv,"AutoCompleteDivListeStyle");
}

function metsEnPlace(valeur, liste){
  while(_completeDiv.childNodes.length>0) {
    _completeDiv.removeChild(_completeDiv.childNodes[0]);
  }
  if (liste.length > 10) {
    _completeDiv.style.height='100px';
    _completeDiv.style.overflow='auto';
    _completeDiv.scrollTop='0px';
  } else {
    _completeDiv.style.height='auto';
    _completeDiv.style.overflow='';    
  }

  // mise en place des suggestions
  for(var f=0; f<liste.length; ++f){
    var nouveauDiv=document.createElement("DIV");
    nouveauDiv.onmousedown=divOnMouseDown;
    nouveauDiv.onmouseover=divOnMouseOver;
    nouveauDiv.onmouseout=divOnMouseOut;
    nouveauDiv.setAttribute('index',f);
    setStylePourElement(nouveauDiv,"AutoCompleteDiv");
    var nouveauSpan=document.createElement("SPAN");
    nouveauSpan.innerHTML=liste[f]; // le texte de la suggestion
    nouveauDiv.appendChild(nouveauSpan);
    _completeDiv.appendChild(nouveauDiv)
  }
  PressAction();
  if(_completeDivRows>0) {
    _completeDiv.height=16*_completeDivRows+4;
  } else {
    hideCompleteDiv();
  }
}

function displayWarning(warning){
  while(_completeDiv.childNodes.length>0) {
    _completeDiv.removeChild(_completeDiv.childNodes[0]);
  }
  var ow=document.createElement("SPAN");
  ow.className="Error";
  ow.innerHTML=warning;
  _completeDiv.appendChild(ow);
  showCompleteDiv();
}



var _lastKeyCode=null;

// Handler pour le keydown du document
var onKeyDownHandler=function(event){
  // accès evenement compatible IE/Firefox
  if(!event&&window.event) {
    event=window.event;
  }
  // on enregistre la touche ayant déclenché l'evenement
  if(event) {
    _lastKeyCode=event.keyCode;
    if (_lastKeyCode==13) {
      if (_completeDiv && _completeDiv.style.visibility=='visible') stopPropagation(event); // stop submit
      completeChoiceAuto(_highlightedSuggestionIndex);
    }
  }
}

var _eventKeycode = null;

// Handler pour le keyup de lu champ texte
var onKeyUpHandler=function(event){
  // accès evenement compatible IE/Firefox
  if(!event&&window.event) {
    event=window.event;
  }
  _eventKeycode=event.keyCode;
  // Dans les cas touches touche haute(38) ou touche basse (40)
  if(_eventKeycode==40||_eventKeycode==38) {
    // on autorise le blur du champ (traitement dans onblur)
    blurThenGetFocus();
  }
  // taille de la selection
  var N=rangeSize(_inputField);
  // taille du texte avant la selection (selection = suggestion d'autocomplétion)
  var v=beforeRangeSize(_inputField);
  // contenu du champ texte
  var V=_inputField.value;
  if(_eventKeycode!=0){
    if(N>0&&v!=-1) {
      // on recupere uniquement le champ texte tapé par l'utilisateur
      V=V.substring(0,v);
    }
    // 13 = touche entrée
    if(_eventKeycode==13||_eventKeycode==3){
      var d=_inputField;
      
      // on mets en place l'ensemble du champ texte en repoussant la selection
      if(_inputField.createTextRange){
        var t=_inputField.createTextRange();
        t.moveStart("character",_inputField.value.length);
        _inputField.select()
      } else if (d.setSelectionRange){
        _inputField.setSelectionRange(_inputField.value.length,_inputField.value.length)
      }
    } else {
      // si on a pas pu agrandir le champ non selectionné, on le mets en place violemment.
      if(_inputField.value!=V) {
	// _inputField.value=V
      }
    }
  }
  // si la touche n'est ni haut, ni bas, on stocke la valeur utilisateur du champ
  if(_eventKeycode!=40&&_eventKeycode!=38) {
    // le champ courant n est pas change si key Up ou key Down
    if ((V.length>1) && (V.charAt(0)==' ')) {
      _inputField.value=V.substring(1);
      V=_inputField.value;
    }
  	_currentInputFieldValue=V;
  }
  if(handleCursorUpDownEnter(_eventKeycode)&&_eventKeycode!=0) {
    // si on a préssé une touche autre que haut/bas/enter
    PressAction();
  }
}

// Change la suggestion selectionné.
// cette méthode traite les touches haut, bas et enter
function handleCursorUpDownEnter(eventCode){
  if(eventCode==40){
    highlightNewValue(_highlightedSuggestionIndex+1);
    return false
  }else if(eventCode==38){
    highlightNewValue(_highlightedSuggestionIndex-1);
    return false
  }else if(eventCode==13||eventCode==3){
    return false
  }
  return true
}

var _completeDivRows = 0;
var _completeDivDivList = null;
var _highlightedSuggestionIndex = -1;
var _highlightedSuggestionDiv = null;

// gère une touche pressée autre que haut/bas/enter
function PressAction(){
  _highlightedSuggestionIndex=-1;
  var suggestionList=_completeDiv.getElementsByTagName("div");
  var suggestionLongueur=suggestionList.length;
  // on stocke les valeurs précédentes
  // nombre de possibilités de complétion
  _completeDivRows=suggestionLongueur;
  // possiblités de complétion
  _completeDivDivList=suggestionList;
  // si le champ est vide, on cache les propositions de complétion
  if(suggestionLongueur==0){
    hideCompleteDiv()
  }else{
    showCompleteDiv()
  }
  var trouve=false;
  // si on a du texte sur lequel travailler
  if(_currentInputFieldValue.length>0){
    var indice;
    // T vaut true si on a dans la liste de suggestions un mot commencant comme l'entrée utilisateur
    for(indice=0; indice<suggestionLongueur; indice++){
      if(getSuggestion(suggestionList.item(indice)).toUpperCase().indexOf(_currentInputFieldValue.toUpperCase())==0) {
        trouve=true;
        break
      }
    }
  }
  // on désélectionne toutes les suggestions
  for(var i=0; i<suggestionLongueur; i++) {
    setStylePourElement(suggestionList.item(i),"AutoCompleteDiv");
  }
  // si l'entrée utilisateur (n) est le début d'une suggestion (n-1) on sélectionne cette suggestion avant de continuer
  if(trouve){
    _highlightedSuggestionIndex=indice;
    _highlightedSuggestionDiv=suggestionList.item(_highlightedSuggestionIndex);
  }else{
    _highlightedSuggestionIndex=-1;
    _highlightedSuggestionDiv=null
  }
  var supprSelection=false;
  switch(_eventKeycode){
    // cursor left, cursor right, page up, page down, others??
    case 8:
    case 33:
    case 34:
    case 35:
    case 35:
    case 36:
    case 37:
    case 39:
    case 45:
    case 46:
      // on supprime la suggestion du texte utilisateur
      supprSelection=true;
      break;
    default:
      break
  }
  // si on a une suggestion (n-1) sélectionnée
  if(!supprSelection&&_highlightedSuggestionDiv){
    setStylePourElement(_highlightedSuggestionDiv,"AutoCompleteDivAct");
    var z;
    if(trouve) {
      z=getSuggestion(_highlightedSuggestionDiv).substr(0);
    } else {
      z=_currentInputFieldValue;
    }
    if(z!=_inputField.value){
      if(_inputField.value!=_currentInputFieldValue) {
        return;
      }
      // si on peut créer des range dans le document
      if(_inputField.createTextRange||_inputField.setSelectionRange) {
	//_inputField.value=z;
      }
      // on sélectionne la fin de la suggestion
      if(_inputField.createTextRange){
        var t=_inputField.createTextRange();
        t.moveStart("character",_currentInputFieldValue.length);
        t.select()
      }else if(_inputField.setSelectionRange){
        _inputField.setSelectionRange(_currentInputFieldValue.length,_inputField.value.length)
      }
    }
  }else{
    // sinon, plus aucune suggestion de sélectionnée
    _highlightedSuggestionIndex=-1;
  }
}

var _cursorUpDownPressed = null;

// permet le blur du champ texte après que la touche haut/bas ai été pressé.
// le focus est récupéré après traitement (via le timeout).
function blurThenGetFocus(){
  _cursorUpDownPressed=true;
  _inputField.blur();
  setTimeout("_inputField.focus();",10);
  return
}

// taille de la selection dans le champ input
function rangeSize(n){
  var N=-1;
  if(n.createTextRange){
    var fa=document.selection.createRange().duplicate();
    N=fa.text.length
  }else if(n.setSelectionRange){
    N=n.selectionEnd-n.selectionStart
  }
  return N
}

// taille du champ input non selectionne
function beforeRangeSize(n){
  var v=0;
  if(n.createTextRange){
    var fa=document.selection.createRange().duplicate();
    fa.moveEnd("textedit",1);
    v=n.value.length-fa.text.length
  }else if(n.setSelectionRange){
    v=n.selectionStart
  }else{
    v=-1
  }
  return v
}

// Place le curseur à la fin du champ
function cursorAfterValue(n){
  if(n.createTextRange){
    var t=n.createTextRange();
    t.moveStart("character",n.value.length);
    t.select()
  } else if(n.setSelectionRange) {
    n.setSelectionRange(n.value.length,n.value.length)
  }
}


// Retourne la valeur de la possibilite (texte) contenu dans une div de possibilite
function getSuggestion(uneDiv){
  if(!uneDiv) {
    return null;
  }
  return trimCR(uneDiv.getElementsByTagName('span')[0].firstChild.data)
}

// supprime les caractères retour chariot et line feed d'une chaine de caractères
function trimCR(chaine){
  for(var f=0,nChaine="",zb="\n\r"; f<chaine.length; f++) {
    if (zb.indexOf(chaine.charAt(f))==-1) {
      nChaine+=chaine.charAt(f);
    }
  }
  return nChaine
}

// Cache completement les choix de completion
function hideCompleteDiv(){
    _completeDiv.style.visibility="hidden"
}

// Rends les choix de completion visibles
function showCompleteDiv(){
  _completeDiv.style.visibility="visible";
  setCompleteDivSize()
}

// Change la suggestion en surbrillance
function highlightNewValue(C){
  if(!_completeDivDivList||_completeDivRows<=0) {
    return;
  }
  showCompleteDiv();
  if(C>=_completeDivRows){
    C=_completeDivRows-1
  }
  if(_highlightedSuggestionIndex!=-1&&C!=_highlightedSuggestionIndex){
    setStylePourElement(_highlightedSuggestionDiv,"AutoCompleteDiv");
    _highlightedSuggestionIndex=-1
  }
  if(C<0){
    _highlightedSuggestionIndex=-1;
    _inputField.focus();
    return
  }
  _highlightedSuggestionIndex=C;
  _highlightedSuggestionDiv=_completeDivDivList.item(C);
  setStylePourElement(_highlightedSuggestionDiv,"AutoCompleteDivAct");
  //_inputField.value=getSuggestion(_highlightedSuggestionDiv);
}

// Handler de resize de la fenetre
var onResizeHandler=function(event){
  // recalcule la taille des suggestions
  setCompleteDivSize();
}

// Handler de blur sur le champ texte
var onBlurHandler=function(event){
  if(!_cursorUpDownPressed){
    // si le blur n'est pas causé par la touche haut/bas
    if (! _weareonselection) hideCompleteDiv();
    // Si la dernière touche préssé est tab, on passe au bouton de validation
    if(_lastKeyCode==9){
      //      _submitButton.focus();
      _lastKeyCode=-1
    }
  }
  _cursorUpDownPressed=false
};

// declenchee quand on clique sur une div contenant une possibilite
var divOnMouseDown=function(){
  completeChoiceAuto(_highlightedSuggestionIndex);
};



// declenchee quand on passe sur une div de possibilite. La div précédente est passee en style normal
var divOnMouseOver=function(){
  if(_highlightedSuggestionDiv) {
    setStylePourElement(_highlightedSuggestionDiv,"AutoCompleteDiv");
  }
  setStylePourElement(this,"AutoCompleteDivAct");
  _highlightedSuggestionIndex=this.getAttribute('index');
};

// declenchee quand la sourie quitte une div de possiblite. La div repasse a l'etat normal
var divOnMouseOut = function(){
  setStylePourElement(this,"AutoCompleteDiv");
};

// declenchee quand la sourie quitte une div de possiblite.
var completeDivOnMouseOut = function(){
  _weareonselection=false;
};
// declenchee quand la sourie entre une div de possiblite
var completeDivOnMouseOver = function(){
  _weareonselection=true;
};



