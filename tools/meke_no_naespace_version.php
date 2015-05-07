<?php
/**
 * Created by Normunds Pureklis.
 * Make no namespace version.
 * Date: 2015.05.07
 * Time: 12:53
 */

echo "Start!\n";
$directory = '../';
$outputDirectory = '../../php-client-api_nn/';
$fileArr = makeFilesArray($directory, '/.+\.php/');

var_dump($fileArr);


foreach ($fileArr as $fileName) {
  $file = $directory . $fileName;
  if (file_exists($file)) {
    try {
      $fh = @fopen($file, 'r');
    } catch (Exception $e) {
      echo "Can not open file (" . $file . ")!\n";
      $fh = FALSE;
    }
  } else {
    echo "Can not open file (" . $file . ")!\n";
    $fh = FALSE;
  }
  $outputTXT = '';
  $print = true;
  while (!feof($fh)) {
    $crow = fgets($fh);
    if (strpos($crow, '//<namespace') === 0) {
      $print = false;
      continue;
    }
    if (strpos($crow, '//namespace>') === 0) {
      $print = true;
      continue;
    }
    if ($print)
      $outputTXT .= $crow;
  }
  fclose($fh);

  $outputFile = $outputDirectory . $fileName;
// print to file
  try {
    $file = fopen($outputFile, "w");
    fwrite($file, $outputTXT);
    fclose($file);
  } catch (Exception $e) {
    echo "Can not open file (" . $outputFile . ")!\n";
  }
}
echo "End!\n";

function makeFilesArray($directory, $regex)
{
  $filesArray = 0;
  $handle = @opendir($directory);
  if ($handle) {
    $filesArray = array();
    $i = 0;
    while (($entry = readdir($handle)) !== FALSE) {
      if ($entry != "." && $entry != "..") {
        if (preg_match($regex, $entry)) {
          $filesArray[$i] = $entry;
          $i++;
        }
      }
    }
    closedir($handle);
  }
  return $filesArray;
}