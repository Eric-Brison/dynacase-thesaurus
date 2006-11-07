<?php

    require_once "HTTP/WebDAV/Server.php";
    require_once "System.php";
    
    /**
     * Filesystem access using WebDAV
     *
     * @access public
     */
    class HTTP_WebDAV_Server_Freedom extends HTTP_WebDAV_Server 
{
  /**
   * Root directory for WebDAV access
   *
   * Defaults to webserver document root (set by ServeRequest)
   *
   * @access private
   * @var    string
   */
  var $base = "";
  var $dbaccess="user=anakeen dbname=freedom";
  var $racine=9;
  /** 
   * MySQL Host where property and locking information is stored
   *
   * @access private
   * @var    string
   */
  var $db_host = "localhost";

  /**
   * MySQL database for property/locking information storage
   *
   * @access private
   * @var    string
   */
  var $db_name = "webdav";

  /**
   * MySQL user for property/locking db access
   *
   * @access private
   * @var    string
   */
  var $db_user = "root";

  /**
   * MySQL password for property/locking db access
   *
   * @access private
   * @var    string
   */
  var $db_passwd = "";


  function __construct() {
     // establish connection to property/locking db
    mysql_connect($this->db_host, $this->db_user, $this->db_passwd) or die(mysql_error());
    mysql_select_db($this->db_name) or die(mysql_error());
  }

  /**
   * Serve a webdav request
   *
   * @access public
   * @param  string  
   */
  function ServeRequest() 
  {
    // special treatment for litmus compliance test
    // reply on its identifier header
    // not needed for the test itself but eases debugging
    foreach(apache_request_headers() as $key => $value) {
      if (stristr($key,"litmus")) {
	error_log("Litmus test $value");
	header("X-Litmus-reply: ".$value);
      }
    }
    $this->base = "";
                
   
    // TODO throw on connection problems

    // let the base class do all the work
    parent::ServeRequest();
  }

  /**
   * No authentication is needed here
   *
   * @access private
   * @param  string  HTTP Authentication type (Basic, Digest, ...)
   * @param  string  Username
   * @param  string  Password
   * @return bool    true on successful authentication
   */
  function check_auth($type, $user, $pass) 
  {
    return true;
  }


  /**
   * PROPFIND method handler
   *
   * @param  array  general parameter passing array
   * @param  array  return array for file properties
   * @return bool   true on success
   */
  function PROPFIND(&$options, &$files) 
  {
    // get absolute fs path to requested resource
    $fspath =  $options["path"];
            
    error_log ( "===========>PROPFIND :".$options["path"].":depth:".$options["depth"] );

           
    // prepare property array
    $files["files"] = array();

    // store information for the requested path itself


    // information for contained resources requested?
    if (!empty($options["depth"]))  { // TODO check for is_dir() first?
                
      // make sure path ends with '/'
      $options["path"] = $this->_slashify($options["path"]);

      // try to open directory
      $freefiles=$this->readfolder($fspath);
      $files["files"]=$freefiles;
    } else {
                
      $freefiles=$this->readfolder($fspath,true);
      $files["files"]=$freefiles;
    }

    if (count($files["files"])==0) return false;
    // ok, all done
    return true;
  } 
        

  function readfolder($fspath,$onlyfld=false) {
    include_once("FDL/Lib.Dir.php");

    $files=array();
    $fldid=$this->path2id($fspath,$vid);
           
    if ($fspath=="/freedav") {
      $info = array();   
      $info["props"] = array();
      $info["props"][] = $this->mkprop("resourcetype", "collection");
      $info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");             
      $info["props"][] = $this->mkprop("displayname", $fspath);
      $info["path"]  = $fspath;
      $files[]=$info;
    } else {
    if ($vid) {
      $files=$this->vidpropinfo($fspath,$fldid,(!$onlyfld));
    } else {

      $fld=new_doc($this->dbaccess,$fldid);
      if ($fld->isAlive()) {
	//error_log("READFOLDER FIRST:".dirname($fspath)."/".$fld->title."ONLY:".intval($onlyfld));
	//$files=$this->docpropinfo($fld,$this->_slashify(dirname($fspath)),true);
	$files=$this->docpropinfo($fld,$this->_slashify(($fspath)),true);
	    
	if (! $onlyfld) {
	  /*
	   $ldoc = getChildDoc($this->dbaccess, $fld->initid,0,"ALL", array(),$action->user->id,"ITEM");
	   error_log("READFOLDER:".countDocs($ldoc));
	   while ($doc=getNextDoc($this->dbaccess,$ldoc)) {
	   //		  $files[]=$this->docpropinfo($doc);
	   error_log("READFOLDER examine :".$doc->title);
	   $files=array_merge($files,$this->docpropinfo($doc,$fspath));
	   }*/
	  if ($fld->doctype=='D') {
	    $tdoc=getFldDoc($this->dbaccess, $fld->initid,array(),200,false);
	  } else {
	    $tdoc = getChildDoc($this->dbaccess, $fld->initid,0,200, array(),$action->user->id,"TABLE");
	  }
	  // error_log("READFOLDER examine :".count($tdoc));
	  foreach ($tdoc as $k=>$v) {
	    $doc=getDocObject($this->dbaccess,$v);
	    $files=array_merge($files,$this->docpropinfo($doc,$fspath,false));
	  }
	} 
      }
      }
    }
    return $files;
  }


  function path2id($fspath,&$vid=null) {
    //error_log("FSPATH :".$fspath);
    if ($fspath=='/')     return $this->racine;

    $fspath=$this->_unslashify($fspath);

    if (ereg("/vid-([0-9]+)-([0-9]+)",$fspath,$reg)) {
      $fid=$reg[1];
      $vid=$reg[2];
      //	    $dvi=new DocVaultIndex($this->dbaccess);
      //$fid=$dvi->getDocId($vid);
	    

      error_log("FSPATH3 :.$fspath vid:[$vid]");
            
    } else {

      $query = "SELECT  value FROM properties WHERE name='fid' and path = '".mysql_escape_string($fspath)."'";
      //error_log("PATH2ID:".$query);
       
      $res = mysql_query($query);
      while ($row = mysql_fetch_assoc($res)) {
	$fid= $row["value"];
      }
      mysql_free_result($res);
    }
    //error_log("FSPATH :".$fspath. "=>".$fid);
    return $fid;
  } 

  function docpropinfo(&$doc,$path,$firstlevel)  {
    // map URI path to filesystem path
    $fspath = $this->base . $path;

    // create result array
    $tinfo = array();
    $info = array();
    // TODO remove slash append code when base clase is able to do it itself
    //$info["path"]  = is_dir($fspath) ? $this->_slashify($path) : $path; 
    if ($doc->id == $this->racine) $doc->title= '';
            
    // no special beautified displayname here ...
            
            
    // creation and modification time

    // type and size (caller already made sure that path exists)
    if (($doc->doctype=='D')||($doc->doctype=='S')) {
      // directory (WebDAV collection)	
      $info = array();   
      $info["props"] = array();
      $info["props"][] = $this->mkprop("resourcetype", "collection");
      $info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");             
      $info["props"][] = $this->mkprop("displayname", utf8_encode($doc->title));
      $path=$this->_slashify($path);
      if ($firstlevel) $info["path"]  = $path;
      else $info["path"]  = $path.utf8_encode($doc->title);
      //$info["path"]  = $path;
      $info["props"][] = $this->mkprop("creationdate",   $doc->revdate );
      $info["props"][] = $this->mkprop("getlastmodified", $doc->revdate);
      //error_log("FOLDER:".$path.":".$doc->title);
      // get additional properties from database
      $query = "SELECT ns, name, value FROM properties WHERE path = '$path'";
      $res = mysql_query($query);
      while ($row = mysql_fetch_assoc($res)) {
	$info["props"][] = $this->mkprop($row["ns"], $row["name"], $row["value"]);
      }
      mysql_free_result($res);
      $tinfo[]=$info;
      $query = "REPLACE INTO properties SET path = '".mysql_escape_string($this->_unslashify($info["path"]))."', name = 'fid', ns= '$prop[ns]', value = '".$doc->initid."'";
      mysql_query($query);
	    
    } else {
      // simple document : search attached files     
  
      // $info["props"][] = $this->mkprop("getcontenttype", $this->_mimetype($fspath));
      $afiles=$doc->GetFilesProperties();
      //error_log("READFILES examine :".count($afiles).'-'.$doc->title.'-'.$doc->id);
      $bpath=basename($path);
      $dpath=$this->_slashify(dirname($path));
	    
      //error_log("FILEDEBUG:".$path."-".$bpath."-".$path);
	    

      $path=$this->_slashify($path);
      foreach ($afiles as $afile) {
	$info = array();   
	$info["props"][] = $this->mkprop("resourcetype", "");
	$aname=utf8_encode($afile["name"]);
	if ((!$firstlevel ) || ($aname == $bpath)) {
	  if ($firstlevel) $info["path"]  = $dpath.$aname;
	  else $info["path"]  = $path.$aname;
	  $filename=$afile["path"];
		
	  if (file_exists($filename)) {
		 
	    $info["props"][] = $this->mkprop("displayname", $aname);
	    $info["props"][] = $this->mkprop("creationdate",   filectime($filename)) ;
	    $info["props"][] = $this->mkprop("getlastmodified", filemtime($filename));
	    $info["props"][] = $this->mkprop("getcontenttype", $this->_mimetype($filename));
	    $info["props"][] = $this->mkprop("getcontentlength",intval($afile["size"] ));
	    // get additional properties from database
	    $query = "SELECT ns, name, value FROM properties WHERE path = '".mysql_escape_string($this->_unslashify($info["path"]))."'";
	    $res = mysql_query($query);
	    while ($row = mysql_fetch_assoc($res)) {
	      $info["props"][] = $this->mkprop($row["ns"], $row["name"], $row["value"]);
	    }
	    mysql_free_result($res);
	    //		error_log("PROP:".print_r($info,true));
	    //error_log("PROP:".$query);
	    $tinfo[]=$info;
	    $query = "REPLACE INTO properties SET path = '".mysql_escape_string($this->_unslashify($info["path"]))."', name = 'fid', ns= '$prop[ns]', value = '".$doc->id."'";
	       
	    mysql_query($query);
	    //error_log("FILE:".$afile["name"]."-".$afile["size"]."-".$path);
	  } else {
	    error_log("FILE ERROR:".$doc->title."-".$doc->id."-".$filename);
	  }
	} 
	//error_log("PROP:".$query);
      }
    }

    return $tinfo;
  }
      
  /**
   * virtual path
   */
  function vidpropinfo($path,$docid,$withfile=false) {
    // map URI path to filesystem path

    // create result array
    $tinfo = array();
    $info = array();
    // TODO remove slash append code when base clase is able to do it itself
    //$info["path"]  = is_dir($fspath) ? $this->_slashify($path) : $path; 
            
    // no special beautified displayname here ...
            
    $onlyfile=false;
    if (ereg("/vid-([^\]*)/(.*)",$path,$reg)) {	    
      //error_log("VIDPROP REG :".$reg[2]);
      $onlyfile=$reg[2];
    }
    // creation and modification time

    // directory (WebDAV collection)	
    if (! $onlyfile) {
      $info = array();   
      $info["props"] = array();
      $info["props"][] = $this->mkprop("resourcetype", "collection");
      $info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");             
      $info["props"][] = $this->mkprop("displayname", utf8_encode($path));
      //      $info["props"][] = $this->mkprop("urn:schemas-microsoft-com:", "Win32FileAttributes", "00000001");
      $path=$this->_slashify($path);
      if ($firstlevel) $info["path"]  = $path;
      else $info["path"]  = $path;
      //$info["path"]  = $path;
      $info["props"][] = $this->mkprop("creationdate",   time() );
      $info["props"][] = $this->mkprop("getlastmodified", time());
      //error_log("VIRTUAL FOLDER:".$path.":");
    }
    $tinfo[]=$info;
    if ($withfile || $onlyfile) {
      // simple document : search attached files     
      $doc=new_doc($this->dbaccess,$docid);
      // $info["props"][] = $this->mkprop("getcontenttype", $this->_mimetype($fspath));
      $afiles=$doc->GetFilesProperties();
      //error_log("VIDPROP examine :".count($afiles).'-'.$doc->title.'-'.$doc->id);
      $bpath=basename($path);
      $dpath=$this->_slashify(dirname($path));
	    
      //error_log("FILEDEBUG:".$path."-".$bpath."-".$path);
	    

      $path=$this->_slashify($path);
      foreach ($afiles as $afile) {
	$aname=utf8_encode($afile["name"]);
	      
	//error_log("SEARCH FILE:[$aname] [$onlyfile]");
	if ((!$onlyfile ) || ($aname == $onlyfile)) {
	  $info = array();   
	  //error_log("FOUND FILE:".$aname);
	  $info["props"][] = $this->mkprop("resourcetype", "");
	     
	  $info["props"][] = $this->mkprop("displayname", $aname);
	  if ($firstlevel) $info["path"]  = $dpath.$aname;
	  else $info["path"]  = $path.$aname;
	  $filename=$afile["path"];
	  $info["props"][] = $this->mkprop("creationdate",   filectime($filename)) ;
	  $info["props"][] = $this->mkprop("getlastmodified", filemtime($filename));
	  $info["props"][] = $this->mkprop("getcontenttype", $this->_mimetype($filename));
	  $info["props"][] = $this->mkprop("getcontentlength",intval($afile["size"] ));
	  $err=$doc->canEdit();
	  if ($err!="") {
	    // add read only attributes for windows
	    $info["props"][] = $this->mkprop("urn:schemas-microsoft-com:", "Win32FileAttributes", "00000001");
	  }
	  $tinfo[]=$info;
	}
	      
	//error_log("PROP:".$query);
      }
    }

    return $tinfo;
  }
  /**
   * detect if a given program is found in the search PATH
   *
   * helper function used by _mimetype() to detect if the 
   * external 'file' utility is available
   *
   * @param  string  program name
   * @param  string  optional search path, defaults to $PATH
   * @return bool    true if executable program found in path
   */
  function _can_execute($name, $path = false) 
  {
    // path defaults to PATH from environment if not set
    if ($path === false) {
      $path = getenv("PATH");
    }
            
    // check method depends on operating system
    if (!strncmp(PHP_OS, "WIN", 3)) {
      // on Windows an appropriate COM or EXE file needs to exist
      $exts = array(".exe", ".com");
      $check_fn = "file_exists";
    } else { 
      // anywhere else we look for an executable file of that name
      $exts = array("");
      $check_fn = "is_executable";
    }
            
    // now check the directories in the path for the program
    foreach (explode(PATH_SEPARATOR, $path) as $dir) {
      // skip invalid path entries
      if (!file_exists($dir)) continue;
      if (!is_dir($dir)) continue;

      // and now look for the file
      foreach ($exts as $ext) {
	if ($check_fn("$dir/$name".$ext)) return true;
      }
    }

    return false;
  }

        
  /**
   * try to detect the mime type of a file
   *
   * @param  string  file path
   * @return string  guessed mime type
   */
  function _mimetype($fspath) 
  {
    return trim(`file -ib $fspath`);
    if (@is_dir($fspath)) {
      // directories are easy
      return "httpd/unix-directory"; 
    } else if (function_exists("mime_content_type")) {
      // use mime magic extension if available
      $mime_type = mime_content_type($fspath);
    } else if ($this->_can_execute("file")) {
      // it looks like we have a 'file' command, 
      // lets see it it does have mime support
      $fp = popen("file -i '$fspath' 2>/dev/null", "r");
      $reply = fgets($fp);
      pclose($fp);
                
      // popen will not return an error if the binary was not found
      // and find may not have mime support using "-i"
      // so we test the format of the returned string 
                
      // the reply begins with the requested filename
      if (!strncmp($reply, "$fspath: ", strlen($fspath)+2)) {                     
	$reply = substr($reply, strlen($fspath)+2);
	// followed by the mime type (maybe including options)
	if (preg_match('/^[[:alnum:]_-]+/[[:alnum:]_-]+;?.*/', $reply, $matches)) {
	  $mime_type = $matches[0];
	}
      }
    } 
            
    if (empty($mime_type)) {
      // Fallback solution: try to guess the type by the file extension
      // TODO: add more ...
      // TODO: it has been suggested to delegate mimetype detection 
      //       to apache but this has at least three issues:
      //       - works only with apache
      //       - needs file to be within the document tree
      //       - requires apache mod_magic 
      // TODO: can we use the registry for this on Windows?
      //       OTOH if the server is Windos the clients are likely to 
      //       be Windows, too, and tend do ignore the Content-Type
      //       anyway (overriding it with information taken from
      //       the registry)
      // TODO: have a seperate PEAR class for mimetype detection?
      switch (strtolower(strrchr(basename($fspath), "."))) {
      case ".html":
	$mime_type = "text/html";
	break;
      case ".gif":
	$mime_type = "image/gif";
	break;
      case ".jpg":
	$mime_type = "image/jpeg";
	break;
      default: 
	$mime_type = "application/octet-stream";
	break;
      }
    }
            
    return $mime_type;
  }

  /**
   * GET method handler
   * 
   * @param  array  parameter passing array
   * @return bool   true on success
   */
  function GET(&$options)  {
    error_log("========>GET :".$options["path"]);
    include_once("FDL/Class.Doc.php");
    // get absolute fs path to requested resource
    $fspath = $this->base . $options["path"];

    $fldid=$this->path2id($options["path"],$vid);
    $doc=new_doc($this->dbaccess,$fldid);
    $afiles=$doc->GetFilesProperties();  
    $bpath=basename($options["path"]);
	   
    foreach ($afiles as $afile) {
      $path=utf8_encode($afile["name"]);
      //error_log("GET SEARCH:".$bpath.'->'.$path);
      if (($vid==$afile["vid"]) || ($path == $bpath)) {
	error_log("GET FOUND:".$path.'-'.$afile["path"]);
	$fspath=$afile["path"];
	break;
      }
    }
    // sanity check
    if (!file_exists($fspath)) return false;
            
    // is this a collection?
    if (is_dir($fspath)) {
      return $this->GetDir($fspath, $options);
    }
            
    // detect resource type
    $options['mimetype'] = $this->_mimetype($fspath); 
                
    // detect modification time
    // see rfc2518, section 13.7
    // some clients seem to treat this as a reverse rule
    // requiering a Last-Modified header if the getlastmodified header was set
    $options['mtime'] = filemtime($fspath);
            
    // detect resource size
    $options['size'] = filesize($fspath);
            
    // no need to check result here, it is handled by the base class
    $options['stream'] = fopen($fspath, "r");
            
    header("Cache-control: no-cache"); 
    header("Pragma: no-cache"); // HTTP 1.0
    error_log("GET NO CACHE :".$options["path"]);
    return true;
  }

  /**
   * GET method handler for directories
   *
   * This is a very simple mod_index lookalike.
   * See RFC 2518, Section 8.4 on GET/HEAD for collections
   *
   * @param  string  directory path
   * @return void    function has to handle HTTP response itself
   */
  function GetDir($fspath, &$options) 
  {
    $path = $this->_slashify($options["path"]);
    if ($path != $options["path"]) {
      header("Location: ".$this->base_uri.$path);
      exit;
    }

    // fixed width directory column format
    $format = "%15s  %-19s  %-s\n";

    $handle = @opendir($fspath);
    if (!$handle) {
      return false;
    }

    echo "<html><head><title>Index of ".htmlspecialchars($options['path'])."</title></head>\n";
            
    echo "<h1>Index of ".htmlspecialchars($options['path'])."</h1>\n";
            
    echo "<pre>";
    printf($format, "Size", "Last modified", "Filename");
    echo "<hr>";

    while ($filename = readdir($handle)) {
      if ($filename != "." && $filename != "..") {
	$fullpath = $fspath."/".$filename;
	$name = htmlspecialchars($filename);
	printf($format, 
	       number_format(filesize($fullpath)),
	       strftime("%Y-%m-%d %H:%M:%S", filemtime($fullpath)), 
	       "<a href='$name'>$name</a>");
      }
    }

    echo "</pre>";

    closedir($handle);

    echo "</html>\n";

    exit;
  }

  /**
   * PUT method handler
   * 
   * @param  array  parameter passing array
   * @return bool   true on success
   */
  function PUT(&$options)  {
    error_log("========>PUT :".$options["path"]);
    include_once("FDL/Class.Doc.php");

    $bpath=basename($options["path"]);
    $fldid=$this->path2id($options["path"],$vid);
    if ($fldid) {
      $stat ="204 No Content";
      $options["new"] = false;
      $doc=new_doc($this->dbaccess,$fldid,true);
      $err=$doc->canEdit();
      if ($err == "") {
	$afiles=$doc->GetFileAttributes();  
	//error_log("PUT SEARCH FILES:".count($afiles));
	foreach ($afiles as $afile) {
	  $fname=utf8_encode($doc->vault_filename($afile->id));

	  //error_log("PUT SEARCH:".$bpath);
	  if ($fname == $bpath) {
	    error_log("PUT FOUND:".$path.'-'.$fname);
	      
	    $bpath=utf8_decode($bpath);
	    $doc->saveFile($afile->id,$options["stream"],$bpath);
	    $err=$doc->postModify();
	    $err=$doc->Modify();

	    break;
	  }
	}
      } 
    } else {
      $options["new"] = true;
      $stat = "201 Created";
      if ($options["new"]) {	    
	$dir=dirname($options["path"]);
	$fldid=$this->path2id($dir);
	$fld=new_doc($this->dbaccess,$fldid);
	$err=$fld->canModify();
	if ($err=="") {
	  //error_log("PUT NEW FILE IN:".$dir);
	  $ndoc=createDoc($this->dbaccess,"SIMPLEFILE");
	  if ($ndoc) {
	    $fa=$ndoc->GetFirstFileAttributes();
	    $bpath=utf8_decode($bpath);
	    $ndoc->saveFile($fa->id, $options["stream"] ,$bpath);
	    //		$ndoc->setTitle($bpath);
	    $err=$ndoc->Add();
	    $err=$ndoc->postModify();
	    $err=$ndoc->Modify();
	    error_log("PUT NEW FILE:".$fa->id."-".$ndoc->id);
	    if ($err=="") {
	      $err=$fld->addFile($ndoc->initid);
	      error_log("PUT ADD IN FOLDER:".$err.$fld->id."UID:".($fld->userid));
	      $this->readfolder($dir);
	    }
	  }
	}
      }
    }


    if ($err!="") $stat=false;

    return $stat;
  }


  /**
   * MKCOL method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  function MKCOL($options) 
  {           

    error_log ( "===========>MKCOL :".$options["path"] );
    include_once("FDL/Class.Doc.php");

    if (!empty($_SERVER["CONTENT_LENGTH"])) { // no body parsing yet
      return "415 Unsupported media type";
    }
    $path=$this->_unslashify($options["path"] );
    $fldid=$this->path2id(dirname($options["path"]));
    if ($fldid) {
      $fld=new_doc($this->dbaccess,$fldid);
      $nfld=createDoc($this->dbaccess,"SIMPLEFOLDER");
      $nreptitle=utf8_decode(basename($path));
      $nfld->setTitle($nreptitle);
      $err=$nfld->Add();
      if ($err=="") {
	$err=$fld->AddFile($nfld->initid);
	error_log ( "NEW FLD:".$nfld->initid);
	$this->docpropinfo($nfld,$path,true);
      }
    }

    /*
     if (!file_exists($parent)) {
     return "409 Conflict";
     }

     if (!is_dir($parent)) {
     $name = basename($path);    return "403 Forbidden";
     }

     if ( file_exists($parent."/".$name) ) {
     return "405 Method not allowed";
     }
    */
            

    if ($err!="") {
      return "403 Forbidden : $err";                 
    }

    return ("201 Created");
  }
        
        
  /**
   * DELETE method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  function DELETE($options)   {
    error_log ( "===========>DELETE :".$options["path"] );

    include_once("FDL/Class.Doc.php");
    $fldid=$this->path2id($options["path"]);
    $doc=new_doc($this->dbaccess,$fldid);

    if (! $doc->isAlive()) {
      return "404 Not found";
    }
    if ($doc->doctype=='D') {
      // just rm the folder : is normally empty
      $err=$doc->delete();
      if ($err!="") {
	return "403 Forbidden:$err";    		
      }
      if ($err=="") {
	$query = "DELETE FROM properties WHERE path LIKE '".$this->_slashify($options["path"])."%'";     
	mysql_query($query);
      }

	      
    } else {
      if ($doc->isLocked()) {
	$err=$doc->unlock();
      }
	      
      if ($err!="") {
	return "403 Forbidden:$err";    		
      }
      $err=$doc->delete();
      if ($err!="") {
	return "403 Forbidden:$err";    		
      }
      $query = "DELETE FROM properties WHERE name='fid' and value=".$doc->initid;
      error_log ( $query );
      mysql_query($query);
    }

    return "204 No Content";
  }


  /**
   * MOVE method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  function MOVE($options)   {
    error_log ( "===========>MOVE :".$options["path"]."->".$options["dest"] );
    // no copying to different WebDAV Servers yet
    if (isset($options["dest_url"])) {
      return "502 bad gateway";
    }
	    
    include_once("FDL/Class.Doc.php");
    $psource=$this->_unslashify($options["path"]);
    $pdirsource=$this->_unslashify(dirname($options["path"]));
    $bsource=basename($psource);
	    
    $srcid=$this->path2id($psource);
    $src=new_doc($this->dbaccess,$srcid);
    //error_log ("SRC : $psource ".$srcid );
    $err=$src->canEdit();
    if ($err=="") {
    
      $pdest=$this->_unslashify($options["dest"]);
      $bdest=basename($pdest);
      $destid=$this->path2id($pdest);


      $pdirdest=$this->_unslashify(dirname($options["dest"]));
      $dirdestid=$this->path2id($pdirdest);
      $ppdest=new_doc($this->dbaccess,$dirdestid);


      if ($destid) {
	$dest=new_doc($this->dbaccess,$destid);
	if ($dest->doctype=='D') {	      
	  //error_log ("MOVE TO FOLDER : $destid:".$dest->title);
	  return "502 bad gateway";

	} else {
		
	  error_log ("DELETE FILE : $destid:".$dest->title);
	  // delete file
	  $err=$dest->delete();
	  if ($err=="") {
	    $query = "DELETE FROM properties WHERE name='fid' and value=".$dest->initid;
	    error_log($query);
	    mysql_query($query);
	    // move
	    $err=$ppdest->addFile($srcid);
	    if ($err=="") {
	      // delete ref from source		    
	      $psrcid=$this->path2id($pdirsource);
	      $psrc=new_doc($this->dbaccess,$psrcid);
	      if ($psrc->isAlive()) {
		$err=$psrc->delFile($srcid);
		if ($err=="") {	
		      
		  $src->addComment(sprintf(_("Move file from %s to %s"),
					   utf8_decode($psrc->title),
					   utf8_decode($ppdest->title)));
		  $query = "DELETE FROM properties WHERE path = '$psource'";
		}
	      }
	    }
	  }

	      
	       
	  if ($bdest != $bsource) {
	    error_log (" RENAMETO2  : $bdest");
	    $src->setTitle(utf8_decode($bdest));
	    $err=$src->modify();
	    $this->docpropinfo($src,$pdest,true);
	    if ($err=="") {

	      $query = "DELETE FROM properties WHERE path = '$psource'";
	      error_log($query);
	      mysql_query($query);

	    }
	    error_log (" RENAMETO  : $bdest : $err");
		
	  }
	      
	}
      } else {
	if ($pdirsource != $pdirdest) {
	  // move
	  $err=$ppdest->addFile($srcid);
	  if ($err=="") {
	    $this->docpropinfo($src,$pdest,true);
	    // delete ref from source		    
	    $psrcid=$this->path2id($pdirsource);
	    $psrc=new_doc($this->dbaccess,$psrcid);
	    if ($psrc->isAlive()) {
	      $err=$psrc->delFile($srcid);
	      if ($err=="") {
		$src->addComment(sprintf(_("Move file from %s to %s"),
					 utf8_decode($psrc->title),
					 utf8_decode($ppdest->title)));
		$query = "DELETE FROM properties WHERE path = '$psource'";
		mysql_query($query);
	      }
	    }
	  }		
	  error_log ("MOVE TO PARENT2 FOLDER : $dirdestid:".$err);
	}
	if ($err=="") {
	  if ($bdest != $bsource) {
	    if ($src->doctype=='D') {
	      $src->setTitle(utf8_decode($bdest)); 
		    
	    } else {

	      $afiles=$src->GetFilesProperties();  		  
	      foreach ($afiles as $afile) {
		$path=utf8_encode($afile["name"]);
		error_log("RENAME SEARCH:".$bsource.'->'.$path);
		if ($path == $bsource) {
		  error_log("RENAME FOUND:".$path.'-'.$afile["path"]);
		  $fspath=$afile["path"];
		  error_log(print_r($afile,true));
		
		  $vf = newFreeVaultFile($this->dbaccess);
		  $vf->Rename($afile["vid"],utf8_decode($bdest));
		  $src->addComment(sprintf(_("Rename file as %s"),utf8_decode($bdest)));
		  $src->postModify();
		  $err=$src->modify();
		}
	      }

	    }
	    $err=$src->modify();
	    $this->docpropinfo($src,$pdest,true);
	    if ($err=="") {

	      $query = "DELETE FROM properties WHERE path = '$psource'";
	      error_log($query);
	      mysql_query($query);

	    }
	    error_log (" RENAMETO2  : $bdest : $err");
	  }
	}
      }
      if  ($src->doctype=='D') {
	$query = "UPDATE properties 
                        SET path = REPLACE(path, '".$psource."', '".$pdest."') 
                        WHERE path LIKE '".$psource."%'";
	mysql_query($query);
	error_log($query);
      }


      if ($err=="") return "201 Created";
    }	    
    error_log("DAV MOVE:$err");
    return "403 Forbidden";  
	    
  }

  /**
   * COPY method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  function COPY($options) {
    error_log ( "===========>COPY :".$options["path"]."->".$options["dest"] );
    // no copying to different WebDAV Servers yet
    if (isset($options["dest_url"])) {
      return "502 bad gateway";
    }
	    
    include_once("FDL/Class.Doc.php");
    $psource=$this->_unslashify($options["path"]);
    $pdirsource=$this->_unslashify(dirname($options["path"]));
    $bsource=basename($psource);
	    
    $srcid=$this->path2id($psource);
    $src=new_doc($this->dbaccess,$srcid);
    error_log ("SRC : $psource ".$srcid );

    $pdest=$this->_unslashify($options["dest"]);
    $bdest=basename($pdest);
    $destid=$this->path2id($pdest);


    $pdirdest=$this->_unslashify(dirname($options["dest"]));
    $dirdestid=$this->path2id($pdirdest);
    $ppdest=new_doc($this->dbaccess,$dirdestid);


		
    if ($destid) {
      $dest=new_doc($this->dbaccess,$destid);
      if ($dest->doctype=='D') {	      
	error_log ("COPY FILE TO REPLACE FOLDER NOT POSSIBLE NORMALLY: $destid:".$dest->title);
	return "502 bad gateway";

      } else {
	error_log ("DELETE FILE : $destid:".$dest->title);
	// delete file
	$err=$dest->delete();

	   

	if ($err=="") {
		
	  $query = "DELETE FROM properties WHERE name='fid' and value=".$dest->initid;
	  error_log($query);
	  mysql_query($query);
	}
      }
    }
    if ($err=="") {
      // copy
      if ($src->doctype=="D") {
	// copy of directory 
	return "501 not implemented";
      } else {
	   
	$copy=$src->copy();

	    
	error_log("COPY :".$copy->id);
	$afiles=$copy->GetFilesProperties();  
	error_log("# FILE :".count($afiles));
	$ff=$copy->GetFirstFileAttributes();
	    
	$f=$copy->getValue($ff->id);
	error_log("RENAME SEARCH:".$f);
	if (ereg ("(.*)\|(.*)", $f, $reg)) {
	  $vf = newFreeVaultFile($this->dbaccess);
	  $vid=$reg[2];
	      
	  $vf->Rename($vid,utf8_decode($bdest));
	  $copy->addComment(sprintf(_("Rename file as %s"),utf8_decode($bdest)));
	  $copy->postModify();
	  $err=$copy->modify();
	}
	    
		  
	    

	$err=$ppdest->addFile($copy->id);
	if ($err=="") {
	  $this->docpropinfo($copy,$pdest,true);
	}
	      

	error_log ("MOVE TO PARENT FOLDER : $dirdestid:".$err);	
	if ($bdest != $bsource) {
	  $copy->setTitle(utf8_decode($bdest));
	  $err=$copy->modify();
	  $this->docpropinfo($copy,$pdest,true);
		
	  error_log (" RENAMETO  : $bdest : $err");
		
	}
      }
    } 


    if ($err=="") return "201 Created";
	    
    error_log("DAV MOVE:$err");
    return "403 Forbidden";  
	    
  }
        

  /**
   * PROPPATCH method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  function PROPPATCH(&$options) 
  {
    global $prefs, $tab;
    error_log ( "===========>PROPPATCH :".$options["path"] );

    $msg = "";
            
    $path = $options["path"];
            
    $dir = dirname($path)."/";
    $base = basename($path);
            
    foreach($options["props"] as $key => $prop) {
      if ($prop["ns"] == "DAV:") {
	$options["props"][$key]['status'] = "403 Forbidden";
      } else {
	if (isset($prop["val"])) {
	  $query = "REPLACE INTO properties SET path = '$options[path]', name = '$prop[name]', ns= '$prop[ns]', value = '$prop[val]'";
	  error_log($query);
	} else {
	  $query = "DELETE FROM properties WHERE path = '$options[path]' AND name = '$prop[name]' AND ns = '$prop[ns]'";
	}       
	mysql_query($query);
      }
    }
                        
    return "";
  }


  /**
   * LOCK method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  function LOCK(&$options)   {
    error_log ( "===========>LOCK :".$options["path"] );
    include_once("FDL/Class.Doc.php");
    if (isset($options["update"])) { // Lock Update
      $query = "UPDATE locks SET expires = ".(time()+300). "and token='".$options["update"]."'";
      mysql_query($query);
                
      if (mysql_affected_rows()) {
	$options["timeout"] = 300; // 5min hardcoded
	return true;
      } else {
	return false;
      }
    }
            
    $fldid=$this->path2id($options["path"],$vid);
    $doc=new_doc($this->dbaccess,$fldid);
    if ($doc->isAffected()) {
      error_log("LOCK ".$doc->title);

      $err=$doc->lock(true);
      if ($err=="") {
	$options["timeout"] = time()+300; // 5min. hardcoded

	$query = "INSERT INTO locks
                        SET token   = '$options[locktoken]'
                          , path    = '$options[path]'
                          , owner   = '$options[owner]'
                          , expires = '$options[timeout]'
                          , exclusivelock  = " .($options['scope'] === "exclusive" ? "1" : "0");
	mysql_query($query);
	if (mysql_affected_rows()) {
	  return "200 OK";
	} 
      } else {
	error_log("Cannot lock ".$doc->title.":$err");
      }
    } else {
      return true;
    }
    return "409 Conflict";
  }

  /**
   * UNLOCK method handler
   *
   * @param  array  general parameter passing array
   * @return bool   true on success
   */
  function UNLOCK(&$options) {
	  
	  
    error_log ( "===========>UNLOCK :".$options["path"] );
    include_once("FDL/Class.Doc.php");
    $fldid=$this->path2id($options["path"],$vid);
    $doc=new_doc($this->dbaccess,$fldid);
	    
    if ($doc->isAffected()) {
      $err=$doc->unlock(true);
      if ($err=="") {
	$query = "DELETE FROM locks
                      WHERE path = '$options[path]'
                        AND token = '$options[token]'";
	mysql_query($query);
	if (mysql_affected_rows()) return "204 No Content";
      }
    } else {      
      return "204 No Content";
    }
    error_log("Cannot unlock ".$doc->title.":$err");
    return  "409 Conflict";
  }

  /**
   * checkLock() helper
   *
   * @param  string resource path to check for locks
   * @return bool   true on success
   */
  function checkLock($path) 
  {
    $result = false;
            
    $query = "SELECT owner, token, expires, exclusivelock
                  FROM locks
                 WHERE path = '$path'
               ";
    $res = mysql_query($query);

    if ($res) {
      $row = mysql_fetch_array($res);
      mysql_free_result($res);

      if ($row) {
	$result = array( "type"    => "write",
			 "scope"   => $row["exclusivelock"] ? "exclusive" : "shared",
			 "depth"   => 0,
			 "owner"   => $row['owner'],
			 "token"   => $row['token'],
			 "expires" => $row['expires']
			 );
      }
    }
    if (! $result) {
      
      include_once("FDL/Class.Doc.php");
      $fldid=$this->path2id($options["path"],$vid);
      $doc=new_doc($this->dbaccess,$fldid);
	    
      if ($doc->isAffected()) {
	if ($doc->isLocked(true)) {
	  $result = array( "type"    => "write",
			   "scope"   =>  "exclusive" ,
			   "depth"   => 0,
			   "owner"   => $doc->locked,
			   "token"   => 'opaquelocktoken:'.md5($doc->id),
			   "expires" => time()+3600
			 );
	  error_log("FREEDOM LOCK ".$doc->title);
	}
      }
    }

    return $result;
  }


  /**
   * create database tables for property and lock storage
   *
   * @param  void
   * @return bool   true on success
   */
  function create_database()   {
    // TODO
    return false;
  }


  /**
   * create database tables for property and lock storage
   *
   * @param  void
   * @return bool   true on success
   */
  function addsession($sessid,$vid,$docid,$owner,$expire=0)  {

    $query = "INSERT INTO sessions
                        SET session   = '$sessid'
                          , vid = $vid
                          , fid = $docid
                          , owner    = '$owner'
                          , expires   = '$expire'";
    mysql_query($query);

    //error_log("addsession $query");
    if (mysql_affected_rows()) {
      return true;
    } 
    return false;
  }


  /**
   * get login from session
   *
   * @param  void
   * @return bool   true on success
   */
  function getLogin($docid,$vid,$sessid)  {

    $query = "select owner from  sessions where 
                         session   = '$sessid' and
                         vid = $vid and
                         fid = $docid";

    //error_log("getLogin $query");
    $res = mysql_query($query);
    $row = mysql_fetch_assoc($res);
    $owner= $row["owner"];
      
    mysql_free_result($res);

    return $owner;
    
    return false;
  }

  /**
   * get session from login
   *
   * @param  int $docid document identificator
   * @param  int $vid vault identificator
   * @param  string $owner user login
   * @return string 
   */
  function getSession($docid,$vid,$owner)  {

    $query = "select session from  sessions where 
                         owner   = '$owner' and
                         vid = $vid and
                         fid = $docid";

    //error_log("getSession $query");
    $res = mysql_query($query);
    $row = mysql_fetch_assoc($res);
    $sid= $row["session"];
      
    mysql_free_result($res);

    return $sid;
    
  }


}


?>
