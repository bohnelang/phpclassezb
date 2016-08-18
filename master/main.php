<?php
/*
 * Load all green and yellow journals from EZB (Elektronische Zeitschriftenbibliothek - EZB - Universität Regensburg)
 *
 * (c) Andreas Bohne-Lang / 2016 / Medical Faculty Mannheim of University Heidelberg
 *
 * Distributed by GPL
 *
 */

/* ---- vvvv Config this part vvvv ---- */

/*
 * EZB_Lib_Code is your bibid from EZB - you need to be subscriber of EZB
 *
 */
$EZB_Lib_Code="UBHE";


/*
 * Lists of sections that sould be load
 * array("") means ALL (Cave!) - a run can take 10 hours and more
 * array("U","TA-TD","WW-YZ") lists the topics taht should bea load
 * ZG = Technik 
 * See the list on start of the program...
 */
$EZB_TopicList2Load=array("ZG");

/*
 * Could be empty but the idea was that an admin (reading the web log files) could contact 
 * you in a case of any problems. 
 */
$EZB_Admin2Admin="Your-Email-Address";

/* 
 * EZB Color codes 
 * 1 = green
 * 2 = yellow
 * 4 = red
 * 6 = yellow_red
 * 7 = green_yellow_red
 */
$EZB_Color_to_Load=array(1,2);

/* ---- ^^^^ Config this part ^^^^ ---- */

#------------------------------------------------------------------
	
require_once("ezb.class.php");

$ezb=new ezb_regensburg();
$ezb->set_bibid($EZB_Lib_Code);
$ezb->set_contact($EZB_Admin2Admin);
$ezb->set_selected_color( $EZB_Color_to_Load );
$ezb->set_translate_title2utf8(true);
#$ezb->debug_on();

$alles=array();
$sl=0;

$ezbfachgebiete_liste = $ezb-> get_fachgebiete_liste();
print_r($ezbfachgebiete_liste);

echo "\n\n ----------------- \n\n";
# Load list of topics 
if( empty($EZB_TopicList2Load) || ( isset($EZB_TopicList2Load[0]) && empty($EZB_TopicList2Load[0]) ) ) {
	foreach($ezbfachgebiete_liste as $tl) $ezb_faecher[] = $tl[notation] ; 
}else{
	$ezb_faecher=$EZB_TopicList2Load;
} 

echo "Load this:\n";
print_r($ezb_faecher);

# Load all subscribed journals 
foreach($ezb_faecher as $fachabk ){
	echo "Load $fachabk...\n";
	echo "  get list for this section...\n";
	$zeitschriften_liste=$ezb-> get_faecher_liste($fachabk);

	# Load all details for all journals
	foreach( $zeitschriften_liste as $zlind => $zlval){
		echo "\t get details for ".$zlval["title"][0]."\n";
		$jid=$zlval["jourid"];
		$alles[$jid]=$ezb-> get_zeitschrift_detail($zlval["jourid"]);
		$alles[$jid][0]["topic"] = $gname ;

		# Do not remove the sleep() - it protects EZB from overload :-)
		if($sl==10){ $sl=0; sleep(1); } else { $sl++; }
	}
}


# Output the result 

print_r($alles);


?>
