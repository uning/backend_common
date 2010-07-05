<?php


function record_time(&$start,$usage="")
{
	$end  = microtime(true); 
	$cost=$end-$start;
	$cost=ceil(1000000*$cost);
	if($usage)
		echo "$usage use time $cost us\n";
	$start = $end;
}

echo "<pre>\n";
record_time($st); 
try {
	/* Connect to a table database */
	$tt = new TokyoTyrantTable("localhost", 10000);
	record_time($st,"connect"); 

        $q = $tt->getQuery();
	record_time($st,"getQuery"); 

	$id = rand()%1000000+10;
	echo "id=$id\n";

	$u = $tt->get($id);
	record_time($st,"get"); 



	echo "u=".$u['u']."\n";
	$uid= $u['u'];
	$q->addCond('u',TokyoTyrant::RDBQC_STREQ,$uid);
	$us=$q->search();
	record_time($st,"search user"); 


        echo 'count($us)='.count($us)."\n";
        $ttotal = 0;
	for($i=0;$i<10;$i++){
		$q = $tt->getQuery();
		record_time($st,"getQuery"); 
		$q->addCond('v',TokyoTyrant::RDBQC_STREQ,$uid.":$i");
		$uss=$q->search();
		record_time($st,"search user with v"); 

		$q = $tt->getQuery();
		record_time($st,"getQuery"); 
		$q->addCond('v',TokyoTyrant::RDBQC_STREQ,$uid.":$i");
		$q->addCond('u',TokyoTyrant::RDBQC_STREQ,$uid);
		$uss1=$q->search();
		record_time($st,"search user with id and v"); 
		echo 'count($uss)='.count($uss).' =='.count($uss1)."\n";
                $ttotal += count($uss1);
                
           
	}
        echo 'count($us)='.count($us)."==$ttotal\n";
         
       echo "u:\n";
       print_r($u);
       echo "us:\n";
       print_r($us);
       echo "uss:\n";
       print_r($uss);
       echo "uss1:\n";
       print_r($uss1);
        
        

} catch (TokyoTyrantException $e) {
	if ($e->getCode() === TokyoTyrant::TTE_KEEP) {
		echo "Existing record! Not modified\n";
	} else {
		echo "Error: " , $e->getMessage() , "\n"; 
	}
} catch(Exception $e){
	var_dump($e);
	$e->print_trace();
}

