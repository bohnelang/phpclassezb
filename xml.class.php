<?php
/*
 * Wrap the xml22-0_4_0 decoding code... 
 *
 * (c) Andreas Bohne-Lang / 2016 / Medical Faculty Mannheim of University Heidelberg
 *
 * Distributed by GPL
 *
 */


class xmltool {

	var $encoding_from="";
	var $encoding_to="";

	function set_encoding_from($encoding){ $this-> encoding_from = $encoding; }
	function set_encoding_to ($encoding){ $this-> encoding_to = $encoding; }
	
	function xmltool ()
	{

		//error_reporting(~E_ALL);

		#print_r($_SERVER); die;
		//phpinfo(); die;

		if(@isset($_SERVER["PWD"])){   // bei aufruf via script stehen nicht die normalen env-vars zu verfuegung
			$path=dirname($_SERVER["PWD"]); // $HOME von openlit
			ini_set("include_path", ini_get('include_path') . PATH_SEPARATOR .   "$path/xml22-0_4_0/xml22");
		} 
		if(@isset($_SERVER["PATH_TRANSLATED"])){ // anders als beim webaufruf.	
			$path = dirname($_SERVER["PATH_TRANSLATED"]);
			ini_set("include_path", ini_get('include_path') . PATH_SEPARATOR .   "$path/xml22-0_4_0/xml22");
		}
		ini_set("include_path", ini_get('include_path') . PATH_SEPARATOR .   "./xml22-0_4_0/xml22");

		require_once("xml22.inc");

		$options = array( 'XML22_OPT_EXTERNALS' => TRUE );
		xml22_setup($options);
	}	



	// fly recursivly through the xml doc
	/*

   [14] => Array
        (
            [tag] => CirculationLetterEntry
            [index] => 14
            [level] => 2
            [parindex] => 4
            [attributes] => Array
                (
                    [letterNumber] => 23
                    [letterType] => 1
                )

            [children] => Array
                (
                    [0] => 15
                    [1] => 17
                    [2] => 23
                    [3] => 29
                )


	   [5] => Array
	        (
	            [tag] => Item
	            [index] => 5
	            [level] => 2
	            [parindex] => 3
	            [attributes] => Array
	                (
	                    [Name] => PubDate
	                    [Type] => Date
	                )

	            [content] => 2001 Jun 12
		ODER
  		   [children] => Array
        	        (
                        [0] => 4
                        [1] => 5
                    	[2] => 6
			)

	        )
	*/
	function _sub_xml2rec(&$doc,$no)
	{
		$ret=array();

		# Wenn es Attribute gibt -> diese auff√ºhren		
		if(isset($doc[$no]["attributes"])){
			foreach($doc[$no]["attributes"] as $attribute_ind => $attribute_val){
				$ret[$attribute_ind]=$attribute_val;
			}
		}

		# Ebene dar√ºber
		if(isset($doc[$no]["children"])){
			foreach($doc[$no]["children"] as $children_index => $children_val){
				$index=$doc[$children_val]["tag"];
				$neues_element=$this->_sub_xml2rec($doc,$children_val);

				# Wenn es mehr als ein Element gibt, brauch ich die Daten in einem Array
				if(!isset($ret[$index])){ 		// Element gibt es noch nicht 
					$ret[$index][]=$neues_element;	// ok dann wird es gesetzt
				}else{ 					// wenn doch schon vorhanden
				
					if(!isset($ret[$index][0])){	// schon ein Array?	
						$ret[$index]=array($ret[$index]); // nein, dann umwandeln
					}
					if(!is_array($ret[$index])) $ret[$index]=array($ret[$index]);
					$ret[$index][]=$neues_element;		// und neues hinzu	
				}
			}
		}

		# Inhalt
		if(isset($doc[$no]["content"])){
			if(count($ret)==0){			// evt. sind schon Attributeintraege vorhanden
				$ret = $doc[$no]["content"];	
			} else {
				$ret["content"] = $doc[$no]["content"];
			}
		}

		return $ret;	
	}

	function xml2rec($efile)
        {
                $ret=array();
		$mark=array();
		$doc=array();

		$efile=trim($efile);
		#print_r($efile);
                $doc=xml22_parse($efile, FALSE, $this->encoding_from, $this->encoding_to );
                #print_r($doc);
		#die("EX");
		if(@empty($doc)) return $ret;
	
		foreach($doc as $ind => $val){
			if($val["level"]==0){	
				$ret["xml"][$ind]=$this->_sub_xml2rec($doc,$ind);
			}
		}
	
                return $ret;
        }


	function _test2()
        {
                $efile=implode('',file("xml/pubmed.xml.txt"));
                $ret=$this-> xml2rec($efile);
		print_r($ret);
		$xmltxt=$this->encode_search_result_to_xml($ret);
		printf($xmltxt);	
        }       

}


?>
