<?php


//Check first wheter tried to update this day
//######################################################################

//Options
$file_name =PROJECT_ROOT . "/auto_update_timestamp.txt";

//Check File existing
if (test_file_existing($file_name)==false){
        //File does not exist, creat it
        write_in_file($file_name,"");
}


//Open File
$information = read_from_file($file_name);

//Get now timestamp
$date = new DateTime();
$timestamp_now = $date->getTimestamp();


// 60 Sec. ~ 60 Sec.
// 10 Sec. ~ 10 Sec.
if ($information + (61) < $timestamp_now){
    //Last updtae check is older more than x time 

        //Info Try with iframe
        $url = strtolower(mb_strcut($_SERVER['SERVER_PROTOCOL'], 0, ($_SERVER['SERVER_PROTOCOL']-4))).":\\\\".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'];
        $url=substr($url, 0, -strlen(basename($_SERVER['PHP_SELF']))) ;

        echo("<iframe src='" . $url . "key_it/auto_update.php' width='1px' height='1px' style='border:1px solid black;'></iframe>");
        
        //Hint
        //echo("Please update software!");

        //Try with include -- FUNKTIONIERT NICHT
       // include_once(PROJECT_ROOT . "/keyit_update/auto_update.php");

        //Write new last update timestamp
        write_in_file($file_name,$timestamp_now);

}else{
// Last update is NOT older than x time

    //Redirect
    //header("Location: ../");


}



//Functions

function read_from_file($file_name){

  try {
//Open File
      $myfile = fopen($file_name, "r") or die("Unable to open file!");

      //Read File 
      $information =  fread($myfile,filesize($file_name));
      //Close file
      fclose($myfile);


      return $information;
  } catch (Exception $e) {
      throw new Exception( $e->getMessage());
  }

}


function write_in_file($file_name,$text){

  try {

      $myfile = fopen($file_name, "w") or die("Unable to open file!");
      fwrite($myfile, $text);
      fclose($myfile);

      return true;

  } catch (Exception $e) {
      throw new Exception( $e->getMessage());
  }

}


function test_file_existing($file_name){

  if (file_exists($file_name)) {
     return true;
  } else {
     return false;
  }

}




?>

