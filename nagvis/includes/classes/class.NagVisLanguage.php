<?
##########################################################################
##     	                           NagVis                               ##
##        *** Klasse zum verarbeiten der Sprachdateien ***              ##
##                               Lizenz GPL                             ##
##########################################################################

/**
* This Class handles the NagVis language files
*/
class NagVisLanguage {
	var $MAINCFG;
	var $MAPCFG;
	var $languageFile;
	
	var $indexes = Array();
	var $values = Array();
	var $nb=0;
	
	/**
	* Constructor
	*
	* @param config $MAINCFG
	* @param config $MAPCFG
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
	function NagVisLanguage(&$MAINCFG,&$MAPCFG,$type='') {
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		
		if($this->MAINCFG->getRuntimeValue('wui')) {
			$type = 'wui_';
		} elseif(isset($type) && $type != '') {
			$type .= '_';
		}
		
		$this->languageFile = $this->MAINCFG->getValue('paths', 'cfg').'languages/' . $type . $this->MAINCFG->getValue('global', 'language').'.txt';
	}

	function readLanguageFile() {
		# we check that the language file specified exists and is readable
		//if(!is_readable($filepath)) {
		//	print "<script>alert('Impossible to read from the language file ".$filepath."');</script>";
		//	return;
		//}
		if($this->checkLanguageFileReadable(1)) {
			# we fill the indexes and values arrays, by reading all the file lines
			array_push($this->indexes,"");
			array_push($this->values,"");
			
			$fic = fopen($this->languageFile,"r");
			while (!feof($fic)) {
				$myline = fgets($fic, 4096);
				$myline = substr($myline,0,strlen($myline)-1);
				if(strlen($myline)>0) {
					$temp = explode("~",$myline);
					array_push($this->indexes,$temp[0]);
					array_push($this->values,$this->replaceSpecial($temp[1]));
					$this->nb = $this->nb+1;	
				}
			}
			fclose ($fic);
			
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Replaces some special chars like �,�,�,...
	 * 
	 * @param String $str
	 * 
	 * @author Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function replaceSpecial($str) {
		// FIXME: replace with sgml-chars
		return $str;
	}
	
	/**
	* Check if the Language-File is readable.
	*
	* @param boolean $printErr
	*
	* @author Lars Michelsen <larsi@nagios-wiki.de>
	*/
    function checkLanguageFileReadable($printErr) {
        if(!is_readable($this->languageFile)) {
        	if($printErr == 1) {
				print "<script>alert('Impossible to read from the language file ".$filepath."');</script>";
				// FIXME
				//$FRONTEND = new frontend($this->MAINCFG,$this->MAPCFG);
				//$FRONTEND->openSite();
				//$FRONTEND->messageBox("24", "LANGFILE~".$this->languageFile);
				//$FRONTEND->closeSite();
				//$FRONTEND->printSite();
			}
	        return FALSE;
        } else {
        	return TRUE;
        }
    }
	
	function getText($myid) {
		if (count($this->indexes)==1) {
			print "The language coudln't be loaded";
			return "";
		} else {
			$key = array_search($myid,$this->indexes); 
			if ($key!==null&&$key!==false) {
				return $this->values[$key];
			} else {
				print "<script>alert('Impossible to find the index ".$myid." in the language file".$this->$filepath."');</script>";
				return FALSE;
			}
		}
	}
	
	
	function getTextReplace($myid,$myvalues) {
		if (count($this->$indexes)==1) {
			print "The language coudln't be loaded";
			return "";
		} else {
			$key = array_search($myid,$this->indexes); 
			if ($key !== null && key !== false) {
				$message = $this->values[$key];
				$vars = explode(',', $myvalues);
				for($i=0;$i<count($vars);$i++) {
					$var = explode('=', $vars[$i]);
					$message = str_replace("[".$var[0]."]", $var[1], $message);
				}
				return $message;
			} else {
				print "<script>alert('Impossible to find the index ".$myid." in the language file".$this->$filepath."');</script>";
				return "";
			}
		}
	}
	
	function getTextSilent($myid) {
		if(count($this->indexes) == 1) {
			return "";
		} else {
			// Cause of the new config-format this has to be case insensitive
			$key = $this->arrayLSearch($myid,$this->indexes);
			if($key !== null && $key !== false && !is_array($key)) {
				return str_replace("'","\'",$this->values[$key]);
			} else {
				return "";
			}
		}
	}
	
	function arrayLSearch($str,$array) {
		$found = Array();
		
		foreach($array as $k => $v) {
			if(@strtolower($v) == @strtolower($str)) {
				$found[] = $k;
			}
		}
		
		$f = @count($found);
		
		if($f == 0) {
			return FALSE;
		} elseif($f == 1) {
			return $found[0];
		} else {
			return $found;
		}
	}
}
?>