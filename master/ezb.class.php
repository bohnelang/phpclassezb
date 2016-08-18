<?php
/*
 * Get journal data from EZB (Elektronische Zeitschriftenbibliothek - EZB - Universität Regensburg)
 *
 * (c) Andreas Bohne-Lang / 2016 / Medical Faculty Mannheim of University Heidelberg
 *
 * Distributed by GPL
 *
 */

require_once("xml.class.php");

class ezb_regensburg
{
	var $bibid="";
	var $xmltool;

	var $url_fachgebiete_base="http://rzblx1.uni-regensburg.de/ezeit/fl.phtml?colors=7&lang=de&xmloutput=1";
	var $url_faecher_base="http://rzblx1.uni-regensburg.de/ezeit/fl.phtml?&lang=de&xmloutput=1";
	var $url_journal_detail_base="http://rzblx1.uni-regensburg.de/ezeit/detail.phtml?lang=de&xmloutput=1";

	var $targetcharset;
	var $contact="";
	var $debug=FALSE;
	var $selectedcolor="";
	var $translate_title2utf8=false;

	function set_translate_title2utf8($in=false){ $this->translate_title2utf8 = $in; }	

	function debug_stat() { return $this->debug; }
	function debug_on() { $this->debug=TRUE; }
	function debug_off() { $this->debug=FALSE; }
 
	function set_contact($mail) { $this->contact = $mail; }

	function set_bibid($bibid) { $this->bibid = $bibid; }
	function get_bibid() { return $this->bibid; }

	function get_selected_color() { return $this->selectedcolor; }
	function set_selected_color($select) { $this->selectedcolor="&selected_colors[]=".join("&selected_colors[]=",$select); }

	function set_target_charset($charset) { $this->targetcharset = $charset; }

	function ezb_regensburg($bibid="")
	{
		if( !empty( $bibid ) ) $this->bibid = $bibid; 
		$this->xmltool = new xmltool();
		$this->targetcharset="utf-8";
		$this->set_selected_color(array("1","2"));
	}




	function _lade_xml_daten($url)
	{
		# <?xml version="1.0" encoding="iso-8859-1

		for($i=0;$i<10;$i++){
			$xfile = @file($url);
			if(! empty($xfile)) break;
			sleep(5);
		}

		if(empty($xfile)) return array(); 
		$efile=implode("", $xfile);

		$tmp = str_replace("\""," ",substr($efile,0,80));
		$tmp=strstr($tmp,"encoding=");
		if( !empty($tmp)){
			list($v1,$v2)=sscanf($tmp,"%s %s");
			#printf("Charsert:$v2\n");
			$this->xmltool->set_encoding_from($v2);
			# hier noch rumbasteln
			#	
			if( strtolower($v2) != strtolower($this->targetcharset)){
				#printf("Conv %s %s \n",$v2,$this->targetcharset);
				$efile=iconv( $v2,$this->targetcharset."//IGNORE.",$efile);
			}
			#	
		}	
		$doc=$this->xmltool->xml2rec($efile);
		if( $this->debug){ printf("URL:%s\n",$url); print_r($doc);}
		return $doc;
	}	

	function get_fachgebiete_liste()
	{
		$url=sprintf("%s&bibid=%s&contact=%s",$this->url_fachgebiete_base,urlencode($this->bibid),urlencode($this->contact) );

		$doc=$this->_lade_xml_daten($url);
	
		return $doc["xml"][0]["ezb_subject_list"][0]["subject"];
	}

	function get_faecher_liste($fachgebiet)
        {
		function unichr($dec) { if ($dec < 128) { $utf = chr($dec); } else if ($dec < 2048) { $utf = chr(192 + (($dec - ($dec % 64)) / 64)); $utf .= chr(128 + ($dec % 64)); } else { $utf = chr(224 + (($dec - ($dec % 4096)) / 4096)); $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64)); $utf .= chr(128 + ($dec % 64)); } return $utf; } 

		$ret=array();
		$sl=0;
		
                $url=sprintf("%s&bibid=%s&notation=%s&sc=A&lc=Z&contact=%s",$this->url_faecher_base.$this->selectedcolor,urlencode($this->bibid),urlencode($fachgebiet),urlencode($this->contact));
		if( $this->debug) echo "$url\n";
		$doc=$this->_lade_xml_daten($url);

		if( $this->debug)print_r($doc); 
		$seiten=$doc["xml"][0]["ezb_alphabetical_list"][0]["navlist"][0]["other_pages"];

		foreach($seiten as $sval){
			for($xxi=0;$xxi<=1; $xxi++){
			for($xxj=0;$xxj<=1; $xxj++){
                        if($xxi==0)$url=sprintf("%s&bibid=%s&notation=%s&sc=%s&lc=%s&contact=%s",$this->url_faecher_base.$this->selectedcolor,urlencode($this->bibid),urlencode($fachgebiet),$sval["sc"],$sval["lc"],urlencode($this->contact));       
                        if($xxi==1)$url=sprintf("%s&bibid=%s&notation=%s&sc=&lc=%s&contact=%s",$this->url_faecher_base.$this->selectedcolor,urlencode($this->bibid),urlencode($fachgebiet),$sval["lc"],urlencode($this->contact));    
			if( $this->debug) echo "\n$url\n";
			$xdoc=$this->_lade_xml_daten($url);
			#print_r($xdoc);
			$journals = $xdoc["xml"][0]["ezb_alphabetical_list"][0]["alphabetical_order"][0]["journals"][0]["journal"];
			foreach( $journals as $jind => $jval){
				if( isset($jval["title"][0])){
					if($this->translate_title2utf8){
						if(strpos($jval["title"][0],"#")!==false){
							$jval["title"][0]=preg_replace("/#(\d{2,5});/e", "unichr($1);",$jval["title"][0]);
						}
					}
				}
				if( isset($jval["jourid"]) ){
					$jid=$jval["jourid"];
					$ret[$jid]=$jval;
					#break;  ###### ###### #### ### 
				}
			}
			
			if( isset($xdoc["xml"][0]["ezb_alphabetical_list"][0]["next_fifty"])) 
			foreach($xdoc["xml"][0]["ezb_alphabetical_list"][0]["next_fifty"] as $zval){
				if($xxj==0)$url=sprintf("%s&bibid=%s&notation=%s&sc=%s&lc=%s&sindex=%s&contact=%s",$this->url_faecher_base.$this->selectedcolor,urlencode($this->bibid),urlencode($fachgebiet),$sval["sc"],$sval["lc"],$zval["sindex"],urlencode($this->contact));
				if($xxj==1)$url=sprintf("%s&bibid=%s&notation=%s&sc=&lc=%s&sindex=%s&contact=%s",$this->url_faecher_base.$this->selectedcolor,urlencode($this->bibid),urlencode($fachgebiet),$sval["sc"],$zval["sindex"],urlencode($this->contact));
				if( $this->debug)echo "$url\n";
				$x2doc=$this->_lade_xml_daten($url);	
                        	if( $this->debug)print_r($doc);
                        	$journals = $x2doc["xml"][0]["ezb_alphabetical_list"][0]["alphabetical_order"][0]["journals"][0]["journal"];
				if( !empty($journals) && is_array($journals))
                        	foreach( $journals as $jval){
					if( isset($jval["title"][0])){
                                        	if($this->translate_title2utf8){
                                                	if(strpos($jval["title"][0],"#")!==false){
                                                        	$jval["title"][0]=preg_replace("/#(\d{2,5});/e", "unichr($1);",$jval["title"][0]);
                                                	}
                                        	}
                                	}

                                	if( isset($jval["jourid"]) ){
                                        	$jid=$jval["jourid"];
                                        	$ret[$jid]=$jval;
                                        	#break;  ###### ###### #### ###
                                	}
				}
                        }
			if($sl==10){ $sl=0; sleep(1); } else { $sl++; }	
		}}
		}		
                return $ret;
        }


	function get_zeitschrift_detail($journalid)
	{
	# &jour_id=8414

               	$ret=array();

                $url=sprintf("%s&bibid=%s&jour_id=%s&contact=%s",$this->url_journal_detail_base,urlencode($this->bibid),$journalid,urlencode($this->contact));

                if( $this->debug)echo "$url\n";
		$doc=$this->_lade_xml_daten($url);
  
		if($this->translate_title2utf8) $doc["xml"][0]["ezb_detail_about_journal"][0]["journal"][0]["title"][0] =  preg_replace("/#(\d{2,5});/e", "unichr($1);", $doc["xml"][0]["ezb_detail_about_journal"][0]["journal"][0]["title"][0]);

		return $doc["xml"][0]["ezb_detail_about_journal"][0]["journal"]; 
	}


	function test()
	{	

		$ezb=new ezb_regensburg("UBHE");

		print_r($ezb-> get_fachgebiete_liste());

		print_r($ezb-> get_faecher_liste(array("G")));
		#print_r($ezb-> get_faecher_liste(array("WW-YZ","SQ-SU","V","W","AN")));

		print_r($ezb-> get_zeitschrift_detail(9910));
	}

}
?>
