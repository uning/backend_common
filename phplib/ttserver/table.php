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

	for($j=0;$j<30;$j++){
		$data['field'.$j]= uniqid();
	}

	$start = floor($tt->num()/100);
        echo "start=$start \n";
	for($i=1;$i<100000;$i++)
	{
		$cur = $i +$start;
		$data['u']= $cur;
		for($j=0;$j<100;$j++){
			$data['v']=$cur.':'.rand()%10;
			$tt->put(null,$data);
		}
		if($cur%100==0){
			record_time($st,"insert $cur*100"); 
		}

	}

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

