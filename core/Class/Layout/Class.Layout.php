<?php
// 
// PHP Layout Class
//   this class is designed to perform the final page layout of
//   an application.
//   this class uses a template with three dynamic zones header,toc and main 
//   doc.
//   
// 
// Layout Class can manage three kind of datas :
//  
// 1) Simple tags :
//    those tags are enclosed into brackets [] and can be replaced with any
//    dynamic data given with the Set method.
//    e.g : [MYDATA]  => $this->Set("MYDATA","this is my text");
//
// 2) Block of Data :
//    those tags are used to manage repeated set of data (as table for instance)
//    You can assign a table of data to a specific block.
//    e.g : $table = array ( "0" => array ( "name" => "John",
//                                          "surname" => "Smith"),
//                           "1" => array ( "name" => "Robert",
//                                          "surname" => "Martin"));
//
//    the block : [BLOCK IDENTITY]
//                <tr><td align="left">[NAME]</td>
//                    <td align="right">[SURNAME]</td>
//                </tr>
//                [ENDBLOCK IDENTITY]
//
//   the code :   $lay = new Layout ("file containing the block");
//                $lay->SetBlockCorresp("IDENTITY","NAME","name");
//                $lay->SetBlockCorresp("IDENTITY","SURNAME","surname");
//                $lay->SetBlockData("IDENTITY",$table);
//   
//                $out = $lay->gen();
//
//      $out  :   <tr><td align="left">John</td>
//                    <td align="right">Smith</td>
//                </tr>
//                <tr><td align="left">Robert</td>
//                    <td align="right">Martin</td>
//                </tr>
//
// 3) Call a specific script (need Core App Environment to work)
//   tag syntax : [ZONE zonename]
//
//     the zone name is linked to a specific application/function
//
//          eg :  [ZONE CORE:APPLIST]
//        
//         then the APPLIST function in the CORE Application is called
//           this function can then use another layout etc......
//
//
// Copyright (c) 1999 Anakeen S.A.
//               Yannick Le Briquer
//
//  $Id: Class.Layout.php,v 1.2 2002/02/04 14:48:07 eric Exp $

$CLASS_LAYOUT_PHP="";
include_once('Class.Log.php');  
include_once('Class.Action.php');  
include_once('Class.Application.php');


class Layout {

//############################################
//# Private var
//#

var $strip='Y';

//########################################################################
//# Public methods
//#  
//#

//# Constructor
 function Layout($caneva="",$action="",$template="[OUT]") {
    $this->LOG = new Log("","Layout");     
    $this->template = $template;
    $this->action=$action;
    $this->generation="";
    $file = $caneva;
    $this->file="";
    if (file_exists($file)) {
      $fd = fopen($file,"r");
      if ($fd) {
        $this->file=$file;
        $this->template = fread($fd,filesize($file));
      }
    } 
  }


 function SetBlockCorresp($p_nom_block,$p_nom_modele,$p_nom=NULL) {
   $this->corresp["$p_nom_block"]["[$p_nom_modele]"]=($p_nom==NULL?$p_nom_modele:"$p_nom");
  }

  function SetBlockData($p_nom_block,$data=NULL) {
   $this->data["$p_nom_block"]=$data;
   // affect the corresp block if not
   if (is_array($data))  {
     reset($data);
     $elem = pos($data);
     if ( isset($elem) && is_array($elem)) {
       reset($elem);
       while (list($k,$v)=each($elem)) {
         if (!isset($this->corresp["$p_nom_block"]["[$k]"])) {
           $this->SetBlockCorresp($p_nom_block,$k);
         }
       }
     }
   }
  }

  function SetBlock($name,$block) {
    if ($this->strip=='Y') {
      $block = StripSlashes($block);
    }
    $out = "";
    if (isset ($this->data) && isset ($this->data["$name"]) && is_array($this->data["$name"])) {
      reset($this->data["$name"]);
      while(list($k,$v) = each($this->data["$name"])) {
        reset($this->corresp["$name"]);
        $loc=$block;
        while(list($k2,$v2) = each ($this->corresp["$name"])) {
          if (isset($v[$v2])) {
            $loc = str_replace( $k2, $v[$v2], $loc);
          } else {
            $loc = str_replace( $k2, "", $loc);
          }
        }
        $out .= $loc;
      }
    }
    $this->ParseBlock($out);
    return ($out);
  } 

  function ParseBlock(&$out) {
    $out = preg_replace(
       "/(?m)\[BLOCK\s*([^\]]*)\]((.*\n)*.*)\[ENDBLOCK\s*\\1\]/e", 
       "\$this->SetBlock('\\1','\\2')",
       $out);
  }

  function ParseZone(&$out) {
    $out = preg_replace(
       "/\[ZONE\s*([^:]*):([^\]]*)\]/e",
       "\$this->execute('\\1','\\2')",
       $out);
  }

  function execute($appname,$actionargn) {


    if ($this->action=="") return ("Layout not used in a core environment");

    // analyse action & its args
    $acturl = parse_url($actionargn);
    $actionname =  $acturl ["path"];

    global $ZONE_ARGS;
    $ZONE_ARGS=array();
    if (isset($acturl ["query"])) {
      $zargs = explode("&", $acturl ["query"] );
      while (list($k, $v) = each($zargs)) {
	if (ereg("([^=]*)=(.*)",$v, $regs)) {
	  // memo zone args for next action execute
	  if ($regs[2][0] == "*") { // its a layout variable
	    $ZONE_ARGS[$regs[1]]=$this->corresptab["[".substr($regs[2],1)."]"];
	  } else   $ZONE_ARGS[$regs[1]]=$regs[2];
	}
      }
    }

    if ($appname != $this->action->parent->name) {
      $appl = new Application();
      $appl->Set($appname,$this->action->parent);
    } else {
      $appl =& $this->action->parent;
    }

    
    if ($actionname != $this->action->name) {
      $act = new Action();

      if ($act->Exists($actionname, $appl->id)) {

	$res = $act->Set($actionname,$appl);
      } else {
	// it's a no-action zone (no ACL, cannot be call directly by URL)
	$act->name = $actionname;
      
	$res = $act->CompleteSet($appl);

      }
      if ($res == "") {
        return($act->execute());
      } else {
        return($res);
      }
    } else {
      return("Fatal loop : $actionname is called in $actionname");
    }
    
  }
           

  function set($tag,$val) {
     $this->corresptab["[$tag]"]=$val;
  }

  function ParseRef(&$out) {

     $out = preg_replace("/\[IMG:([^\]]*)\]/e",
                         "\$this->action->GetImageUrl('\\1')",
                         $out);

     $out = preg_replace("/\[SCRIPT:([^\]]*)\]/e",
                         "\$this->action->GetScriptUrl('\\1')",
                         $out);

     $out = preg_replace("/\[TEXT:([^\]]*)\]/e",
                         "\$this->action->Text('\\1')",
                         $out);
  }

  function GenJsRef() {
    $js = "";
    $list = $this->action->parent->GetJsRef();
    reset($list);
    while(list($k,$v) = each($list)) {
      $js .= "<script language=\"JavaScript1.2\" src=\"$v\"></script>\n";
    }
    return $js;
  }

  function GenJsCode() {
    $list = $this->action->parent->GetJsCode();
    reset($list);
    $out = "";
    while(list($k,$v) = each($list)) {
      $out .= $v."\n";
    }
    return($out);
  }

  function ParseJs(&$out) {
       $out = preg_replace("/\[JS:REF\]/e",
                           "\$this->GenJsRef()",
                           $out);
       
       $out = preg_replace("/\[JS:CODE\]/e",
                           "\$this->GenJsCode()",
                           $out);
  }

  function GenCssRef() {
    $js = "";
    $list = $this->action->parent->GetCssRef();
    reset($list);
    while(list($k,$v) = each($list)) {
      $js .= "<style type=\"text/css\" src=\"$v\"></style>\n";
    }
    return $js;
  }

  function GenCssCode() {
    $list = $this->action->parent->GetCssCode();
    reset($list);
    $out = "";
    while(list($k,$v) = each($list)) {
      $out .= $v."\n";
    }
    return($out);
  }
  function ParseCss(&$out) {
       $out = preg_replace("/\[CSS:REF\]/e",
                           "\$this->GenCssRef()",
                           $out);
       
       $out = preg_replace("/\[CSS:CODE\]/e",
                           "\$this->GenCssCode()",
                           $out);
  }
  function gen() {

    // if used in an app , set the app params
    if ($this->action != "") {
      $list=$this->action->parent->GetAllParam();
      while (list($k,$v)=each($list)) {
        $this->set($k,$v);
      }
    }
    
    $out = $this->template;

    $this->ParseBlock($out);

    $this->ParseZone($out);

    // Parse IMG: and LAY: tags
    $this->ParseRef($out);

    if (isset ($this->corresptab)) {
      reset($this->corresptab); 
      while (list($k,$v) = each($this->corresptab)) {
        $out = str_replace($k,$v,$out);
      }
    }

    $this->ParseJs($out);
    $this->ParseCss($out);

    return($out);
  }
}
?>
