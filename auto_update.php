<?php

//VERSION: 0.0.0.1


//Check first wheter tried to update this day
//######################################################################

//Load Path
define('PROJECT_ROOT', __DIR__);



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


//#################################################################################

/**
 * General and Repository-Settings
 */
$user = "dmd2222";                // The Github user which owns the repository https://github.com/schnoog/
$repo = "php_auto_updater";               // The repository name https://github.com/schnoog/php_github_updater
$branch = "main";                     // the branch (keep empty to use the default branch)
$do_update = true;                // Should updates be applied
$target_directory =  PROJECT_ROOT;        // The root directory of the projects local installation __DIR__ if this script is placed along the other files

if ($target_directory == "PROJECT_ROOT"){$target_directory = realpath(__DIR__ . '/..') . "/";}
//$target_directory="../";
$target_directory=__DIR__;
//print_r(scandir($target_directory));
echo("target_directory:" . $target_directory);

$write_output = true;             // Should the steps performed be written into $write_output_file
$write_output_file = __DIR__ . DIRECTORY_SEPARATOR . "updatestep.info"; // And the filename
$usage_password = "";   // If  $usage_password isn't empty (""), this password will be required to perform the update check /action
/**
 * Interface-Settings
 */
$capture_requests = true;         // Should get/post requests containing the item "updateaction" with the valie check or update trigger the script

/**
 * GitHub User Account 
 * -Only needed if rate limiter hits,should be no problem with f.e. 1 update per hour
 * -(mygitpw.php contains those 2 (user & pass) lines but isn't part of the repository)
 */
$github_account['user'] = "";          
$github_account['pass'] = "";   
if(file_exists('mygitpw.php')) include_once('mygitpw.php');
/**
 * Branch setting
 */
if(strlen($branch)<1) GetBranch($user,$repo);
/**
 * Let the magic happen and caputre requests
 * 
 */
$hasaction = false;
if ($capture_requests){  
  if(isset($_REQUEST['updateaction'])){

    if(strlen($usage_password)>0){
        if(!isset($_REQUEST['pw'])) {
          DirectOut("Password missing",true,true);
          die ("No password");
        }
        if($_REQUEST['pw'] !== $usage_password) {
          DirectOut("Wrong Password",true,true);
          die ("Wrong password");
        }
    }
          if($_REQUEST['updateaction'] == "check"){
            CheckCommits($user,$repo,false,$target_directory);
            $use_own_gui = false;
            $hasaction = true;
          }
          if($_REQUEST['updateaction'] == "update"){
            CheckCommits($user,$repo,true,$target_directory);
            $use_own_gui = false;
            $hasaction = true;
          }
          if($_REQUEST['updateaction'] == "clear"){
            DirectOut("",true,true);
            $hasaction = true;
          }          
  }

}
  if(!$hasaction)  CheckCommits($user,$repo,$do_update,$target_directory);







 //#################################################################################       

        //Write new last update timestamp
        write_in_file($file_name,$timestamp_now);

}else{
// Last update is NOT older than x time




}








//Functions

function QuickStart($user,$repo,$do_update,$dir = __DIR__){
    global $commit_completed;
      $tmp = CheckCommits($user,$repo,$do_update,$dir);
      if(count($tmp)>0){
            if($do_update){
                if($commit_completed){
                    echo "<h1>Updated " . count($tmp) . " files</h1>";
                }else{
                    echo "<h1>Complete update performed</h1>";
                }
            }else{
              if($commit_completed){
                echo "<h1>Update for " . count($tmp) . " files available</h1>";
              }else{
                  echo "<h1>Full package update required</h1>";
              }
            }
      }
  }
  
  
  
  function CheckCommits($user,$repo,$download_if_not_matching,$dir = __DIR__){
    global $commit_completed;
      $not_matching = array();
      DirectOut("##############################",true,true);
      DirectOut("##############################");
      DirectOut("Starting update process from",true);
      DirectOut("https://github.com/$user/$repo");
      DirectOut("##############################");
      DirectOut("Step 1: Get the last commits");
      $LastCommits = GetGitCommits($user,$repo,true);
      $commits_num = count($LastCommits);
      DirectOut("-Github returned $commits_num commits");
      DirectOut("Step 2: Start checking commits");
      DirectOut("   Progress: ");
      $cnt=0;
      $fcnt = 0;
      foreach($LastCommits as $LastSHA => $lastData){
          DirectOut("*",false);
          $cnt++;
          $commit_completed = false;
          $CommFiles = GetGitCommitFiles($user,$repo,$LastSHA,true);
          $thisAll = count($CommFiles);
          $thisOK = 0;
          foreach($CommFiles as $filename => $filesha){
              $fcnt++;
              $localHash = GitFileHash($dir . DIRECTORY_SEPARATOR . $filename);
              if($filesha == $localHash){
                  $thisOK++;
                  if($thisOK == $thisAll) {
                    $commit_completed = true;
                    break;
                  }
              }else{
                $not_matching[$filesha] = $filename;
  
              }
          }
  
          if($commit_completed) break;
          
      }
      DirectOut("-Update check completed for $cnt commits and $fcnt files");
      $missfiles = count($not_matching);
      DirectOut("##############################");
      if($missfiles > 299) {
        $tmp = "More than 300 files are outdated";
        DirectOut($tmp);
        $commit_completed = false;
      }elseif($missfiles > 0){
        $tmp = "Number of outdated files: " . $missfiles;
        DirectOut($tmp);
      }else{
        $tmp = "Your installation is up to date";
        DirectOut($tmp);
      }
  
  
  
  
      if($download_if_not_matching){
          if(count($not_matching) > 0) DownloadMissingFiles($user,$repo,$not_matching,$commit_completed);
      }else{
  
      }
  
  return $not_matching;
  }
  /**
   * Download files
   * if $commit_completed = true, download single files listed in the last 10 commits
   * otherwise download the zip and replace all files
   */
  function DownloadMissingFiles($user,$repo,$not_matching,$commit_completed,$dir = __DIR__){
    DirectOut("Step 3: Perform the update");
    DirectOut("by ");
        if($commit_completed){
          $branch = GetBranch($user,$repo);
          $toUpdate = count($not_matching);
          DirectOut("replacing individually $toUpdate files",false);
          DirectOut("   Progress: ");
          foreach($not_matching as $sha => $filename){
            DirectOut("*",false);
            $remoteurl = "https://raw.githubusercontent.com/$user/$repo/$branch/$filename";
            $filetmp = getSslPage($remoteurl);
            file_put_contents($dir . DIRECTORY_SEPARATOR . $filename,$filetmp);
          }
            DirectOut('Files copied');
            DirectOut('Update completed');
        }else{
            DirectOut("replacing all files from zip",false);
            
            DownloadMasterZipAndUnpack($user,$repo,$dir);
        }
  
  }
  /**
   *
   */
  function GetGitCommits($user,$repo,$renew = true){
    $tmpfile = 'git.commits.tmp';
    $commits = array();
    if($renew == true){
        if(file_exists($tmpfile)) unlink($tmpfile);
    }
    if(file_exists($tmpfile)){
        $tmp = file_get_contents($tmpfile);
    }else{
        $tmp = getSslPage("https://api.github.com/repos/$user/$repo/commits");
        file_put_contents($tmpfile,$tmp);
    }
    $tmp = json_decode($tmp,true);
    for($x=0;$x < count($tmp); $x++){
        $comm = $tmp[$x];
        $commits[$comm['sha']]['url'] = $comm['commit']['url'];
        $commits[$comm['sha']]['date'] = $comm['commit']['author']['date'];
    }
    if(file_exists($tmpfile)) unlink($tmpfile);
    return $commits;
  }
  
  /**
  *
  */
  function GetGitCommitFiles($user,$repo,$commit,$renew = true){
  $tmpfile = 'git.commitfiles.tmp';
  $commits = array();
  if($renew == true){
      if(file_exists($tmpfile)) unlink($tmpfile);
  }
  if(file_exists($tmpfile)){
      $tmp = file_get_contents($tmpfile);
  }else{
      $tmp = getSslPage("https://api.github.com/repos/$user/$repo/commits/" . $commit);
      file_put_contents($tmpfile,$tmp);
  }
  $tmp = json_decode($tmp,true);
  for($x=0;$x < count($tmp['files']); $x++){
      $comm = $tmp['files'][$x];
      $commits[$comm['filename']] = $comm['sha'];
  }
  if(file_exists($tmpfile)) unlink($tmpfile);
  return $commits;
  }
  /**
   *
   */
  function DownloadMasterZipAndUnpack($user,$repo,$dir=__DIR__){
    $branch = GetBranch($user,$repo);
  
    if(!file_exists($branch . ".zip")){
    $remoteurl = "https://github.com/$user/$repo/archive/".$branch.".zip";
    DirectOut("-Downloading $remoteurl");
    $filetmp = getSslPage($remoteurl,true);
    file_put_contents($branch .".zip",$filetmp);
    }
    $len=strlen($repo . "-" .$branch . "/");
  
    if(is_dir('./unpack_temp_dir')) rrmdir('./unpack_temp_dir');
    mkdir('./unpack_temp_dir');
    $zip = new ZipArchive;
    if ($zip->open($branch .'.zip') === TRUE) {
        DirectOut("-Create temporary directory and unpack the archive");
        $zip->extractTo('./unpack_temp_dir/');
        DirectOut($zip->numFiles . " files unpacked, start to copy them");
        for ($i = 0; $i < $zip->numFiles; $i++) {
          $filename = $zip->getNameIndex($i);
          $currfile = "./unpack_temp_dir/" . $filename;
          $newfile = $dir . DIRECTORY_SEPARATOR . substr($filename,$len);
  
          $lastchar = substr($currfile,-1);
          if($lastchar != "/" && $lastchar != "\\") {
            copy($currfile,$newfile);
          }else{
            if(strlen($newfile) > 0)@mkdir($newfile);
          }
        }
        $zip->close();
        DirectOut("Files copied");
        unlink($branch . '.zip');
        rrmdir('./unpack_temp_dir');
        DirectOut("Update completed");
    } else {
        echo 'Fehler';
    }
  }
  /**
   * 
   */
  function GetBranch($user,$repo){
    global $branch;
    if(strlen($branch)>0) return $branch;
    $callurl = "https://api.github.com/repos/$user/$repo";
    $tmp = getSslPage($callurl );
    $repodata = json_decode($tmp,true);
    $branch = $repodata['default_branch'];
    return $branch;
  }
  
  
  
  /**
   * Helper
   */
  function GitFileHash($file2check){
    if(!file_exists($file2check)) return false;
    global $lastmime;
      $cont=file_get_contents($file2check);
      $file_info = new finfo(FILEINFO_MIME_TYPE);
      $lastmime = $file_info->buffer($cont);
      if(strpos(".". $lastmime,'text/'))  $cont = str_replace("\r","" ,$cont);
      if($lastmime == "application/x-wine-extension-ini") $cont = str_replace("\r","" ,$cont);
      $len = mb_strlen($cont,'8bit');
      $toc ="blob " . $len . chr(0) .  $cont ;
      $tmp = sha1($toc);
      return $tmp;
    }
  /**
  *
  */
    function getSslPage($url,$nologin = false) {
      global $github_account;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_REFERER, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      if($nologin)$github_account['user'] = "";
      if(isset($github_account['user']) && isset($github_account['pass'])){
            $x = strlen($github_account['user']) * strlen($github_account['pass']);
            if ($x > 0){
              $ulog = $github_account['user'] . ":" . $github_account['pass'];
              curl_setopt($ch, CURLOPT_USERPWD, $ulog);
            }
      }
      curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }
  /**
   *
   */
  function DebugOut($given){
    echo "<hr><pre>" . print_r($given,true). "</pre>";
  }
  /**
  *
  */
  function DirectOut($output,$InNewLine = true,$empty_before = false){
    global $write_output_file, $write_output;
    if(!$write_output) return true;
      $tx = "";
      if(file_exists($write_output_file)){
      $tx = file_get_contents($write_output_file);
      if(strlen($tx) > 0){
        if($InNewLine) $tx .= "\n";
      }
    }
      if($empty_before)$tx = "";
          file_put_contents($write_output_file,$tx . $output);
  }
  /**
  *
  */
  function rrmdir($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
            rrmdir($dir. DIRECTORY_SEPARATOR .$object);
          else
            unlink($dir. DIRECTORY_SEPARATOR .$object);
        }
      }
      rmdir($dir);
    }
    }

















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

